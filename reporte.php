<?php
include 'auth.php';
include 'db.php';

// Configuración de Fechas
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

// Array de nombres de meses para el selector
$meses_nombres = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// CONSULTA MAESTRA: Trae TODO lo de ese mes y año
$sql = "SELECT p.*, u.nombre, u.apellido, u.tipo_usuario 
        FROM pagos p 
        JOIN usuarios u ON p.usuario_id = u.id 
        WHERE MONTH(p.fecha) = '$mes' AND YEAR(p.fecha) = '$anio' 
        ORDER BY p.fecha DESC";
$resultado = $conn->query($sql);

// CÁLCULOS DE TOTALES
$total_efectivo = 0;
$total_tarjeta = 0;
$total_transf = 0;
$gran_total = 0;

// Hacemos un primer recorrido (o podríamos sumar en SQL, pero aquí aprovechamos el loop)
// Para simplificar, sumaremos mientras mostramos la tabla, pero para las tarjetas de arriba haremos queries rápidos.
$res_total = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes' AND YEAR(fecha)='$anio'");
$gran_total = $res_total->fetch_assoc()['total'] ?? 0;

$res_cat = $conn->query("SELECT forma_pago, SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes' AND YEAR(fecha)='$anio' GROUP BY forma_pago");
while($r = $res_cat->fetch_assoc()){
    if($r['forma_pago'] == 'Efectivo') $total_efectivo = $r['total'];
    if($r['forma_pago'] == 'Tarjeta') $total_tarjeta = $r['total'];
    if($r['forma_pago'] == 'Transferencia') $total_transf = $r['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-900 font-sans">

    <nav class="bg-black text-white p-4 flex justify-between items-center print:hidden">
        <div class="font-bold text-xl"><i class="fas fa-file-invoice-dollar text-green-500"></i> REPORTE <span class="text-gray-500">MENSUAL</span></div>
        <a href="pagos.php" class="bg-gray-800 hover:bg-gray-700 px-4 py-2 rounded text-sm">Volver a Caja</a>
    </nav>

    <div class="max-w-5xl mx-auto p-8 bg-white shadow-xl mt-8 min-h-screen">
        
        <div class="flex justify-between items-end mb-8 border-b-2 border-black pb-4">
            <div>
                <h1 class="text-4xl font-bold text-black uppercase">LEGIÓN 868</h1>
                <p class="text-gray-500 text-sm">Reporte General de Ingresos</p>
            </div>
            <div class="text-right">
                <form action="" method="GET" class="print:hidden flex gap-2">
                    <select name="mes" onchange="this.form.submit()" class="border p-1 rounded">
                        <?php foreach($meses_nombres as $n => $nombre): ?>
                            <option value="<?= $n ?>" <?= $n==$mes?'selected':'' ?>><?= $nombre ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="anio" onchange="this.form.submit()" class="border p-1 rounded">
                        <option value="2024" <?= $anio=='2024'?'selected':'' ?>>2024</option>
                        <option value="2025" <?= $anio=='2025'?'selected':'' ?>>2025</option>
                        <option value="2026" <?= $anio=='2026'?'selected':'' ?>>2026</option>
                    </select>
                </form>
                <div class="hidden print:block text-2xl font-bold">
                    <?= $meses_nombres[$mes] ?> <?= $anio ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-8 text-center">
            <div class="p-4 bg-green-100 rounded border border-green-200">
                <p class="text-xs font-bold text-green-800 uppercase">Total Efectivo</p>
                <p class="text-xl font-bold text-green-900">$<?= number_format($total_efectivo) ?></p>
            </div>
            <div class="p-4 bg-blue-100 rounded border border-blue-200">
                <p class="text-xs font-bold text-blue-800 uppercase">Total Tarjeta</p>
                <p class="text-xl font-bold text-blue-900">$<?= number_format($total_tarjeta) ?></p>
            </div>
            <div class="p-4 bg-purple-100 rounded border border-purple-200">
                <p class="text-xs font-bold text-purple-800 uppercase">Total Transf.</p>
                <p class="text-xl font-bold text-purple-900">$<?= number_format($total_transf) ?></p>
            </div>
            <div class="p-4 bg-black text-white rounded shadow-lg">
                <p class="text-xs font-bold text-gray-400 uppercase">GRAN TOTAL</p>
                <p class="text-2xl font-bold text-green-400">$<?= number_format($gran_total) ?></p>
            </div>
        </div>

        <table class="w-full text-left text-sm border-collapse">
            <thead>
                <tr class="bg-gray-200 border-b-2 border-gray-400 text-gray-600 uppercase text-xs">
                    <th class="p-3">Día</th>
                    <th class="p-3">Cliente</th>
                    <th class="p-3">Concepto</th>
                    <th class="p-3 text-center">Método</th>
                    <th class="p-3 text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-300">
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 text-gray-500 font-mono"><?= date('d/M H:i', strtotime($row['fecha'])) ?></td>
                            <td class="p-3 font-bold">
                                <?= $row['nombre'] ?> <?= $row['apellido'] ?>
                                <span class="text-xs text-gray-400 font-normal block"><?= ucfirst($row['tipo_usuario']) ?></span>
                            </td>
                            <td class="p-3 text-gray-700"><?= $row['concepto'] ?></td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold border 
                                    <?= $row['forma_pago']=='Efectivo'?'bg-green-50 text-green-700 border-green-200':
                                       ($row['forma_pago']=='Tarjeta'?'bg-blue-50 text-blue-700 border-blue-200':'bg-purple-50 text-purple-700 border-purple-200') ?>">
                                    <?= $row['forma_pago'] ?>
                                </span>
                            </td>
                            <td class="p-3 text-right font-bold text-gray-900">$<?= number_format($row['monto']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="p-10 text-center text-gray-400">No hay movimientos en este mes.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="mt-12 pt-8 border-t border-gray-300 flex justify-between text-xs text-gray-400 hidden print:flex">
            <div>Generado por Sistema Legión 868</div>
            <div>Fecha de impresión: <?= date('d/m/Y H:i') ?></div>
        </div>

    </div>

    <button onclick="window.print()" class="fixed bottom-8 right-8 bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-full shadow-2xl print:hidden flex items-center gap-2 font-bold transition transform hover:scale-110">
        <i class="fas fa-print text-xl"></i> IMPRIMIR REPORTE
    </button>

</body>
</html>