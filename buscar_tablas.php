<style>/* contenedor que permite scroll cuando haga falta */
.tabla-registros {
  width: 100%;
  overflow-x: auto;              /* scroll horizontal si hay muchas columnas */
  -webkit-overflow-scrolling: touch;
  margin-top: 10px;
}

/* fuerza reparto de ancho y evita que una columna estire demasiado */
.tabla-registros table {
  width: 100%;
  table-layout: fixed;           /* clave: reparte el ancho entre columnas */
  border-collapse: collapse;
}

/* celdas: permitir quiebre de palabra y wrap */
.tabla-registros th,
.tabla-registros td {
  padding: 8px;
  border: 1px solid #e6e6e6;
  text-align: left;
  vertical-align: top;
  overflow: hidden;              /* evita que el contenido rompa el layout */
  text-overflow: ellipsis;
  white-space: normal;           /* importante: permitir wrap */
  word-break: break-word;        /* quiebra palabras largas */
  overflow-wrap: anywhere;       /* mejor compatibilidad */
}

/* Columna 1 (contador) más estrecha */
.tabla-registros table th:first-child,
.tabla-registros table td:first-child {
  width: 48px;
  max-width: 48px;
  text-align: center;
  white-space: nowrap;           /* mantener el número en una línea */
}

/* Última columna (ej: DETALLES_PROFESORES) - permitimos saltos de línea */
.tabla-registros table th.last-col,
.tabla-registros table td.last-col {
  max-width: 360px;              /* ajusta este valor a gusto (px o %) */
  white-space: pre-line;         /* respeta saltos de línea y hace wrap */
  word-break: break-word;
  overflow-wrap: anywhere;
}

/* móviles: reducir padding y ancho de la last-col */
@media (max-width: 768px) {
  .tabla-registros th, .tabla-registros td { padding: 6px; font-size: 13px; }
  .tabla-registros table th:first-child, .tabla-registros table td:first-child { width: 36px; max-width: 36px; }
  .tabla-registros table th.last-col, .tabla-registros table td.last-col { max-width: 220px; }
}

    
    </style>
<?php
include 'conn.php';

if (isset($_POST['identificador'])) {
    $identificador = $_POST['identificador'];

    // 🔹 PRODUCTIVIDAD
    $tablasProductividad = [
        "premios" => "identificador",
        "libros" => "identificador",
        "solicitud" => "identificador_solicitud",
        "creacion" => "identificador_completo",
        "innovacion" => "identificador",
        "patentes" => "identificador",
        "titulos" => "identificador",
        "trabajos_cientificos" => "identificador",
        "traduccion_libros" => "identificador",
        "produccion_t_s" => "identificador"
    ];

    // 🔹 BONIFICACIÓN
    $tablasBonificacion = [
        "creacion_bon" => "identificador_completo",
        "direccion_tesis" => "identificador",
        "ponencias_bon" => "identificador_completo",
        "posdoctoral" => "identificador",
        "publicacion_bon" => "identificador_completo",
        "resena_bon" => "identificador_completo",
        "trabajos_cientificos_bon" => "identificador",
        "traduccion_bon" => "identificador"
    ];

    // 🔹 Nombres amigables
 $nombresTablas = [
    "premios" => "Premios",
    "libros" => "Libros",
    "solicitud" => "Artículos", // <- renombrada aquí
    "creacion" => "Creaciones",
    "innovacion" => "Innovaciones",
    "patentes" => "Patentes",
    "titulos" => "Títulos",
    "trabajos_cientificos" => "Trabajos Científicos",
    "traduccion_libros" => "Traducción de Libros",
    "produccion_t_s" => "Producción T-S",

    "creacion_bon" => "Creaciones (Bonificación)",
    "direccion_tesis" => "Dirección de Tesis",
    "ponencias_bon" => "Ponencias (Bonificación)",
    "posdoctoral" => "Estancias Posdoctorales",
    "publicacion_bon" => "Publicaciones (Bonificación)",
    "resena_bon" => "Reseñas (Bonificación)",
    "trabajos_cientificos_bon" => "Trabajos Científicos (Bonificación)",
    "traduccion_bon" => "Traducciones (Bonificación)"
];

    // 🔹 Función para buscar coincidencias
    function buscarTablas($tablas, $identificador, $conn) {
        $encontradas = [];
        foreach ($tablas as $tabla => $campo) {
            $query = "SELECT COUNT(*) as total FROM $tabla WHERE $campo = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $identificador);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['total'] > 0) {
                $encontradas[] = $tabla;
            }
        }
        return $encontradas;
    }

    // 🔹 Buscar en cada grupo
    $encontradasProductividad = buscarTablas($tablasProductividad, $identificador, $conn);
    $encontradasBonificacion = buscarTablas($tablasBonificacion, $identificador, $conn);

    // 🔹 Mostrar resultados
    if (!empty($encontradasProductividad) || !empty($encontradasBonificacion)) {
        echo "<h3>El identificador <strong>$identificador</strong> se encuentra en:</h3>";

        if (!empty($encontradasProductividad)) {
            echo "<h4><i class='fas fa-chart-line' style='color:#28a745;'></i> Productividad</h4>
                  <ul class='tabla-lista'>";
            foreach ($encontradasProductividad as $tabla) {
                $nombre = $nombresTablas[$tabla] ?? ucfirst($tabla);
                echo "<li>
                        <a class='tabla-link' onclick=\"cargarRegistros('$tabla', '{$tablasProductividad[$tabla]}', '$identificador')\"> 
                            ▶ $nombre
                        </a>
                        <div id='registros-$tabla' class='tabla-registros'></div>
                      </li>";
            }
            echo "</ul>";
        }

        if (!empty($encontradasBonificacion)) {
            echo "<h4><i class='fas fa-bolt' style='color:#ffc107;'></i> Bonificación</h4>
                  <ul class='tabla-lista'>";
            foreach ($encontradasBonificacion as $tabla) {
                $nombre = $nombresTablas[$tabla] ?? ucfirst($tabla);
                echo "<li>
                        <a class='tabla-link' onclick=\"cargarRegistros('$tabla', '{$tablasBonificacion[$tabla]}', '$identificador')\"> 
                            ▶ $nombre
                        </a>
                        <div id='registros-$tabla' class='tabla-registros'></div>
                      </li>";
            }
            echo "</ul>";
        }

    } else {
        echo "<p>No se encontraron registros para este identificador.</p>";
    }
}
?>
