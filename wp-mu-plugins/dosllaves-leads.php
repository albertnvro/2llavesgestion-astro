<?php
/**
 * Plugin Name: DosLlaves Leads
 * Description: Endpoint REST para recibir leads de valoración desde la web Astro.
 * Version: 1.1.0
 */

defined('ABSPATH') || exit;

const DOSLLAVES_ADMIN_EMAIL = 'info@indexar.es';
const DOSLLAVES_FROM_EMAIL  = 'info@indexar.es';
const DOSLLAVES_FROM_NAME   = 'DosLlavesGestión';

add_action('init', function () {
    register_post_type('dosllaves_lead', [
        'labels' => [
            'name' => 'Leads DosLlaves',
            'singular_name' => 'Lead DosLlaves',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-building',
        'supports' => ['title', 'editor', 'custom-fields'],
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route('dosllaves/v1', '/lead', [
        'methods' => 'POST',
        'callback' => 'dosllaves_receive_lead',
        'permission_callback' => '__return_true',
    ]);
});

function dosllaves_clean($value) {
    return sanitize_text_field((string) $value);
}

function dosllaves_receive_lead(WP_REST_Request $request) {
    $data = $request->get_json_params();

    if (!is_array($data)) {
        return new WP_REST_Response([
            'ok' => false,
            'message' => 'Solicitud inválida.',
        ], 400);
    }

    // Honeypot anti-spam
    if (!empty($data['website'])) {
        return new WP_REST_Response(['ok' => true], 200);
    }

    // Rate limit básico por IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'dosllaves_lead_' . md5($ip);

    if (get_transient($rate_key)) {
        return new WP_REST_Response([
            'ok' => false,
            'message' => 'Demasiados envíos seguidos.',
        ], 429);
    }

    set_transient($rate_key, 1, 60);

    $name = dosllaves_clean($data['nombre'] ?? '');
    $phone = dosllaves_clean($data['telefono'] ?? $data['phone'] ?? '');
    $phone = preg_replace('/\D+/', '', $phone);
    $email = sanitize_email($data['email'] ?? '');

    $address = dosllaves_clean($data['address'] ?? '');
    $place_id = dosllaves_clean($data['place_id'] ?? '');
    $lat = dosllaves_clean($data['lat'] ?? '');
    $lng = dosllaves_clean($data['lng'] ?? '');
    $situacion = dosllaves_clean($data['situacion'] ?? '');
    $objetivo = dosllaves_clean($data['objetivo'] ?? '');
    $message = sanitize_textarea_field($data['mensaje'] ?? '');
    $privacy = dosllaves_clean($data['privacy'] ?? '');
    $privacy_at = dosllaves_clean($data['privacy_accepted_at'] ?? '');

    if (!$name || !$phone || !$email) {
        return new WP_REST_Response([
            'ok' => false,
            'message' => 'Nombre, teléfono y email son obligatorios.',
        ], 400);
    }

    if (!preg_match('/^\d{9,15}$/', $phone)) {
        return new WP_REST_Response([
            'ok' => false,
            'message' => 'El teléfono no es válido.',
        ], 400);
    }

    $started_at = isset($data['form_started_at']) ? (int) $data['form_started_at'] : 0;
    if ($started_at > 0) {
        $now_ms = (int) round(microtime(true) * 1000);
        $elapsed = $now_ms - $started_at;

        if ($elapsed < 3000) {
            return new WP_REST_Response([
                'ok' => false,
                'message' => 'Solicitud no válida.',
            ], 400);
        }
    }

    if (preg_match_all('/https?:\/\/|www\./i', $message, $matches) && count($matches[0]) > 2) {
        return new WP_REST_Response([
            'ok' => false,
            'message' => 'El mensaje contiene demasiados enlaces.',
        ], 400);
    }

    if (!is_email($email)) {
        return new WP_REST_Response([
            'ok' => false,
            'message' => 'El email no es válido.',
        ], 400);
    }

    if (!$privacy) {
        return new WP_REST_Response([
            'ok' => false,
            'message' => 'Debes aceptar la política de privacidad.',
        ], 400);
    }

    $lead_lines = [
        'Nombre: ' . $name,
        'Teléfono: ' . $phone,
        'Email: ' . $email,
        'Dirección: ' . $address,
        'Place ID: ' . $place_id,
        'Lat/Lng: ' . $lat . ', ' . $lng,
        'Situación: ' . $situacion,
        'Objetivo: ' . $objetivo,
        'Mensaje: ' . $message,
        'Privacidad: ' . $privacy,
        'Aceptada en: ' . $privacy_at,
        'IP: ' . $ip,
        'Fecha WP: ' . current_time('mysql'),
    ];

    $lead_text = implode("\n", $lead_lines);

    $post_id = wp_insert_post([
        'post_type' => 'dosllaves_lead',
        'post_status' => 'publish',
        'post_title' => 'Lead - ' . $name . ' - ' . current_time('mysql'),
        'post_content' => $lead_text,
    ]);

    if ($post_id) {
        foreach ($data as $key => $value) {
            update_post_meta(
                $post_id,
                sanitize_key($key),
                is_scalar($value) ? sanitize_text_field((string) $value) : wp_json_encode($value)
            );
        }
    }

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . DOSLLAVES_FROM_NAME . ' <' . DOSLLAVES_FROM_EMAIL . '>',
    ];

    $admin_headers = $headers;
    $admin_headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';

    $admin_subject = 'Nuevo lead de valoración - DosLlavesGestión';
    $admin_body = "Nuevo lead recibido desde la web:\n\n" . $lead_text;

    wp_mail(DOSLLAVES_ADMIN_EMAIL, $admin_subject, $admin_body, $admin_headers);

    $client_subject = 'Hemos recibido tu solicitud - DosLlavesGestión';
    $client_body = "Hola " . $name . ",\n\n"
        . "Hemos recibido tu solicitud de valoración. Pronto nos pondremos en contacto contigo para revisar tu inmueble y orientarte sobre la mejor modelo.\n\n"
        . "Resumen de tu solicitud:\n\n"
        . "Dirección: " . $address . "\n"
        . "Situación: " . $situacion . "\n"
        . "Objetivo: " . $objetivo . "\n"
        . "Teléfono: " . $phone . "\n"
        . "Email: " . $email . "\n\n"
        . "Gracias por contactar con DosLlavesGestión.\n\n"
        . "Un saludo,\n"
        . "Equipo DosLlavesGestión";

    wp_mail($email, $client_subject, $client_body, $headers);

    return new WP_REST_Response([
        'ok' => true,
        'lead_id' => $post_id,
    ], 200);
}


/* =========================================================
 * Admin: mostrar datos del lead en una caja clara
 * ========================================================= */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'dosllaves_lead_details',
        'Datos del lead',
        'dosllaves_render_lead_details_box',
        'dosllaves_lead',
        'normal',
        'high'
    );
});

function dosllaves_render_lead_details_box($post): void {
    $fields = [
        'nombre' => 'Nombre',
        'telefono' => 'Teléfono',
        'email' => 'Email',
        'address' => 'Zona / Dirección',
        'situacion' => 'Situación',
        'objetivo' => 'Objetivo',
        'mensaje' => 'Mensaje',
        'privacy' => 'Privacidad',
        'privacy_accepted_at' => 'Aceptada en',
    ];

    echo '<table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;">';
    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr>';
        echo '<th style="width:220px;text-align:left;padding:12px;border-bottom:1px solid #e5e7eb;background:#f8fafc;color:#344054;">' . esc_html($label) . '</th>';
        echo '<td style="padding:12px;border-bottom:1px solid #e5e7eb;color:#101828;">' . nl2br(esc_html($value ?: '-')) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

add_filter('manage_dosllaves_lead_posts_columns', function ($columns) {
    return [
        'cb' => $columns['cb'],
        'title' => 'Lead',
        'nombre' => 'Nombre',
        'telefono' => 'Teléfono',
        'email' => 'Email',
        'objetivo' => 'Objetivo',
        'date' => 'Fecha',
    ];
});

add_action('manage_dosllaves_lead_posts_custom_column', function ($column, $post_id) {
    if (in_array($column, ['nombre', 'telefono', 'email', 'objetivo'], true)) {
        echo esc_html(get_post_meta($post_id, $column, true) ?: '-');
    }
}, 10, 2);
