<?php
date_default_timezone_set('America/Mexico_City');
include 'auth.php';
include 'db.php';

$mes_actual = date('m');
$anio_actual = date('Y');

// --- 1. DATOS PARA LAS TARJETAS (KPIs) ---
$total_mes = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes_actual' AND YEAR(fecha)='$anio_actual' AND forma_pago != 'Monedero'")->fetch_assoc()['total'] ?? 0;
$bebidas_mes = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes_actual' AND (concepto LIKE '%Agua%' OR concepto LIKE '%Electrolífe%') AND forma_pago != 'Monedero'")->fetch_assoc()['total'] ?? 0;
$servicios_mes = $total_mes - $bebidas_mes;

// --- 2. DATOS PARA GRÁFICA DE INGRESOS DIARIOS ---
// CORRECCIÓN DEL ERROR SQL: Ahora agrupamos y ordenamos estrictamente por DATE(fecha)
$dias_labels = []; $ingresos_data = [];
$res_diario = $conn->query("SELECT DATE(fecha) as dia, SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes_actual' AND forma_pago != 'Monedero' GROUP BY DATE(fecha) ORDER BY DATE(fecha) ASC");
while($row = $res_diario->fetch_assoc()){
    $dias_labels[] = date('d', strtotime($row['dia']));
    $ingresos_data[] = $row['total'];
}

// --- 3. DATOS PARA GRÁFICA DE MÉTODOS DE PAGO ---
$metodos_labels = []; $metodos_data = [];
$res_metodos = $conn->query("SELECT forma_pago, SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes_actual' GROUP BY forma_pago");
while($row = $res_metodos->fetch_assoc()){
    $metodos_labels[] = $row['forma_pago'];
    $metodos_data[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-sans">
    
    <nav class="bg-black border-b border-gray-800 p-4 flex justify-between items-center sticky top-0 z-50 shadow-md">
        <div class="text-xl font-black tracking-widest text-white flex items-center gap-2">
            REPORTE<span class="text-green-500">MENSUAL</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="index.php" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm transition font-bold shadow flex items-center gap-2">
                <i class="fas fa-home"></i> Inicio
            </a>
            <a href="logout.php" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded text-sm transition font-bold shadow flex items-center gap-2">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </nav>

    <div class="p-6 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 mt-4">
            <div class="bg-gray-800 p-6 rounded-2xl border-l-8 border-green-500 shadow-xl">
                <p class="text-xs uppercase font-bold text-gray-400">Total Recaudado (<?= date('F') ?>)</p>
                <h3 class="text-3xl font-bold text-white mt-2">$<?= number_format($total_mes, 2) ?></h3>
            </div>
            <div class="bg-gray-800 p-6 rounded-2xl border-l-8 border-cyan-500 shadow-xl">
                <p class="text-xs uppercase font-bold text-gray-400">Venta Bebidas</p>
                <h3 class="text-3xl font-bold text-white mt-2">$<?= number_format($bebidas_mes, 2) ?></h3>
            </div>
            <div class="bg-gray-800 p-6 rounded-2xl border-l-8 border-purple-500 shadow-xl">
                <p class="text-xs uppercase font-bold text-gray-400">Venta Servicios</p>
                <h3 class="text-3xl font-bold text-white mt-2">$<?= number_format($servicios_mes, 2) ?></h3>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-lg">
                <h4 class="text-gray-400 font-bold uppercase text-xs mb-6 text-center">Flujo de Caja Diario</h4>
                <canvas id="chartIngresos" height="200"></canvas>
            </div>
            <div class="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-lg flex flex-col items-center">
                <h4 class="text-gray-400 font-bold uppercase text-xs mb-6">Uso de Métodos de Pago</h4>
                <div class="w-full max-w-[280px]"><canvas id="chartMetodos"></canvas></div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-2xl overflow-hidden border border-gray-700 shadow-xl">
            <div class="bg-black p-4"><h3 class="font-bold text-sm uppercase">Detalle de Ingresos Reales</h3></div>
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-900 text-gray-500 text-[10px] uppercase tracking-widest">
                    <tr><th class="p-4">Día</th><th class="p-4">Concepto</th><th class="p-4">Método</th><th class="p-4 text-right">Monto</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php 
                    $res_lista = $conn->query("SELECT * FROM pagos WHERE MONTH(fecha)='$mes_actual' AND forma_pago != 'Monedero' ORDER BY fecha DESC LIMIT 15");
                    while($p = $res_lista->fetch_assoc()):
                    ?>
                    <tr class="hover:bg-gray-700/50 transition">
                        <td class="p-4 text-gray-400"><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                        <td class="p-4 font-bold"><?= $p['concepto'] ?></td>
                        <td class="p-4"><span class="bg-gray-700 px-2 py-1 rounded text-[10px] font-bold"><?= $p['forma_pago'] ?></span></td>
                        <td class="p-4 text-right font-bold text-green-400">$<?= number_format($p['monto'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const ctxIngresos = document.getElementById('chartIngresos').getContext('2d');
        new Chart(ctxIngresos, {
            type: 'bar',
            data: {
                labels: <?= json_encode($dias_labels) ?>,
                datasets: [{ label: 'Ingresos', data: <?= json_encode($ingresos_data) ?>, backgroundColor: '#22c55e', borderRadius: 5 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        const ctxMetodos = document.getElementById('chartMetodos').getContext('2d');
        new Chart(ctxMetodos, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($metodos_labels) ?>,
                datasets: [{ data: <?= json_encode($metodos_data) ?>, backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6'], borderWidth: 0 }]
            },
            options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</body>
</html>