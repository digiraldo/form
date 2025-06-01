<?php
// api/areas.php
session_start();

// Verificar que el usuario esté logueado como admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']); 
    exit;
}

// Obtener la acción solicitada
header('Content-Type: application/json');
$areas_file = __DIR__ . '/../data/areas.json';
$users_file = __DIR__ . '/../data/users.json';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

function get_users_by_ids($ids) {
    global $users_file;
    if (!file_exists($users_file)) return [];
    $users_data = file_get_contents($users_file);
    if ($users_data === false) return []; // No se pudo leer el archivo
    $users = json_decode($users_data, true);
    if ($users === null && json_last_error() !== JSON_ERROR_NONE) return []; // Error de decodificación JSON
    
    $ids_array = is_array($ids) ? $ids : []; // Asegurar que $ids sea un array
    if (empty($ids_array)) return [];

    return array_values(array_filter($users, function($u) use ($ids_array) { 
        return isset($u['id']) && in_array($u['id'], $ids_array); 
    }));
}

if ($action === 'list') {
    // Permitir listar áreas a propietarios y administradores
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['owner', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso prohibido: No tienes permisos para ver la lista de áreas']);
        exit;
    }
    
    $areas_content = file_get_contents($areas_file);
    if ($areas_content === false) {
        error_log("Error al leer areas.json");
        echo json_encode(['success' => false, 'error' => 'Error interno al leer las áreas (lectura fallida).', 'areas' => []]);
        exit;
    }
    $areas = json_decode($areas_content, true);
    if ($areas === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error al decodificar areas.json: " . json_last_error_msg());
        echo json_encode(['success' => false, 'error' => 'Error interno al leer las áreas (JSON inválido).', 'areas' => []]);
        exit;
    }
    $areas = $areas ?: []; // Asegurar que $areas sea un array si el archivo está vacío pero es JSON válido ([])    // Si el usuario es administrador, filtrar solo las áreas que administra
    if ($_SESSION['user_role'] === 'admin' && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $filtered_areas = [];
        
        foreach ($areas as $area) {
            if (isset($area['admins']) && in_array($user_id, $area['admins'])) {
                $filtered_areas[] = $area;
            }
        }
        
        // Enriquecer con información de usuarios
        $users_data = file_get_contents($users_file);
        $users = json_decode($users_data, true) ?: [];
        $users_map = [];
        
        foreach ($users as $user) {
            $users_map[$user['id']] = [
                'username' => $user['username'],
                'profile_image_url' => isset($user['profile_image_filename']) ? 
                    '/form/profile_images/' . $user['profile_image_filename'] : 
                    '/form/profile_images/default.png'
            ];
        }
        
        // Enriquecer los datos de área con información de usuario
        $areas_with_users = [];
        foreach ($filtered_areas as $area) {
            $admins_with_data = [];
            $editors_with_data = [];
            
            // Procesar administradores
            if (isset($area['admins']) && is_array($area['admins'])) {
                foreach ($area['admins'] as $admin_id) {
                    if (isset($users_map[$admin_id])) {
                        $admins_with_data[] = $users_map[$admin_id];
                    }
                }
            }
            
            // Procesar editores
            if (isset($area['editors']) && is_array($area['editors'])) {
                foreach ($area['editors'] as $editor_id) {
                    if (isset($users_map[$editor_id])) {
                        $editors_with_data[] = $users_map[$editor_id];
                    }
                }
            }
                  // Actualizar el área con datos enriquecidos
        $area['admins_data'] = $admins_with_data;
        $area['editors_data'] = $editors_with_data;
        $areas_with_users[] = $area;
    }
    
    echo json_encode(['success' => true, 'data' => $areas_with_users]);
} else {
        // El usuario es propietario, mostrar todas las áreas
        // Enriquecer con información de usuarios
        $users_data = file_get_contents($users_file);
        $users = json_decode($users_data, true) ?: [];
        $users_map = [];
        
        foreach ($users as $user) {
            $users_map[$user['id']] = [
                'username' => $user['username'],
                'profile_image_url' => isset($user['profile_image_filename']) ? 
                    '/form/profile_images/' . $user['profile_image_filename'] : 
                    '/form/profile_images/default.png'
            ];
        }
        
        // Enriquecer los datos de área con información de usuario
        $areas_with_users = [];
        foreach ($areas as $area) {
            $admins_with_data = [];
            $editors_with_data = [];
            
            // Procesar administradores
            if (isset($area['admins']) && is_array($area['admins'])) {
                foreach ($area['admins'] as $admin_id) {
                    if (isset($users_map[$admin_id])) {
                        $admins_with_data[] = $users_map[$admin_id];
                    }
                }
            }
            
            // Procesar editores
            if (isset($area['editors']) && is_array($area['editors'])) {
                foreach ($area['editors'] as $editor_id) {
                    if (isset($users_map[$editor_id])) {
                        $editors_with_data[] = $users_map[$editor_id];
                    }
                }
            }
            
            // Actualizar el área con datos enriquecidos
            $area['admins_data'] = $admins_with_data;
            $area['editors_data'] = $editors_with_data;
            $areas_with_users[] = $area;
        }
        
        echo json_encode(['success' => true, 'data' => $areas_with_users]);
    }
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $admins_raw = $_POST['admins'] ?? [];
    $editors_raw = $_POST['editors'] ?? [];
    $color = $_POST['color'] ?? '#4285F4'; // Capturamos el color del área, con valor predeterminado

    // Filtrar valores vacíos que pueden venir de FormData si el select está vacío y se añade '[]' como workaround
    $admins = is_array($admins_raw) ? array_filter($admins_raw, function($value) { return $value !== ''; }) : [];
    $editors = is_array($editors_raw) ? array_filter($editors_raw, function($value) { return $value !== ''; }) : [];

    if ($name === '') {
        echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
        exit;
    }
    $areas_content = file_exists($areas_file) ? file_get_contents($areas_file) : '[]';
    $areas = json_decode($areas_content, true);
    if ($areas === null) $areas = []; // Si el archivo está vacío o corrupto, empezar de nuevo
    
    $new_id = uniqid();
    $areas[] = [
        'id' => $new_id,
        'name' => $name,
        'description' => $description,
        'color' => $color, // Incluimos el color en el objeto área
        'admins' => $admins,
        'editors' => $editors
    ];
    if (file_put_contents($areas_file, json_encode($areas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        echo json_encode(['success' => false, 'error' => 'No se pudo guardar el área.']);
        exit;
    }
    
    // Sincronizar usuarios
    if (file_exists($users_file)) {
        $users_content = file_get_contents($users_file);
        $users = json_decode($users_content, true);
        if ($users !== null) {
            foreach ($users as &$user) {
                if (isset($user['role']) && $user['role'] === 'admin' && isset($user['id']) && in_array($user['id'], $admins)) {
                    $user['areas_admin'] = $user['areas_admin'] ?? [];
                    $user['areas_admin'][] = $new_id;
                    $user['areas_admin'] = array_values(array_unique($user['areas_admin']));
                }
                if (isset($user['role']) && $user['role'] === 'editor' && isset($user['id']) && in_array($user['id'], $editors)) {
                    $user['areas_editor'] = $user['areas_editor'] ?? [];
                    $user['areas_editor'][] = $new_id;
                    $user['areas_editor'] = array_values(array_unique($user['areas_editor']));
                }
            }
            file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    echo json_encode(['success' => true, 'id' => $new_id]);
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $admins_raw = $_POST['admins'] ?? [];
    $editors_raw = $_POST['editors'] ?? [];
    $color = $_POST['color'] ?? '#4285F4'; // Capturar el color en edición

    $admins = is_array($admins_raw) ? array_filter($admins_raw, function($value) { return $value !== ''; }) : [];
    $editors = is_array($editors_raw) ? array_filter($editors_raw, function($value) { return $value !== ''; }) : [];

    if ($id === '' || $name === '') {
        echo json_encode(['success' => false, 'error' => 'ID y nombre obligatorios']);
        exit;
    }
    
    // Verificar permisos según el rol del usuario
    if ($_SESSION['user_role'] === 'admin') {
        $areas_content = file_exists($areas_file) ? file_get_contents($areas_file) : '[]';
        $areas_temp = json_decode($areas_content, true);
        if ($areas_temp === null) $areas_temp = [];
        
        // Buscar el área que se está editando
        $area_found = false;
        $area_admin_access = false;
        
        foreach ($areas_temp as $area_tmp) {
            if (isset($area_tmp['id']) && $area_tmp['id'] === $id) {
                $area_found = true;
                // Verificar si el usuario actual es administrador de esta área
                if (isset($area_tmp['admins']) && in_array($_SESSION['user_id'], $area_tmp['admins'])) {
                    $area_admin_access = true;
                    // Para administradores, mantener la lista original de administradores
                    $admins = $area_tmp['admins'];
                }
                break;
            }
        }
        
        if (!$area_found) {
            echo json_encode(['success' => false, 'error' => 'Área no encontrada']);
            exit;
        }
        
        if (!$area_admin_access) {
            echo json_encode(['success' => false, 'error' => 'No tienes permisos para editar esta área']);
            exit;
        }
    }
    
    $areas_content = file_exists($areas_file) ? file_get_contents($areas_file) : '[]';
    $areas = json_decode($areas_content, true);
    if ($areas === null) $areas = [];

    $found = false;
    $original_admins = [];
    $original_editors = [];    foreach ($areas as &$area) {
        if (isset($area['id']) && $area['id'] === $id) {
            $original_admins = $area['admins'] ?? [];
            $original_editors = $area['editors'] ?? [];

            $area['name'] = $name;
            $area['description'] = $description;
            $area['color'] = $color; // Guardamos el color actualizado
            $area['admins'] = $admins;
            $area['editors'] = $editors;
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo json_encode(['success' => false, 'error' => 'Área no encontrada']);
        exit;
    }
    if (file_put_contents($areas_file, json_encode($areas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el área.']);
        exit;
    }

    // Sincronizar usuarios: quitar de los originales, añadir a los nuevos
    if (file_exists($users_file)) {
        $users_content = file_get_contents($users_file);
        $users = json_decode($users_content, true);
        if ($users !== null) {
            // Usuarios que ya no son admin/editor de esta área
            $admins_to_remove = array_diff($original_admins, $admins);
            $editors_to_remove = array_diff($original_editors, $editors);
            // Usuarios que son nuevos admin/editor de esta área
            $admins_to_add = array_diff($admins, $original_admins);
            $editors_to_add = array_diff($editors, $original_editors);

            foreach ($users as &$user) {
                if (!isset($user['id']) || !isset($user['role'])) continue;

                if ($user['role'] === 'admin') {
                    $user['areas_admin'] = $user['areas_admin'] ?? [];
                    // Quitar si ya no es admin de esta área
                    if (in_array($user['id'], $admins_to_remove)) {
                        $user['areas_admin'] = array_values(array_diff($user['areas_admin'], [$id]));
                    }
                    // Añadir si es nuevo admin de esta área
                    if (in_array($user['id'], $admins_to_add)) {
                        $user['areas_admin'][] = $id;
                        $user['areas_admin'] = array_values(array_unique($user['areas_admin']));
                    }
                }
                if ($user['role'] === 'editor') {
                    $user['areas_editor'] = $user['areas_editor'] ?? [];
                    // Quitar si ya no es editor de esta área
                    if (in_array($user['id'], $editors_to_remove)) {
                        $user['areas_editor'] = array_values(array_diff($user['areas_editor'], [$id]));
                    }
                    // Añadir si es nuevo editor de esta área
                    if (in_array($user['id'], $editors_to_add)) {
                        $user['areas_editor'][] = $id;
                        $user['areas_editor'] = array_values(array_unique($user['areas_editor']));
                    }
                }
            }
            file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    if ($id === '') {
        echo json_encode(['success' => false, 'error' => 'ID obligatorio']);
        exit;
    }
    // Validar que no existan formularios asociados
    $forms_dir = __DIR__ . '/../data/forms/';
    if (is_dir($forms_dir)) {
        $form_files = glob($forms_dir . '*.json');
        if ($form_files === false) $form_files = []; // En caso de error en glob
        foreach ($form_files as $ff) {
            $form_content = file_get_contents($ff);
            if ($form_content === false) continue;
            $form = json_decode($form_content, true);
            if ($form !== null && ($form['area_id'] ?? null) === $id) {
                echo json_encode(['success' => false, 'error' => 'No se puede eliminar el área: tiene formularios asociados']);
                exit;
            }
        }
    }
    $areas_content = file_exists($areas_file) ? file_get_contents($areas_file) : '[]';
    $areas = json_decode($areas_content, true);
    if ($areas === null) $areas = [];

    $area_to_delete_admins = [];
    $area_to_delete_editors = [];
    $initial_count = count($areas);

    $areas = array_values(array_filter($areas, function($a) use ($id, &$area_to_delete_admins, &$area_to_delete_editors) { 
        if (isset($a['id']) && $a['id'] === $id) {
            $area_to_delete_admins = $a['admins'] ?? [];
            $area_to_delete_editors = $a['editors'] ?? [];
            return false; // No incluir para eliminar
        }
        return true; // Mantener
    }));

    if (count($areas) === $initial_count && $initial_count > 0) { // No se encontró o no se eliminó nada
        // Podría ser que el área no existiera, o que el areas.json estuviera vacío/corrupto y se inicializó a []
        // Si $initial_count era 0, entonces no había nada que eliminar.
        // Si $initial_count > 0 y no cambió, el ID no se encontró.
        // No es necesariamente un error si el ID no existe, la operación de borrado es idempotente.
    }

    if (file_put_contents($areas_file, json_encode($areas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el archivo de áreas al eliminar.']);
        exit;
    }
    
    // Sincronizar usuarios
    if (file_exists($users_file)) {
        $users_content = file_get_contents($users_file);
        $users = json_decode($users_content, true);
        if ($users !== null) {
            foreach ($users as &$user) {
                if (isset($user['role']) && $user['role'] === 'admin' && isset($user['areas_admin']) && is_array($user['areas_admin'])) {
                    $user['areas_admin'] = array_values(array_diff($user['areas_admin'], [$id]));
                }
                if (isset($user['role']) && $user['role'] === 'editor' && isset($user['areas_editor']) && is_array($user['areas_editor'])) {
                    $user['areas_editor'] = array_values(array_diff($user['areas_editor'], [$id]));
                }
            }
            file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no soportada']);
exit;
