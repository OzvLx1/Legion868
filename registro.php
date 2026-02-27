<?php
include 'auth.php'; // Seguridad
include 'db.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    
    // --- NUEVOS DATOS MÉDICOS Y TALLA ---
    $enfermedades = $_POST['enfermedades'];
    $lesiones = $_POST['lesiones'];
    $operaciones = $_POST['operaciones'];
    $talla_playera = $_POST['talla_playera'];
    
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $horario = $_POST['horario'];
    $tipo_usuario = $_POST['tipo_usuario'];

    // Insertar en la base de datos con las nuevas columnas
    $sql = "INSERT INTO usuarios (nombre, apellido, fecha_nacimiento, enfermedades, lesiones, operaciones, talla_playera, email, telefono, horario, tipo_usuario) 
            VALUES ('$nombre', '$apellido', '$fecha_nacimiento', '$enfermedades', '$lesiones', '$operaciones', '$talla_playera', '$email', '$telefono', '$horario', '$tipo_usuario')";

    if ($conn->query($sql) === TRUE) {
        
        // --- CORREO DE NOTIFICACIÓN ---
        $para = "admin@legion868.com"; 
        $asunto = "🔔 Nuevo Legionario: $nombre $apellido";
        
        $cuerpo = "Hola Admin,\n\n";
        $cuerpo .= "Se acaba de registrar un nuevo miembro en el sistema:\n\n";
        $cuerpo .= "👤 Nombre: $nombre $apellido\n";
        $cuerpo .= "🎂 Cumpleaños: $fecha_nacimiento\n";
        $cuerpo .= "👕 Talla de Playera: $talla_playera\n\n";
        $cuerpo .= "--- EXPEDIENTE MÉDICO ---\n";
        $cuerpo .= "🩺 Enfermedades: " . ($enfermedades ?: 'Ninguna') . "\n";
        $cuerpo .= "🩹 Lesiones: " . ($lesiones ?: 'Ninguna') . "\n";
        $cuerpo .= "🏥 Operaciones: " . ($operaciones ?: 'Ninguna') . "\n\n";
        $cuerpo .= "--- DETALLES ---\n";
        $cuerpo .= "🏷️ Categoría: " . ucfirst($tipo_usuario) . "\n";
        $cuerpo .= "⏰ Horario: $horario\n";
        $cuerpo .= "📞 Teléfono: $telefono\n\n";
        $cuerpo .= "Saludos,\nSistema Legión 868";

        $cabeceras = "From: no-reply@legion868.com" . "\r\n" .
                     "Reply-To: no-reply@legion868.com" . "\r\n" .
                     "X-Mailer: PHP/" . phpversion();

        @mail($para, $asunto, $cuerpo, $cabeceras);
        // --- FIN DEL CORREO ---

        $mensaje = "<div class='bg-green-500 text-white p-3 rounded mb-4 font-bold text-center shadow-lg'>¡Expediente de Legionario registrado con éxito! 🏋️</div>";
    } else {
        $mensaje = "<div class='bg-red-500 text-white p-3 rounded mb-4'>Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Ingreso - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white font-sans">

    <nav class="bg-black border-b border-gray-800 p-4 flex justify-between items-center">
        <div class="text-xl font-bold text-lime-500 flex items-center gap-2">
            <i class="fas fa-user-plus"></i> NUEVO <span class="text-white">INGRESO</span>
        </div>
        <a href="index.php" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm transition">Volver</a>
    </nav>

    <div class="flex items-center justify-center min-h-screen p-6 my-8">
        <div class="bg-gray-800 p-8 rounded-xl shadow-2xl border border-gray-700 w-full max-w-3xl">
            
            <h2 class="text-2xl font-bold mb-6 text-center text-lime-500">Ficha de Inscripción & Historial Clínico</h2>
            
            <?= $mensaje ?>

            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="md:col-span-2 border-b border-gray-700 pb-2 mb-2">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider"><i class="fas fa-id-card mr-2"></i>Datos Personales</h3>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">NOMBRE</label>
                    <input type="text" name="nombre" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">APELLIDO</label>
                    <input type="text" name="apellido" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">FECHA DE NACIMIENTO</label>
                    <input type="date" name="fecha_nacimiento" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">TALLA DE PLAYERA</label>
                    <select name="talla_playera" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-purple-500">
                        <option value="" disabled selected>-- Seleccionar Talla --</option>
                        <option value="CHICA">CHICA</option>
                        <option value="MEDIANA">MEDIANA</option>
                        <option value="GRANDE">GRANDE</option>
                        <option value="EXTRA GRANDE">EXTRA GRANDE</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">TELÉFONO / WHATSAPP</label>
                    <input type="text" name="telefono" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-purple-500">
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">CORREO ELECTRÓNICO</label>
                    <input type="email" name="email" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-purple-500">
                </div>

                <div class="md:col-span-2 border-b border-gray-700 pb-2 mb-2 mt-4">
                    <h3 class="text-sm font-bold text-red-400 uppercase tracking-wider"><i class="fas fa-heartbeat mr-2"></i>Historial Médico</h3>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-gray-400 text-xs font-bold mb-1">ENFERMEDADES CRÓNICAS / ALERGIAS <span class="text-gray-600 font-normal">(Dejar en blanco si no hay)</span></label>
                    <input type="text" name="enfermedades" placeholder="Ej. Asma, Diabetes, Alergia al polvo..." class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-red-500">
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">LESIONES <span class="text-gray-600 font-normal">(Opcional)</span></label>
                    <input type="text" name="lesiones" placeholder="Ej. Esguince tobillo derecho" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-red-500">
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">OPERACIONES RECIENTES <span class="text-gray-600 font-normal">(Opcional)</span></label>
                    <input type="text" name="operaciones" placeholder="Ej. Rodilla hace 2 años" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-red-500">
                </div>

                <div class="md:col-span-2 border-b border-gray-700 pb-2 mb-2 mt-4">
                    <h3 class="text-sm font-bold text-blue-400 uppercase tracking-wider"><i class="fas fa-dumbbell mr-2"></i>Datos de Entrenamiento</h3>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">HORARIO PREFERIDO</label>
                    <select name="horario" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-blue-500">
                        <option value="6:00 a.m.">6:00 a.m.</option>
                        <option value="7:15 a.m.">7:15 a.m.</option>
                        <option value="8:30 a.m.">8:30 a.m.</option>
                        <option value="5:00 p.m.">5:00 p.m.</option>
                        <option value="6:00 p.m.">6:00 p.m.</option>
                        <option value="7:05 p.m.">7:05 p.m.</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs font-bold mb-1">CATEGORÍA</label>
                    <select name="tipo_usuario" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-blue-500">
                        <option value="estandar">Adulto / Estandar</option>
                        <option value="teen">Teen (Adolescente)</option>
                    </select>
                </div>

                <div class="md:col-span-2 mt-6">
                    <button type="submit" class="w-full bg-lime-600 hover:bg-purple-500 text-white font-bold py-4 rounded transition shadow-lg text-lg">
                        <i class="fas fa-check-circle mr-2"></i> REGISTRAR EXPEDIENTE
                    </button>
                </div>

            </form>
        </div>
    </div>
</body>
</html>