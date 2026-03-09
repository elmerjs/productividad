<?php
// dashboard_analitica.php (Versión Artículos - LITE)
include_once('conn.php'); 

if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// 1. FILTROS (Solo Artículos)
$years = [];
try {
    $sqlYears = "SELECT DISTINCT ano_publicacion FROM solicitud WHERE ano_publicacion > 0 ORDER BY ano_publicacion DESC";
    $resultYears = $conn->query($sqlYears);
    if ($resultYears) { while ($row = $resultYears->fetch_assoc()) { $years[] = $row['ano_publicacion']; } }
} catch (Exception $e) {}

$currentYear = date('Y');
$selectedYear = isset($_GET['anio']) ? intval($_GET['anio']) : (count($years) > 0 ? $years[0] : $currentYear);

// 2. KPIS
$sqlKPI = "SELECT 
            COUNT(DISTINCT s.id_solicitud_articulo) as total_solicitudes, 
            COUNT(DISTINCT sp.fk_id_profesor) as total_profesores 
           FROM solicitud s 
           JOIN solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud 
           WHERE s.ano_publicacion = ?";
$stmtKPI = $conn->prepare($sqlKPI);
$stmtKPI->bind_param("i", $selectedYear);
$stmtKPI->execute();
$kpis = $stmtKPI->get_result()->fetch_assoc();
$stmtKPI->close();

// 3. TOP 50 (Algoritmo IPCP solo Artículos)
$sqlTop = "SELECT 
            t.documento_tercero, t.nombre_completo, d.NOMBRE_DEPTO_CORT as departamento, 
            COUNT(s.id_solicitud_articulo) as num_articulos,
            ROUND(AVG(s.numero_autores), 1) as avg_autores,
            ROUND(SUM(
                (CASE WHEN s.tipo_articulo LIKE '%Full%' OR s.tipo_articulo LIKE '%Original%' THEN 1.0 ELSE 0.3 END) * (CASE WHEN s.numero_autores <= 1 THEN 1.0 WHEN s.numero_autores <= 3 THEN 0.5 ELSE 0.2 END)
            ), 2) as ipcp_score
           FROM solicitud s 
           JOIN solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
           JOIN tercero t ON sp.fk_id_profesor = t.documento_tercero
           LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
           WHERE s.ano_publicacion = ? 
           GROUP BY t.documento_tercero, t.nombre_completo 
           ORDER BY num_articulos DESC 
           LIMIT 50";

$stmtTop = $conn->prepare($sqlTop);
$stmtTop->bind_param("i", $selectedYear);
$stmtTop->execute();
$resTop = $stmtTop->get_result();
$topProfesores = [];
while ($row = $resTop->fetch_assoc()) { $topProfesores[] = $row; }
$stmtTop->close();

// 4. GRÁFICA
$sqlFac = "SELECT f.nombre_fac_min as facultad, COUNT(s.id_solicitud_articulo) as total
           FROM solicitud s JOIN solicitud_profesor sp ON s.id_solicitud_articulo = sp.fk_id_solicitud
           JOIN tercero t ON sp.fk_id_profesor = t.documento_tercero LEFT JOIN deparmanentos d ON t.fk_depto = d.PK_DEPTO
           LEFT JOIN facultad f ON d.FK_FAC = f.PK_FAC WHERE s.ano_publicacion = ? GROUP BY f.PK_FAC ORDER BY total DESC";
$stmtFac = $conn->prepare($sqlFac);
$stmtFac->bind_param("i", $selectedYear);
$stmtFac->execute();
$resFac = $stmtFac->get_result();
$labelsFac = []; $valuesFac = [];
while ($row = $resFac->fetch_assoc()) { 
    $labelsFac[] = empty($row['facultad']) ? 'Sin Facultad' : $row['facultad']; 
    $valuesFac[] = $row['total']; 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.A.V.I.A. | Artículos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        .clickable-row { cursor: pointer; }
        .clickable-row:hover { background-color: #374151 !important; }
        .fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .custom-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
        .custom-scroll::-webkit-scrollbar-track { background: #1f2937; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen pb-12">

    <nav class="border-b border-gray-700 bg-gray-800 p-3 sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-tr from-blue-600 to-cyan-400 rounded-lg animate-pulse shadow-blue-500/50 flex items-center justify-center font-bold text-[10px]">LITE</div>
                <div>
                    <h1 class="text-lg font-bold tracking-wider text-white">PROYECTO S.A.V.I.A.</h1>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Módulo de Artículos</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                
                <a href="dashboard_analitica_full.php" class="bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-500 hover:to-green-400 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-emerald-900/40 border border-emerald-400/20 transform hover:scale-105">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Ver Auditoría Integral (Libros/Patentes)
                </a>

                <div class="h-6 w-px bg-gray-600 mx-1"></div>

                <a href="lista_solicitudes.php" class="text-xs text-gray-300 hover:text-white border border-gray-600 px-3 py-1.5 rounded transition">
                    Volver al Listado
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4">
        
        <div class="flex flex-wrap justify-between items-center mb-4 bg-gray-800/40 p-3 rounded-lg border border-gray-700 backdrop-blur-sm">
            <div>
                <h2 class="text-xl font-bold text-white">Tablero de Artículos</h2>
                <p class="text-xs text-gray-400">Vigencia Auditada: <span class="text-cyan-400 font-bold"><?php echo $selectedYear; ?></span></p>
            </div>
            <form action="" method="GET" class="flex gap-2 items-center">
                <select name="anio" class="bg-gray-900 text-xs border border-gray-600 text-white py-1 px-3 rounded focus:border-cyan-500 outline-none">
                    <?php foreach($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-4 py-1 rounded text-xs text-white font-semibold transition">Filtrar</button>
            </form>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="bg-gray-800 p-3 rounded-lg border border-gray-700 shadow flex justify-between items-center group">
                <div><p class="text-[10px] text-gray-500 uppercase font-bold">Artículos</p><p class="text-2xl font-bold text-white group-hover:text-blue-400 transition"><?php echo $kpis['total_solicitudes'] ?? 0; ?></p></div>
                <div class="text-blue-500 opacity-50"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></div>
            </div>
            <div class="bg-gray-800 p-3 rounded-lg border border-gray-700 shadow flex justify-between items-center group">
                <div><p class="text-[10px] text-gray-500 uppercase font-bold">Docentes</p><p class="text-2xl font-bold text-white group-hover:text-purple-400 transition"><?php echo $kpis['total_profesores'] ?? 0; ?></p></div>
                <div class="text-purple-500 opacity-50"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg></div>
            </div>
            <div class="bg-gray-800 p-3 rounded-lg border border-red-900/40 shadow flex justify-between items-center relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-[10px] text-red-400 uppercase font-bold">Riesgo (Artículos)</p>
                    <p class="text-2xl font-bold text-white">~5%</p>
                </div>
                <div class="absolute right-0 bottom-0 opacity-20"><svg class="w-12 h-12 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-6">
            <div class="lg:col-span-4 bg-gray-800 p-3 rounded-lg border border-gray-700 shadow-lg h-[480px] flex flex-col">
                <h3 class="text-xs font-semibold mb-2 text-gray-300 uppercase tracking-wide">Distribución (Artículos)</h3>
                <div class="flex-grow relative"><canvas id="chartFacultad"></canvas></div>
            </div>

            <div class="lg:col-span-8 bg-gray-800 p-3 rounded-lg border border-gray-700 shadow-lg h-[480px] flex flex-col">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-wide">Ranking Riesgo (Artículos > 5)</h3>
                </div>
                <div class="overflow-y-auto custom-scroll flex-grow border border-gray-700 rounded bg-gray-900/50">
                    <table class="w-full text-left text-xs text-gray-400">
                        <thead class="text-[10px] uppercase bg-gray-900 text-gray-300 sticky top-0 z-10 shadow-sm border-b border-gray-700">
                            <tr>
                                <th class="px-3 py-2 bg-gray-900">Docente</th>
                                <th class="px-3 py-2 text-center bg-gray-900">Cant.</th>
                                <th class="px-3 py-2 text-center bg-gray-900">Score IPCP</th>
                                <th class="px-3 py-2 text-center bg-gray-900">Diagnóstico</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <?php foreach($topProfesores as $prof): 
                                $nArts = $prof['num_articulos'];
                                $score = floatval($prof['ipcp_score']);
                                $statusBadge = '<span class="text-green-500 opacity-80">Normal</span>';
                                $rowClass = "hover:bg-gray-700";

                                if ($score > 5.0) {
                                    $statusBadge = '<span class="text-red-400 font-bold animate-pulse text-[10px] bg-red-900/30 px-2 py-0.5 rounded border border-red-800">CRÍTICO</span>';
                                    $rowClass = "bg-red-900/10 border-l-2 border-red-500 hover:bg-red-900/20";
                                } elseif ($nArts > 5 && $score <= 2.5) {
                                    $statusBadge = '<span class="text-yellow-400 font-bold text-[10px] bg-yellow-900/30 px-2 py-0.5 rounded border border-yellow-800">Volumen</span>';
                                    $rowClass = "bg-yellow-900/10 border-l-2 border-yellow-500 hover:bg-yellow-900/20";
                                }
                                $onclick = "cargarDetalles('".$prof['documento_tercero']."', '".htmlspecialchars($prof['nombre_completo'])."')";
                            ?>
                            <tr onclick="<?php echo $onclick; ?>" class="clickable-row transition <?php echo $rowClass; ?>">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-200"><?php echo htmlspecialchars($prof['nombre_completo']); ?></div>
                                    <div class="text-[10px] text-gray-500 truncate w-32"><?php echo htmlspecialchars($prof['departamento'] ?? '-'); ?></div>
                                </td>
                                <td class="px-3 py-2 text-center font-mono text-white"><?php echo $nArts; ?></td>
                                <td class="px-3 py-2 text-center font-bold text-cyan-400"><?php echo $score; ?></td>
                                <td class="px-3 py-2 text-center"><?php echo $statusBadge; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="detalle-container" class="hidden fade-in bg-gray-800 rounded-lg border border-gray-600 shadow-2xl overflow-hidden mb-12 flex flex-col">
            <div class="bg-gray-900 p-2 border-b border-gray-700 flex justify-between items-center flex-shrink-0">
                <div class="flex items-center gap-3">
                    <h3 class="text-xs font-bold text-white px-2 uppercase tracking-wide">Auditoría de Artículo</h3>
                    <p class="text-xs text-gray-400"><span id="nombre-docente-detalle" class="text-blue-400 font-bold uppercase"></span> (<span id="cedula-docente-detalle"></span>)</p>
                </div>
                <button onclick="document.getElementById('detalle-container').classList.add('hidden')" class="text-xs text-gray-300 hover:text-white bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded transition">Cerrar</button>
            </div>
            
            <div class="overflow-y-auto max-h-64 custom-scroll">
                <table class="w-full text-left text-xs text-gray-400">
                    <thead class="uppercase bg-gray-900/50 text-gray-300 sticky top-0 border-b border-gray-700 z-10">
                        <tr>
                            <th class="px-4 py-2 bg-gray-900 w-1/3">Producto</th>
                            <th class="px-4 py-2 bg-gray-900">Tipología & Clasificación</th>
                            <th class="px-4 py-2 text-center bg-gray-900">Autores</th>
                            <th class="px-4 py-2 text-center bg-gray-900">Puntos</th>
                            <th class="px-4 py-2 text-center bg-gray-900">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-detalle-body" class="divide-y divide-gray-700"></tbody>
                </table>
            </div>
            
            <div id="loading-spinner" class="hidden text-center py-6">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
            </div>
            
            <div id="analisis-forense-box" class="hidden p-4 border-t border-gray-700 bg-gray-900/50 m-2 rounded-lg text-xs">
                <h4 id="analisis-titulo" class="font-bold text-sm mb-1 uppercase tracking-wider flex items-center gap-2"></h4>
                <p id="analisis-texto" class="leading-relaxed opacity-90 text-gray-300"></p>
            </div>
        </div>
    </div>

    <script>
        const labelsFac = <?php echo json_encode($labelsFac); ?>;
        const valuesFac = <?php echo json_encode($valuesFac); ?>;
        const ctxFac = document.getElementById('chartFacultad').getContext('2d');
        if (labelsFac.length > 0) {
            new Chart(ctxFac, {
                type: 'bar', 
                data: { labels: labelsFac, datasets: [{ label: 'Artículos', data: valuesFac, backgroundColor: 'rgba(6, 182, 212, 0.6)', borderColor: 'rgba(6, 182, 212, 1)', borderWidth: 1, borderRadius: 3, barPercentage: 0.7 }] },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true, grid: { color: '#374151' }, ticks: { color: '#9CA3AF', font: {size: 10} } }, y: { grid: { display: false }, ticks: { color: '#D1D5DB', font: {size: 10} } } }, plugins: { legend: { display: false } } }
            });
        }

        // LÓGICA DE DETALLE (Usa la API antigua de solo docentes)
        function cargarDetalles(cedula, nombre) {
            const container = document.getElementById('detalle-container');
            const tbody = document.getElementById('tabla-detalle-body');
            const spinner = document.getElementById('loading-spinner');
            const boxAnalisis = document.getElementById('analisis-forense-box');
            const anio = "<?php echo $selectedYear; ?>";

            container.classList.remove('hidden');
            document.getElementById('nombre-docente-detalle').innerText = nombre;
            document.getElementById('cedula-docente-detalle').innerText = cedula;
            tbody.innerHTML = '';
            spinner.classList.remove('hidden');
            boxAnalisis.classList.add('hidden');
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });

            fetch(`api_detalles_docente.php?cedula=${cedula}&anio=${anio}`)
                .then(r => r.json())
                .then(data => {
                    spinner.classList.add('hidden');
                    if (data.lista && data.lista.length > 0) {
                        data.lista.forEach(item => {
                            let autoresClass = "text-gray-400";
                            const numAutores = parseInt(item.numero_autores) || 1;
                            if(numAutores > 4) autoresClass = "text-red-400 font-bold";
                            else if(numAutores === 1) autoresClass = "text-blue-400 font-bold";

                            const row = `
                                <tr class="hover:bg-gray-700/50 transition">
                                    <td class="px-4 py-2 align-top">
                                        <div class="text-gray-200 text-sm font-medium mb-1 truncate w-64" title="${item.titulo_articulo}">${item.titulo_articulo || 'Sin Título'}</div>
                                        <div class="text-[10px] text-cyan-500 italic border-t border-gray-700 pt-1">${item.nombre_revista || ''} (ISSN: ${item.issn || '-'})</div>
                                    </td>
                                    <td class="px-4 py-2 align-top">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold text-white bg-gray-700 px-2 py-0.5 rounded w-fit border border-gray-600">${item.tipo_articulo || 'N/A'}</span>
                                            <span class="text-[10px] text-gray-400">${item.tipo_productividad || ''}</span>
                                            <span class="text-[10px] font-bold text-yellow-500 bg-yellow-900/20 px-1 rounded w-fit mt-1 border border-yellow-900/50">${item.tipo_publindex || ''}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-center align-top text-xs ${autoresClass}">👥 ${numAutores}</td>
                                    <td class="px-4 py-2 text-center align-top font-bold text-white text-sm">${item.puntaje || '0'}</td>
                                    <td class="px-4 py-2 text-center align-top"><span class="px-2 py-0.5 rounded text-[10px] bg-gray-700 border border-gray-600">${item.estado_solicitud || 'Radicado'}</span></td>
                                </tr>`;
                            tbody.innerHTML += row;
                        });
                    }
                    if (data.analisis) {
                        boxAnalisis.classList.remove('hidden');
                        boxAnalisis.className = `p-4 border-t m-2 rounded-lg text-xs border ${data.analisis.clase_css}`;
                        let icon = '✅';
                        if(data.analisis.titulo.includes('CRÍTICA')) icon = '🚨';
                        else if(data.analisis.titulo.includes('PREVENTIVA')) icon = '⚠️';
                        document.getElementById('analisis-titulo').innerHTML = `${icon} ${data.analisis.titulo} <span class="ml-auto text-[10px] font-normal opacity-70 bg-black/20 px-2 rounded">Score: ${data.analisis.score}</span>`;
                        document.getElementById('analisis-texto').innerText = data.analisis.mensaje;
                    }
                });
        }
    </script>
</body>
</html>