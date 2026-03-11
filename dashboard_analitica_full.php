<?php
// dashboard_analitica_full.php - VERSIÓN: SEMÁFORO DE RIESGO ACUMULATIVO
include_once('conn.php'); 
$conn->set_charset("utf8mb4");

// 1. FILTROS BÁSICOS
$years = [];
$resY = $conn->query("SELECT DISTINCT anio_vigencia FROM matriz_productividad WHERE anio_vigencia > 0 ORDER BY anio_vigencia DESC");
if($resY) { while($row = $resY->fetch_assoc()) $years[] = $row['anio_vigencia']; }

$currentYear = date('Y');
$selectedYear = isset($_GET['anio']) ? intval($_GET['anio']) : ($years[0] ?? $currentYear);

// 2. CONSULTA MAESTRA (AHORA IGNORA TODOS LOS ANULADOS EN TIEMPO REAL)
$sqlMaster = "
SELECT 
    m.fk_profesor, 
    m.nombre_profesor, 
    COALESCE(m.departamento, 'NO ASIGNADO') as departamento,
    COALESCE(f.NOMBREC_FAC, 'SIN FACULTAD') as facultad,
    COUNT(*) as total_items,
    SUM(CASE WHEN m.clasificacion_pago='PUNTOS_SALARIALES' THEN 1 ELSE 0 END) as items_salariales,
    SUM(CASE WHEN m.clasificacion_pago='BONIFICACION' THEN 1 ELSE 0 END) as items_bonif,
    ROUND(SUM(
        CASE 
            WHEN m.tipo_producto='LIBRO' THEN 4.0 
            WHEN m.tipo_producto='PATENTE' THEN 5.0
            WHEN m.tipo_producto='ARTICULO' AND m.subtipo LIKE '%Full%' THEN 1.0
            WHEN m.tipo_producto='ARTICULO' THEN 0.3
            WHEN m.clasificacion_pago='BONIFICACION' THEN 0
            ELSE 0.5
        END * (CASE WHEN m.numero_autores <= 1 THEN 1.0 WHEN m.numero_autores <= 3 THEN 0.5 ELSE 0.2 END)
    ), 2) as score_ipcp
FROM matriz_productividad m
LEFT JOIN deparmanentos d ON m.departamento = d.NOMBRE_DEPTO_CORT
LEFT JOIN facultad f ON d.FK_FAC = f.PK_FAC
WHERE m.anio_vigencia = $selectedYear

/* --- INICIO: FILTRO DINÁMICO DE ESTADOS ANULADOS ('an') --- */
AND NOT EXISTS (SELECT 1 FROM solicitud s WHERE m.origen_tabla = 'solicitud' AND m.origen_id = s.id_solicitud_articulo AND LOWER(TRIM(s.estado_solicitud)) = 'an')
AND NOT EXISTS (SELECT 1 FROM libros l WHERE m.origen_tabla = 'libros' AND m.origen_id = l.id_libro AND LOWER(TRIM(l.estado)) = 'an')
AND NOT EXISTS (SELECT 1 FROM titulos t WHERE m.origen_tabla = 'titulos' AND m.origen_id = t.id_titulo AND LOWER(TRIM(t.estado_titulo)) = 'an')
AND NOT EXISTS (SELECT 1 FROM premios p WHERE m.origen_tabla = 'premios' AND m.origen_id = p.id AND LOWER(TRIM(p.estado)) = 'an')
AND NOT EXISTS (SELECT 1 FROM patentes pat WHERE m.origen_tabla = 'patentes' AND m.origen_id = pat.id_patente AND LOWER(TRIM(pat.estado)) = 'an')
AND NOT EXISTS (SELECT 1 FROM innovacion i WHERE m.origen_tabla = 'innovacion' AND m.origen_id = i.id_innovacion AND LOWER(TRIM(i.estado)) = 'an')
AND NOT EXISTS (SELECT 1 FROM produccion_t_s pts WHERE m.origen_tabla = 'produccion_t_s' AND m.origen_id = pts.id_produccion AND LOWER(TRIM(pts.estado)) = 'an')
AND NOT EXISTS (SELECT 1 FROM trabajos_cientificos tc WHERE m.origen_tabla = 'trabajos_cientificos' AND m.origen_id = tc.id AND LOWER(TRIM(tc.estado_cient)) = 'an')
AND NOT EXISTS (SELECT 1 FROM trabajos_cientificos_bon tcb WHERE m.origen_tabla = 'trabajos_cientificos_bon' AND m.origen_id = tcb.id AND LOWER(TRIM(tcb.estado_tcb)) = 'an')
AND NOT EXISTS (SELECT 1 FROM creacion c WHERE m.origen_tabla = 'creacion' AND m.origen_id = c.id AND LOWER(TRIM(c.estado_creacion)) = 'an')
AND NOT EXISTS (SELECT 1 FROM creacion_bon cb WHERE m.origen_tabla = 'creacion_bon' AND m.origen_id = cb.id AND LOWER(TRIM(cb.estado_cb)) = 'an')
AND NOT EXISTS (SELECT 1 FROM traduccion_libros tl WHERE m.origen_tabla = 'traduccion_libros' AND m.origen_id = tl.id_traduccion AND LOWER(TRIM(tl.estado)) = 'an')
AND NOT EXISTS (SELECT 1 FROM traduccion_bon tb WHERE m.origen_tabla = 'traduccion_bon' AND m.origen_id = tb.id AND LOWER(TRIM(tb.estado)) = 'an')
/* --- FIN FILTRO ANULADOS --- */

GROUP BY m.fk_profesor, m.nombre_profesor
ORDER BY score_ipcp DESC";

$resMaster = $conn->query($sqlMaster);
$dataSet = [];

if ($resMaster) {
    while($row = $resMaster->fetch_assoc()) {
        $dataSet[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.A.V.I.A. Full | Auditoría Integral</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body{font-family:'Segoe UI',sans-serif}
        .custom-scroll::-webkit-scrollbar { width:8px }
        .custom-scroll::-webkit-scrollbar-track { background:#1f2937 }
        .custom-scroll::-webkit-scrollbar-thumb { background:#4b5563;border-radius:4px }
        .row-animate { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen pb-10">

<nav class="border-b border-gray-700 bg-gray-800 p-3 sticky top-0 z-50 shadow-lg">
    <div class="container mx-auto flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-700 rounded-lg shadow-lg shadow-green-900/50 flex items-center justify-center font-bold text-white text-xs">FULL</div>
            <div>
                <h1 class="text-lg font-bold tracking-wider text-white">S.A.V.I.A. INTEGRAL</h1>
                <p class="text-[10px] text-gray-400">Sistema de Auditoría & Vigilancia Integral</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="index.php" class="text-xs text-gray-400 hover:text-white border border-gray-600 px-3 py-1.5 rounded transition">Volver</a>
            <a href="etl_llenar_matriz.php" class="bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-blue-900/40">
                🔄 Sincronizar Datos
            </a>
        </div>
    </div>
</nav>

<div class="container mx-auto p-4">
    
    <div class="flex justify-between items-center mb-6 bg-gray-800/50 p-4 rounded-xl border border-gray-700">
        <div>
            <h2 class="text-xl font-bold flex items-center gap-2">
                Tablero de Control Unificado
                <span id="badge-filtro" class="hidden bg-emerald-600 text-white text-[10px] px-2 py-0.5 rounded-full cursor-pointer hover:bg-red-500 transition" onclick="resetFiltro()" title="Clic para quitar filtro">
                    Facultad: <span id="lbl-filtro"></span> ✖
                </span>
            </h2>
            <p class="text-xs text-gray-400">Vigencia Auditada: <span class="text-green-400 font-bold"><?php echo $selectedYear; ?></span></p>
        </div>
        <form class="flex gap-2 items-center">
            <span class="text-xs text-gray-500">Vigencia:</span>
            <select name="anio" class="bg-gray-900 text-sm border border-gray-600 rounded px-3 py-1 text-white" onchange="this.form.submit()">
                <?php foreach($years as $y): ?>
                <option value="<?php echo $y; ?>" <?php echo $y==$selectedYear?'selected':''; ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg transition hover:border-blue-500/30">
            <p class="text-[10px] text-gray-500 uppercase font-bold">Puntos Salariales (Riesgo)</p>
            <p class="text-3xl font-bold text-blue-400" id="kpi-puntos">0</p>
        </div>
        <div class="bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg transition hover:border-yellow-500/30">
            <p class="text-[10px] text-gray-500 uppercase font-bold">Bonificaciones (Pago Único)</p>
            <p class="text-3xl font-bold text-yellow-400" id="kpi-bonif">0</p>
        </div>
        <div class="bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg transition hover:border-purple-500/30">
            <p class="text-[10px] text-gray-500 uppercase font-bold">Docentes Activos</p>
            <p class="text-3xl font-bold text-purple-400" id="kpi-docentes">0</p>
        </div>
        <div class="bg-gray-800 p-4 rounded-xl border border-red-900/30 shadow-lg relative overflow-hidden transition hover:bg-red-900/10">
             <p class="text-[10px] text-red-400 uppercase font-bold">Prioridad Revisión</p>
             <p class="text-3xl font-bold text-white" id="kpi-alertas">0</p>
             <div class="absolute right-2 bottom-2 text-4xl opacity-20">🚨</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <div class="lg:col-span-1 bg-gray-800 p-4 rounded-xl border border-gray-700 h-[550px] flex flex-col">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-xs font-bold text-gray-400 uppercase">Riesgo por Facultad</h3>
                <span class="text-[9px] text-gray-500">Clic en barra para filtrar 👆</span>
            </div>
            <div class="flex-grow relative"><canvas id="chartFacultad"></canvas></div>
        </div>

        <div class="lg:col-span-2 bg-gray-800 p-4 rounded-xl border border-gray-700 h-[550px] flex flex-col">
            <h3 class="text-xs font-bold text-gray-400 uppercase mb-4">Ranking de Seguimiento (Por Depto)</h3>
            <div class="overflow-y-auto custom-scroll flex-grow border border-gray-700 rounded bg-gray-900/30 relative">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-gray-900 text-[10px] uppercase sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-4 py-3">Docente / Departamento</th>
                            <th class="px-4 py-3 text-center">Items</th>
                            <th class="px-4 py-3 text-center">Score IPCP</th>
                            <th class="px-4 py-3 text-center">Estado Auditoría</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-body" class="divide-y divide-gray-800"></tbody>
                </table>
                <div id="msg-vacio" class="hidden absolute inset-0 flex items-center justify-center text-gray-500 text-sm">Sin datos para mostrar.</div>
            </div>
        </div>
    </div>

    <div id="panel-detalle" class="hidden bg-gray-800 rounded-xl border border-gray-600 shadow-2xl overflow-hidden mb-12">
        <div class="bg-gray-900 p-3 flex justify-between items-center border-b border-gray-700">
            <div>
                <h3 class="font-bold text-white text-sm">Auditoría Detallada</h3>
                <p class="text-xs text-gray-400">Docente: <span id="lbl-docente" class="text-blue-400 uppercase"></span></p>
            </div>
            <button onclick="document.getElementById('panel-detalle').classList.add('hidden')" class="bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded text-xs text-white">Cerrar</button>
        </div>
        <div class="max-h-96 overflow-y-auto custom-scroll">
            <table class="w-full text-left text-xs text-gray-400">
                <thead class="bg-gray-900/50 uppercase sticky top-0 border-b border-gray-700">
                    <tr>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2 w-1/3">Producto</th>
                        <th class="px-4 py-2">Detalle</th>
                        <th class="px-4 py-2 text-center">Autores</th>
                        <th class="px-4 py-2 text-center">Puntaje</th>
                    </tr>
                </thead>
                <tbody id="body-detalle" class="divide-y divide-gray-700"></tbody>
            </table>
        </div>
        <div id="loader" class="hidden text-center py-4"><div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div></div>
        <div id="box-analisis" class="hidden p-4 border-t border-gray-700 bg-gray-900/30 text-xs"></div>
    </div>

</div>

<script>
const rawData = <?php echo json_encode($dataSet); ?>;
let chartInstance = null;

if(rawData.length === 0) console.warn("No hay datos para mostrar.");

// 2. RENDERIZADO
function renderDashboard(filtroFacultad = null) {
    const data = filtroFacultad ? rawData.filter(d => d.facultad === filtroFacultad) : rawData;

    let tPuntos = 0, tBonif = 0, tAlertas = 0;
    data.forEach(d => {
        tPuntos += parseFloat(d.items_salariales || 0);
        tBonif += parseFloat(d.items_bonif || 0);
        if(parseFloat(d.score_ipcp) > 40) tAlertas++;
    });

    document.getElementById('kpi-puntos').innerText = Math.round(tPuntos);
    document.getElementById('kpi-bonif').innerText = Math.round(tBonif);
    document.getElementById('kpi-docentes').innerText = data.length;
    document.getElementById('kpi-alertas').innerText = tAlertas;

    const tbody = document.getElementById('tabla-body');
    tbody.innerHTML = '';
    
    if(data.length === 0) {
        document.getElementById('msg-vacio').classList.remove('hidden');
    } else {
        document.getElementById('msg-vacio').classList.add('hidden');
        data.forEach(row => {
            let score = parseFloat(row.score_ipcp);
            let salarial = parseInt(row.items_salariales);
            let badge = '<span class="text-green-500">Normal</span>';
            let rowClass = "hover:bg-gray-700 cursor-pointer transition row-animate";

            if (score > 40.0) {
                badge = '<span class="bg-red-900/50 text-red-200 px-2 py-0.5 rounded border border-red-700 font-bold animate-pulse text-[10px]">REVISIÓN PRIORITARIA</span>';
                rowClass += " bg-red-900/10 border-l-2 border-red-500";
            } else if (score > 15.0) {
                badge = '<span class="bg-orange-900/50 text-orange-200 px-2 py-0.5 rounded border border-orange-700 font-bold text-[10px]">SUSTENTACIÓN REQUERIDA</span>';
                rowClass += " bg-orange-900/10 border-l-2 border-orange-500";
            } else if (salarial > 8) {
                badge = '<span class="bg-yellow-900/50 text-yellow-200 px-2 py-0.5 rounded border border-yellow-700 text-[10px]">ALTO VOLUMEN</span>';
            }

            let safeName = row.nombre_profesor.replace(/'/g, "\\'"); 

            // TABLA POR DEPARTAMENTO
            let tr = `
                <tr class="${rowClass}" onclick="verDetalle('${row.fk_profesor}', '${safeName}')">
                    <td class="px-4 py-3">
                        <div class="font-bold text-white">${row.nombre_profesor}</div>
                        <div class="text-[10px] text-gray-500 truncate w-48">${row.departamento}</div> 
                    </td>
                    <td class="px-4 py-3 text-center font-mono text-white">${salarial}</td>
                    <td class="px-4 py-3 text-center font-bold text-cyan-400 text-sm">${score}</td>
                    <td class="px-4 py-3 text-center text-[10px]">${badge}</td>
                </tr>`;
            tbody.innerHTML += tr;
        });
    }

    const badge = document.getElementById('badge-filtro');
    if(filtroFacultad) {
        badge.classList.remove('hidden');
        document.getElementById('lbl-filtro').innerText = filtroFacultad;
    } else {
        badge.classList.add('hidden');
    }
}

// 3. CHART INTELIGENTE (SEMÁFORO ACUMULATIVO)
function initChart() {
    const facData = {}; 

    rawData.forEach(d => {
        if(!facData[d.facultad]) facData[d.facultad] = { count: 0, risk: 0 };
        facData[d.facultad].count++;
        
        // --- LÓGICA DE RIESGO ACUMULADO ---
        let puntaje = parseFloat(d.score_ipcp || 0);
        let volumen = parseInt(d.items_salariales || 0);

        if(puntaje > 40) facData[d.facultad].risk += 10;      
        else if(puntaje > 15) facData[d.facultad].risk += 3;  
        else if(volumen > 8) facData[d.facultad].risk += 1;   
    });
    
    const sortedFacultades = Object.keys(facData).sort((a,b) => facData[b].count - facData[a].count);
    const values = sortedFacultades.map(f => facData[f].count);
    
    const colors = sortedFacultades.map(f => {
        let r = facData[f].risk;
        if(r >= 10) return '#ef4444'; // ROJO
        if(r >= 3) return '#f97316';  // NARANJA
        if(r >= 1) return '#eab308';  // AMARILLO
        return '#10b981';             // VERDE
    });

    const ctx = document.getElementById('chartFacultad').getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sortedFacultades,
            datasets: [{ 
                label: 'Docentes', 
                data: values, 
                backgroundColor: colors, 
                borderRadius: 4 
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { color: '#374151' } }, y: { grid: { display: false } } },
            onClick: (e) => {
                const points = chartInstance.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                if (points.length) renderDashboard(chartInstance.data.labels[points[0].index]);
                else resetFiltro();
            }
        }
    });
}

function resetFiltro() { renderDashboard(null); }

// 4. DETALLE
function verDetalle(cedula, nombre) {
    const panel = document.getElementById('panel-detalle');
    const tbody = document.getElementById('body-detalle');
    const loader = document.getElementById('loader');
    const box = document.getElementById('box-analisis');
    
    document.getElementById('lbl-docente').innerText = nombre;
    panel.classList.remove('hidden');
    tbody.innerHTML = '';
    loader.classList.remove('hidden');
    box.classList.add('hidden');
    panel.scrollIntoView({behavior:'smooth', block:'center'});

    fetch(`api_detalles_full.php?cedula=${cedula}&anio=<?php echo $selectedYear; ?>`)
        .then(r => r.json())
        .then(data => {
            loader.classList.add('hidden');
            if(data.lista) {
                if(data.lista) {
                data.lista.forEach(item => {
                    let color = item.clasificacion_pago === 'PUNTOS_SALARIALES' ? 'text-blue-300' : 'text-yellow-500';
                    let badge = item.clasificacion_pago === 'PUNTOS_SALARIALES' ? 'SALARIO' : 'BONIF.';
                    
                    let brechaHTML = '';
                    let rowOpacity = '';
                    let rowStrike = '';

                    // LÓGICA VISUAL: SI ESTÁ ANULADO LO VOLVEMOS GRIS Y TACHADO
                    if (item.es_anulado) {
                        badge = 'ANULADO';
                        color = 'text-red-500';
                        rowOpacity = 'opacity-50 grayscale'; // Lo hace grisáceo y translúcido
                        rowStrike = 'line-through text-gray-500'; // Tacha el texto
                        brechaHTML = `<span class="text-[9px] bg-red-900 border border-red-500 text-red-200 px-1.5 py-0.5 rounded-full ml-2 shadow-sm font-bold">🚫 DESCARTADO</span>`;
                    } else {
                        // Lógica normal de brecha si NO está anulado
                        let diff = parseInt(item.anio_vigencia) - parseInt(item.anio_produccion_real);
                        if(diff > 0) {
                            let colorBrecha = diff >= 3 ? 'bg-orange-600' : 'bg-purple-600';
                            brechaHTML = `<span class="text-[9px] ${colorBrecha} text-white px-1.5 py-0.5 rounded-full ml-2 shadow-sm font-bold">⏱️ +${diff} años</span>`;
                        } else if (diff < 0) {
                            brechaHTML = `<span class="text-[9px] bg-red-600 text-white px-1.5 py-0.5 rounded-full ml-2 font-bold">⚠️ Error Fecha</span>`;
                        } else {
                            brechaHTML = `<span class="text-[9px] text-green-500 ml-2">✓ Al día</span>`;
                        }
                    }

                    let row = `
                        <tr class="hover:bg-gray-700/50 ${rowOpacity}">
                            <td class="px-4 py-2">
                                <div class="font-bold ${color}">${item.tipo_producto}</div>
                                <div class="text-[9px] border border-gray-600 ${item.es_anulado ? 'bg-red-900/50 border-red-700 text-red-300' : ''} px-1 rounded inline-block mt-1">${badge}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-white font-medium ${rowStrike}">${item.titulo_producto || '-'}</div>
                                <div class="text-[10px] text-gray-500 italic mt-0.5">Prod: ${item.anio_produccion_real} ${brechaHTML}</div>
                            </td>
                            <td class="px-4 py-2 text-gray-500 text-[11px] ${rowStrike}">${item.subtipo}<br><span class="text-cyan-600">${item.detalle_extra || ''}</span></td>
                            <td class="px-4 py-2 text-center text-xs">👥 ${item.numero_autores}</td>
                            <td class="px-4 py-2 text-center font-bold ${item.es_anulado ? 'text-red-500 line-through' : 'text-white'}">${item.puntaje_final}</td>
                        </tr>`;
                    tbody.innerHTML += row;
                });
            }
            }
            if(data.analisis) {
                box.classList.remove('hidden');
                box.innerHTML = `<h4 class="font-bold uppercase mb-1 ${data.analisis.clase_css.includes('red')?'text-red-400':'text-green-400'}">${data.analisis.titulo}</h4><p class="opacity-80">${data.analisis.mensaje}</p>`;
                box.className = `p-4 border-t border-gray-700 bg-gray-900/30 text-xs ${data.analisis.clase_css}`;
            }
        });
}

document.addEventListener('DOMContentLoaded', () => {
    initChart();
    renderDashboard(null);
});
</script>
</body>
</html>