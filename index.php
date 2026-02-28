<?php
include 'auth.php';
include 'db.php';

// --- 1. CONSULTAS DE ESTADÍSTICAS ---
// Contar usuarios totales
$total_users = $conn->query("SELECT count(*) as total FROM usuarios")->fetch_assoc()['total'];

// Dinero del mes (Caja)
$mes_actual = date('m');
$anio_actual = date('Y');
$total_caja = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes_actual' AND YEAR(fecha)='$anio_actual'")->fetch_assoc()['total'] ?? 0;

// Asistencias de hoy (CORREGIDO: Usamos 'fecha' en lugar de 'fecha_hora')
$fecha_hoy = date('Y-m-d');
// Intentamos contar con la columna 'fecha' que es la más común
$check_col = $conn->query("SHOW COLUMNS FROM asistencias LIKE 'fecha_hora'");
$col_name = ($check_col->num_rows > 0) ? 'fecha_hora' : 'fecha';
$total_asistencias = $conn->query("SELECT count(*) as total FROM asistencias WHERE DATE($col_name)='$fecha_hoy'")->fetch_assoc()['total'];


// --- 2. CONSULTA DE CUMPLEAÑEROS DEL MES ---
$sql_cumples = "SELECT nombre, apellido, fecha_nacimiento, 
                DAY(fecha_nacimiento) as dia,
                TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad_nueva
                FROM usuarios 
                WHERE MONTH(fecha_nacimiento) = '$mes_actual' 
                ORDER BY dia ASC";
$res_cumples = $conn->query($sql_cumples);

$meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$nombre_mes = $meses[(int)$mes_actual];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-sans h-screen flex flex-col overflow-hidden">

<nav class="bg-black border-b border-gray-800 p-4 flex justify-between items-center sticky top-0 z-50 shadow-md">
        <div class="text-2xl font-black tracking-widest text-white flex items-center gap-2">
            LEGIÓN<span class="text-green-500">868</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-gray-400 text-sm hidden md:inline">Hola, Administrador</span>
            
            <a href="reportemensual.php" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition font-bold shadow flex items-center gap-2">
                <i class="fas fa-chart-line"></i> Reportes
            </a>
            
            <a href="logout.php" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded text-sm transition font-bold shadow flex items-center gap-2">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </nav>
    <div class="flex flex-1 overflow-hidden">
        
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <h2 class="text-3xl font-bold mb-6 flex items-center gap-2"><i class="fas fa-tachometer-alt text-lime-500"></i> Panel de Control</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg flex items-center justify-between">
                    <div><p class="text-gray-400 text-sm font-bold uppercase">Legionarios</p><h3 class="text-3xl font-bold text-white mt-1"><?= $total_users ?></h3></div>
                    <div class="bg-blue-900/50 p-4 rounded-full text-blue-400"><i class="fas fa-users text-2xl"></i></div>
                </div>
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg flex items-center justify-between">
                    <div><p class="text-gray-400 text-sm font-bold uppercase">Ingresos (<?= substr($nombre_mes,0,3) ?>)</p><h3 class="text-3xl font-bold text-green-400 mt-1">$<?= number_format($total_caja) ?></h3></div>
                    <div class="bg-green-900/50 p-4 rounded-full text-green-400"><i class="fas fa-dollar-sign text-2xl"></i></div>
                </div>
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg flex items-center justify-between">
                    <div><p class="text-gray-400 text-sm font-bold uppercase">Entrenando Hoy</p><h3 class="text-3xl font-bold text-orange-400 mt-1"><?= $total_asistencias ?></h3></div>
                    <div class="bg-orange-900/50 p-4 rounded-full text-orange-400"><i class="fas fa-running text-2xl"></i></div>
                </div>
            </div>

            <h3 class="text-xl font-bold text-gray-300 mb-4">Accesos Directos</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="pagos.php" class="group bg-gray-800 hover:bg-green-900/40 border border-gray-700 hover:border-green-500 p-6 rounded-xl shadow-lg transition duration-300 text-center flex flex-col items-center gap-3">
                    <div class="bg-gray-700 group-hover:bg-green-600 w-14 h-14 rounded-full flex items-center justify-center transition"><i class="fas fa-cash-register text-2xl text-green-400 group-hover:text-white"></i></div>
                    <span class="font-bold text-gray-300 group-hover:text-white">Caja & Stock</span>
                </a>
                <a href="legionarios.php" class="group bg-gray-800 hover:bg-blue-900/40 border border-gray-700 hover:border-blue-500 p-6 rounded-xl shadow-lg transition duration-300 text-center flex flex-col items-center gap-3">
                    <div class="bg-gray-700 group-hover:bg-blue-600 w-14 h-14 rounded-full flex items-center justify-center transition"><i class="fas fa-users text-2xl text-blue-400 group-hover:text-white"></i></div>
                    <span class="font-bold text-gray-300 group-hover:text-white">Legionarios</span>
                </a>
                <a href="registro.php" class="group bg-gray-800 hover:bg-purple-900/40 border border-gray-700 hover:border-purple-500 p-6 rounded-xl shadow-lg transition duration-300 text-center flex flex-col items-center gap-3">
                    <div class="bg-gray-700 group-hover:bg-purple-600 w-14 h-14 rounded-full flex items-center justify-center transition"><i class="fas fa-user-plus text-2xl text-purple-400 group-hover:text-white"></i></div>
                    <span class="font-bold text-gray-300 group-hover:text-white">Nuevo Ingreso</span>
                </a>
                <a href="asistencia.php" class="group bg-gray-800 hover:bg-orange-900/40 border border-gray-700 hover:border-orange-500 p-6 rounded-xl shadow-lg transition duration-300 text-center flex flex-col items-center gap-3">
                    <div class="bg-gray-700 group-hover:bg-orange-600 w-14 h-14 rounded-full flex items-center justify-center transition"><i class="fas fa-clipboard-check text-2xl text-orange-400 group-hover:text-white"></i></div>
                    <span class="font-bold text-gray-300 group-hover:text-white">Check-in</span>
                </a>
                <a href="#" class="group bg-gray-800 hover:bg-yellow-900/40 border border-gray-700 hover:border-yellow-500 p-6 rounded-xl shadow-lg transition duration-300 text-center flex flex-col items-center gap-3 md:col-span-2 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 text-yellow-500/10 text-9xl"><i class="fas fa-sun"></i></div>
                    <div class="bg-gray-700 group-hover:bg-yellow-500 w-14 h-14 rounded-full flex items-center justify-center transition z-10"><i class="fas fa-umbrella-beach text-2xl text-yellow-400 group-hover:text-white"></i></div>
                    <span class="font-bold text-gray-300 group-hover:text-white z-10">Curso de Verano</span>
                    <span class="text-xs text-yellow-500 font-bold uppercase tracking-widest z-10">Inscripciones</span>
                </a>
            </div>
        </main>

        <aside class="w-80 bg-gray-800 border-l border-gray-700 hidden lg:flex flex-col">
            <div class="p-6 border-b border-gray-700 bg-gray-900/50">
                <h3 class="text-lg font-bold text-pink-400 flex items-center gap-2"><i class="fas fa-birthday-cake animate-bounce"></i> Cumpleaños</h3>
                <p class="text-xs text-gray-500 uppercase font-bold mt-1">Festejados de <?= $nombre_mes ?></p>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <?php if ($res_cumples->num_rows > 0): ?>
                    <?php while($c = $res_cumples->fetch_assoc()): ?>
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-700/50 hover:bg-gray-700 border border-gray-600 transition">
                            <div class="bg-gray-800 w-12 h-12 rounded-lg flex flex-col items-center justify-center border border-gray-600">
                                <span class="text-xs text-pink-500 font-bold uppercase"><?= substr($nombre_mes, 0, 3) ?></span>
                                <span class="text-xl font-bold text-white leading-none"><?= $c['dia'] ?></span>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-200 text-sm"><?= $c['nombre'] ?> <?= $c['apellido'] ?></h4>
                                <p class="text-xs text-gray-400">Cumple <?= $c['edad_nueva'] ?> años</p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center p-8 text-gray-500">
                        <i class="fas fa-calendar-times text-4xl mb-3 opacity-30"></i>
                        <p class="text-sm">Sin cumpleaños registrados.</p>
                        <p class="text-xs mt-2 text-gray-600">(Falta registrar fechas)</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="p-4 bg-gray-900/30 border-t border-gray-700 text-center">
                <a href="legionarios.php" class="text-xs text-blue-400 hover:text-blue-300 font-bold">Ver lista completa <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
        </aside>
    </div>
</body>
</html>