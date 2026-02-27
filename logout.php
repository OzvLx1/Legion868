<?php
session_start();
session_destroy(); // Destruye la sesión (cierra el candado)
header("Location: login.php"); // Te manda de vuelta a la entrada
exit();
?>