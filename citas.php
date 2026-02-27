<?php
include 'auth.php'; // <--- ESTA LÍNEA ES EL CANDADO
include 'db.php';

$mensaje = "";

// --- 1. PROCESAR NUEVA CITA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_cita'])) {
    $usuario_id = $_POST['usuario_id'];
    $servicio = $_POST['servicio'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $notas = $_POST['notas'];

    // Verificar si ya existe cita a esa misma hora para ese servicio (Evitar empalmes)
    $check = $conn->query("SELECT id FROM reservaciones WHERE fecha = '$fecha' AND hora = '$hora' AND servicio = '$servicio'");
    
    if ($check->num_rows > 0) {
        $mensaje = "<div class='bg-red-500 text-white p-4 rounded mb-6 font-bold'>⚠️ Ya hay una cita de $servicio a esa hora. Por favor elige otra.</div>";
    } else {
        $sql = "INSERT INTO reservaciones (usuario_id, servicio, fecha, hora, notas) VALUES ('$usuario_id', '$servicio', '$fecha', '$hora', '$notas')";
        if ($conn->query($sql) === TRUE) {
            $mensaje = "<div class='bg-green-500 text-white p-4 rounded mb-6 font-bold'>✅ Cita agendada exitosamente.</div>";
        } else {
            $mensaje = "<div class='bg-red-500 text-white p-4 rounded mb-6'>Error: " . $conn->error . "</div>";
        }
    }
}

// --- 2. ELIMINAR CITA (CANCELAR) ---
if (isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    $conn->query("DELETE FROM reservaciones WHERE id=$id_borrar");
    header("Location: citas.php"); // Recargar para limpiar url
    exit();
}

// --- 3. OBTENER DATOS ---
// Lista de atletas para el menú desplegable
$atletas = $conn->query("SELECT id, nombre, apellido FROM usuarios ORDER BY nombre ASC");

// Lista de próximas citas (unimos tablas para ver el nombre del atleta, no solo su ID)
$sql_citas = "SELECT r.*, u.nombre, u.apellido, u.telefono 
              FROM reservaciones r 
              JOIN usuarios u ON r.usuario_id = u.id 
              ORDER BY r.fecha ASC, r.hora ASC";
$lista_citas = $conn->query($sql_citas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas y Reservas - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-sans">

    <nav class="bg-black border-b border-gray-800 p-4 flex justify-between items-center sticky top-0 z-50">
        <div class="text-xl font-bold text-orange-500">LEGIÓN <span class="text-white">CITAS</span></div>
        <a href="index.php" class="text-gray-400 hover:text-white"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
    </nav>

    <div class="p-8 max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 shadow-lg sticky top-24">
                <h2 class="text-2xl font-bold mb-4 text-orange-500">Nueva Reserva</h2>
                <?= $mensaje ?>

                <form method="POST" action="">
                    <input type="hidden" name="crear_cita" value="1">
                    
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Legionario</label>
                        <select name="usuario_id" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
                            <option value="">Selecciona un atleta...</option>
                            <?php while($atleta = $atletas->fetch_assoc()): ?>
                                <option value="<?= $atleta['id'] ?>">
                                    <?= $atleta['nombre'] ?> <?= $atleta['apellido'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Servicio</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="servicio" value="Nutrición" class="peer sr-only" required>
                                <div class="p-3 bg-gray-900 border border-gray-600 rounded text-center peer-checked:bg-green-600 peer-checked:text-white transition text-sm">
                                    🍏 Nutrición
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="servicio" value="Body-Care" class="peer sr-only" required>
                                <div class="p-3 bg-gray-900 border border-gray-600 rounded text-center peer-checked:bg-blue-600 peer-checked:text-white transition text-sm">
                                    💆 Body-Care
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Fecha y Hora</label>
                        <input type="date" name="fecha" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white mb-2">
                        <input type="time" name="hora" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm font-bold mb-2">Notas (Opcional)</label>
                        <textarea name="notas" placeholder="Ej: Revisión mensual..." class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white h-20"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-black font-bold py-3 rounded transition">
                        AGENDAR CITA
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <h2 class="text-2xl font-bold mb-6">Próximas Citas</h2>

            <div class="bg-gray-800 rounded-xl overflow-hidden shadow-lg border border-gray-700">
                <table class="w-full text-left">
                    <thead class="bg-black text-gray-400 uppercase text-xs">
                        <tr>
                            <th class="p-4">Fecha / Hora</th>
                            <th class="p-4">Legionario</th>
                            <th class="p-4">Servicio</th>
                            <th class="p-4">Notas</th>
                            <th class="p-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if ($lista_citas->num_rows > 0): ?>
                            <?php while($cita = $lista_citas->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-700 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-white"><?= date('d/M', strtotime($cita['fecha'])) ?></div>
                                        <div class="text-sm text-gray-400"><?= date('h:i A', strtotime($cita['hora'])) ?></div>
                                    </td>
                                    <td class="p-4 font-bold"><?= $cita['nombre'] ?> <?= $cita['apellido'] ?></td>
                                    <td class="p-4">
                                        <?php if($cita['servicio'] == 'Nutrición'): ?>
                                            <span class="text-green-400 font-bold text-sm">🍏 Nutrición</span>
                                        <?php else: ?>
                                            <span class="text-blue-400 font-bold text-sm">💆 Body-Care</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-gray-400 text-sm italic"><?= $cita['notas'] ?></td>
                                    <td class="p-4 text-right">
                                        <a href="citas.php?borrar=<?= $cita['id'] ?>" onclick="return confirm('¿Cancelar esta cita?')" class="text-red-500 hover:text-white" title="Cancelar Cita">
                                            <i class="fas fa-times-circle text-xl"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-10 text-center text-gray-500">No hay citas programadas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>