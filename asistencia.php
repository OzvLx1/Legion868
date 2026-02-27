<?php
include 'auth.php';
include 'db.php';

$mensaje = "";

// 1. CONFIGURACIÓN DE FECHAS (SEMANA ACTUAL)
$lunes = new DateTime();
$lunes->modify('this week monday');

$dias_semana = [];
$nombres_dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$fechas_sql = [];

$temp_date = clone $lunes;
for ($i = 0; $i < 6; $i++) {
    $dias_semana[] = [
        'nombre' => $nombres_dias[$i],
        'fecha' => $temp_date->format('Y-m-d'),
        'dia_num' => $temp_date->format('d')
    ];
    $fechas_sql[] = $temp_date->format('Y-m-d');
    $temp_date->modify('+1 day');
}

$fecha_inicio = $fechas_sql[0];
$fecha_fin = $fechas_sql[5];

// 2. VISTA ACTUAL (ADULTOS O TEENS)
$vista = isset($_GET['v']) ? $_GET['v'] : 'adultos';

// 3. CONFIGURACIÓN DE FILTROS SEGÚN VISTA
$filtro_tipo_usuario = "";
$horarios_disponibles = [];

if ($vista == 'adultos') {
    $filtro_tipo_usuario = "AND tipo_usuario != 'teen'";
    // Horarios específicos solicitados para adultos
    $horarios_disponibles = ["6:00 a.m.", "7:15 a.m.", "8:30 a.m.", "7:05 p.m."];
} else {
    $filtro_tipo_usuario = "AND tipo_usuario = 'teen'";
    // Horarios específicos solicitados para teens
    $horarios_disponibles = ["5:00 p.m.", "6:00 p.m."];
}

// 4. FILTRO DE HORARIO SELECCIONADO
$horario_seleccionado = isset($_GET['horario']) ? $_GET['horario'] : '';
$filtro_horario_sql = "";

if ($horario_seleccionado) {
    $filtro_horario_sql = "AND horario = '$horario_seleccionado'";
}

// 5. PROCESAR GUARDADO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $asistencias_enviadas = isset($_POST['asistencia']) ? $_POST['asistencia'] : [];
    
    // Obtenemos los usuarios que corresponden a la vista actual para limpiar
    // Usamos el filtro de tipo y horario para no borrar asistencias de otros grupos
    $users_result = $conn->query("SELECT id FROM usuarios WHERE 1=1 $filtro_tipo_usuario $filtro_horario_sql");
    
    while($u = $users_result->fetch_assoc()) {
        $uid = $u['id'];
        // Borramos asistencias de esta semana
        $conn->query("DELETE FROM asistencias WHERE usuario_id = $uid AND fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'");
        
        // Insertamos las nuevas
        if (isset($asistencias_enviadas[$uid])) {
            foreach ($asistencias_enviadas[$uid] as $fecha => $valor) {
                $conn->query("INSERT IGNORE INTO asistencias (usuario_id, fecha) VALUES ('$uid', '$fecha')");
            }
        }
    }
    $mensaje = "<div class='bg-green-500 text-white p-3 rounded mb-4 font-bold text-center animate-pulse'>✅ Asistencia guardada correctamente</div>";
}

// 6. CONSULTAR DATOS
// Usuarios filtrados por VISTA y por HORARIO
$sql_users = "SELECT id, nombre, apellido, horario, tipo_usuario FROM usuarios WHERE 1=1 $filtro_tipo_usuario $filtro_horario_sql ORDER BY nombre ASC";
$res_users = $conn->query($sql_users);

// Mapa de asistencia
$mapa_asistencia = [];
if ($res_users->num_rows > 0) {
    $sql_asist = "SELECT usuario_id, fecha FROM asistencias WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    $res_asist = $conn->query($sql_asist);
    while($row = $res_asist->fetch_assoc()) {
        $mapa_asistencia[$row['usuario_id']][$row['fecha']] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia V2 - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .check-custom { width: 24px; height: 24px; cursor: pointer; accent-color: #22c55e; }
        .tabla-hover tbody tr:hover { background-color: #1f2937; }
    </style>
</head>
<body class="bg-gray-900 text-white font-sans">

    <nav class="bg-black border-b border-gray-800 p-4 flex justify-between items-center sticky top-0 z-50">
        <div class="text-xl font-bold text-lime-500 flex items-center gap-2">
            <i class="fas fa-calendar-check"></i> ASISTENCIA <span class="text-white">SEMANAL</span>
        </div>
        <a href="index.php" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm transition">Volver</a>
    </nav>

    <div class="p-6 max-w-6xl mx-auto">
        
        <?= $mensaje ?>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <a href="asistencia.php?v=adultos" class="block p-4 rounded-xl border-t-4 text-center transition shadow-lg <?= $vista=='adultos' ? 'bg-blue-900/40 border-blue-500 ring-1 ring-blue-500' : 'bg-gray-800 border-gray-600 opacity-60 hover:opacity-100' ?>">
                <div class="text-sm font-bold uppercase text-gray-300">GRUPO</div>
                <div class="text-2xl font-bold text-white"><i class="fas fa-user-tie mr-2"></i> ADULTOS</div>
            </a>
            <a href="asistencia.php?v=teens" class="block p-4 rounded-xl border-t-4 text-center transition shadow-lg <?= $vista=='teens' ? 'bg-orange-900/40 border-orange-500 ring-1 ring-orange-500' : 'bg-gray-800 border-gray-600 opacity-60 hover:opacity-100' ?>">
                <div class="text-sm font-bold uppercase text-gray-300">GRUPO</div>
                <div class="text-2xl font-bold text-white"><i class="fas fa-child mr-2"></i> TEENS</div>
            </a>
        </div>

        <div class="bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-xl mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase font-bold text-lime-500">Semana del <?= $lunes->format('d M') ?></p>
                <h2 class="text-xl font-bold text-white">Pasar Lista: <?= strtoupper($vista) ?></h2>
            </div>

            <form method="GET" action="" class="flex items-center gap-2">
                <input type="hidden" name="v" value="<?= $vista ?>">
                
                <div class="flex items-center gap-2 bg-black/30 p-2 rounded-lg border border-gray-600">
                    <label class="text-xs font-bold text-gray-400">HORARIO:</label>
                    <select name="horario" onchange="this.form.submit()" class="bg-gray-900 text-white p-1 rounded border border-gray-600 outline-none focus:border-orange-500 text-sm font-bold w-40">
                        <option value="">-- Todos --</option>
                        <?php foreach($horarios_disponibles as $h): ?>
                            <option value="<?= $h ?>" <?= $horario_seleccionado==$h ? 'selected' : '' ?>>
                                <?= $h ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="horario_oculto" value="<?= $horario_seleccionado ?>"> 
            <input type="hidden" name="vista_oculta" value="<?= $vista ?>">

            <div class="bg-gray-800 rounded-xl overflow-hidden border border-gray-600 shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse tabla-hover">
                        <thead>
                            <tr class="bg-black text-gray-400 text-xs uppercase tracking-wider">
                                <th class="p-4 border-b border-gray-700 min-w-[200px]">
                                    Legionario (<?= $res_users->num_rows ?>)
                                </th>
                                <?php foreach($dias_semana as $dia): ?>
                                    <th class="p-2 border-b border-gray-700 text-center border-l border-gray-700 <?= ($dia['fecha'] == date('Y-m-d')) ? 'bg-orange-900/50 text-white' : '' ?>">
                                        <div class="font-bold"><?= $dia['nombre'] ?></div>
                                        <div class="text-[10px]"><?= $dia['dia_num'] ?></div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700 text-sm">
                            <?php 
                            $res_users->data_seek(0);
                            if ($res_users->num_rows > 0): 
                                while($u = $res_users->fetch_assoc()): 
                                    $uid = $u['id'];
                            ?>
                                <tr>
                                    <td class="p-3 font-bold text-gray-200 border-r border-gray-700">
                                        <?= $u['nombre'] ?> <?= $u['apellido'] ?>
                                        <span class="block text-[10px] text-gray-500 font-normal uppercase flex justify-between">
                                            <span><?= $u['horario'] ?></span>
                                            <span class="<?= $u['tipo_usuario']=='teen'?'text-orange-400':'text-blue-400' ?>"><?= substr($u['tipo_usuario'],0,1) ?></span>
                                        </span>
                                    </td>

                                    <?php foreach($dias_semana as $dia): 
                                        $fecha_iter = $dia['fecha'];
                                        $checked = isset($mapa_asistencia[$uid][$fecha_iter]) ? 'checked' : '';
                                        $bg_cell = ($fecha_iter == date('Y-m-d')) ? 'bg-gray-700/30' : '';
                                    ?>
                                        <td class="p-2 text-center border-l border-gray-700 <?= $bg_cell ?>">
                                            <input type="checkbox" name="asistencia[<?= $uid ?>][<?= $fecha_iter ?>]" class="check-custom rounded focus:ring-orange-500" <?= $checked ?>>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="p-10 text-center text-gray-500 italic">No hay legionarios en esta categoría/horario.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-lime-600 hover:bg-orange-500 text-white font-bold py-3 px-8 rounded-full shadow-lg transform hover:scale-105 transition flex items-center gap-2">
                    <i class="fas fa-save"></i> GUARDAR SEMANA
                </button>
            </div>
        </form>

    </div>
</body>
</html>