<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Buscamos al usuario
    $sql = "SELECT id, nombre, password, tipo_usuario FROM usuarios WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verificamos si la contraseña coincide
        if (password_verify($password, $row['password'])) {
            // ¡CONTRASEÑA CORRECTA!
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['rol'] = $row['tipo_usuario'];
            
            header("Location: index.php"); // Al Dashboard
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso - Legión 868</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center h-screen">
    <div class="bg-gray-800 p-8 rounded-xl shadow-2xl border border-gray-700 w-96">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">LEGIÓN <span class="text-orange-500">868</span></h1>
            <p class="text-gray-400 text-sm mt-2">Sistema Administrativo</p>
        </div>
        <?php if($error): ?>
            <div class="bg-red-500/20 text-red-200 p-3 rounded mb-4 text-center text-sm border border-red-500">
                <?= $error ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Correo" required class="w-full mb-4 p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-orange-500">
            <input type="password" name="password" placeholder="Contraseña" required class="w-full mb-6 p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-orange-500">
            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-500 text-white font-bold py-3 rounded transition">ENTRAR</button>
        </form>
    </div>
</body>
</html>