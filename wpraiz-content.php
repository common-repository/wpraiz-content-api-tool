<?php

/*
 * Plugin Name: WPRaiz Content API Tool
 * Plugin URI: https://wpraiz.com.br
 * Donate link: https://wpraiz.com.br
 * Description: Plugin para criar postagens via API REST com campos personalizados de SEO, upload de imagens e categoria principal.
 * Version: 1.4
 * Author: José caro
 * License: GPLv3
 */

class API_Post_Creator_With_Image {

    public function __construct() {
        // Registra o endpoint na API REST
        add_action('rest_api_init', [$this, 'register_routes']);
        // Garante que os arquivos necessários para manipulação de mídia estejam disponíveis
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    public function register_routes() {
        register_rest_route('api-post-creator/v1', '/create-post', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_create_post'],
            'permission_callback' => [$this, 'authenticate_user'],
        ]);

        // Novo endpoint GET para verificação
        register_rest_route('api-post-creator/v1', '/check-status', [
            'methods' => 'GET',
            'callback' => [$this, 'check_status'],
            'permission_callback' => [$this, 'authenticate_user'],
        ]);
    }

    // Função para validar autenticação e plugins de SEO ativos
    public function check_status() {
        $seo_plugin = $this->detect_seo_plugin();
        
        return new WP_REST_Response([
            'message' => 'Plugin ativo e autenticado com sucesso.',
            'seo_plugin' => $seo_plugin ? $seo_plugin : 'Nenhum plugin de SEO detectado',
        ], 200);
    }

    // Função para detectar o plugin de SEO ativo
    private function detect_seo_plugin() {
        if (defined('SEOPRESS_VERSION')) {
            return 'seopress';
        } elseif (defined('WPSEO_VERSION')) {
            return 'yoastseo';
        } elseif (defined('RANK_MATH_VERSION')) {
            return 'rankmath';
        }
        return null;
    }

    public function handle_create_post($request) {
    $params = $request->get_json_params();

    if (empty($params['title']) || empty($params['content'])) {
        return new WP_Error('missing_data', 'Título e conteúdo são obrigatórios.', ['status' => 400]);
    }
            
    remove_filter('content_save_pre', 'wp_filter_post_kses');

    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($params['title']),
        'post_content' => $params['content'],
        'post_status'  => sanitize_text_field($params['status'] ?? 'draft'),
        'post_author'  => get_current_user_id(),
    ]);

    add_filter('content_save_pre', 'wp_filter_post_kses');

    if (is_wp_error($post_id)) {
        return new WP_Error('post_creation_failed', 'Falha ao criar o post.', ['status' => 500]);
    }

    // Verifica ou cria a categoria principal
    if (!empty($params['primary_category'])) {
        $primary_category_id = $this->get_or_create_category(trim($params['primary_category']));
        if (!is_wp_error($primary_category_id)) {
            wp_set_post_terms($post_id, [$primary_category_id], 'category');
        }
    }

    // Faz o upload da imagem se a URL for fornecida
    if (!empty($params['image_url'])) {
        $image_id = $this->upload_image_from_url($params['image_url'], $post_id);
        if (!is_wp_error($image_id)) {
            set_post_thumbnail($post_id, $image_id);
        }
    }

    // Adiciona os campos de SEO com base no plugin ativo
    if (!empty($params['seo_title']) || !empty($params['seo_description'])) {
        // Verifica SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            if (!empty($params['seopress_title'])) {
                update_post_meta($post_id, '_seopress_titles_title', sanitize_text_field($params['seopress_title']));
            }
            if (!empty($params['seopress_desc'])) {
                update_post_meta($post_id, '_seopress_titles_desc', sanitize_text_field($params['seopress_desc']));
            }
        }
        // Verifica Yoast SEO
        elseif (is_plugin_active('wordpress-seo/wp-seo.php')) {
            if (!empty($params['seopress_title'])) {
                update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($params['seopress_title']));
            }
            if (!empty($params['seopress_desc'])) {
                update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_text_field($params['seopress_desc']));
            }
        }
        // Verifica Rank Math SEO
        elseif (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            if (!empty($params['seopress_title'])) {
                update_post_meta($post_id, 'rank_math_title', sanitize_text_field($params['seopress_title']));
            }
            if (!empty($params['seopress_desc'])) {
                update_post_meta($post_id, 'rank_math_description', sanitize_text_field($params['seopress_desc']));
            }
        }
    }

    return new WP_REST_Response([
        'message' => 'Post criado com sucesso!',
        'post_id' => $post_id,
        'post_url' => get_permalink($post_id),
    ], 201);
    }

    /**
     * Função para verificar ou criar uma categoria.
     */
    public function get_or_create_category($category_name) {
        $term = get_term_by('name', $category_name, 'category');
        if ($term) {
            return $term->term_id;
        } else {
            $new_term = wp_insert_term($category_name, 'category');
            if (is_wp_error($new_term)) {
                return $new_term;
            }
            return $new_term['term_id'];
        }
    }

    public function upload_image_from_url($image_url, $post_id) {
        // Usa wp_remote_get() para obter o conteúdo da imagem da URL
        $response = wp_remote_get($image_url);
    
        if (is_wp_error($response)) {
            return new WP_Error('image_download_failed', 'Falha ao baixar a imagem.');
        }
    
        $image_data = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
    
        // Verifica se a resposta foi bem-sucedida
        if ($http_code !== 200 || !$image_data) {
            return new WP_Error('image_download_failed', 'Falha ao baixar a imagem.');
        }
    
        // Cria um arquivo temporário
        $tmp_file = wp_tempnam($image_url);
        global $wp_filesystem;
    
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        WP_Filesystem();
        $wp_filesystem->put_contents($tmp_file, $image_data);
    
        // Define as informações do arquivo para o upload
        $file_array = array();
        $file_array['name'] = basename(wp_parse_url($image_url, PHP_URL_PATH)); // Usando wp_parse_url()
        $file_array['tmp_name'] = $tmp_file;
    
        // Faz o upload da imagem para o WordPress
        $image_id = media_handle_sideload($file_array, $post_id);
    
        if (is_wp_error($image_id)) {
            wp_delete_file($tmp_file);  // Remove o arquivo temporário
            return $image_id;
        }
    
        return $image_id;
    }

    public function authenticate_user($request) {
        return current_user_can('edit_posts');
    }
}

class API_Post_Creator_With_Menu {

    public function __construct() {
        // Adiciona o sub-menu na página de Ferramentas
        add_action('admin_menu', [$this, 'add_plugin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function add_plugin_menu() {
        add_submenu_page(
            'tools.php', // O menu principal "Ferramentas"
            'WPRaiz Content API', // Título da página
            'WPRaiz Content API', // Texto do menu
            'manage_options', // Capability (permissão)
            'wpraiz-content-api', // Slug da página
            [$this, 'render_admin_page'] // Função que renderiza a página
        );
    }

    public function render_admin_page() {
    $site_url = get_site_url();
    $endpoint_url = $site_url . '/wp-json/api-post-creator/v1/create-post';
    $check_url = $site_url . '/wp-json/api-post-creator/v1/check-status';
    ?>
    <div class="wrap">
        <h1>WPRaiz Content API</h1>
        <img src="<?php echo esc_url(plugins_url('assets/images/logo_wpraiz.png', __FILE__)); ?>" width="300" alt="WPRaiz Logo" />

        <h2>Endpoint da API</h2>
        <p>
            Este é o endpoint ativo para criar postagens via API:
            <input type="text" id="api-endpoint" value="<?php echo esc_url($endpoint_url); ?>" readonly style="width: 100%; max-width: 600px;" />
            <button onclick="copyToClipboard()" class="button button-primary">Copiar</button>
        </p>
        
        <h2>Verificar Status do Plugin</h2>
        <p>
            Utilize o endpoint abaixo para validar se o plugin está instalado corretamente e funcionando, além de verificar o plugin de SEO ativo:
            <input type="text" id="check-endpoint" value="<?php echo esc_url($check_url); ?>" readonly style="width: 100%; max-width: 600px;" />
            <button onclick="copyToClipboardCheck()" class="button button-primary">Copiar</button>
        </p>
        
        <h2>Instruções Iniciais</h2>
        <p>
            Este plugin permite criar postagens via API REST no WordPress. Para utilizá-lo, você deve fazer uma requisição POST para o endpoint acima.
            Abaixo está um exemplo de corpo (Body) da requisição:
        </p>
        <pre>
        {
            "title": "Título do Post",
            "content": "Este é o conteúdo do post",
            "status": "publish",
            "primary_category": "Geral",
            "seopress_title": "Título SEO",
            "seopress_desc": "Descrição SEO",
            "image_url": "https://seu-site.com/imagem.jpg"
        }
        </pre>
        <p>
            <strong>Importante:</strong> Para autenticar sua requisição, você deve usar a <em>senha de aplicativo</em> e não a senha principal do usuário.
        </p>
        
        <h2> Visite o WPRaiz</h2>
        <p>
            <a href="https://wpraiz.com.br" target="_blank" class="button button-primary">Visitar WPRaiz</a>
            <a href="https://youtube.com/wpraiz" target="_blank" class="button button-primary">Visitar Youtube</a>
        </p>
    </div>
    <script type="text/javascript">
        function copyToClipboard() {
            var copyText = document.getElementById("api-endpoint");
            copyText.select();
            document.execCommand("copy");
            alert("Endpoint copiado: " + copyText.value);
        }

        function copyToClipboardCheck() {
            var copyText = document.getElementById("check-endpoint");
            copyText.select();
            document.execCommand("copy");
            alert("Endpoint de verificação copiado: " + copyText.value);
        }
    </script>
    <?php
    }

    public function enqueue_admin_scripts($hook_suffix) {
        // Certifique-se de que você só adiciona os scripts na página correta
        if ($hook_suffix === 'tools_page_wpraiz-content-api') {
            wp_enqueue_script(
                'wpraiz-admin-script',
                plugins_url('assets/js/admin-scripts.js', __FILE__),
                array('jquery'),
                '1.3', // Definindo a versão do script
                true
            );
        }
    }
}


function api_post_creator_with_image_and_menu_init() {
    new API_Post_Creator_With_Image();
    new API_Post_Creator_With_Menu();
    }
    add_action('plugins_loaded', 'api_post_creator_with_image_and_menu_init');
