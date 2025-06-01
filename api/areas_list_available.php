<?php
session_start();
// Solo verificamos que esté logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');
$areas_file = __DIR__ . '/../data/areas.json';
$users_file = __DIR__ . '/../data/users.json';

if (!file_exists($areas_file)) {
    echo json_encode(['success' => true, 'areas' => []]);
    exit;
}

$areas_content = file_get_contents($areas_file);
if ($areas_content === false) {
    error_log("Error al leer areas.json");
    echo json_encode(['success' => false, 'error' => 'Error interno al leer las áreas', 'areas' => []]);
    exit;
}

$areas = json_decode($areas_content, true);
if ($areas === null && json_last_error() !== JSON_ERROR_NONE) {
    error_log("Error al decodificar areas.json: " . json_last_error_msg());
    echo json_encode(['success' => false, 'error' => 'Error interno al leer las áreas (JSON inválido)', 'areas' => []]);
    exit;
}

$areas = $areas ?: []; // Asegurar que sea un array
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Si es owner, devolvemos todas las áreas
if ($user_role === 'owner') {
    echo json_encode(['success' => true, 'areas' => $areas]);
    exit;
}

// Para admin o editor, filtramos las áreas disponibles según sus permisos
if (!file_exists($users_file)) {
    echo json_encode(['success' => true, 'areas' => []]);
    exit;
}

$users_content = file_get_contents($users_file);
$users = json_decode($users_content, true);
if ($users === null) {
    echo json_encode(['success' => true, 'areas' => []]);
    exit;
}

$current_user = null;
foreach ($users as $user) {
    if ($user['id'] === $user_id) {
        $current_user = $user;
        break;
    }
}

if (!$current_user) {
    echo json_encode(['success' => true, 'areas' => []]);
    exit;
}

$available_area_ids = [];
if ($user_role === 'admin' && isset($current_user['areas_admin'])) {
    $available_area_ids = $current_user['areas_admin'];
} elseif ($user_role === 'editor' && isset($current_user['areas_editor'])) {
    $available_area_ids = $current_user['areas_editor'];
}

// Filtrar áreas por las disponibles
$filtered_areas = array_filter($areas, function($area) use ($available_area_ids) {
    return in_array($area['id'], $available_area_ids);
});

echo json_encode(['success' => true, 'areas' => array_values($filtered_areas)]);