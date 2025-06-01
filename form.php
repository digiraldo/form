<?php
// form.php (Página Pública)
$form_id = $_GET['id'] ?? null;
$form_data = null;
$error_message = '';
$success_message = '';
$disagreement_feedback_messages = []; 
$is_disagreement_submission = false; 
$form_expired = false; // Nueva bandera para el estado de caducidad
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === 'true';
$debug_info = [];

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['PHP_SELF']);
$script_path = ($script_path === '.' || $script_path === '/') ? '' : $script_path; 

define('API_FORMS_URL', $protocol . $host . $script_path . '/api/forms.php');
define('API_RESPONSES_URL', $protocol . $host . $script_path . '/api/responses.php');

if ($debug_mode) {
    $debug_info['API_FORMS_URL'] = API_FORMS_URL;
    $debug_info['API_RESPONSES_URL'] = API_RESPONSES_URL;
}

if (!$form_id) {
    $error_message = "No se especificó ningún formulario.";
} else {
    $url = API_FORMS_URL . '?action=get_public&id=' . urlencode($form_id);
    if ($debug_mode) $debug_info['form_load_url'] = $url;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($debug_mode) {
        $debug_info['form_load_http_code'] = $httpcode;
        $debug_info['form_load_curl_error'] = curl_error($ch);
        $debug_info['form_load_response_raw'] = $response;
    }
    curl_close($ch);

    if ($response === false) {
        $error_message = "Error al contactar la API para obtener el formulario. Verifique la URL de la API y la conectividad.";
    } elseif ($httpcode !== 200) {
        $api_error = json_decode($response, true);
        $error_message = "Formulario no encontrado o error de API: " . ($api_error['message'] ?? "Error desconocido (HTTP $httpcode)");
    } else {
        $api_response = json_decode($response, true);
        if ($debug_mode) $debug_info['form_load_api_response_decoded'] = $api_response;
        if (isset($api_response['success']) && $api_response['success'] && isset($api_response['data'])) {
            $form_data = $api_response['data'];

            // Verificar fecha de caducidad
            if (!empty($form_data['expiration_date'])) {
                try {
                    $expiration_date_obj = new DateTime($form_data['expiration_date']);
                    // Para comparar correctamente, la fecha de expiración se considera hasta el final del día.
                    // Es decir, si expira el 12, es válido hasta las 23:59:59 del 12.
                    // Por lo tanto, el formulario expira *después* de esa fecha.
                    $expiration_date_obj->setTime(23, 59, 59); 
                    $current_date_obj = new DateTime();

                    if ($current_date_obj > $expiration_date_obj) {
                        $form_expired = true;
                        $error_message = "Este formulario ha expirado el " . $expiration_date_obj->format('d/m/Y') . " y ya no acepta respuestas.";
                         if ($debug_mode) $debug_info['expiration_check'] = "Expirado. Actual: " . $current_date_obj->format('Y-m-d H:i:s') . ", Expira: " . $expiration_date_obj->format('Y-m-d H:i:s');
                    } else {
                         if ($debug_mode) $debug_info['expiration_check'] = "Vigente. Actual: " . $current_date_obj->format('Y-m-d H:i:s') . ", Expira: " . $expiration_date_obj->format('Y-m-d H:i:s');
                    }
                } catch (Exception $e) {
                    // Manejar error de formato de fecha si es necesario, aunque la API debería guardarlo bien.
                    if ($debug_mode) $debug_info['expiration_check_error'] = "Error al procesar fecha de caducidad: " . $e->getMessage();
                }
            }

        } else {
            $error_message = "No se pudieron cargar los datos del formulario: " . ($api_response['message'] ?? "Respuesta inválida de la API.");
        }
    }
}

// Procesar envío del formulario solo si no ha expirado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $form_data && !$form_expired) {
    // ... (resto del código de procesamiento de envío existente, sin cambios)
    if ($debug_mode) $debug_info['post_data_received'] = $_POST;

    $submission_data = ['form_id' => $form_id, 'submitted_at' => date('Y-m-d H:i:s'), 'fields' => []];
    $form_valid = true;
    $validation_errors = [];

    foreach ($form_data['fields'] as $field_struct) { 
        $field_post_name_base = 'field_' . ($field_struct['id'] ?? preg_replace("/[^a-zA-Z0-9_]/", "", $field_struct['label']));
        $value = null; 

        if ($field_struct['type'] === 'terms') {
            $field_post_name = $field_post_name_base . '_agreement_response';
            $value = $_POST[$field_post_name] ?? null; 
            if ($debug_mode) $debug_info['field_processing_terms'][$field_post_name] = ['expected_name' => $field_post_name, 'value_received' => $value, 'structure' => $field_struct];
        } elseif ($field_struct['type'] === 'accept_only_terms') {
            $field_post_name = $field_post_name_base;
            $value = isset($_POST[$field_post_name]) ? 'accepted' : null;
        } elseif ($field_struct['type'] === 'file') {
            $field_post_name = $field_post_name_base;
            if (isset($_FILES[$field_post_name]) && $_FILES[$field_post_name]['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0775, true); }
                $filename = uniqid('file_') . '_' . basename($_FILES[$field_post_name]['name']);
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES[$field_post_name]['tmp_name'], $target_path)) {
                    $value = $filename;
                } else {
                    $form_valid = false;
                    $validation_errors[$field_post_name] = "Error al subir el archivo para '" . htmlspecialchars($field_struct['label']) . "'.";
                }
            } elseif (!empty($field_struct['required'])) {
                $form_valid = false;
                $validation_errors[$field_post_name] = "El campo de archivo '" . htmlspecialchars($field_struct['label']) . "' es obligatorio.";
            }
        } elseif ($field_struct['type'] === 'birthdate') {
            $field_post_name = $field_post_name_base;
            $value = $_POST[$field_post_name] ?? null;
            if (!empty($field_struct['required']) && empty($value)) {
                $form_valid = false;
                $validation_errors[$field_post_name] = "El campo '" . htmlspecialchars($field_struct['label']) . "' es obligatorio.";
            }
            // Guardar la fecha original y la edad
            if (!empty($value)) {
                $birthdate = DateTime::createFromFormat('Y-m-d', $value);
                if ($birthdate) {
                    $today = new DateTime();
                    $age = $today->diff($birthdate)->y;
                    $submission_data['fields'][htmlspecialchars($field_struct['label'])] = $age; // Edad
                    $submission_data['fields'][htmlspecialchars($field_struct['label']) . ' (fecha)'] = $value; // Fecha original
                } else {
                    $form_valid = false;
                    $validation_errors[$field_post_name] = "Fecha de nacimiento inválida.";
                }
            }
        } elseif ($field_struct['type'] === 'downloadable') {
            $field_post_name = $field_post_name_base;
            $value = isset($field_struct['file_url']) ? $field_struct['file_url'] : '';
            // No se guarda nada en la respuesta, solo se muestra el enlace
        } else {
            $field_post_name = $field_post_name_base;
            $value = $_POST[$field_post_name] ?? null;
            if ($debug_mode) $debug_info['field_processing'][$field_post_name] = ['expected_name' => $field_post_name, 'value_received' => $value, 'structure' => $field_struct];
        }

        if (!empty($field_struct['required'])) {
            if ($field_struct['type'] === 'terms') {
                if (empty($value)) { 
                    $form_valid = false;
                    $validation_errors[$field_post_name] = "Debe seleccionar una opción para \"" . htmlspecialchars($field_struct['label']) . "\".";
                }
            } elseif (empty($value) && $field_struct['type'] !== 'checkbox') { 
                 $form_valid = false;
                 $validation_errors[$field_post_name] = "El campo \"" . htmlspecialchars($field_struct['label']) . "\" es obligatorio.";
            } 
        }

        if ($field_struct['type'] === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
             $form_valid = false;
             $validation_errors[$field_post_name] = "El correo electrónico para \"" . htmlspecialchars($field_struct['label']) . "\" no es válido.";
        }
        
        // Validaciones específicas
        if ($field_struct['type'] === 'accept_only_terms' && empty($value) && !empty($field_struct['required'])) {
            $form_valid = false;
            $validation_errors[$field_post_name] = "Debes aceptar los términos para continuar.";
        }
        if ($field_struct['type'] === 'birthdate' && !empty($value)) {
            $birthdate = DateTime::createFromFormat('Y-m-d', $value);
            if ($birthdate) {
                $today = new DateTime();
                $age = $today->diff($birthdate)->y;
                $submission_data['fields'][htmlspecialchars($field_struct['label'])] = $age; // Guardar solo la edad
            } else {
                $form_valid = false;
                $validation_errors[$field_post_name] = "Fecha de nacimiento inválida.";
            }
        } elseif ($field_struct['type'] === 'file') {
            $submission_data['fields'][htmlspecialchars($field_struct['label'])] = $value ?? '';
        } elseif ($field_struct['type'] === 'accept_only_terms') {
            $submission_data['fields'][htmlspecialchars($field_struct['label'])] = $value ?? '';
        } elseif (!in_array($field_struct['type'], ['birthdate', 'file', 'accept_only_terms'])) {
            if ($field_struct['type'] === 'terms') {
                $submission_data['fields'][htmlspecialchars($field_struct['label'])] = htmlspecialchars($value ?? 'not_selected');
                if ($value === 'disagree') {
                    $is_disagreement_submission = true; 
                    if (!empty($field_struct['disagreement_message'])) {
                        $disagreement_feedback_messages[] = htmlspecialchars($field_struct['label']) . ": " . htmlspecialchars($field_struct['disagreement_message']);
                    } else {
                        $disagreement_feedback_messages[] = htmlspecialchars($field_struct['label']) . ": Ha indicado que no está de acuerdo con estos términos.";
                    }
                }
            } elseif (is_array($value)) {
                $submission_data['fields'][htmlspecialchars($field_struct['label'])] = array_map('htmlspecialchars', $value);
            } else {
                $submission_data['fields'][htmlspecialchars($field_struct['label'])] = htmlspecialchars($value ?? '');
            }
        }
    }
    
    if ($debug_mode) $debug_info['submission_data_prepared'] = $submission_data;

    if ($form_valid) {
        if ($debug_mode) $debug_info['submission_api_url'] = API_RESPONSES_URL . '?action=submit';
        
        $ch_submit = curl_init();
        curl_setopt($ch_submit, CURLOPT_URL, API_RESPONSES_URL . '?action=submit');
        curl_setopt($ch_submit, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_submit, CURLOPT_POST, true);
        curl_setopt($ch_submit, CURLOPT_POSTFIELDS, json_encode($submission_data));
        curl_setopt($ch_submit, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch_submit, CURLOPT_TIMEOUT, 15);
        
        $submit_response_json = curl_exec($ch_submit);
        $submit_httpcode = curl_getinfo($ch_submit, CURLINFO_HTTP_CODE);
        if ($debug_mode) {
            $debug_info['submission_http_code'] = $submit_httpcode;
            $debug_info['submission_curl_error'] = curl_error($ch_submit);
            $debug_info['submission_response_raw'] = $submit_response_json;
        }
        curl_close($ch_submit);

        if ($submit_response_json === false) {
            $error_message = "Error al enviar el formulario a la API (cURL falló).";
        } else {
            $submit_response = json_decode($submit_response_json, true);
            if ($debug_mode) $debug_info['submission_response_decoded'] = $submit_response;
            
            if ($submit_httpcode === 200 && isset($submit_response['success']) && $submit_response['success']) {
                $base_success_message = $submit_response['message'] ?? "¡Formulario enviado con éxito!";
                if ($is_disagreement_submission && !empty($disagreement_feedback_messages)) {
                    $success_message = "Su respuesta ha sido registrada. <br><strong>Información importante debido a su selección:</strong><ul>";
                    foreach ($disagreement_feedback_messages as $msg) {
                        $success_message .= "<li>" . $msg . "</li>";
                    }
                    $success_message .= "</ul>";
                } else {
                    $success_message = $base_success_message;
                }
            } else {
                $error_message = "Error al procesar el formulario: " . ($submit_response['message'] ?? "Respuesta inesperada de la API (HTTP $submit_httpcode)");
            }
        }
    } else {
        $error_message = "Por favor, corrige los errores en el formulario.";
        if ($debug_mode) $debug_info['validation_errors'] = $validation_errors;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $form_expired) {
    // Si se intenta enviar un formulario expirado, reforzar el mensaje de error.
    $error_message = "Este formulario ha expirado y ya no acepta respuestas.";
}


?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $form_data ? htmlspecialchars($form_data['title']) : 'Formulario'; ?> - Custom Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: var(--bs-light-bg-subtle); padding-top: 2rem; padding-bottom: 2rem; }
        .form-container { max-width: 700px; margin: auto; background-color: var(--bs-body-bg); padding: 2rem; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
        .form-header img { max-height: 70px; margin-bottom: 1rem; }
        .debug-info { margin-top: 2rem; padding: 1rem; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.25rem; font-size: 0.8rem; white-space: pre-wrap; word-wrap: break-word; }
        .terms-text-display { padding: 0.5rem; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 0.25rem; margin-bottom: 0.5rem; max-height: 150px; overflow-y: auto;}
        fieldset[disabled] .form-control, fieldset[disabled] .form-select, fieldset[disabled] .form-check-input {
            background-color: #e9ecef; /* Estilo para campos deshabilitados */
            opacity: 0.7;
        }
        .theme-toggle-btn {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            background-color: var(--bs-body-bg);
            border: none;
            border-radius: 50%;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <?php if ($debug_mode && !empty($debug_info)): ?>
                <div class="alert alert-warning debug-info">
                    <h5 class="alert-heading">Información de Depuración</h5>
                    <pre><?php echo htmlspecialchars(print_r($debug_info, true)); ?></pre>
                </div>
            <?php endif; ?>

            <?php if ($error_message && !$success_message): // Mostrar errores generales o de expiración ?>
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> ¡Error!</h4>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <?php if (!empty($validation_errors)): ?>
                        <hr>
                        <ul class="mb-0">
                            <?php foreach ($validation_errors as $val_err): ?>
                                <li><?php echo htmlspecialchars($val_err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): 
                $alert_class = $is_disagreement_submission ? 'alert-warning' : 'alert-success';
                $alert_icon = $is_disagreement_submission ? 'fa-exclamation-triangle' : 'fa-check-circle';
                $alert_heading_text = $is_disagreement_submission ? '¡Atención!' : '¡Éxito!';
            ?>
                <div class="alert <?php echo $alert_class; ?> text-center" role="alert">
                    <h4 class="alert-heading"><i class="fas <?php echo $alert_icon; ?>"></i> <?php echo $alert_heading_text; ?></h4>
                    <p><?php echo $success_message; ?></p>
                    <?php if (!$is_disagreement_submission): ?>
                        <p>Gracias por completar el formulario.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($form_data && !$success_message && !$form_expired): // Mostrar formulario solo si hay datos, no hay mensaje de éxito y NO ha expirado ?>
                <div class="form-header text-center">
                    <?php if (!empty($form_data['logoUrl'])): ?>
                        <img src="<?php echo htmlspecialchars($form_data['logoUrl']); ?>" alt="<?php echo htmlspecialchars($form_data['companyName'] ?? 'Logo'); ?>" class="img-fluid rounded">
                    <?php endif; ?>
                    <?php if (!empty($form_data['companyName'])): ?>
                        <p class="lead text-muted"><?php echo htmlspecialchars($form_data['companyName']); ?></p>
                    <?php endif; ?>
                    <h1 class="h3 mb-3"><?php echo htmlspecialchars($form_data['title']); ?></h1>
                    <?php if (!empty($form_data['description'])): ?>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($form_data['description'])); ?></p>
                    <?php endif; ?>
                    <hr>
                </div>

                <form method="POST" action="form.php?id=<?php echo htmlspecialchars($form_id); ?><?php echo $debug_mode ? '&debug=true' : ''; ?>" id="publicForm" enctype="multipart/form-data">
                    <fieldset <?php if ($form_expired) echo 'disabled'; // Deshabilitar todos los campos si ha expirado ?>>
                        <?php foreach ($form_data['fields'] as $index => $field_struct): 
                            $field_html_id_attr_base = 'field_html_' . ($field_struct['id'] ?? preg_replace("/[^a-zA-Z0-9_]/", "", $field_struct['label']) . '_' . $index);
                            $field_post_name_base = 'field_' . ($field_struct['id'] ?? preg_replace("/[^a-zA-Z0-9_]/", "", $field_struct['label']));
                            $field_post_name_for_validation = ($field_struct['type'] === 'terms') ? $field_post_name_base . '_agreement_response' : $field_post_name_base;
                            $is_invalid = isset($validation_errors[$field_post_name_for_validation]) ? 'is-invalid' : '';
                        ?>
                            <div class="form-modern-card mb-3">
                                <label for="<?php echo $field_html_id_attr_base; ?>" class="form-label fw-bold">
                                    <?php echo htmlspecialchars($field_struct['label']); ?>
                                    <?php if (!empty($field_struct['required'])): ?> <span class="text-danger">*</span><?php endif; ?>
                                </label>

                                <?php switch ($field_struct['type']):
                                    case 'text': 
                                        $field_post_name = $field_post_name_base; ?>
                                        <input type="text" class="form-control <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" placeholder="<?php echo htmlspecialchars($field_struct['placeholder'] ?? ''); ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> value="<?php echo htmlspecialchars($_POST[$field_post_name] ?? ''); ?>">
                                        <?php break;
                                    case 'textarea': 
                                        $field_post_name = $field_post_name_base; ?>
                                        <textarea class="form-control <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" rows="3" placeholder="<?php echo htmlspecialchars($field_struct['placeholder'] ?? ''); ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?>><?php echo htmlspecialchars($_POST[$field_post_name] ?? ''); ?></textarea>
                                        <?php break;
                                    case 'email': 
                                        $field_post_name = $field_post_name_base; ?>
                                        <input type="email" class="form-control <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" placeholder="<?php echo htmlspecialchars($field_struct['placeholder'] ?? 'correo@ejemplo.com'); ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> value="<?php echo htmlspecialchars($_POST[$field_post_name] ?? ''); ?>">
                                        <?php break;
                                    case 'tel': 
                                        $field_post_name = $field_post_name_base; ?>
                                        <input type="tel" class="form-control <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" placeholder="<?php echo htmlspecialchars($field_struct['placeholder'] ?? ''); ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> value="<?php echo htmlspecialchars($_POST[$field_post_name] ?? ''); ?>">
                                        <?php break;
                                    case 'date': 
                                        $field_post_name = $field_post_name_base; ?>
                                        <input type="date" class="form-control <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> value="<?php echo htmlspecialchars($_POST[$field_post_name] ?? ''); ?>">
                                        <?php break;
                                    case 'birthdate': 
                                        $field_post_name = $field_post_name_base; ?>
                                        <input type="date" class="form-control <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> value="<?php echo htmlspecialchars($_POST[$field_post_name] ?? ''); ?>">
                                        <small class="form-text text-muted">La edad se calculará automáticamente.</small>
                                        <?php break;
                                    case 'radio': 
                                        $field_post_name = $field_post_name_base; 
                                        // Soporte para string de opciones separado por saltos de línea
                                        $options = $field_struct['options'] ?? [];
                                        if (!is_array($options) && is_string($options)) {
                                            $options = preg_split('/\r?\n/', $options);
                                        }
                                        if (!empty($options) && is_array($options)):
                                            foreach ($options as $opt_idx => $option): 
                                                $option_val = htmlspecialchars($option);
                                                $option_html_id = $field_html_id_attr_base . '_' . $opt_idx;
                                                $checked = (isset($_POST[$field_post_name]) && $_POST[$field_post_name] === $option_val) ? 'checked' : '';
                                            ?>
                                                <div class="form-check">
                                                    <input class="form-check-input <?php echo $is_invalid; ?>" type="radio" name="<?php echo $field_post_name; ?>" id="<?php echo $option_html_id; ?>" value="<?php echo $option_val; ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> <?php echo $checked; ?>>
                                                    <label class="form-check-label" for="<?php echo $option_html_id; ?>">
                                                        <?php echo $option_val; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; 
                                        endif;
                                        break;
                                    case 'checkbox': 
                                        $field_post_name = $field_post_name_base; 
                                        $options = $field_struct['options'] ?? [];
                                        if (!is_array($options) && is_string($options)) {
                                            $options = preg_split('/\r?\n/', $options);
                                        }
                                        if (!empty($options) && is_array($options)) {
                                            foreach ($options as $opt_idx => $option):
                                                $option_val = htmlspecialchars($option);
                                                $option_html_id = $field_html_id_attr_base . '_' . $opt_idx;
                                                $checkbox_post_name = $field_post_name . '[]'; 
                                                $checked = (isset($_POST[$field_post_name]) && is_array($_POST[$field_post_name]) && in_array($option_val, $_POST[$field_post_name])) ? 'checked' : '';
                                            ?>
                                                <div class="form-check">
                                                    <input class="form-check-input <?php echo $is_invalid; ?>" type="checkbox" name="<?php echo $checkbox_post_name; ?>" id="<?php echo $option_html_id; ?>" value="<?php echo $option_val; ?>" <?php echo $checked; ?>>
                                                    <label class="form-check-label" for="<?php echo $option_html_id; ?>">
                                                        <?php echo $option_val; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach;
                                        }
                                        break;
                                    case 'select': 
                                        $field_post_name = $field_post_name_base; 
                                        $options = $field_struct['options'] ?? [];
                                        if (!is_array($options) && is_string($options)) {
                                            $options = preg_split('/\r?\n/', $options);
                                        }
                                        ?>
                                        <select class="form-select <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name . (!empty($field_struct['multiple_select']) ? '[]' : ''); ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> <?php echo !empty($field_struct['multiple_select']) ? 'multiple' : ''; ?>>
                                            <option value="">Selecciona una opción</option>
                                            <?php if (!empty($options) && is_array($options)): 
                                                foreach ($options as $option): 
                                                    $option_val = htmlspecialchars($option);
                                                    $selected = '';
                                                    if (!empty($field_struct['multiple_select']) && isset($_POST[$field_post_name]) && is_array($_POST[$field_post_name]) && in_array($option_val, $_POST[$field_post_name])) {
                                                        $selected = 'selected';
                                                    } elseif (isset($_POST[$field_post_name]) && $_POST[$field_post_name] == $option_val) {
                                                        $selected = 'selected';
                                                    }
                                            ?>
                                                <option value="<?php echo $option_val; ?>" <?php echo $selected; ?>><?php echo $option_val; ?></option>
                                            <?php endforeach; 
                                            endif; ?>
                                        </select>
                                        <?php break;
                                    case 'terms': 
                                        $terms_text = $field_struct['terms_text'] ?? 'Acepto los términos y condiciones.';
                                        $agreement_response_post_name = $field_post_name_base . '_agreement_response';
                                        $current_agreement_value = $_POST[$agreement_response_post_name] ?? '';
                                        ?>
                                        <div class="terms-text-display mb-2">
                                            <?php echo nl2br(htmlspecialchars($terms_text)); ?>
                                        </div>
                                        <div class="mt-2 <?php echo $is_invalid ? 'is-invalid' : ''; ?>">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input <?php echo $is_invalid; ?>" type="radio" name="<?php echo $agreement_response_post_name; ?>" 
                                                       id="<?php echo $field_html_id_attr_base; ?>_agree" value="agree" 
                                                       <?php if(!empty($field_struct['required'])) echo 'required'; ?>
                                                       <?php echo ($current_agreement_value === 'agree') ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="<?php echo $field_html_id_attr_base; ?>_agree">Estoy de acuerdo y me someto a tal</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input <?php echo $is_invalid; ?>" type="radio" name="<?php echo $agreement_response_post_name; ?>" 
                                                       id="<?php echo $field_html_id_attr_base; ?>_disagree" value="disagree"
                                                       <?php if(!empty($field_struct['required'])) echo 'required'; ?>
                                                       <?php echo ($current_agreement_value === 'disagree') ? 'checked' : ''; ?>>
                                                <label class="form-check-label small" for="<?php echo $field_html_id_attr_base; ?>_disagree">No estoy de acuerdo</label>
                                            </div>
                                        </div>
                                        <?php break;
                                    case 'accept_only_terms': 
                                        $field_post_name = $field_post_name_base; 
                                        $checked = isset($_POST[$field_post_name]) && $_POST[$field_post_name] === 'on' ? 'checked' : '';
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input <?php echo $is_invalid; ?>" type="checkbox" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> <?php echo $checked; ?>>
                                            <label class="form-check-label" for="<?php echo $field_html_id_attr_base; ?>">
                                                <?php echo htmlspecialchars($field_struct['terms_text'] ?? 'Acepto los términos y condiciones.'); ?>
                                            </label>
                                        </div>
                                        <?php break;
                                    case 'file': 
                                        $field_post_name = $field_post_name_base;
                                        $uploaded_file = $_FILES[$field_post_name]['name'] ?? '';
                                        $last_uploaded = '';
                                        if (!empty($uploaded_file) && isset($_FILES[$field_post_name]) && $_FILES[$field_post_name]['error'] === UPLOAD_ERR_OK) {
                                            $last_uploaded = basename($_FILES[$field_post_name]['name']);
                                        } elseif (!empty($_POST['last_uploaded_' . $field_post_name])) {
                                            $last_uploaded = htmlspecialchars($_POST['last_uploaded_' . $field_post_name]);
                                        }
                                        // Input para subir nuevo archivo
                                        ?>
                                        <label class="form-label">Cargar documento</label>
                                        <input type="file" class="form-control <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar,.txt">
                                        <?php if ($last_uploaded): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Archivo subido: <?php echo $last_uploaded; ?></span>
                                                <a href="uploads/<?php echo $last_uploaded; ?>" class="btn btn-outline-primary btn-sm ms-2" target="_blank"><i class="fas fa-download"></i> Descargar archivo</a>
                                            </div>
                                        <?php endif; ?>
                                        <small class="form-text text-muted">Puedes subir un archivo (PDF, imagen, etc.).</small>
                                        <?php break;
                                    case 'downloadable':
                                        $field_post_name = $field_post_name_base;
                                        $file_url = isset($field_struct['file_url']) ? $field_struct['file_url'] : '';
                                        $file_uploaded = isset($field_struct['file_uploaded']) ? $field_struct['file_uploaded'] : '';
                                        $instructions = isset($field_struct['instructions']) ? $field_struct['instructions'] : '';
                                        ?>
                                        <label class="form-label">Descargar documento</label>
                                        <?php if ($instructions): ?>
                                            <div class="mb-2 text-muted small"><i class="fas fa-info-circle me-1"></i><?php echo nl2br(htmlspecialchars($instructions)); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($file_uploaded)): ?>
                                            <a href="downloads/<?php echo rawurlencode($file_uploaded); ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="fas fa-download"></i> Descargar archivo
                                            </a>
                                            <span class="badge bg-success ms-2">Archivo subido</span>
                                        <?php elseif (!empty($file_url)): ?>
                                            <a href="<?php echo htmlspecialchars($file_url); ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="fas fa-download"></i> Descargar documento
                                            </a>
                                            <span class="badge bg-info ms-2">Enlace externo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No hay documento disponible</span>
                                        <?php endif; ?>
                                        <small class="form-text text-muted">Descarga el documento proporcionado por el creador del formulario.</small>
                                        <?php break;
                                    case 'number':
                                        $field_post_name = $field_post_name_base;
                                        $min = isset($field_struct['min']) ? 'min="' . htmlspecialchars($field_struct['min']) . '"' : '';
                                        $max = isset($field_struct['max']) ? 'max="' . htmlspecialchars($field_struct['max']) . '"' : '';
                                        ?>
                                        <input type="number" class="form-control <?php echo $is_invalid; ?>" id="<?php echo $field_html_id_attr_base; ?>" name="<?php echo $field_post_name; ?>" placeholder="<?php echo htmlspecialchars($field_struct['placeholder'] ?? ''); ?>" <?php echo !empty($field_struct['required']) ? 'required' : ''; ?> <?php echo $min; ?> <?php echo $max; ?> value="<?php echo htmlspecialchars($_POST[$field_post_name] ?? ''); ?>">
                                        <?php break;
                                    case 'image':
                                        $image_title = $field_struct['image_title'] ?? '';
                                        $image_url = '';
                                        if (!empty($field_struct['image_file_uploaded'])) {
                                            $image_url = 'uploads/' . htmlspecialchars($field_struct['image_file_uploaded']);
                                        } elseif (!empty($field_struct['image_file_url'])) {
                                            $image_url = htmlspecialchars($field_struct['image_file_url']);
                                        }
                                        ?>
                                        <div class="form-group mb-4 text-center">
                                            <?php if ($image_url): ?>
                                                <div class="form-image-public-wrapper d-flex flex-column align-items-center justify-content-center" style="width:100%;">
                                                    <img src="<?php echo $image_url; ?>" alt="Imagen" class="form-image-field-public img-fluid rounded shadow-sm" style="width:100%;max-width:100%;height:auto;object-fit:contain;display:block;">
                                                    <?php if (!empty($image_title)): ?>
                                                        <div class="form-image-caption mt-2 text-center w-100" style="font-size:1.05rem;font-weight:500;opacity:0.85;"> <?php echo htmlspecialchars($image_title); ?> </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center text-muted">No hay imagen disponible.</div>
                                            <?php endif; ?>
                                        </div>
                                        <?php break;
                                    default: ?>
                                        <p class="text-danger">Tipo de campo desconocido: <?php echo htmlspecialchars($field_struct['type']); ?></p>
                                <?php endswitch; ?>
                                <?php 
                                $error_key_to_check = ($field_struct['type'] === 'terms') ? $field_post_name_base . '_agreement_response' : $field_post_name_base;
                                if (isset($validation_errors[$error_key_to_check])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo htmlspecialchars($validation_errors[$error_key_to_check]); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" <?php if ($form_expired) echo 'disabled'; ?>>
                                <i class="fas fa-paper-plane me-2"></i>Enviar Respuestas
                            </button>
                        </div>
                    </fieldset>
                </form>
            <?php elseif (!$success_message && !$form_expired && !$error_message): // Si no hay form_data y no hay error inicial (ej. ID no provisto) y no ha expirado ?>
                <div class="alert alert-warning text-center" role="alert">
                    Cargando formulario... o no se pudo encontrar el formulario especificado.
                </div>
            <?php endif; ?>
            
            <?php // Mensaje si el formulario cargó pero ya está expirado (y no se ha enviado un POST)
            if ($form_data && $form_expired && $_SERVER['REQUEST_METHOD'] !== 'POST' && !$success_message): ?>
                 <div class="alert alert-warning text-center" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-clock"></i> Formulario Expirado</h4>
                    <p>Este formulario ya no acepta respuestas.</p>
                    <?php if (!empty($form_data['expiration_date'])): ?>
                        <p class="small mb-0">Fecha de caducidad: <?php echo htmlspecialchars( (new DateTime($form_data['expiration_date']))->format('d/m/Y') ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <button class="theme-toggle-btn" id="themeToggleBtn" type="button" aria-label="Cambiar tema">
        <i class="fas fa-moon" id="themeToggleIcon"></i>
    </button>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const storedTheme = localStorage.getItem('theme');
            if (storedTheme) {
                document.documentElement.setAttribute('data-bs-theme', storedTheme);
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
            }
            // Botón sol/luna
            function updateThemeIcon() {
                const theme = document.documentElement.getAttribute('data-bs-theme');
                const icon = document.getElementById('themeToggleIcon');
                if (theme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                updateThemeIcon();
                const btn = document.getElementById('themeToggleBtn');
                if (btn) {
                    btn.addEventListener('click', function() {
                        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                        document.documentElement.setAttribute('data-bs-theme', newTheme);
                        localStorage.setItem('theme', newTheme);
                        updateThemeIcon();
                    });
                }
            });
        })();
    </script>
</body>
</html>
