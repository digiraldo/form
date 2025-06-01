<?php
session_start(); 

define('DATA_DIR_FORMS', __DIR__ . '/../data/forms/'); 
define('DATA_DIR_RESPONSES', __DIR__ . '/../data/responses/');
define('ADMIN_EMAIL', 'tu_correo_admin@example.com'); 
define('FROM_EMAIL', 'noreply@tusitio.com'); 

/**
 * Sanitiza un nombre de archivo.
 */
function sanitize_filename_responses($filename) {
    $filename = preg_replace('/[^A-Za-z0-9_.-]/', '', $filename);
    $filename = str_replace(['..', '/'], '', $filename);
    return $filename;
}

/**
 * Obtiene la estructura de un formulario específico por su ID.
 * Devuelve todo el objeto del formulario para poder acceder a creator_id.
 */
function get_form_data_by_id_for_responses($formId) { // Renombrada para evitar conflicto si se usa otra similar
    $filePath = DATA_DIR_FORMS . sanitize_filename_responses($formId) . '.json';
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if ($content === false) return null;
        $data = json_decode($content, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null; // Devuelve todo el formulario
    }
    return null;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? null); 
$current_user_id = $_SESSION['user_id'] ?? null;
$current_user_role = $_SESSION['user_role'] ?? null;

if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($input['form_id']) || !isset($input['fields'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos de envío inválidos o incompletos.']);
        exit;
    }

    $formId = sanitize_filename_responses($input['form_id']);
    $responsesDir = DATA_DIR_RESPONSES;
    $filePath = $responsesDir . $formId . '_responses.json';

    if (!is_dir($responsesDir)) {
        if (!mkdir($responsesDir, 0775, true) && !is_dir($responsesDir)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno: No se pudo crear el directorio de respuestas.']);
            exit;
        }
    }

    $all_responses = [];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if ($content !== false) {
            $decoded_content = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_content)) {
                $all_responses = $decoded_content;
            }
        }
    }

    $submission_id = bin2hex(random_bytes(4));
    $new_response_entry = [
        'submission_id' => $submission_id,
        'submitted_at' => $input['submitted_at'] ?? date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A', 
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A', 
        'data' => []
    ];

    $user_email_for_copy = null;
    $form_structure_for_email = get_form_data_by_id_for_responses($formId); 
    $form_title_for_email = $form_structure_for_email['title'] ?? $formId;


    foreach ($input['fields'] as $label => $value) {
        $sanitized_label = htmlspecialchars($label);
        if (is_array($value)) {
            $new_response_entry['data'][$sanitized_label] = array_map('htmlspecialchars', $value);
        } else {
            $new_response_entry['data'][$sanitized_label] = htmlspecialchars($value);
        }

        if ($form_structure_for_email && isset($form_structure_for_email['fields']) && is_array($form_structure_for_email['fields'])) {
            foreach ($form_structure_for_email['fields'] as $field_struct) {
                if (isset($field_struct['label']) && $field_struct['label'] === $label && isset($field_struct['type']) && $field_struct['type'] === 'email') {
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $user_email_for_copy = $value;
                    }
                    break; 
                }
            }
        }
    }
    
    $all_responses[] = $new_response_entry;
    $json_data = json_encode($all_responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json_data === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al codificar las respuestas en JSON.']);
        exit;
    }

    if (file_put_contents($filePath, $json_data) !== false) {
        $email_sent_admin = false;
        $email_sent_user = false;
        $email_errors = [];

        $email_body_intro = "Se ha recibido una nueva respuesta para el formulario: \"$form_title_for_email\"\n";
        $email_body_intro .= "ID de Envío: $submission_id\n";
        $email_body_intro .= "Fecha: " . $new_response_entry['submitted_at'] . "\n\n";
        $email_body_intro .= "Respuestas:\n";
        $email_body_content = "";
        foreach($new_response_entry['data'] as $q => $a) {
            $email_body_content .= htmlspecialchars_decode($q) . ": " . (is_array($a) ? implode(', ', array_map('htmlspecialchars_decode', $a)) : htmlspecialchars_decode($a)) . "\n";
        }

        $admin_subject = "Nueva respuesta recibida para: $form_title_for_email (ID: $submission_id)";
        $admin_body = $email_body_intro . $email_body_content;
        $admin_headers = "From: " . FROM_EMAIL . "\r\n" .
                         "Reply-To: " . FROM_EMAIL . "\r\n" .
                         "X-Mailer: PHP/" . phpversion();
        if (@mail(ADMIN_EMAIL, $admin_subject, $admin_body, $admin_headers)) { 
            $email_sent_admin = true;
        } else {
            $email_errors[] = "No se pudo enviar el correo de notificación al administrador.";
            error_log("Error al enviar correo al admin para form $formId, submission $submission_id. Verifica la configuración de mail() en php.ini.");
        }

        if ($user_email_for_copy) {
            $user_subject = "Copia de tu respuesta para: $form_title_for_email";
            $user_body = "Gracias por completar el formulario \"$form_title_for_email\".\n";
            $user_body .= "Aquí tienes una copia de tus respuestas:\n\n";
            $user_body .= $email_body_content;
            $user_headers = "From: " . FROM_EMAIL . "\r\n" .
                            "Reply-To: " . FROM_EMAIL . "\r\n" .
                            "X-Mailer: PHP/" . phpversion();
            if (@mail($user_email_for_copy, $user_subject, $user_body, $user_headers)) {
                $email_sent_user = true;
            } else {
                $email_errors[] = "No se pudo enviar la copia del correo al usuario ($user_email_for_copy).";
                 error_log("Error al enviar correo al usuario $user_email_for_copy para form $formId, submission $submission_id. Verifica la configuración de mail() en php.ini.");
            }
        }
        
        $response_message = 'Respuesta guardada exitosamente.';
        if ($email_sent_admin) $response_message .= ' Notificación enviada al administrador.';
        if ($user_email_for_copy && $email_sent_user) $response_message .= ' Se ha enviado una copia a tu correo.';
        if (!empty($email_errors)) {
            $response_message .= ' Hubo un problema al intentar enviar algunas notificaciones por correo.'; 
        }

        echo json_encode(['success' => true, 'message' => $response_message]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo de respuestas.']);
    }

} elseif ($action === 'get_responses' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
        exit;
    }

    $formIdToGet = $_GET['form_id'] ?? null;
    if (!$formIdToGet) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de formulario no proporcionado.']);
        exit;
    }

    // Verificar permisos para ver respuestas
    if ($current_user_role === 'editor') {
        $form_details = get_form_data_by_id_for_responses($formIdToGet);
        if (!$form_details || !isset($form_details['creator_id']) || $form_details['creator_id'] !== $current_user_id) {
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para ver las respuestas de este formulario.']);
            exit;
        }
    }
    // Owner y Admin pueden ver todas las respuestas

    $formIdSanitized = sanitize_filename_responses($formIdToGet);
    $filePath = DATA_DIR_RESPONSES . $formIdSanitized . '_responses.json';
    
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if ($content === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo de respuestas.']);
            exit;
        }
        $responses = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo json_encode(['success' => true, 'data' => $responses]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al decodificar las respuestas.']);
        }
    } else {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No hay respuestas para este formulario aún (archivo no existe).']);
    }


} elseif ($action === 'export_csv' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        die('Acceso no autorizado para exportar.');
    }

    $formIdToExport = $_GET['form_id'] ?? null;
    if (!$formIdToExport) {
        http_response_code(400);
        die('ID de formulario no proporcionado para exportar.');
    }
    
    $formIdSanitized = sanitize_filename_responses($formIdToExport);
    $formStructure = get_form_data_by_id_for_responses($formIdSanitized); // Usar la función que devuelve todo el form

    // Verificar permisos para exportar CSV
    if ($current_user_role === 'editor') {
        if (!$formStructure || !isset($formStructure['creator_id']) || $formStructure['creator_id'] !== $current_user_id) {
            http_response_code(403);
            die('No tienes permiso para exportar las respuestas de este formulario.');
        }
    }
    // Owner y Admin pueden exportar todas las respuestas

    if (!$formStructure || !isset($formStructure['fields'])) {
        http_response_code(404);
        die('Estructura del formulario no encontrada o inválida.');
    }
    $field_labels = array_map(function($field) {
        return $field['label'];
    }, $formStructure['fields']);

    $responsesFilePath = DATA_DIR_RESPONSES . $formIdSanitized . '_responses.json';
    $responsesData = [];
    if (file_exists($responsesFilePath)) {
        $content = file_get_contents($responsesFilePath);
        if ($content !== false) {
            $decoded_content = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_content)) {
                $responsesData = $decoded_content;
            }
        }
    }
    if (empty($responsesData)) {
        die('No hay respuestas para exportar para este formulario.');
    }

    $csvFileName = 'respuestas_' . $formIdSanitized . '_' . date('YmdHis') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $csvFileName . '"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    $headers = ['ID Envio', 'Fecha Envio'];
    foreach ($field_labels as $label) {
        $headers[] = $label; 
    }
    fputcsv($output, $headers);
    foreach ($responsesData as $response) {
        $row = [
            $response['submission_id'] ?? '',
            $response['submitted_at'] ?? ''
        ];
        foreach ($field_labels as $label) {
            $answer = $response['data'][$label] ?? '';
            if (is_array($answer)) {
                $row[] = implode(', ', $answer);
            } else {
                $row[] = $answer;
            }
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;

} else {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción no válida o método incorrecto.']);
}
?>
