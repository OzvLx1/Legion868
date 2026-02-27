<?php
include 'auth.php';
include 'db.php';

$mensaje = "";

// --- 1. ELIMINAR USUARIO ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    // Borramos al usuario (idealmente también sus pagos/asistencias, pero por ahora solo el usuario)
    if ($conn->query("DELETE FROM usuarios WHERE id = $id") === TRUE) {
        $mensaje = "<div class='bg-red-500 text-white p-3 rounded mb-4 text-center font-bold'>🗑️ Legionario dado de baja.</div>";
    }
}

// --- 2. GUARDAR EDICIÓN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'editar') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $telefono = $_POST['telefono'];
    $horario = $_POST['horario'];
    $tipo_usuario = $_POST['tipo_usuario'];

    $sql_update = "UPDATE usuarios SET 
        nombre = '$nombre', 
        apellido = '$apellido', 
        fecha_nacimiento = '$fecha_nacimiento', 
        telefono = '$telefono', 
        horario = '$horario', 
        tipo_usuario = '$tipo_usuario' 
        WHERE id = $id";

    if ($conn->query($sql_update) === TRUE) {
        $mensaje = "<div class='bg-green-500 text-white p-3 rounded mb-4 text-center font-bold'>✅ Perfil actualizado correctamente.</div>";
    } else {
        $mensaje = "<div class='bg-red-500 text-white p-3 rounded mb-4 text-center'>Error al actualizar.</div>";
    }
}

// --- 3. MODO EDICIÓN (Cargar datos al formulario) ---
$edit_user = null;
if (isset($_GET['editar'])) {
    $id_editar = $_GET['editar'];
    $res_edit = $conn->query("SELECT * FROM usuarios WHERE id = $id_editar");
    if ($res_edit->num_rows > 0) {
        $edit_user = $res_edit->fetch_assoc();
    }
}

// --- 4. LISTA COMPLETA ---
$sql_lista = "SELECT * FROM usuarios ORDER BY nombre ASC";
$res_lista = $conn->query($sql_lista);

// Opciones de horario exactas
$horarios = ["6:00 a.m.", "7:15 a.m.", "8:30 a.m.", "5:00 p.m.", "6:00 p.m.", "7:05 p.m."];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Directorio - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-sans">

    <nav class="bg-black border-b border-gray-800 p-4 flex justify-between items-center sticky top-0 z-50">
        <div class="text-xl font-bold text-lime-500 flex items-center gap-2">
            <i class="fas fa-users"></i> DIRECTORIO <span class="text-white">LEGIÓN</span>
        </div>
        <div class="flex gap-3">
            <a href="registro.php" class="bg-lime-600 hover:bg-lime-500 text-white px-4 py-2 rounded text-sm transition font-bold"><i class="fas fa-plus mr-1"></i> Nuevo</a>
            <a href="index.php" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm transition">Volver al Inicio</a>
        </div>
    </nav>

    <div class="p-6 max-w-7xl mx-auto">
        
        <?= $mensaje ?>

        <?php if ($edit_user): ?>
            <div class="bg-blue-900/30 border border-blue-500 p-6 rounded-xl shadow-2xl mb-8 animate-pulse">
                <h2 class="text-xl font-bold text-blue-400 mb-4"><i class="fas fa-user-edit mr-2"></i>Editando Perfil: <?= $edit_user['nombre'] ?></h2>
                <form method="POST" action="legionarios.php" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">

                    <div>
                        <label class="block text-gray-400 text-xs font-bold mb-1">Nombre</label>
                        <input type="text" name="nombre" value="<?= $edit_user['nombre'] ?>" class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold mb-1">Apellido</label>
                        <input type="text" name="apellido" value="<?= $edit_user['apellido'] ?>" class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold mb-1">Fecha Nacimiento (Cumpleaños)</label>
                        <input type="date" name="fecha_nacimiento" value="<?= $edit_user['fecha_nacimiento'] ?>" class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold mb-1">Teléfono</label>
                        <input type="text" name="telefono" value="<?= $edit_user['telefono'] ?>" class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold mb-1">Horario (Oficial)</label>
                        <select name="horario" class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-blue-500">
                            <?php foreach($horarios as $h): ?>
                                <option value="<?= $h ?>" <?= ($edit_user['horario'] == $h) ? 'selected' : '' ?>><?= $h ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs font-bold mb-1">Categoría</label>
                        <select name="tipo_usuario" class="w-full p-2 bg-gray-900 border border-gray-600 rounded text-white text-sm outline-none focus:border-blue-500">
                            <option value="estandar" <?= ($edit_user['tipo_usuario'] == 'estandar') ? 'selected' : '' ?>>Adulto</option>
                            <option value="teen" <?= ($edit_user['tipo_usuario'] == 'teen') ? 'selected' : '' ?>>Teen</option>
                            <option value="staff" <?= ($edit_user['tipo_usuario'] == 'staff') ? 'selected' : '' ?>>Staff</option>
                        </select>
                    </div>

                    <div class="md:col-span-3 flex justify-end gap-3 mt-2">
                        <a href="legionarios.php" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded transition">Cancelar</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded transition shadow-lg"><i class="fas fa-save mr-2"></i> Guardar Cambios</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="bg-gray-800 rounded-xl overflow-hidden shadow-lg border border-gray-700">
            <div class="bg-gray-900 p-4 border-b border-gray-700 flex justify-between items-center">
                <h3 class="font-bold text-gray-300 uppercase">Lista de Legionarios</h3>
                <span class="text-xs bg-gray-700 px-2 py-1 rounded text-gray-400"><?= $res_lista->num_rows ?> registrados</span>
            </div>
            
            <div class="overflow-x-auto max-h-[600px]">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-black text-gray-400 text-xs uppercase sticky top-0 z-10 shadow">
                        <tr>
                            <th class="p-4 border-b border-gray-700">Nombre Completo</th>
                            <th class="p-4 border-b border-gray-700">Categoría</th>
                            <th class="p-4 border-b border-gray-700 text-center">Horario</th>
                            <th class="p-4 border-b border-gray-700 text-center">Cumpleaños</th>
                            <th class="p-4 border-b border-gray-700 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if ($res_lista->num_rows > 0): while($u = $res_lista->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-700 transition group">
                                <td class="p-4">
                                    <div class="font-bold text-gray-200"><?= $u['nombre'] ?> <?= $u['apellido'] ?></div>
                                    <div class="text-[10px] text-gray-500 flex items-center gap-2 mt-1">
                                        <i class="fas fa-phone"></i> <?= $u['telefono'] ?: 'Sin registro' ?>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border 
                                        <?= $u['tipo_usuario']=='teen' ? 'bg-orange-900/30 text-orange-400 border-orange-800' : 'bg-blue-900/30 text-blue-400 border-blue-800' ?>">
                                        <?= $u['tipo_usuario'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center text-gray-300 font-bold"><?= $u['horario'] ?></td>
                                <td class="p-4 text-center">
                                    <?php if ($u['fecha_nacimiento']): ?>
                                        <div class="text-pink-400 font-bold"><i class="fas fa-birthday-cake text-[10px] mr-1"></i><?= date('d M', strtotime($u['fecha_nacimiento'])) ?></div>
                                    <?php else: ?>
                                        <span class="text-gray-600 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex justify-center gap-3">
                                        <a href="legionarios.php?editar=<?= $u['id'] ?>" class="bg-gray-700 hover:bg-blue-600 text-gray-300 hover:text-white w-8 h-8 rounded flex items-center justify-center transition" title="Editar">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <a href="legionarios.php?eliminar=<?= $u['id'] ?>" onclick="return confirm('¿Seguro que deseas ELIMINAR a este legionario?')" class="bg-gray-700 hover:bg-red-600 text-gray-300 hover:text-white w-8 h-8 rounded flex items-center justify-center transition" title="Dar de baja">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" class="p-8 text-center text-gray-500">No hay usuarios registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>