<?php
/**
 * Plugin Name: DosLlaves CMS
 * Description: Ajustes administrables para SEO, páginas legales y código en head para la web Astro.
 * Version: 1.0.0
 * Author: Indexar
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =========================================================
 * Defaults
 * ========================================================= */

function dlcms_pages(): array {
    return [
        'home' => ['label' => 'Home', 'path' => '/'],
        'quienes_somos' => ['label' => 'Quiénes somos', 'path' => '/quienes-somos/'],
        'contacto' => ['label' => 'Contacto', 'path' => '/contacto/'],
        'recursos' => ['label' => 'Recursos arrendadores', 'path' => '/recursos-arrendadores/'],
        'privacidad' => ['label' => 'Política de privacidad', 'path' => '/politica-de-privacidad/'],
        'aviso_legal' => ['label' => 'Aviso legal', 'path' => '/aviso-legal/'],
        'gestion_integral' => ['label' => 'Gestión integral del alquiler', 'path' => '/servicios/gestion-integral-alquiler/'],
        'analisis_rentabilidad' => ['label' => 'Análisis de rentabilidad', 'path' => '/servicios/analisis-rentabilidad/'],
        'alquiler_habitaciones' => ['label' => 'Alquiler por habitaciones', 'path' => '/servicios/alquiler-por-habitaciones/'],
        'reformas_roi' => ['label' => 'Reformas orientadas a ROI', 'path' => '/servicios/reformas-orientadas-roi/'],
        'comercializacion_inquilinos' => ['label' => 'Comercialización e inquilinos', 'path' => '/servicios/comercializacion-inquilinos/'],
        'calculadora_antigua' => ['label' => 'Calculadora antigua / redirección', 'path' => '/calculadora-rentabilidad-alquiler/'],
    ];
}

function dlcms_get_seo_options(): array {
    $saved = get_option('dlcms_seo_options', []);

    if (!is_array($saved)) {
        $saved = [];
    }

    $defaults = [
        'global_head_code' => '',
        'pages' => [],
    ];

    foreach (dlcms_pages() as $key => $page) {
        $defaults['pages'][$page['path']] = [
            'label' => $page['label'],
            'path' => $page['path'],
            'title' => '',
            'description' => '',
            'robots' => 'index,follow',
            'head_code' => '',
        ];
    }

    return array_replace_recursive($defaults, $saved);
}

function dlcms_get_legal_options(): array {
    $saved = get_option('dlcms_legal_options', []);

    if (!is_array($saved)) {
        $saved = [];
    }

    return array_merge([
        'privacy_policy' => '',
        'legal_notice' => '',
    ], $saved);
}

/* =========================================================
 * Admin menu
 * ========================================================= */

add_action('admin_menu', function () {
    add_menu_page(
        'DosLlaves CMS',
        'DosLlaves CMS',
        'manage_options',
        'dlcms-seo',
        'dlcms_render_seo_page',
        'dashicons-admin-site-alt3',
        24
    );

    add_submenu_page(
        'dlcms-seo',
        'SEO',
        'SEO',
        'manage_options',
        'dlcms-seo',
        'dlcms_render_seo_page'
    );

    add_submenu_page(
        'dlcms-seo',
        'Legal',
        'Legal',
        'manage_options',
        'dlcms-legal',
        'dlcms_render_legal_page'
    );
});

/* =========================================================
 * SEO page
 * ========================================================= */

function dlcms_render_seo_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = dlcms_get_seo_options();

    if (
        isset($_POST['dlcms_seo_nonce']) &&
        wp_verify_nonce($_POST['dlcms_seo_nonce'], 'dlcms_save_seo')
    ) {
        $posted_pages = $_POST['pages'] ?? [];
        $new_pages = [];

        foreach (dlcms_pages() as $key => $page) {
            $path = $page['path'];
            $posted = $posted_pages[$key] ?? [];

            $robots = sanitize_text_field(wp_unslash($posted['robots'] ?? 'index,follow'));

            if (!in_array($robots, ['index,follow', 'noindex,nofollow'], true)) {
                $robots = 'index,follow';
            }

            $new_pages[$path] = [
                'label' => $page['label'],
                'path' => $path,
                'title' => sanitize_text_field(wp_unslash($posted['title'] ?? '')),
                'description' => sanitize_textarea_field(wp_unslash($posted['description'] ?? '')),
                'robots' => $robots,
                'head_code' => wp_unslash($posted['head_code'] ?? ''),
            ];
        }

        $options = [
            'global_head_code' => wp_unslash($_POST['global_head_code'] ?? ''),
            'pages' => $new_pages,
        ];

        update_option('dlcms_seo_options', $options);

        echo '<div class="updated notice"><p>Ajustes SEO guardados correctamente.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>DosLlaves CMS · SEO</h1>

        <p>
            Edita el title, meta description, robots y código adicional en head para las páginas principales.
            En una web Astro estática, estos cambios se aplican al volver a compilar y subir la web.
        </p>

        <form method="post">
            <?php wp_nonce_field('dlcms_save_seo', 'dlcms_seo_nonce'); ?>

            <h2>Código global en head</h2>
            <p>
                Pega aquí scripts o verificaciones globales: Google Search Console, Analytics, Tag Manager,
                píxeles, código de plugins externos de cookies, etc.
            </p>

            <textarea
                name="global_head_code"
                rows="8"
                style="width:100%;font-family:monospace;"
            ><?php echo esc_textarea($options['global_head_code'] ?? ''); ?></textarea>

            <hr style="margin:32px 0;">

            <h2>Páginas</h2>

            <?php foreach (dlcms_pages() as $key => $page) :
                $current = $options['pages'][$page['path']] ?? [];
                ?>
                <details style="margin:0 0 16px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;" <?php echo $key === 'home' ? 'open' : ''; ?>>
                    <summary style="cursor:pointer;font-weight:700;font-size:16px;">
                        <?php echo esc_html($page['label']); ?>
                        <code style="margin-left:8px;"><?php echo esc_html($page['path']); ?></code>
                    </summary>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">Meta title</th>
                            <td>
                                <input
                                    type="text"
                                    name="pages[<?php echo esc_attr($key); ?>][title]"
                                    value="<?php echo esc_attr($current['title'] ?? ''); ?>"
                                    style="width:100%;max-width:760px;"
                                >
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Meta description</th>
                            <td>
                                <textarea
                                    name="pages[<?php echo esc_attr($key); ?>][description]"
                                    rows="3"
                                    style="width:100%;max-width:760px;"
                                ><?php echo esc_textarea($current['description'] ?? ''); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Indexación</th>
                            <td>
                                <select name="pages[<?php echo esc_attr($key); ?>][robots]">
                                    <option value="index,follow" <?php selected($current['robots'] ?? 'index,follow', 'index,follow'); ?>>
                                        Index, follow
                                    </option>
                                    <option value="noindex,nofollow" <?php selected($current['robots'] ?? 'index,follow', 'noindex,nofollow'); ?>>
                                        Noindex, nofollow
                                    </option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Código extra en head</th>
                            <td>
                                <textarea
                                    name="pages[<?php echo esc_attr($key); ?>][head_code]"
                                    rows="5"
                                    style="width:100%;max-width:760px;font-family:monospace;"
                                ><?php echo esc_textarea($current['head_code'] ?? ''); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </details>
            <?php endforeach; ?>

            <?php submit_button('Guardar SEO'); ?>
        </form>
    </div>
    <?php
}

/* =========================================================
 * Legal page
 * ========================================================= */

function dlcms_render_legal_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = dlcms_get_legal_options();

    if (
        isset($_POST['dlcms_legal_nonce']) &&
        wp_verify_nonce($_POST['dlcms_legal_nonce'], 'dlcms_save_legal')
    ) {
        $options = [
            'privacy_policy' => wp_kses_post(wp_unslash($_POST['privacy_policy'] ?? '')),
            'legal_notice' => wp_kses_post(wp_unslash($_POST['legal_notice'] ?? '')),
        ];

        update_option('dlcms_legal_options', $options);

        echo '<div class="updated notice"><p>Textos legales guardados correctamente.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>DosLlaves CMS · Legal</h1>

        <p>
            Gestiona desde aquí los textos de Política de privacidad y Aviso legal.
        </p>

        <form method="post">
            <?php wp_nonce_field('dlcms_save_legal', 'dlcms_legal_nonce'); ?>

            <h2>Política de privacidad</h2>
            <?php
            wp_editor(
                $options['privacy_policy'],
                'privacy_policy',
                [
                    'textarea_name' => 'privacy_policy',
                    'media_buttons' => false,
                    'textarea_rows' => 16,
                ]
            );
            ?>

            <hr style="margin:32px 0;">

            <h2>Aviso legal</h2>
            <?php
            wp_editor(
                $options['legal_notice'],
                'legal_notice',
                [
                    'textarea_name' => 'legal_notice',
                    'media_buttons' => false,
                    'textarea_rows' => 16,
                ]
            );
            ?>

            <?php submit_button('Guardar textos legales'); ?>
        </form>
    </div>
    <?php
}

/* =========================================================
 * REST endpoints
 * ========================================================= */

add_action('rest_api_init', function () {
    register_rest_route('dosllaves/v1', '/site-seo', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => function () {
            return new WP_REST_Response(dlcms_get_seo_options(), 200);
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('dosllaves/v1', '/site-legal', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => function () {
            $options = dlcms_get_legal_options();

            return new WP_REST_Response([
                'privacy_policy' => wp_kses_post($options['privacy_policy']),
                'legal_notice' => wp_kses_post($options['legal_notice']),
            ], 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
