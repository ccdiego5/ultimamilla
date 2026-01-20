<?php
/**
 * Plugin Name: Última Milla
 * Plugin URI: https://example.com
 * Description: Sistema de gestión de servicios de última milla con seguimiento de estados para clientes, mensajeros y administradores.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://example.com
 * Text Domain: ultima-milla
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('ULTIMA_MILLA_VERSION', '1.0.0');
define('ULTIMA_MILLA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ULTIMA_MILLA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ULTIMA_MILLA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class Ultima_Milla_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Cargar archivos necesarios
        $this->load_dependencies();
        
        // Hooks de activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Hooks de inicialización
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        require_once ULTIMA_MILLA_PLUGIN_DIR . 'includes/class-roles.php';
        require_once ULTIMA_MILLA_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once ULTIMA_MILLA_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once ULTIMA_MILLA_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
        require_once ULTIMA_MILLA_PLUGIN_DIR . 'includes/class-form-builder.php';
        require_once ULTIMA_MILLA_PLUGIN_DIR . 'admin/class-admin-solicitudes.php';
        require_once ULTIMA_MILLA_PLUGIN_DIR . 'admin/class-admin-formularios.php';
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Crear roles personalizados
        Ultima_Milla_Roles::create_roles();
        
        // Registrar post types
        Ultima_Milla_Post_Types::register();
        
        // Crear tabla para formularios personalizados
        $this->create_database_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Crear tablas de base de datos
     */
    private function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla para campos de formularios
        $table_name = $wpdb->prefix . 'um_form_fields';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            field_type varchar(50) NOT NULL,
            field_label varchar(255) NOT NULL,
            field_name varchar(100) NOT NULL,
            field_required tinyint(1) DEFAULT 0,
            field_options text,
            field_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Inicialización
     */
    public function init() {
        // Registrar post types
        Ultima_Milla_Post_Types::register();
        
        // Registrar shortcodes
        Ultima_Milla_Shortcodes::register();
        
        // Registrar handlers AJAX
        Ultima_Milla_Ajax_Handlers::register();
        
        // Inicializar clase de formularios (para manejar acciones admin_init)
        Ultima_Milla_Admin_Formularios::init();
        
        // Cargar traducciones
        load_plugin_textdomain('ultima-milla', false, dirname(ULTIMA_MILLA_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Agregar menús de administración
     */
    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            __('Última Milla', 'ultima-milla'),
            __('Última Milla', 'ultima-milla'),
            'read',
            'ultima-milla',
            array($this, 'render_main_page'),
            'dashicons-location',
            30
        );
        
        // Submenú: Solicitudes
        add_submenu_page(
            'ultima-milla',
            __('Solicitudes', 'ultima-milla'),
            __('Solicitudes', 'ultima-milla'),
            'read',
            'ultima-milla-solicitudes',
            array('Ultima_Milla_Admin_Solicitudes', 'render_page')
        );
        
        // Submenú: Formularios (solo para admin)
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'ultima-milla',
                __('Formularios', 'ultima-milla'),
                __('Formularios', 'ultima-milla'),
                'manage_options',
                'ultima-milla-formularios',
                array('Ultima_Milla_Admin_Formularios', 'render_page')
            );
        }
        
        // Remover primer submenú duplicado
        remove_submenu_page('ultima-milla', 'ultima-milla');
    }
    
    /**
     * Página principal (redirige a solicitudes)
     */
    public function render_main_page() {
        wp_redirect(admin_url('admin.php?page=ultima-milla-solicitudes'));
        exit;
    }
    
    /**
     * Encolar assets del admin
     */
    public function enqueue_admin_assets($hook) {
        // Solo cargar en nuestras páginas
        if (strpos($hook, 'ultima-milla') === false) {
            return;
        }
        
        // DataTables CSS (tema nativo de WordPress)
        wp_enqueue_style(
            'um-datatables',
            'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css',
            array(),
            '1.13.8'
        );
        
        // SweetAlert2 CSS
        wp_enqueue_style(
            'um-sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11'
        );
        
        // Estilos personalizados (después de DataTables para sobrescribir)
        wp_enqueue_style(
            'ultima-milla-admin',
            ULTIMA_MILLA_PLUGIN_URL . 'assets/css/admin.css',
            array('um-datatables', 'um-sweetalert2'),
            ULTIMA_MILLA_VERSION . '.' . time()
        );
        
        // DataTables JS
        wp_enqueue_script(
            'um-datatables',
            'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
            array('jquery'),
            '1.13.8',
            true
        );
        
        // SweetAlert2 JS
        wp_enqueue_script(
            'um-sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
            array('jquery'),
            '11',
            true
        );
        
        // Scripts personalizados
        wp_enqueue_script(
            'ultima-milla-admin',
            ULTIMA_MILLA_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'um-datatables', 'um-sweetalert2'),
            ULTIMA_MILLA_VERSION . '.' . time(),
            true
        );
        
        // Pasar datos a JavaScript
        wp_localize_script('ultima-milla-admin', 'ultimaMillaAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ultima_milla_admin_nonce'),
            'i18n' => array(
                'processing' => __('Procesando...', 'ultima-milla'),
                'search' => __('Buscar:', 'ultima-milla'),
                'lengthMenu' => __('Mostrar _MENU_ registros', 'ultima-milla'),
                'info' => __('Mostrando _START_ a _END_ de _TOTAL_ registros', 'ultima-milla'),
                'infoEmpty' => __('Mostrando 0 a 0 de 0 registros', 'ultima-milla'),
                'infoFiltered' => __('(filtrado de _MAX_ registros totales)', 'ultima-milla'),
                'loadingRecords' => __('Cargando...', 'ultima-milla'),
                'zeroRecords' => __('No se encontraron registros coincidentes', 'ultima-milla'),
                'emptyTable' => __('No hay datos disponibles en la tabla', 'ultima-milla'),
                'paginate' => array(
                    'first' => __('Primero', 'ultima-milla'),
                    'previous' => __('Anterior', 'ultima-milla'),
                    'next' => __('Siguiente', 'ultima-milla'),
                    'last' => __('Último', 'ultima-milla')
                )
            )
        ));
    }
    
    /**
     * Encolar assets del frontend
     */
    public function enqueue_frontend_assets() {
        // Bootstrap 5
        wp_enqueue_style(
            'bootstrap-5',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
            array(),
            '5.3.2'
        );
        
        wp_enqueue_script(
            'bootstrap-5',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.2',
            true
        );
        
        // Estilos personalizados
        wp_enqueue_style(
            'ultima-milla-frontend',
            ULTIMA_MILLA_PLUGIN_URL . 'assets/css/frontend.css',
            array('bootstrap-5'),
            ULTIMA_MILLA_VERSION
        );
        
        // Scripts personalizados
        wp_enqueue_script(
            'ultima-milla-frontend',
            ULTIMA_MILLA_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'bootstrap-5'),
            ULTIMA_MILLA_VERSION,
            true
        );
        
        // Pasar datos a JavaScript
        wp_localize_script('ultima-milla-frontend', 'ultimaMilla', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ultima_milla_nonce')
        ));
    }
}

// Inicializar el plugin
function ultima_milla_init() {
    return Ultima_Milla_Plugin::get_instance();
}

// Ejecutar
ultima_milla_init();
