<?php
include 'auth.php'; // <--- ESTA LÍNEA ES EL CANDADO
include 'db.php';

$mensaje = "";
$usuario = null;

// 1. VERIFICAR A QUIÉN VAMOS A EDITAR
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM usuarios WHERE id = $id";
    $resultado = $conn->query($sql);
    
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
    } else {
        die("Usuario no encontrado.");
    }
}

// 2. PROCESAR EL FORMULARIO CUANDO SE GUARDAN LOS CAMBIOS
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $nacimiento = $_POST['fecha_nacimiento'];
    $lesiones = $_POST['lesiones'];
    $operacion = $_POST['operaciones']; // Ojo: en la BD se llama 'operaciones' (plural)
    $enfermedad = $_POST['enfermedad'];
    $horario = $_POST['horario'];
    $playera = $_POST['talla_playera']; // En el form se llamará 'talla_playera' para coincidir
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $tipo = $_POST['tipo_usuario'];
    $estado_pago = $_POST['estado_pago']; // Nuevo: Poder cambiar si ya pagó o no

    $sql_update = "UPDATE usuarios SET 
                   nombre='$nombre', 
                   apellido='$apellido', 
                   fecha_nacimiento='$nacimiento',
                   lesiones='$lesiones',
                   operaciones='$operacion',
                   enfermedad='$enfermedad',
                   horario='$horario',
                   talla_playera='$playera',
                   email='$email',
                   telefono='$telefono',
                   tipo_usuario='$tipo',
                   estado_pago='$estado_pago'
                   WHERE id=$id";

    if ($conn->query($sql_update) === TRUE) {
        $mensaje = "<div class='bg-green-500 text-white p-4 rounded mb-4'>✅ Datos actualizados correctamente. <a href='legionarios.php' class='underline font-bold'>Volver a la lista</a></div>";
        // Recargar los datos para ver los cambios reflejados
        $usuario = $conn->query("SELECT * FROM usuarios WHERE id=$id")->fetch_assoc();
    } else {
        $mensaje = "<div class='bg-red-500 text-white p-4 rounded mb-4'>❌ Error al actualizar: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Legionario - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white font-sans">

    <div class="max-w-3xl mx-auto mt-10 p-8 bg-gray-800 rounded-xl shadow-lg border border-gray-700">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-orange-500">Editar Legionario</h2>
            <a href="legionarios.php" class="text-gray-400 hover:text-white">Cancelar y Volver</a>
        </div>
        
        <?= $mensaje ?>

        <?php if ($usuario): ?>
        <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
            
            <div>
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Nombre</label>
                <input type="text" name="nombre" value="<?= $usuario['nombre'] ?>" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
            </div>
            <div>
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Apellido</label>
                <input type="text" name="apellido" value="<?= $usuario['apellido'] ?>" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
            </div>

            <div>
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" value="<?= $usuario['fecha_nacimiento'] ?>" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
            </div>
            <div>
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Talla de Playera</label>
                <select name="talla_playera" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
                    <option value="<?= $usuario['talla_playera'] ?>" selected><?= $usuario['talla_playera'] ?> (Actual)</option>
                    <option value="XS">XS</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Teléfono</label>
                <input type="text" name="telefono" value="<?= $usuario['telefono'] ?>" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
            </div>
            <div>
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Email</label>
                <input type="email" name="email" value="<?= $usuario['email'] ?>" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
            </div>

            <div class="col-span-2 border-t border-gray-700 pt-4 mt-2">
                <h3 class="text-white font-bold mb-4">Datos Médicos</h3>
            </div>
            <div class="col-span-2">
                <label class="block text-gray-400 mb-2 text-sm">Lesiones</label>
                <input type="text" name="lesiones" value="<?= $usuario['lesiones'] ?>" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
            </div>
            <div class="col-span-2">
                <label class="block text-gray-400 mb-2 text-sm">Operaciones</label>
                <input type="text" name="operaciones" value="<?= $usuario['operaciones'] ?>" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
            </div>
            <div class="col-span-2">
                <label class="block text-gray-400 mb-2 text-sm">Enfermedades</label>
                <input type="text" name="enfermedad" value="<?= $usuario['enfermedad'] ?>" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
            </div>

            <div class="col-span-2 border-t border-gray-700 pt-4 mt-2">
                <h3 class="text-orange-500 font-bold mb-4">Administración</h3>
            </div>
            
            <div>
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Horario</label>
                <select name="horario" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
                    <option value="<?= $usuario['horario'] ?>" selected><?= $usuario['horario'] ?> (Actual)</option>
                    <optgroup label="Adultos">
                        <option value="6:00 AM">6:00 AM</option>
                        <option value="7:15 AM">7:15 AM</option>
                        <option value="8:30 AM">8:30 AM</option>
                        <option value="7:05 PM">7:05 PM</option>
                    </optgroup>
                    <optgroup label="Legión Teens">
                        <option value="5:00 PM">5:00 PM</option>
                        <option value="6:00 PM">6:00 PM</option>
                    </optgroup>
                </select>
            </div>

            <div>
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Estatus de Pago</label>
                <select name="estado_pago" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
                    <option value="pagado" <?= $usuario['estado_pago'] == 'pagado' ? 'selected' : '' ?>>✅ Pagado (Al día)</option>
                    <option value="pendiente" <?= $usuario['estado_pago'] == 'pendiente' ? 'selected' : '' ?>>❌ Pendiente (Debe)</option>
                </select>
            </div>
            
             <div class="col-span-2">
                <label class="block text-gray-400 mb-2 text-sm uppercase font-bold">Tipo de Membresía</label>
                <select name="tipo_usuario" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-orange-500 outline-none">
                    <option value="estandar" <?= $usuario['tipo_usuario'] == 'estandar' ? 'selected' : '' ?>>🏋️ Legionario (Adulto)</option>
                    <option value="teen" <?= $usuario['tipo_usuario'] == 'teen' ? 'selected' : '' ?>>🔥 Legionario Teens</option>
                    <option value="staff" <?= $usuario['tipo_usuario'] == 'staff' ? 'selected' : '' ?>>🛡️ Staff</option>
                </select>
            </div>

            <div class="col-span-2 mt-4">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 rounded-lg text-lg transition shadow-lg">
                    GUARDAR CAMBIOS
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

</body>
</html>