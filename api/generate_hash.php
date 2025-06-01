<?php
$passwordToHash = 'Forja@2025'; // O la contraseña que quieras
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);
echo "La contraseña es: " . $passwordToHash . "<br>";
echo "El hash es: " . $hashedPassword;
?>