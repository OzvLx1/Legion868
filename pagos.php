<?php
// FORZAR ZONA HORARIA DE MÉXICO
date_default_timezone_set('America/Mexico_City');

include 'auth.php';
include 'db.php';

$mensaje = "";
$mes_actual = date('m');
$anio_actual = date('Y');
$fecha_hoy = date('Y-m-d');
$fecha_exacta = date('Y-m-d H:i:s');

// --- 1. GESTIÓN DEL FONDO DE CAJA DIARIO ---
$res_fondo = $conn->query("SELECT fondo_inicial FROM caja_diaria WHERE fecha = '$fecha_hoy'");
if ($res_fondo->num_rows == 0) {
    $conn->query("INSERT INTO caja_diaria (fecha, fondo_inicial) VALUES ('$fecha_hoy', 0)");
    $fondo_hoy = 0;
} else {
    $fondo_hoy = $res_fondo->fetch_object()->fondo_inicial;
}

// 2. VISTA ACTUAL
$vista = isset($_GET['v']) ? $_GET['v'] : 'adultos';

// --- 3. PROCESAR FORMULARIOS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // A) ACTUALIZAR FONDO INICIAL
    if (isset($_POST['accion']) && $_POST['accion'] == 'fondo') {
        $nuevo_fondo = $_POST['fondo_inicial'];
        $conn->query("UPDATE caja_diaria SET fondo_inicial = '$nuevo_fondo' WHERE fecha = '$fecha_hoy'");
        $fondo_hoy = $nuevo_fondo;
        $mensaje = "<div class='bg-blue-600 text-white p-3 rounded mb-6 font-bold text-center'>💵 Fondo de caja actualizado a $$fondo_hoy</div>";
    }
    // B) REABASTECER INVENTARIO
    elseif (isset($_POST['accion']) && $_POST['accion'] == 'reabastecer') {
        $prod = $_POST['producto_stock'];
        $cant = $_POST['cantidad_stock'];
        $conn->query("UPDATE inventario SET stock = stock + $cant WHERE producto = '$prod'");
        $mensaje = "<div class='bg-blue-600 text-white p-3 rounded mb-6 font-bold text-center'>📦 Stock actualizado: +$cant $prod</div>";
    }
    // C) COBRAR (VENTA NORMAL, PREPAGO O PLAN ESPECIAL)
    elseif (isset($_POST['accion']) && $_POST['accion'] == 'cobrar') {
        $usuario_id = $_POST['usuario_id'];
        $precio_unitario = $_POST['precio_unitario'];
        $cantidad = $_POST['cantidad'];
        $concepto = $_POST['concepto'];
        $forma_pago = $_POST['forma_pago']; 

        // NUEVA LÓGICA: Si es Abono o Plan Especial, tomamos el monto escrito a mano
        $es_monto_manual = ($concepto == 'Abono / Prepago' || $concepto == 'Plan Especial');
        $monto_total = $es_monto_manual ? $_POST['monto_total_visual'] : ($precio_unitario * $cantidad);
        
        $concepto_final = ($cantidad > 1 && !$es_monto_manual) ? "$concepto (x$cantidad)" : $concepto;

        // VERIFICACIÓN DE MONEDERO
        if ($forma_pago == 'Monedero') {
            $saldo_actual = $conn->query("SELECT saldo_monedero FROM usuarios WHERE id = '$usuario_id'")->fetch_object()->saldo_monedero;
            if ($saldo_actual < $monto_total) {
                $mensaje = "<div class='bg-red-500 text-white p-3 rounded mb-6 font-bold text-center animate-bounce'>❌ Error: El usuario no tiene saldo suficiente. Saldo actual: $$saldo_actual</div>";
                goto saltar_cobro; 
            }
        }

        // 1. Guardar el pago 
        $sql_pago = "INSERT INTO pagos (usuario_id, monto, concepto, forma_pago, fecha) VALUES ('$usuario_id', '$monto_total', '$concepto_final', '$forma_pago', '$fecha_exacta')";
        
        if ($conn->query($sql_pago) === TRUE) {
            
            // 2. Lógica Especial
            if ($concepto == 'Abono / Prepago') {
                $conn->query("UPDATE usuarios SET saldo_monedero = saldo_monedero + $monto_total WHERE id = '$usuario_id'");
                $mensaje = "<div class='bg-green-500 text-white p-3 rounded mb-6 font-bold text-center shadow animate-pulse'>💰 Abono registrado. Saldo a favor sumado.</div>";
            } else {
                // Si es un Plan Especial o Mensualidad normal, se marca como pagado
                if (!in_array($concepto, ['Agua', 'Electrolífe'])) {
                    $conn->query("UPDATE usuarios SET estado_pago = 'pagado' WHERE id = '$usuario_id'");
                } else {
                    $conn->query("UPDATE inventario SET stock = stock - $cantidad WHERE producto = '$concepto'");
                }

                if ($forma_pago == 'Monedero') {
                    $conn->query("UPDATE usuarios SET saldo_monedero = saldo_monedero - $monto_total WHERE id = '$usuario_id'");
                }

                $mensaje = "<div class='bg-green-500 text-white p-3 rounded mb-6 font-bold text-center shadow animate-pulse'>💰 Venta Registrada: $concepto_final en $forma_pago</div>";
            }
        }
        saltar_cobro: 
    }
}

// --- 4. ELIMINAR PAGO ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $qry = $conn->query("SELECT * FROM pagos WHERE id = $id");
    if($row = $qry->fetch_assoc()) {
        $concepto_borrado = $row['concepto'];
        $monto_borrado = $row['monto'];
        $uid = $row['usuario_id'];
        
        if (strpos($concepto_borrado, 'Agua') !== false || strpos($concepto_borrado, 'Electrolífe') !== false) {
            $prod_nombre = (strpos($concepto_borrado, 'Agua') !== false) ? 'Agua' : 'Electrolífe';
            $cant_dev = 1;
            if (preg_match('/\(x(\d+)\)/', $concepto_borrado, $matches)) { $cant_dev = $matches[1]; }
            $conn->query("UPDATE inventario SET stock = stock + $cant_dev WHERE producto = '$prod_nombre'");
        }

        if ($row['forma_pago'] == 'Monedero') {
            $conn->query("UPDATE usuarios SET saldo_monedero = saldo_monedero + $monto_borrado WHERE id = $uid");
        }
        if ($row['concepto'] == 'Abono / Prepago') {
            $conn->query("UPDATE usuarios SET saldo_monedero = saldo_monedero - $monto_borrado WHERE id = $uid");
        }
    }
    $conn->query("DELETE FROM pagos WHERE id = $id");
    header("Location: pagos.php?v=" . $vista);
}

// --- 5. CONSULTAS DE FINANZAS ---
$total_bebidas = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes_actual' AND YEAR(fecha)='$anio_actual' AND forma_pago != 'Monedero' AND (concepto LIKE '%Agua%' OR concepto LIKE '%Electrolífe%')")->fetch_assoc()['total'] ?? 0;
$total_teens = $conn->query("SELECT SUM(p.monto) as total FROM pagos p JOIN usuarios u ON p.usuario_id=u.id WHERE MONTH(p.fecha)='$mes_actual' AND forma_pago != 'Monedero' AND u.tipo_usuario='teen' AND p.concepto NOT LIKE '%Agua%' AND p.concepto NOT LIKE '%Electrolífe%' AND p.concepto != 'Abono / Prepago'")->fetch_assoc()['total'] ?? 0;
$total_adultos = $conn->query("SELECT SUM(p.monto) as total FROM pagos p JOIN usuarios u ON p.usuario_id=u.id WHERE MONTH(p.fecha)='$mes_actual' AND forma_pago != 'Monedero' AND u.tipo_usuario!='teen' AND p.concepto NOT LIKE '%Agua%' AND p.concepto NOT LIKE '%Electrolífe%' AND p.concepto != 'Abono / Prepago'")->fetch_assoc()['total'] ?? 0;

$total_abonos = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE MONTH(fecha)='$mes_actual' AND YEAR(fecha)='$anio_actual' AND concepto = 'Abono / Prepago'")->fetch_assoc()['total'] ?? 0;
$gran_total = $total_adultos + $total_teens + $total_bebidas + $total_abonos;

$ingreso_efectivo_hoy = $conn->query("SELECT SUM(monto) as total FROM pagos WHERE DATE(fecha)='$fecha_hoy' AND forma_pago = 'Efectivo'")->fetch_assoc()['total'] ?? 0;
$caja_fisica_hoy = $fondo_hoy + $ingreso_efectivo_hoy;

$stock_agua = $conn->query("SELECT stock FROM inventario WHERE producto='Agua'")->fetch_object()->stock ?? 0;
$stock_electro = $conn->query("SELECT stock FROM inventario WHERE producto='Electrolífe'")->fetch_object()->stock ?? 0;

$res_saldos = $conn->query("SELECT nombre, apellido, saldo_monedero FROM usuarios WHERE saldo_monedero > 0 ORDER BY saldo_monedero DESC");

// Configuración Vistas
$filtro_sql_usuarios = "";
$filtro_sql_historial = "";

$opcion_abono = ['nombre'=>'Abono / Prepago', 'precio'=>'manual'];
$opcion_especial = ['nombre'=>'Plan Especial', 'precio'=>'manual']; // NUEVA OPCIÓN

if ($vista == 'adultos') {
    $filtro_sql_usuarios = "WHERE tipo_usuario != 'teen'";
    $filtro_sql_historial = "AND u.tipo_usuario != 'teen' AND p.concepto NOT LIKE '%Agua%' AND p.concepto NOT LIKE '%Electrolífe%'";
    // Agregamos Plan Especial a los adultos
    $planes_disponibles = [['nombre'=>'Mensual','precio'=>3500], ['nombre'=>'Semanal','precio'=>1250], ['nombre'=>'4 Veces x Semana','precio'=>2800], ['nombre'=>'3 Veces x Semana','precio'=>2500], ['nombre'=>'Clase Suelta','precio'=>250], $opcion_especial, $opcion_abono];
} elseif ($vista == 'teens') {
    $filtro_sql_usuarios = "WHERE tipo_usuario = 'teen'";
    $filtro_sql_historial = "AND u.tipo_usuario = 'teen' AND p.concepto NOT LIKE '%Agua%' AND p.concepto NOT LIKE '%Electrolífe%'";
    // Agregamos Plan Especial a los teens
    $planes_disponibles = [['nombre'=>'Paquete 3x Semana','precio'=>1650], ['nombre'=>'Mensualidad Completa','precio'=>2500], ['nombre'=>'Clase Suelta','precio'=>250], $opcion_especial, $opcion_abono];
} elseif ($vista == 'bebidas') {
    $filtro_sql_usuarios = ""; 
    $filtro_sql_historial = "AND (p.concepto LIKE '%Agua%' OR p.concepto LIKE '%Electrolífe%' OR p.concepto = 'Abono / Prepago')";
    $planes_disponibles = [['nombre'=>'Agua','precio'=>30], ['nombre'=>'Electrolífe','precio'=>30], $opcion_abono];
}

$sql_lista = "SELECT id, nombre, apellido, saldo_monedero, tipo_usuario, horario FROM usuarios $filtro_sql_usuarios ORDER BY nombre ASC";
$res_lista = $conn->query($sql_lista);

$sql_historial = "SELECT p.*, u.nombre, u.apellido FROM pagos p JOIN usuarios u ON p.usuario_id = u.id WHERE DATE(p.fecha) = '$fecha_hoy' $filtro_sql_historial ORDER BY p.id DESC";
$res_historial = $conn->query($sql_historial);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja & Prepago - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-sans">

    <nav class="bg-black border-b border-gray-800 p-4 flex justify-between items-center sticky top-0 z-50">
        <div class="text-xl font-bold text-green-500 flex items-center gap-2">
            <i class="fas fa-cash-register"></i> CAJA <span class="text-white">LEGIÓN</span>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="index.php" class="text-gray-400 hover:text-white text-sm border border-gray-600 px-3 py-1 rounded transition"><i class="fas fa-home mr-2"></i>Inicio</a>
            <a href="reporte.php" class="text-gray-400 hover:text-white text-sm border border-gray-600 px-3 py-1 rounded transition"><i class="fas fa-file-alt mr-2"></i>Reporte</a>
            <div class="bg-gray-800 px-4 py-1 rounded border border-gray-600">
                <span class="text-xs text-gray-400 uppercase">Ingresos Mes</span>
                <span class="font-bold text-green-400 ml-2">$<?= number_format($gran_total) ?></span>
            </div>
        </div>
    </nav>

    <div class="p-6 max-w-7xl mx-auto">
        
        <div class="bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg mb-6 flex justify-between items-center flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-black p-3 rounded-lg border border-gray-700 text-center">
                    <p class="text-[10px] uppercase text-gray-500 font-bold">Total Físico en Caja</p>
                    <p class="text-2xl font-bold text-green-400">$<?= number_format($caja_fisica_hoy) ?></p>
                </div>
                <div class="text-sm text-gray-400">
                    <p>Fondo Inicial: <b>$<?= number_format($fondo_hoy) ?></b></p>
                    <p>Ventas en Efectivo Hoy: <b class="text-white">$<?= number_format($ingreso_efectivo_hoy) ?></b></p>
                </div>
            </div>
            
            <form method="POST" action="" class="flex items-end gap-2">
                <input type="hidden" name="accion" value="fondo">
                <div>
                    <label class="block text-[10px] uppercase text-gray-500 font-bold">Cambiar Fondo Inicial</label>
                    <input type="number" name="fondo_inicial" value="<?= $fondo_hoy ?>" class="w-24 bg-gray-900 text-white text-sm p-2 rounded border border-gray-600 outline-none focus:border-green-500">
                </div>
                <button type="submit" class="bg-gray-700 hover:bg-gray-600 px-3 py-2 rounded text-sm font-bold transition"><i class="fas fa-save"></i></button>
            </form>
        </div>

        <?= $mensaje ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <a href="pagos.php?v=adultos" class="block p-3 rounded-lg border-t-4 text-center transition <?= $vista=='adultos'?'bg-blue-900/40 border-blue-500 ring-1 ring-blue-500':'bg-gray-800 border-gray-600' ?>">
                <div class="text-xs font-bold uppercase text-gray-300">Adultos</div>
                <div class="text-lg font-bold">$<?= number_format($total_adultos) ?></div>
            </a>
            <a href="pagos.php?v=teens" class="block p-3 rounded-lg border-t-4 text-center transition <?= $vista=='teens'?'bg-orange-900/40 border-orange-500 ring-1 ring-orange-500':'bg-gray-800 border-gray-600' ?>">
                <div class="text-xs font-bold uppercase text-gray-300">Teens</div>
                <div class="text-lg font-bold">$<?= number_format($total_teens) ?></div>
            </a>
            <a href="pagos.php?v=bebidas" class="block p-3 rounded-lg border-t-4 text-center transition <?= $vista=='bebidas'?'bg-cyan-900/40 border-cyan-400 ring-1 ring-cyan-400':'bg-gray-800 border-gray-600' ?>">
                <div class="text-xs font-bold uppercase text-gray-300">Bebidas</div>
                <div class="text-lg font-bold">$<?= number_format($total_bebidas) ?></div>
            </a>
            
            <div class="bg-gray-800 rounded-lg border border-purple-500 shadow-lg overflow-hidden flex flex-col">
                <div class="bg-purple-900/30 p-2 border-b border-purple-500/50 text-center text-xs font-bold uppercase text-purple-300">💰 Saldos a Favor</div>
                <div class="p-2 overflow-y-auto max-h-16 text-xs text-gray-300">
                    <?php if($res_saldos->num_rows > 0): while($s = $res_saldos->fetch_assoc()): ?>
                        <div class="flex justify-between border-b border-gray-700 last:border-0 py-1">
                            <span><?= $s['nombre'] ?></span>
                            <span class="font-bold text-green-400">$<?= $s['saldo_monedero'] ?></span>
                        </div>
                    <?php endwhile; else: ?>
                        <div class="text-center text-gray-500 italic mt-2">Nadie con saldo</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if($vista == 'bebidas'): ?>
        <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg flex justify-around items-center">
                <div class="text-center">
                    <p class="text-xs text-gray-400 uppercase mb-1">Agua</p>
                    <div class="text-4xl font-bold <?= $stock_agua < 10 ? 'text-red-500 animate-pulse' : 'text-cyan-400' ?>"><?= $stock_agua ?></div>
                </div>
                <div class="w-px h-16 bg-gray-700"></div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 uppercase mb-1">Electro</p>
                    <div class="text-4xl font-bold <?= $stock_electro < 10 ? 'text-red-500 animate-pulse' : 'text-purple-400' ?>"><?= $stock_electro ?></div>
                </div>
            </div>
            <div class="bg-gray-800 p-4 rounded-xl border border-gray-700 shadow-lg">
                <form method="POST" action="" class="flex items-end gap-2">
                    <input type="hidden" name="accion" value="reabastecer">
                    <div class="flex-1">
                        <label class="text-[10px] uppercase text-gray-500 font-bold">Producto</label>
                        <select name="producto_stock" class="w-full bg-gray-900 text-white text-sm p-2 rounded border border-gray-600 outline-none">
                            <option value="Agua">Agua</option>
                            <option value="Electrolífe">Electrolífe</option>
                        </select>
                    </div>
                    <div class="w-24">
                        <label class="text-[10px] uppercase text-gray-500 font-bold">Cant</label>
                        <input type="number" name="cantidad_stock" placeholder="+" class="w-full bg-gray-900 text-white text-sm p-2 rounded border border-gray-600 outline-none">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded font-bold text-sm h-[38px]"><i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-xl mb-8 sticky top-20 z-40">
            <h2 class="text-lg font-bold text-gray-300 mb-4 flex items-center gap-2">
                <i class="fas fa-wallet"></i> 
                <?php 
                    if($vista == 'adultos') echo "Cobro ADULTO";
                    elseif($vista == 'teens') echo "Cobro TEEN";
                    else echo "Venta BEBIDAS";
                ?>
            </h2>
            
            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <input type="hidden" name="accion" value="cobrar">
                <div class="md:col-span-3">
                    <label class="block text-gray-500 text-xs font-bold mb-1">CLIENTE</label>
                    <select name="usuario_id" id="select-usuario" required class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-green-500">
                        <option value="">Seleccionar...</option>
                        <?php if ($res_lista->num_rows > 0): $res_lista->data_seek(0); while($row = $res_lista->fetch_assoc()): ?>
                            <?php $info_saldo = ($row['saldo_monedero'] > 0) ? " (A favor: $".$row['saldo_monedero'].")" : ""; ?>
                            <option value='<?= $row['id'] ?>'><?= $row['nombre'] ?> <?= $row['apellido'] ?><?= $info_saldo ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-500 text-xs font-bold mb-1">PRODUCTO / PLAN</label>
                    <select name="concepto" id="select-plan" required onchange="actualizarTotal()" class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-green-500">
                        <option value="" data-precio="0">-- Elegir --</option>
                        <?php foreach($planes_disponibles as $plan): ?>
                            <option value="<?= $plan['nombre'] ?>" data-precio="<?= $plan['precio'] ?>"><?= $plan['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-gray-500 text-xs font-bold mb-1">CANT</label>
                    <input type="number" name="cantidad" id="input-cantidad" value="1" min="1" onchange="actualizarTotal()" onkeyup="actualizarTotal()" class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-center text-sm outline-none focus:border-green-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-500 text-xs font-bold mb-1">TOTAL ($)</label>
                    <input type="number" name="monto_total_visual" id="input-total" readonly class="w-full p-2 bg-gray-700 border border-gray-600 rounded text-green-400 font-bold text-center outline-none transition">
                    <input type="hidden" name="precio_unitario" id="input-precio-unitario">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-500 text-xs font-bold mb-1">MÉTODO PAGO</label>
                    <select name="forma_pago" required class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-green-500">
                        <option value="Efectivo">💵 Efectivo</option>
                        <option value="Transferencia">📱 Transferencia</option>
                        <option value="Tarjeta">💳 Tarjeta</option>
                        <option value="Monedero" class="text-purple-400 font-bold">💰 Monedero (Saldo a Favor)</option>
                    </select>
                </div>
                <div class="md:col-span-12 mt-2">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded transition shadow-lg flex justify-center items-center gap-2">
                        <i class="fas fa-check-circle"></i> CONFIRMAR COBRO
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-gray-800 rounded-xl overflow-hidden shadow-lg border border-gray-700 mb-8">
            <div class="bg-gray-900 p-3 border-b border-gray-700"><h3 class="font-bold text-gray-300 text-sm uppercase">Movimientos de Hoy (<?= ucfirst($vista) ?>)</h3></div>
            <div class="overflow-x-auto max-h-[300px]">
                <table class="w-full text-left text-sm">
                    <thead class="bg-black text-gray-500 text-xs uppercase sticky top-0">
                        <tr>
                            <th class="p-3">Hora</th>
                            <th class="p-3">Cliente</th>
                            <th class="p-3">Concepto</th>
                            <th class="p-3 text-center">Método</th>
                            <th class="p-3 text-right">Total</th>
                            <th class="p-3 text-center">Borrar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if ($res_historial->num_rows > 0): while($h = $res_historial->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-700">
                                <td class="p-3 text-gray-500"><?= date('H:i', strtotime($h['fecha'])) ?></td>
                                <td class="p-3 font-bold"><?= $h['nombre'] ?> <?= $h['apellido'] ?></td>
                                <td class="p-3 text-gray-300"><?= $h['concepto'] ?></td>
                                <td class="p-3 text-center text-xs">
                                    <span class="px-2 py-1 rounded <?= ($h['forma_pago']=='Monedero') ? 'bg-purple-900/50 text-purple-300 border border-purple-700' : 'bg-gray-700 text-gray-300' ?>">
                                        <?= $h['forma_pago'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-right font-mono text-green-400 font-bold">$<?= number_format($h['monto']) ?></td>
                                <td class="p-3 text-center">
                                    <a href="pagos.php?eliminar=<?= $h['id'] ?>&v=<?= $vista ?>" onclick="return confirm('¿Borrar este movimiento?')" class="text-gray-600 hover:text-red-500"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="6" class="p-8 text-center text-gray-500 italic">Sin movimientos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function actualizarTotal() {
            const select = document.getElementById('select-plan');
            const totalInput = document.getElementById('input-total');
            const cantInput = document.getElementById('input-cantidad');
            const unitInput = document.getElementById('input-precio-unitario');
            
            const precio = select.options[select.selectedIndex].getAttribute('data-precio');
            
            if (precio === 'manual') {
                totalInput.readOnly = false;
                totalInput.value = '';
                totalInput.placeholder = "Escribe monto $$";
                totalInput.classList.remove('bg-gray-700', 'cursor-not-allowed');
                totalInput.classList.add('bg-gray-900', 'border', 'border-green-500');
                totalInput.focus();
                cantInput.value = 1;
                cantInput.readOnly = true;
            } else {
                totalInput.readOnly = true;
                totalInput.placeholder = "";
                totalInput.classList.add('bg-gray-700', 'cursor-not-allowed');
                totalInput.classList.remove('bg-gray-900', 'border', 'border-green-500');
                cantInput.readOnly = false;
                
                const cant = cantInput.value || 0;
                totalInput.value = precio * cant;
                unitInput.value = precio;
            }
        }
    </script>
</body>
</html>