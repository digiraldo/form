<?php
// index.php - Redirección a la página principal
session_start();

// Verificar si el usuario ya está logueado
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Si ya está logueado, redirigir al dashboard
    header('Location: admin_dashboard.php');
    exit();
} else {
    // Si no está logueado, redirigir al login
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formularios - Redirigiendo...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <h4 class="mt-3 text-secondary">
            <i class="fas fa-journal-whills me-2"></i>
            Redirigiendo a Formularios...
        </h4>
        <p class="text-muted">Si no eres redirigido automáticamente, 
            <a href="login.php" class="text-decoration-none">haz clic aquí</a>
        </p>
    </div>

    <script>
        // Fallback de redirección por JavaScript en caso de que los headers no funcionen
        setTimeout(function() {
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                window.location.href = 'admin_dashboard.php';
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }, 2000); // Redirigir después de 2 segundos
    </script>
</body>
</html>