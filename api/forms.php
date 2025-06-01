<?php
// Iniciar sesión solo si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurar header para JSON
header('Content-Type: application/json');

require_once __DIR__ . '/users.php';

// --- Configuración y Rutas ---
define('DATA_DIR_FORMS', __DIR__ . '/../data/forms/');
define('DATA_DIR_RESPONSES', __DIR__ . '/../data/responses/');

/**
 * Obtiene la ruta completa de un archivo de formulario
 */
function get_form_path($form_id) {
    if (empty($form_id)) return null;
    $sanitized_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $form_id);
    if ($sanitized_id !== $form_id) return null; // ID inválido
    $path = DATA_DIR_FORMS . $sanitized_id . '.json';
    return (file_exists($path)) ? $path : null;
}

// --- Funciones de Ayuda ---

function ensure_admin_logged_in() { // Esta función ahora solo verifica si está logueado, los roles se verifican por acción
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        http_response_code(401); 
        echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Debes iniciar sesión.']);
        exit;
    }
}

function generate_unique_id() {
    return bin2hex(random_bytes(3));
}

function get_current_user_areas() {
    $current_user_id = $_SESSION['user_id'] ?? null;
    $current_user_role = $_SESSION['user_role'] ?? null;
    $users = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
    foreach ($users as $u) {
        if ($u['id'] === $current_user_id) {
            return [
                'areas_admin' => $u['areas_admin'] ?? [],
                'areas_editor' => $u['areas_editor'] ?? []
            ];
        }
    }
    return ['areas_admin' => [], 'areas_editor' => []];
}

/**
 * Lee todos los datos de los formularios, aplicando filtro por rol si es 'editor'.
 * @return array Arreglo de formularios.
 */
function get_all_forms_data() {
    $forms = [];
    if (!is_dir(DATA_DIR_FORMS)) {
        mkdir(DATA_DIR_FORMS, 0775, true); 
    }
    $form_files = glob(DATA_DIR_FORMS . '*.json');
    if ($form_files === false) { 
        return [];
    }

    // Cargar datos de áreas para hacer el "join"
    $areas_data = [];
    $areas_file_path = __DIR__ . '/../data/areas.json';
    if (file_exists($areas_file_path)) {
        $areas_content = file_get_contents($areas_file_path);
        if ($areas_content !== false) {
            $areas_array = json_decode($areas_content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($areas_array)) {
                // Crear un mapa de id => area para búsqueda rápida
                foreach ($areas_array as $area) {
                    if (isset($area['id'])) {
                        $areas_data[$area['id']] = $area;
                    }
                }
            }
        }
    }

    $current_user_id = $_SESSION['user_id'] ?? null;
    $current_user_role = $_SESSION['user_role'] ?? null;
    $user_areas = get_current_user_areas();
    $areas_admin = $user_areas['areas_admin'];
    $areas_editor = $user_areas['areas_editor'];

    foreach ($form_files as $form_file_path) {
        $form_content = file_get_contents($form_file_path);
        if ($form_content === false) continue; 
        $formData = json_decode($form_content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($formData['id'])) continue;        // --- FILTRO POR ÁREA Y ROL ---
        if ($current_user_role === 'owner') {
            // Owner ve todo
        } elseif ($current_user_role === 'admin') {
            if (!in_array($formData['area_id'] ?? '', $areas_admin)) continue;
        } elseif ($current_user_role === 'editor') {
            $has_access = false;
            
            // Verificar acceso por creator_id
            $creator_ids = $formData['creator_id'] ?? [];
            if (!is_array($creator_ids)) $creator_ids = $creator_ids ? [$creator_ids] : [];
            if (in_array($current_user_id, $creator_ids)) {
                $has_access = true;
            }
            
            // Si no tiene acceso regular, verificar permisos cruzados
            if (!$has_access) {
                $permisos_cruzados = $formData['permisos_cruzados'] ?? [];
                if (is_array($permisos_cruzados)) {
                    // Calcular estado actual de permisos cruzados
                    $tiene_permiso_cruzado = false;
                    foreach ($permisos_cruzados as $permiso) {
                        if ($permiso['user_id'] === $current_user_id) {
                            if ($permiso['accion'] === 'asignado') {
                                $tiene_permiso_cruzado = true;
                            } elseif ($permiso['accion'] === 'revocado') {
                                $tiene_permiso_cruzado = false;
                            }
                        }
                    }
                    if ($tiene_permiso_cruzado) {
                        $has_access = true;
                    }
                }
            }
            
            if (!$has_access) continue;
        }$formData['responses_count'] = get_responses_count($formData['id']);
        $formData['expiration_date'] = $formData['expiration_date'] ?? null; 
        $formData['creator_id'] = $formData['creator_id'] ?? null; // Asegurar que exista para la respuesta        // Agregar el nombre y color del área basado en area_id
        $area_id = $formData['area_id'] ?? null;
        if ($area_id && isset($areas_data[$area_id])) {
            $formData['area_name'] = $areas_data[$area_id]['name'];
            $formData['area_color'] = $areas_data[$area_id]['color'] ?? '#6c757d';
        } else {
            $formData['area_name'] = 'Sin área';
            $formData['area_color'] = '#6c757d'; // Color gris por defecto
        }

        $formData['disagreement_count'] = 0;
        $form_fields_structure = $formData['fields'] ?? null; 

        if ($form_fields_structure) {
            $terms_field_labels = [];
            foreach ($form_fields_structure as $field) {
                if (isset($field['type']) && $field['type'] === 'terms' && isset($field['label'])) {
                    $terms_field_labels[] = $field['label'];
                }
            }

            if (!empty($terms_field_labels)) {
                $responses_file_path = DATA_DIR_RESPONSES . sanitize_filename($formData['id']) . '_responses.json';
                if (file_exists($responses_file_path)) {
                    $responses_content = file_get_contents($responses_file_path);
                    if ($responses_content !== false) {
                        $responses = json_decode($responses_content, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($responses)) {
                            foreach ($responses as $response_entry) {
                                if (isset($response_entry['data']) && is_array($response_entry['data'])) {
                                    foreach ($terms_field_labels as $label) {
                                        if (isset($response_entry['data'][$label]) && $response_entry['data'][$label] === 'disagree') {
                                            $formData['disagreement_count']++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // --- Cálculo de permisos dinámicos para el usuario actual ---
        $creator_ids = $formData['creator_id'] ?? [];
        if (!is_array($creator_ids)) {
            $creator_ids = $creator_ids ? [$creator_ids] : [];
        }
        $is_owner = ($current_user_role === 'owner');
        $is_admin = ($current_user_role === 'admin');
        $is_editor = ($current_user_role === 'editor');
        $is_creator = in_array($current_user_id, $creator_ids);
        $is_admin_of_form = isset($formData['admin_id']) && $formData['admin_id'] === $current_user_id;
        $is_owner_of_form = isset($formData['owner_id']) && $formData['owner_id'] === $current_user_id;

        // Permisos de edición
        $formData['can_edit_current_user'] = false;
        $formData['can_delete_current_user'] = false;
        $formData['can_duplicate_current_user'] = false;
        $formData['can_manage_permissions_current_user'] = false;

        if ($is_owner) {
            $formData['can_edit_current_user'] = true;
            $formData['can_delete_current_user'] = true;
            $formData['can_duplicate_current_user'] = true;
            $formData['can_manage_permissions_current_user'] = true;        } elseif ($is_admin) {
            // Verificar si el formulario pertenece a un área que administra
            $user_areas = get_current_user_areas();
            $form_area_id = $formData['area_id'] ?? null;
            $is_admin_of_area = in_array($form_area_id, $user_areas['areas_admin']);
            
            // Admin puede editar/eliminar/duplicar si es creador o si el formulario pertenece a su área
            if ($is_creator || $is_admin_of_area) {
                $formData['can_edit_current_user'] = true;
                $formData['can_delete_current_user'] = true;
                $formData['can_duplicate_current_user'] = true;
                $formData['can_manage_permissions_current_user'] = true;
            } else if ($is_admin_of_form) {
                // Puede duplicar, pero no eliminar ni editar
                $formData['can_duplicate_current_user'] = true;
                $formData['can_manage_permissions_current_user'] = true;
            }
        } elseif ($is_editor) {
            // Editor solo puede editar/eliminar/duplicar si es creador
            if ($is_creator) {
                $formData['can_edit_current_user'] = true;
                $formData['can_delete_current_user'] = true;
                $formData['can_duplicate_current_user'] = true;
            }
        }

        $forms[] = $formData;
    }
    usort($forms, function($a, $b) {
        return ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0);
    });
    return $forms;
}

function get_form_data_by_id($formId) {
    $filePath = DATA_DIR_FORMS . sanitize_filename($formId) . '.json';
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if ($content === false) return null;
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data['expiration_date'] = $data['expiration_date'] ?? null; 
            $data['creator_id'] = $data['creator_id'] ?? null; // Asegurar que exista
            return $data;
        }
        return null;
    }
    return null;
}

function save_form_data($formData) {
    if (!isset($formData['id'])) return false;
    $filePath = DATA_DIR_FORMS . sanitize_filename($formData['id']) . '.json';
    if (!is_dir(dirname(DATA_DIR_FORMS))) { // Corregido: dirname() para el directorio
        mkdir(dirname(DATA_DIR_FORMS), 0775, true);
    }
    if (isset($formData['expiration_date']) && empty($formData['expiration_date'])) {
        $formData['expiration_date'] = null;
    }

    $json_data = json_encode($formData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_data === false) return false; 
    return file_put_contents($filePath, $json_data) !== false;
}

function delete_form_and_responses($formId) {
    $formPath = DATA_DIR_FORMS . sanitize_filename($formId) . '.json';
    $responsesPath = DATA_DIR_RESPONSES . sanitize_filename($formId) . '_responses.json';
    
    $deletedForm = true;
    if (file_exists($formPath)) {
        $deletedForm = unlink($formPath);
    }
    
    $deletedResponses = true; 
    if (file_exists($responsesPath)) {
        $deletedResponses = unlink($responsesPath);
    }
    
    return $deletedForm && $deletedResponses;
}

function sanitize_filename($filename) {
    $filename = preg_replace('/[^A-Za-z0-9_.-]/', '', $filename);
    $filename = str_replace(['..', '/'], '', $filename);
    return $filename;
}

function get_responses_count($formId) {
    $responsesPath = DATA_DIR_RESPONSES . sanitize_filename($formId) . '_responses.json';
    if (file_exists($responsesPath)) {
        $content = file_get_contents($responsesPath);
        if ($content === false) return 0;
        $responses = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($responses)) {
            return count($responses);
        }
    }
    return 0;
}

// --- PROCESAMIENTO DE ARCHIVO Y URL PARA CAMPOS DOWNLOADABLE (ADMIN) ---
function process_downloadable_fields(&$fields, $form_id) {
    $downloads_dir = __DIR__ . '/../downloads/';
    if (!is_dir($downloads_dir)) { mkdir($downloads_dir, 0775, true); }
    $download_urls_file = $downloads_dir . 'download_urls.json';
    $download_urls = file_exists($download_urls_file) ? json_decode(file_get_contents($download_urls_file), true) : [];
    foreach ($fields as &$field) {
        if (isset($field['type']) && $field['type'] === 'downloadable') {
            // Procesar archivo subido
            if (isset($_FILES['fields']['name'][$field['id']]['file_uploaded']) && $_FILES['fields']['error'][$field['id']]['file_uploaded'] === UPLOAD_ERR_OK) {
                $original_name = basename($_FILES['fields']['name'][$field['id']]['file_uploaded']);
                $ext = pathinfo($original_name, PATHINFO_EXTENSION);
                $safe_name = $form_id . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($original_name, PATHINFO_FILENAME)) . ($ext ? ('.' . $ext) : '');
                $target_path = $downloads_dir . $safe_name;
                if (move_uploaded_file($_FILES['fields']['tmp_name'][$field['id']]['file_uploaded'], $target_path)) {
                    $field['file_uploaded'] = $safe_name;
                    $field['file_url'] = '';
                    unset($download_urls[$form_id]);
                }
            } elseif (!empty($field['file_url'])) {
                // Si hay URL, guardar en el JSON especial
                $download_urls[$form_id] = $field['file_url'];
                $field['file_uploaded'] = '';
            } elseif (!empty($field['last_uploaded'])) {
                // Si no hay archivo nuevo ni URL pero sí last_uploaded, conservarlo
                $field['file_uploaded'] = $field['last_uploaded'];
            } else {
                // Si no hay nada, limpiar ambos
                $field['file_uploaded'] = '';
                unset($download_urls[$form_id]);
            }
        }
    }
    file_put_contents($download_urls_file, json_encode($download_urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// --- Procesar campos tipo imagen (guardar archivo en uploads/) ---
function process_image_fields(&$fields, $form_id, $old_fields = null) {
    $uploads_dir = __DIR__ . '/../uploads/';
    if (!is_dir($uploads_dir)) { mkdir($uploads_dir, 0775, true); }
    foreach ($fields as &$field) {
        if (isset($field['type']) && $field['type'] === 'image') {
            if (isset($_FILES['fields']['name'][$field['id']]['image_file']) && $_FILES['fields']['error'][$field['id']]['image_file'] === UPLOAD_ERR_OK) {
                $original_name = basename($_FILES['fields']['name'][$field['id']]['image_file']);
                $ext = pathinfo($original_name, PATHINFO_EXTENSION);
                $safe_name = $form_id . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($original_name, PATHINFO_FILENAME)) . ($ext ? ('.' . $ext) : '');
                $target_path = $uploads_dir . $safe_name;
                if (move_uploaded_file($_FILES['fields']['tmp_name'][$field['id']]['image_file'], $target_path)) {
                    $field['image_file_uploaded'] = $safe_name;
                    $field['image_file_url'] = '';
                }
            } elseif (!empty($field['last_uploaded'])) {
                $field['image_file_uploaded'] = $field['last_uploaded'];
            } elseif ($old_fields) {
                foreach ($old_fields as $old_field) {
                    if ($old_field['id'] === $field['id'] && !empty($old_field['image_file_uploaded'])) {
                        $field['image_file_uploaded'] = $old_field['image_file_uploaded'];
                        $field['image_file_url'] = $old_field['image_file_url'] ?? '';
                    }
                }
            }
        }
    }
}

// --- Manejo de Acciones (Routing Básico) ---
$action = $_REQUEST['action'] ?? null;
$formId_param = $_GET['id'] ?? null; 

// Asegurar que el usuario esté logueado para todas las acciones de formularios (excepto get_public)
if ($action !== 'get_public') {
    ensure_admin_logged_in(); // Verifica sesión y existencia de user_id y user_role
}

$current_user_id = $_SESSION['user_id'] ?? null;
$current_user_role = $_SESSION['user_role'] ?? null;


switch ($action) {
    case 'list': 
        // La función get_all_forms_data() ya maneja el filtro por rol 'editor'
        $forms = get_all_forms_data();
        echo json_encode(['success' => true, 'data' => $forms]);
        break;

    case 'create':
        // DEBUG: Guardar POST y FILES para depuración
        file_put_contents(__DIR__ . '/../downloads/debug_post_files.log',
            "---- " . date('Y-m-d H:i:s') . " ----\n" .
            "_POST:\n" . print_r($_POST, true) .
            "\n_FILES:\n" . print_r($_FILES, true) .
            "\n-----------------------------\n",
            FILE_APPEND
        );
        // Permitido para owner, admin, editor
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Si hay archivos subidos, usar $_POST y $_FILES, si no, usar JSON
            $input = $_POST;
            $fields = isset($_POST['fields']) && is_array($_POST['fields']) ? $_POST['fields'] : [];
            // Convertir fields a array indexado y agregar 'id'
            $fields_indexed = [];
            foreach ($fields as $field_id => $field) {
                if (is_array($field)) {
                    $field['id'] = $field_id;
                    $fields_indexed[] = $field;
                }
            }
            if (empty($input['formTitle'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
                exit;
            }
            $newFormId = generate_unique_id();
            // Procesar archivos/URLs para campos 'downloadable'
            process_downloadable_fields($fields_indexed, $newFormId);
            process_image_fields($fields_indexed, $newFormId); // NUEVO: procesar imagen
            // Procesar archivos para campos 'image'
            process_image_fields($fields_indexed, $newFormId);
            $expiration = isset($input['expirationDate']) ? htmlspecialchars($input['expirationDate']) : (isset($input['expiration_date']) ? htmlspecialchars($input['expiration_date']) : null);
            // Determinar owner_id y admin_id según el usuario en sesión
            $owner_id = null;
            $admin_id = null;
            if ($current_user_role === 'owner') {
                $owner_id = $current_user_id;
            } else {
                // Buscar el owner real (primer usuario owner en users.json)
                $users = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
                foreach ($users as $u) {
                    if ($u['role'] === 'owner') {
                        $owner_id = $u['id'];
                        break;
                    }
                }
            }
            if ($current_user_role === 'admin') {
                $admin_id = $current_user_id;
            } elseif ($current_user_role === 'editor') {
                // Buscar admin_id del editor
                $users = $users ?? (file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : []);
                foreach ($users as $u) {
                    if ($u['id'] === $current_user_id && isset($u['admin_id'])) {
                        $admin_id = $u['admin_id'];
                        break;
                    }
                }
            }
            $area_id = $input['area_id'] ?? null;
            if ($current_user_role === 'admin') {
                $user_areas = get_current_user_areas();
                if (!$area_id || !in_array($area_id, $user_areas['areas_admin'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Solo puedes crear formularios en tus áreas.']);
                    exit;
                }
            } elseif ($current_user_role === 'editor') {
                $user_areas = get_current_user_areas();
                if (!$area_id || !in_array($area_id, $user_areas['areas_editor'])) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Solo puedes crear formularios en tus áreas.']);
                    exit;
                }
            }
            $newForm = [
                'id' => $newFormId,
                'title' => htmlspecialchars($input['formTitle'] ?? 'Formulario sin título'),
                'description' => htmlspecialchars($input['formDescription'] ?? ''),
                'companyName' => htmlspecialchars($input['companyName'] ?? ''),
                'logoUrl' => filter_var($input['logoUrl'] ?? '', FILTER_VALIDATE_URL) ? $input['logoUrl'] : '',
                'expiration_date' => $expiration,
                'owner_id' => $owner_id,
                'admin_id' => $admin_id,
                'creator_id' => [$current_user_id], // Siempre array
                'updated_id' => $current_user_id,
                'permisos_cruzados' => [], // Inicialmente vacío
                'fields' => $fields_indexed,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'public_url' => 'form.php?id=' . $newFormId,
                'area_id' => $area_id
            ];
            if (isset($newForm['fields']) && is_array($newForm['fields'])) {
                foreach ($newForm['fields'] as &$field) {
                    $field['id'] = $field['id'] ?? uniqid('field_');
                    $field['label'] = htmlspecialchars($field['label'] ?? 'Campo sin etiqueta');
                    $field['type'] = htmlspecialchars($field['type'] ?? 'text');
                    if (isset($field['options']) && is_array($field['options'])) {
                        $field['options'] = array_map('htmlspecialchars', $field['options']);
                    }
                    if ($field['type'] === 'terms') {
                        $field['disagreement_message'] = htmlspecialchars($field['disagreement_message'] ?? '');
                        unset($field['agreement_response']);
                    }
                }
                unset($field);
            }
            if (save_form_data($newForm)) {
                $newForm['responses_count'] = 0;
                $newForm['disagreement_count'] = 0;
                echo json_encode(['success' => true, 'message' => 'Formulario creado exitosamente.', 'data' => $newForm]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al guardar el formulario.']);
            }
            return;
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            return;
        }
        break;

    case 'get': // Para editar en admin_dashboard
    case 'get_public': // Para form.php
        if (!$formId_param) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de formulario no proporcionado.']);
            exit;
        }
        $formData = get_form_data_by_id($formId_param);

        if ($action === 'get') { // Si es para editar, verificar permisos si es editor
            if ($current_user_role === 'editor') {
                $creator_ids = $formData['creator_id'] ?? [];
                if (!is_array($creator_ids)) {
                    $creator_ids = $creator_ids ? [$creator_ids] : [];
                }
                if ($formData === null || !in_array($current_user_id, $creator_ids)) {
                    http_response_code(403); // Forbidden
                    echo json_encode(['success' => false, 'message' => 'No tienes permiso para acceder a este formulario.']);
                    exit;
                }
            }
        }
        
        if ($formData) {
            echo json_encode(['success' => true, 'data' => $formData]);
        } else {
            http_response_code(404); 
            echo json_encode(['success' => false, 'message' => 'Formulario no encontrado.']);
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formId_param) {
            $input = $_POST;
            $fields = isset($_POST['fields']) && is_array($_POST['fields']) ? $_POST['fields'] : [];
            $fields_indexed = [];
            foreach ($fields as $field_id => $field) {
                if (is_array($field)) {
                    $field['id'] = $field_id;
                    $fields_indexed[] = $field;
                }
            }
            if (empty($input['formTitle'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
                exit;
            }
            $existingForm = get_form_data_by_id($formId_param);
            if (!$existingForm) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Formulario no encontrado para editar.']);
                exit;
            }
            validate_area_permission($existingForm['area_id'] ?? null, 'edit');
            // Validación de permisos avanzada
            $creator_ids = $existingForm['creator_id'];
            if (!is_array($creator_ids)) {
                $creator_ids = $creator_ids ? [$creator_ids] : [];
            }
            $can_edit = false;
            if ($current_user_role === 'owner') {
                $can_edit = true;
            } elseif ($current_user_role === 'admin') {
                // Admin puede editar si es creador o si el formulario es de un editor de su área (ya validado en la UI)
                $can_edit = in_array($current_user_id, $creator_ids);
            } elseif ($current_user_role === 'editor') {
                $can_edit = in_array($current_user_id, $creator_ids);
            }
            if (!$can_edit) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar este formulario.']);
                exit;
            }
            process_downloadable_fields($fields_indexed, $formId_param);
            process_image_fields($fields_indexed, $formId_param, $existingForm['fields'] ?? null); // PASAR CAMPOS ANTERIORES
            $expiration = isset($input['expirationDate']) ? htmlspecialchars($input['expirationDate']) : (isset($input['expiration_date']) ? htmlspecialchars($input['expiration_date']) : null);
            // Permitir actualizar creator_id y permisos_cruzados si el usuario es owner o admin
            $new_creator_ids = isset($_POST['creator_id']) ? (array)$_POST['creator_id'] : $existingForm['creator_id'];
            $new_permisos_cruzados = isset($_POST['permisos_cruzados']) ? $_POST['permisos_cruzados'] : $existingForm['permisos_cruzados'] ?? [];
            $updatedForm = array_merge($existingForm, [
                'title' => htmlspecialchars($input['formTitle'] ?? $existingForm['title']),
                'description' => htmlspecialchars($input['formDescription'] ?? $existingForm['description']),
                'companyName' => htmlspecialchars($input['companyName'] ?? $existingForm['companyName']),
                'logoUrl' => filter_var($input['logoUrl'] ?? $existingForm['logoUrl'], FILTER_VALIDATE_URL) ? $input['logoUrl'] : $existingForm['logoUrl'],
                'expiration_date' => $expiration,
                'fields' => $fields_indexed,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_id' => $current_user_id,
                'creator_id' => $new_creator_ids,
                'permisos_cruzados' => $new_permisos_cruzados
            ]);
            if (isset($updatedForm['fields']) && is_array($updatedForm['fields'])) {
                foreach ($updatedForm['fields'] as &$field) {
                    $field['id'] = $field['id'] ?? uniqid('field_');
                    $field['label'] = htmlspecialchars($field['label'] ?? 'Campo sin etiqueta');
                    $field['type'] = htmlspecialchars($field['type'] ?? 'text');
                    if (isset($field['options']) && is_array($field['options'])) {
                        $field['options'] = array_map('htmlspecialchars', $field['options']);
                    }
                    if ($field['type'] === 'terms') {
                        $field['disagreement_message'] = htmlspecialchars($field['disagreement_message'] ?? '');
                        unset($field['agreement_response']);
                    }
                }
                unset($field);
            }
            if (save_form_data($updatedForm)) {
                $updatedForm['responses_count'] = get_responses_count($updatedForm['id']);
                $updatedForm['disagreement_count'] = 0;
                $form_fields_structure_edit = $updatedForm['fields'] ?? null;
                if ($form_fields_structure_edit) {
                    $terms_field_labels_edit = [];
                    foreach ($form_fields_structure_edit as $field_edit) {
                        if (isset($field_edit['type']) && $field_edit['type'] === 'terms' && isset($field_edit['label'])) {
                            $terms_field_labels_edit[] = $field_edit['label'];
                        }
                    }
                    if (!empty($terms_field_labels_edit)) {
                        $responses_file_path_edit = DATA_DIR_RESPONSES . sanitize_filename($updatedForm['id']) . '_responses.json';
                        if (file_exists($responses_file_path_edit)) {
                            $responses_content_edit = file_get_contents($responses_file_path_edit);
                            if ($responses_content_edit !== false) {
                                $responses_edit = json_decode($responses_content_edit, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($responses_edit)) {
                                    foreach ($responses_edit as $response_entry_edit) {
                                        if (isset($response_entry_edit['data']) && is_array($response_entry_edit['data'])) {
                                            foreach ($terms_field_labels_edit as $label_edit) {
                                                if (isset($response_entry_edit['data'][$label_edit]) && $response_entry_edit['data'][$label_edit] === 'disagree') {
                                                    $updatedForm['disagreement_count']++;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                echo json_encode(['success' => true, 'message' => 'Formulario actualizado exitosamente.', 'data' => $updatedForm]);
                return;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el formulario.']);
                return;
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido o ID de formulario faltante.']);
            return;
        }
        break;

    case 'delete':
        if (($_SERVER['REQUEST_METHOD'] === 'DELETE' || $_SERVER['REQUEST_METHOD'] === 'POST') && $formId_param) {
            $form_to_delete = get_form_data_by_id($formId_param);
            if (!$form_to_delete) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Formulario no encontrado para eliminar.']);
                exit;
            }
            validate_area_permission($form_to_delete['area_id'] ?? null, 'delete');
            // Validación de permisos avanzada
            $creator_ids = $form_to_delete['creator_id'];
            if (!is_array($creator_ids)) {
                $creator_ids = $creator_ids ? [$creator_ids] : [];
            }
            $can_delete = false;
            if ($current_user_role === 'owner') {
                $can_delete = true;
            } elseif ($current_user_role === 'admin') {
                $can_delete = in_array($current_user_id, $creator_ids);
            } elseif ($current_user_role === 'editor') {
                $can_delete = in_array($current_user_id, $creator_ids);
            }
            if (!$can_delete) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar este formulario.']);
                exit;
            }
            if (delete_form_and_responses($formId_param)) {
                echo json_encode(['success' => true, 'message' => 'Formulario eliminado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el formulario.']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido o ID de formulario faltante.']);
        }
        break;
    
    case 'duplicate':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $formId_param) {
            $originalForm = get_form_data_by_id($formId_param);
            if (!$originalForm) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Formulario original no encontrado para duplicar.']);
                exit;
            }

            // Verificar permisos para duplicar
            if ($current_user_role === 'editor' && $originalForm['creator_id'] !== $current_user_id) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para duplicar este formulario.']);
                exit;
            }
            // Admin y Owner pueden duplicar

            $newDuplicatedId = generate_unique_id();
            $duplicatedForm = $originalForm; 
            $duplicatedForm['id'] = $newDuplicatedId;
            $duplicatedForm['title'] = $originalForm['title'] . ' (Copia)';
            $duplicatedForm['created_at'] = date('Y-m-d H:i:s');
            $duplicatedForm['updated_at'] = date('Y-m-d H:i:s');
            $duplicatedForm['public_url'] = 'form.php?id=' . $newDuplicatedId;
            $duplicatedForm['expiration_date'] = $originalForm['expiration_date'] ?? null;
            // Asignar owner_id y admin_id correctamente según el usuario que duplica
            if ($current_user_role === 'owner') {
                $duplicatedForm['owner_id'] = $current_user_id;
                $duplicatedForm['admin_id'] = null;
            } elseif ($current_user_role === 'admin') {
                // Mantener el owner original, pero el admin es el que duplica
                $duplicatedForm['admin_id'] = $current_user_id;
                // Buscar el owner real (primer usuario owner en users.json)
                $users = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
                foreach ($users as $u) {
                    if ($u['role'] === 'owner') {
                        $duplicatedForm['owner_id'] = $u['id'];
                        break;
                    }
                }
            } elseif ($current_user_role === 'editor') {
                // Buscar admin_id del editor
                $users = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
                foreach ($users as $u) {
                    if ($u['id'] === $current_user_id && isset($u['admin_id'])) {
                        $duplicatedForm['admin_id'] = $u['admin_id'];
                        break;
                    }
                }
                // Buscar el owner real (primer usuario owner en users.json)
                foreach ($users as $u) {
                    if ($u['role'] === 'owner') {
                        $duplicatedForm['owner_id'] = $u['id'];
                        break;
                    }
                }
            }
            $duplicatedForm['creator_id'] = [$current_user_id]; // Siempre array
            $duplicatedForm['updated_id'] = $current_user_id;
            $duplicatedForm['permisos_cruzados'] = [];

            if (isset($duplicatedForm['fields']) && is_array($duplicatedForm['fields'])) {
                foreach ($duplicatedForm['fields'] as &$field) {
                    if ($field['type'] === 'terms') {
                       $field['disagreement_message'] = $field['disagreement_message'] ?? '';
                       unset($field['agreement_response']);
                    }
                }
                unset($field);
            }

            if (save_form_data($duplicatedForm)) {
                $duplicatedForm['responses_count'] = 0; 
                $duplicatedForm['disagreement_count'] = 0; 
                echo json_encode(['success' => true, 'message' => 'Formulario duplicado exitosamente.', 'data' => $duplicatedForm]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al guardar el formulario duplicado.']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido o ID de formulario faltante.']);
        }
        break;    case 'add_editor':
        ensure_admin_logged_in();
        $form_id = $_POST['form_id'] ?? ($_GET['form_id'] ?? null);
        $user_id_to_add = $_POST['user_id'] ?? ($_GET['user_id'] ?? null);
        if (!$form_id || !$user_id_to_add) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros para asignar permisos.']);
            exit;
        }
        
        $form_path = DATA_DIR_FORMS . $form_id . '.json';
        if (!file_exists($form_path)) {
            echo json_encode(['success' => false, 'message' => 'El formulario solicitado no existe.']);
            exit;
        }
        
        $form = json_decode(file_get_contents($form_path), true);
        if (!$form) {
            echo json_encode(['success' => false, 'message' => 'Error al leer los datos del formulario.']);
            exit;
        }
        
        // Verificar título del formulario para respuesta personalizada
        $form_title = $form['title'] ?? 'Formulario';
        
        // Verificar permisos del usuario actual
        $current_user_id = $_SESSION['user_id'];
        $current_user_role = $_SESSION['user_role'];
        $area_id = $form['area_id'] ?? null;
        $is_owner = $current_user_role === 'owner';
        $is_admin_area = false;
        
        if ($current_user_role === 'admin' && $area_id) {
            $users = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true);
            foreach ($users as $u) {
                if ($u['id'] === $current_user_id && isset($u['areas_admin']) && in_array($area_id, $u['areas_admin'])) {
                    $is_admin_area = true;
                    break;
                }
            }
        }
        
        if (!$is_owner && !$is_admin_area) {
            echo json_encode([
                'success' => false, 
                'message' => 'No tienes permisos para modificar los editores de este formulario.',
                'error_code' => 'PERMISSION_DENIED'
            ]);
            exit;
        }
        
        // Obtener info del usuario para la respuesta personalizada
        $user_to_add_info = null;
        $users = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true);
        foreach ($users as $u) {
            if ($u['id'] === $user_id_to_add) {
                $user_to_add_info = $u;
                break;
            }
        }
        $user_to_add_name = $user_to_add_info ? $user_to_add_info['username'] : 'Usuario';
        
        // Asegurar que creator_id es un array
        if (!isset($form['creator_id']) || !is_array($form['creator_id'])) {
            $form['creator_id'] = [];
        }
        
        // Verificar si ya tiene el permiso
        if (in_array($user_id_to_add, $form['creator_id'])) {
            echo json_encode([
                'success' => true, 
                'message' => "El usuario $user_to_add_name ya tiene permisos de edición.", 
                'form_title' => $form_title,
                'user' => ['id' => $user_id_to_add, 'username' => $user_to_add_name]
            ]);
            exit;
        }
        
        // Añadir el editor y guardar
        $form['creator_id'][] = $user_id_to_add;
        $result = file_put_contents($form_path, json_encode($form, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar los cambios en el formulario.']);
            exit;
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "Permiso de edición otorgado a $user_to_add_name.", 
            'form_title' => $form_title,
            'user' => ['id' => $user_id_to_add, 'username' => $user_to_add_name]
        ]);
        exit;
        
    case 'remove_editor':
        ensure_admin_logged_in();
        $form_id = $_POST['form_id'] ?? ($_GET['form_id'] ?? null);
        $user_id_to_remove = $_POST['user_id'] ?? ($_GET['user_id'] ?? null);
        if (!$form_id || !$user_id_to_remove) {
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros para revocar permisos.']);
            exit;
        }
        
        $form_path = DATA_DIR_FORMS . $form_id . '.json';
        if (!file_exists($form_path)) {
            echo json_encode(['success' => false, 'message' => 'El formulario solicitado no existe.']);
            exit;
        }
        
        $form = json_decode(file_get_contents($form_path), true);
        if (!$form) {
            echo json_encode(['success' => false, 'message' => 'Error al leer los datos del formulario.']);
            exit;
        }
        
        // Verificar título del formulario para respuesta personalizada
        $form_title = $form['title'] ?? 'Formulario';
        
        // Verificar permisos del usuario actual
        $current_user_id = $_SESSION['user_id'];
        $current_user_role = $_SESSION['user_role'];
        $area_id = $form['area_id'] ?? null;
        $is_owner = $current_user_role === 'owner';
        $is_admin_area = false;
        
        if ($current_user_role === 'admin' && $area_id) {
            $users = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true);
            foreach ($users as $u) {
                if ($u['id'] === $current_user_id && isset($u['areas_admin']) && in_array($area_id, $u['areas_admin'])) {
                    $is_admin_area = true;
                    break;
                }
            }
        }
        
        if (!$is_owner && !$is_admin_area) {
            echo json_encode([
                'success' => false, 
                'message' => 'No tienes permisos para modificar los editores de este formulario.',
                'error_code' => 'PERMISSION_DENIED'
            ]);
            exit;
        }
        
        // Obtener info del usuario para la respuesta personalizada
        $user_to_remove_info = null;
        $users = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true);
        foreach ($users as $u) {
            if ($u['id'] === $user_id_to_remove) {
                $user_to_remove_info = $u;
                break;
            }
        }
        $user_to_remove_name = $user_to_remove_info ? $user_to_remove_info['username'] : 'Usuario';
        
        // Verificar si no tiene el permiso
        if (!isset($form['creator_id']) || !is_array($form['creator_id']) || !in_array($user_id_to_remove, $form['creator_id'])) {
            echo json_encode([
                'success' => true, 
                'message' => "El usuario $user_to_remove_name no tiene permisos de edición que revocar.", 
                'form_title' => $form_title,
                'user' => ['id' => $user_id_to_remove, 'username' => $user_to_remove_name]
            ]);
            exit;
        }
        
        // Eliminar el editor y guardar
        $form['creator_id'] = array_values(array_diff($form['creator_id'], [$user_id_to_remove]));
        $result = file_put_contents($form_path, json_encode($form, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar los cambios en el formulario.']);
            exit;
        }
          echo json_encode([
            'success' => true, 
            'message' => "Permiso de edición revocado a $user_to_remove_name.",
            'form_title' => $form_title,
            'user' => ['id' => $user_id_to_remove, 'username' => $user_to_remove_name]
        ]);
        exit;

    case 'assign_cross_area_permission':
        header('Content-Type: application/json');
        // ensure_logged_in(); // Eliminada esta línea, la verificación ya se hace globalmente
        
        // Solo owner y admin pueden asignar permisos cruzados
        if (!in_array($current_user_role, ['owner', 'admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para asignar permisos cruzados.']);
            exit;
        }
        
        $form_id = $_POST['form_id'] ?? '';
        $user_id = $_POST['user_id'] ?? '';
        $action_type = $_POST['action_type'] ?? 'assign'; // assign o revoke
        
        if (empty($form_id) || empty($user_id)) {
            echo json_encode(['success' => false, 'message' => 'ID del formulario y usuario son requeridos.']);
            exit;
        }
        
        // Cargar formulario
        $form_path = get_form_path($form_id);
        if (!$form_path || !file_exists($form_path)) {
            echo json_encode(['success' => false, 'message' => 'Formulario no encontrado.']);
            exit;
        }
        
        $form = json_decode(file_get_contents($form_path), true);
        if (!$form) {
            echo json_encode(['success' => false, 'message' => 'Error al leer el formulario.']);
            exit;
        }
        
        // Verificar permisos sobre el formulario
        if ($current_user_role === 'admin') {
            $user_areas = get_current_user_areas();
            if (!in_array($form['area_id'] ?? '', $user_areas['areas_admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos sobre este formulario.']);
                exit;
            }
        }
        
        // Verificar que el usuario existe y obtener información
        $users = get_all_users();
        $target_user = null;
        foreach ($users as $user) {
            if ($user['id'] === $user_id) {
                $target_user = $user;
                break;
            }
        }
        
        if (!$target_user) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            exit;
        }
        
        // Solo se pueden asignar permisos cruzados a editores
        if ($target_user['role'] !== 'editor') {
            echo json_encode(['success' => false, 'message' => 'Solo se pueden asignar permisos cruzados a editores.']);
            exit;
        }
        
        // Verificar que no sea de la misma área (sería redundante)
        $target_user_areas = $target_user['areas_editor'] ?? [];
        if (in_array($form['area_id'] ?? '', $target_user_areas)) {
            echo json_encode(['success' => false, 'message' => 'Este usuario ya tiene acceso por pertenecer al área del formulario.']);
            exit;
        }
        
        // Inicializar permisos_cruzados si no existe
        if (!isset($form['permisos_cruzados']) || !is_array($form['permisos_cruzados'])) {
            $form['permisos_cruzados'] = [];
        }
        
        $now = date('Y-m-d H:i:s');
          if ($action_type === 'assign') {
            // Verificar el estado actual del usuario (última acción)
            $last_action = null;
            $last_action_date = null;
            
            // Buscar la última acción del usuario en el historial
            foreach ($form['permisos_cruzados'] as $permiso) {
                if ($permiso['user_id'] === $user_id) {
                    $permiso_date = $permiso['fecha'];
                    // Si no hay fecha anterior o esta es más reciente
                    if ($last_action_date === null || $permiso_date > $last_action_date) {
                        $last_action = $permiso['accion'];
                        $last_action_date = $permiso_date;
                    }
                }
            }
            
            // Solo bloquear si la última acción fue "asignado" (usuario tiene permisos activos)
            if ($last_action === 'asignado') {
                echo json_encode(['success' => false, 'message' => 'Este usuario ya tiene permisos cruzados activos sobre este formulario.']);
                exit;
            }
              // Asignar permiso
            $form['permisos_cruzados'][] = [
                'user_id' => $user_id,
                'username' => $target_user['username'],
                'asignado_por' => $current_user_id,
                'asignado_por_username' => $_SESSION['admin_username'] ?? 'Sistema',
                'fecha' => $now,
                'accion' => 'asignado'
            ];
            
            // También agregar a creator_id para que funcione con el sistema actual
            if (!isset($form['creator_id']) || !is_array($form['creator_id'])) {
                $form['creator_id'] = [];
            }
            if (!in_array($user_id, $form['creator_id'])) {
                $form['creator_id'][] = $user_id;
            }
            
            $message = "Permiso cruzado asignado a {$target_user['username']}.";
              } else { // revoke
            // Verificar el estado actual del usuario (última acción)
            $last_action = null;
            $last_action_date = null;
            
            // Buscar la última acción del usuario en el historial
            foreach ($form['permisos_cruzados'] as $permiso) {
                if ($permiso['user_id'] === $user_id) {
                    $permiso_date = $permiso['fecha'];
                    // Si no hay fecha anterior o esta es más reciente
                    if ($last_action_date === null || $permiso_date > $last_action_date) {
                        $last_action = $permiso['accion'];
                        $last_action_date = $permiso_date;
                    }
                }
            }
            
            // Solo permitir revocar si la última acción fue "asignado" (usuario tiene permisos activos)
            if ($last_action !== 'asignado') {
                echo json_encode(['success' => false, 'message' => 'Este usuario no tiene permisos cruzados activos sobre este formulario.']);
                exit;
            }
              // Revocar permiso (agregar entrada de revocación)
            $form['permisos_cruzados'][] = [
                'user_id' => $user_id,
                'username' => $target_user['username'],
                'asignado_por' => $current_user_id,
                'asignado_por_username' => $_SESSION['admin_username'] ?? 'Sistema',
                'fecha' => $now,
                'accion' => 'revocado'
            ];
            
            // Remover de creator_id solo si es un permiso cruzado (no original)
            if (isset($form['creator_id']) && is_array($form['creator_id'])) {
                // Verificar si el usuario tiene otros permisos (no cruzados)
                $has_regular_permission = false;
                
                // Si es el propietario original o admin del área, no remover
                if ($form['owner_id'] === $user_id || $form['admin_id'] === $user_id) {
                    $has_regular_permission = true;
                }
                
                // Si no tiene permisos regulares, remover de creator_id
                if (!$has_regular_permission) {
                    $form['creator_id'] = array_values(array_diff($form['creator_id'], [$user_id]));
                }
            }
            
            $message = "Permiso cruzado revocado a {$target_user['username']}.";
        }
        
        // Actualizar fecha de modificación
        $form['updated_at'] = $now;
        $form['updated_id'] = $current_user_id;
        
        // Guardar formulario
        $result = file_put_contents($form_path, json_encode($form, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result === false) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar los cambios.']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'form_title' => $form['title'] ?? 'Formulario',
            'user' => [
                'id' => $user_id,
                'username' => $target_user['username']
            ],
            'action' => $action_type,
            'timestamp' => $now
        ]);
        exit;

    case 'get_cross_area_permissions':
        header('Content-Type: application/json');
        // ensure_logged_in(); // Esta función no existe, usar ensure_admin_logged_in o una específica
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            http_response_code(401); 
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Debes iniciar sesión.']);
            exit;
        }
        $current_user_id = $_SESSION['user_id']; // Definir para usar en la lógica de permisos
        $current_user_role = $_SESSION['user_role']; // Definir para usar en la lógica de permisos
        
        $form_id = $_GET['form_id'] ?? '';
        
        if (empty($form_id)) {
            echo json_encode(['success' => false, 'message' => 'ID del formulario es requerido.']);
            exit;
        }
        
        // Cargar formulario
        $form_path = get_form_path($form_id);
        if (!$form_path || !file_exists($form_path)) {
            echo json_encode(['success' => false, 'message' => 'Formulario no encontrado.']);
            exit;
        }
        
        $form = json_decode(file_get_contents($form_path), true);
        if (!$form) {
            echo json_encode(['success' => false, 'message' => 'Error al leer el formulario.']);
            exit;
        }
        
        // Verificar permisos de lectura
        if ($current_user_role === 'admin') {
            $user_areas = get_current_user_areas();
            if (!in_array($form['area_id'] ?? '', $user_areas['areas_admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este formulario.']);
                exit;
            }
        } elseif ($current_user_role === 'editor') {
            // Editor solo puede ver si tiene permisos
            $creator_ids = $form['creator_id'] ?? [];
            if (!is_array($creator_ids)) $creator_ids = $creator_ids ? [$creator_ids] : [];
            if (!in_array($current_user_id, $creator_ids)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para ver este formulario.']);
                exit;
            }
        }
        
        $permisos_cruzados = $form['permisos_cruzados'] ?? [];
        
        // Obtener estado actual de permisos (solo los activos)
        $permisos_activos = [];
        $historial = [];
        
        foreach ($permisos_cruzados as $permiso) {
            $historial[] = $permiso;
            
            if ($permiso['accion'] === 'asignado') {
                $permisos_activos[$permiso['user_id']] = $permiso;
            } elseif ($permiso['accion'] === 'revocado') {
                unset($permisos_activos[$permiso['user_id']]);
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'form_id' => $form_id,
                'form_title' => $form['title'] ?? 'Formulario',
                'permisos_activos' => array_values($permisos_activos),
                'historial_completo' => array_reverse($historial) // Más recientes primero
            ]
        ]);
        exit;

    default:
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'Acción no válida o no especificada.']);
        break;
}

function validate_area_permission($form_area_id, $action = 'manage') {
    $current_user_role = $_SESSION['user_role'] ?? null;
    $user_areas = get_current_user_areas();
    if ($current_user_role === 'owner') return true;
    if ($current_user_role === 'admin' && !in_array($form_area_id, $user_areas['areas_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para gestionar formularios de esta área.']);
        exit;
    }
    if ($current_user_role === 'editor' && !in_array($form_area_id, $user_areas['areas_editor'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para gestionar formularios de esta área.']);
        exit;
    }
    return true;
}
