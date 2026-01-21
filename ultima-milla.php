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
        
        // Auto-asignar rol de cliente al registrarse
        add_action('user_register', array($this, 'auto_asignar_rol_cliente'));
        
        // Cargar traducciones
        load_plugin_textdomain('ultima-milla', false, dirname(ULTIMA_MILLA_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Auto-asignar rol de "Cliente Última Milla" al registrarse
     */
    public function auto_asignar_rol_cliente($user_id) {
        $user = get_userdata($user_id);
        
        // Solo asignar si el usuario no tiene rol o tiene el rol 'subscriber'
        if (empty($user->roles) || in_array('subscriber', $user->roles)) {
            $user->set_role('um_cliente');
        }
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
            
            // Submenú: Ayuda y Shortcodes
            add_submenu_page(
                'ultima-milla',
                __('Ayuda y Shortcodes', 'ultima-milla'),
                __('Ayuda y Shortcodes', 'ultima-milla'),
                'manage_options',
                'ultima-milla-ayuda',
                array($this, 'render_ayuda_page')
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
     * Página de ayuda y shortcodes
     */
    public function render_ayuda_page() {
        ?>
        <div class="wrap ultima-milla-admin">
            <h1 class="wp-heading-inline">
                <?php _e('Ayuda y Shortcodes', 'ultima-milla'); ?>
            </h1>
            
            <hr class="wp-header-end">
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Shortcodes Disponibles', 'ultima-milla'); ?></h2>
                <div class="inside">
                    <p><?php _e('Copia y pega estos shortcodes en tus páginas de WordPress:', 'ultima-milla'); ?></p>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 30%;"><?php _e('Shortcode', 'ultima-milla'); ?></th>
                                <th><?php _e('Descripción', 'ultima-milla'); ?></th>
                                <th style="width: 120px;"><?php _e('Acción', 'ultima-milla'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>[ultima_milla_form id="X"]</code></td>
                                <td>
                                    <strong><?php _e('Formulario para crear solicitudes', 'ultima-milla'); ?></strong><br>
                                    <span class="description"><?php _e('Solo visible para usuarios logueados. Reemplaza X con el ID del formulario.', 'ultima-milla'); ?></span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=ultima-milla-formularios'); ?>" class="button button-small">
                                        <?php _e('Ver Formularios', 'ultima-milla'); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><code>[ultima_milla_mis_solicitudes]</code></td>
                                <td>
                                    <strong><?php _e('Listado de solicitudes del cliente', 'ultima-milla'); ?></strong><br>
                                    <span class="description"><?php _e('Con DataTables (búsqueda, filtrado, paginación). Solo usuarios logueados.', 'ultima-milla'); ?></span>
                                </td>
                                <td>
                                    <button type="button" class="button button-small button-primary copiar-shortcode" 
                                            data-shortcode="[ultima_milla_mis_solicitudes]">
                                        <span class="dashicons dashicons-admin-page"></span> <?php _e('Copiar', 'ultima-milla'); ?>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Sistema de Registro y Login', 'ultima-milla'); ?></h2>
                <div class="inside">
                    <h3><?php _e('Paso 1: Habilitar el Registro Público', 'ultima-milla'); ?></h3>
                    <p>
                        <a href="<?php echo admin_url('options-general.php'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-admin-settings" style="vertical-align: middle;"></span>
                            <?php _e('Ir a Ajustes → Generales', 'ultima-milla'); ?>
                        </a>
                    </p>
                    <p class="description"><?php _e('Marca la casilla "Cualquiera puede registrarse" y guarda los cambios.', 'ultima-milla'); ?></p>
                    
                    <hr style="margin: 20px 0;">
                    
                    <h3><?php _e('Paso 2: URLs de Registro y Login', 'ultima-milla'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('URL de Registro:', 'ultima-milla'); ?></th>
                            <td>
                                <div class="um-shortcode-box">
                                    <code><?php echo wp_registration_url(); ?></code>
                                </div>
                                <button type="button" class="button button-small copiar-shortcode" 
                                        data-shortcode="<?php echo esc_attr(wp_registration_url()); ?>">
                                    <span class="dashicons dashicons-admin-page"></span> <?php _e('Copiar URL', 'ultima-milla'); ?>
                                </button>
                                <p class="description"><?php _e('Los clientes irán aquí para crear su cuenta', 'ultima-milla'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('URL de Login:', 'ultima-milla'); ?></th>
                            <td>
                                <div class="um-shortcode-box">
                                    <code><?php echo wp_login_url(); ?></code>
                                </div>
                                <button type="button" class="button button-small copiar-shortcode" 
                                        data-shortcode="<?php echo esc_attr(wp_login_url()); ?>">
                                    <span class="dashicons dashicons-admin-page"></span> <?php _e('Copiar URL', 'ultima-milla'); ?>
                                </button>
                                <p class="description"><?php _e('Los clientes irán aquí para iniciar sesión', 'ultima-milla'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="notice notice-info inline">
                        <p>
                            <strong><?php _e('Tip:', 'ultima-milla'); ?></strong>
                            <?php _e('Agrega estas URLs en tu menú de navegación para que los clientes puedan acceder fácilmente.', 'ultima-milla'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Auto-Asignación de Rol', 'ultima-milla'); ?></h2>
                <div class="inside">
                    <div class="notice notice-success inline">
                        <p>
                            <strong><?php _e('¡Activado automáticamente!', 'ultima-milla'); ?></strong><br>
                            <?php _e('Cuando un usuario se registre usando el sistema nativo de WordPress, automáticamente se le asignará el rol de "Cliente Última Milla".', 'ultima-milla'); ?>
                        </p>
                    </div>
                    
                    <h4><?php _e('¿Cómo funciona?', 'ultima-milla'); ?></h4>
                    <ol>
                        <li><?php _e('Usuario va a la URL de registro', 'ultima-milla'); ?></li>
                        <li><?php _e('Completa el formulario de WordPress', 'ultima-milla'); ?></li>
                        <li><?php _e('El plugin detecta el nuevo registro', 'ultima-milla'); ?></li>
                        <li><?php _e('Automáticamente asigna el rol "Cliente Última Milla"', 'ultima-milla'); ?></li>
                        <li><?php _e('El usuario puede iniciar sesión y crear solicitudes', 'ultima-milla'); ?></li>
                    </ol>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Configuración de Páginas', 'ultima-milla'); ?></h2>
                <div class="inside">
                    <div class="notice notice-success inline">
                        <p>
                            <strong><?php _e('Paso a paso para crear tus páginas:', 'ultima-milla'); ?></strong>
                        </p>
                    </div>
                    
                    <h3><?php _e('Paso 1: Crear Páginas en WordPress', 'ultima-milla'); ?></h3>
                    <p>
                        <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="button button-primary" target="_blank">
                            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                            <?php _e('Crear Nueva Página', 'ultima-milla'); ?>
                        </a>
                    </p>
                    
                    <hr style="margin: 20px 0;">
                    
                    <h3><?php _e('Paso 2: Configurar Páginas Recomendadas', 'ultima-milla'); ?></h3>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 25%;"><?php _e('Nombre de Página', 'ultima-milla'); ?></th>
                                <th style="width: 40%;"><?php _e('Shortcode a Pegar', 'ultima-milla'); ?></th>
                                <th><?php _e('Descripción', 'ultima-milla'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Solicitar Servicio', 'ultima-milla'); ?></strong></td>
                                <td>
                                    <div class="um-shortcode-box" style="display: inline-block; background: #f0f0f1; padding: 5px 10px; border-radius: 3px;">
                                        <code>[ultima_milla_form id="<?php 
                                            // Obtener el primer formulario publicado
                                            $forms = get_posts(array(
                                                'post_type' => 'um_formulario',
                                                'post_status' => 'publish',
                                                'posts_per_page' => 1
                                            ));
                                            echo !empty($forms) ? $forms[0]->ID : '1';
                                        ?>"]</code>
                                    </div>
                                    <button type="button" class="button button-small copiar-shortcode" 
                                            data-shortcode="[ultima_milla_form id=&quot;<?php echo !empty($forms) ? $forms[0]->ID : '1'; ?>&quot;]">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </td>
                                <td>
                                    <span class="description"><?php _e('Formulario para que los clientes creen solicitudes. Solo visible si están logueados.', 'ultima-milla'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Mis Solicitudes', 'ultima-milla'); ?></strong></td>
                                <td>
                                    <div class="um-shortcode-box" style="display: inline-block; background: #f0f0f1; padding: 5px 10px; border-radius: 3px;">
                                        <code>[ultima_milla_mis_solicitudes]</code>
                                    </div>
                                    <button type="button" class="button button-small copiar-shortcode" 
                                            data-shortcode="[ultima_milla_mis_solicitudes]">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </td>
                                <td>
                                    <span class="description"><?php _e('Lista de solicitudes del cliente con DataTables. Solo visible si están logueados.', 'ultima-milla'); ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="notice notice-info inline" style="margin-top: 20px;">
                        <p>
                            <strong><?php _e('Instrucciones:', 'ultima-milla'); ?></strong>
                        </p>
                        <ol>
                            <li><?php _e('Haz clic en "Crear Nueva Página" arriba', 'ultima-milla'); ?></li>
                            <li><?php _e('Dale un título (ej: "Solicitar Servicio" o "Mis Solicitudes")', 'ultima-milla'); ?></li>
                            <li><?php _e('Copia el shortcode correspondiente usando el botón de copiar', 'ultima-milla'); ?></li>
                            <li><?php _e('Pégalo en el contenido de la página (en el editor de bloques o clásico)', 'ultima-milla'); ?></li>
                            <li><?php _e('Publica la página', 'ultima-milla'); ?></li>
                        </ol>
                    </div>
                    
                    <hr style="margin: 20px 0;">
                    
                    <h3><?php _e('Paso 3: Agregar al Menú (Opcional)', 'ultima-milla'); ?></h3>
                    <p>
                        <a href="<?php echo admin_url('nav-menus.php'); ?>" class="button" target="_blank">
                            <span class="dashicons dashicons-menu-alt" style="vertical-align: middle;"></span>
                            <?php _e('Ir a Apariencia → Menús', 'ultima-milla'); ?>
                        </a>
                    </p>
                    <p class="description"><?php _e('Agrega las páginas que creaste y también puedes agregar enlaces personalizados a Login y Registro.', 'ultima-milla'); ?></p>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Comportamiento de Seguridad', 'ultima-milla'); ?></h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row" style="width: 250px;"><?php _e('Usuario NO logueado:', 'ultima-milla'); ?></th>
                            <td>
                                <span class="dashicons dashicons-lock" style="color: #d63638;"></span>
                                <?php _e('Verá un mensaje "Acceso Restringido" con botones para:', 'ultima-milla'); ?>
                                <ul style="margin-top: 10px;">
                                    <li><strong><?php _e('Iniciar Sesión', 'ultima-milla'); ?></strong> (redirige a <?php echo wp_login_url(); ?>)</li>
                                    <li><strong><?php _e('Registrarse', 'ultima-milla'); ?></strong> (redirige a <?php echo wp_registration_url(); ?>)</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Usuario logueado:', 'ultima-milla'); ?></th>
                            <td>
                                <span class="dashicons dashicons-unlock" style="color: #00a32a;"></span>
                                <?php _e('Verá el contenido completo:', 'ultima-milla'); ?>
                                <ul style="margin-top: 10px;">
                                    <li><?php _e('Formulario de solicitud (si es la página con ese shortcode)', 'ultima-milla'); ?></li>
                                    <li><?php _e('Tabla de sus solicitudes con DataTables (si es la página de "Mis Solicitudes")', 'ultima-milla'); ?></li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php
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
        
        // SweetAlert2 CSS
        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11'
        );
        
        // SweetAlert2 JS
        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            array('jquery'),
            '11',
            true
        );
        
        // DataTables CSS (para tabla de solicitudes del cliente)
        wp_enqueue_style(
            'datatables',
            'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css',
            array('bootstrap-5'),
            '1.13.8'
        );
        
        // DataTables JS
        wp_enqueue_script(
            'datatables',
            'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
            array('jquery'),
            '1.13.8',
            true
        );
        
        wp_enqueue_script(
            'datatables-bootstrap5',
            'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js',
            array('datatables'),
            '1.13.8',
            true
        );
        
        // Estilos personalizados
        wp_enqueue_style(
            'ultima-milla-frontend',
            ULTIMA_MILLA_PLUGIN_URL . 'assets/css/frontend.css',
            array('bootstrap-5', 'datatables'),
            ULTIMA_MILLA_VERSION
        );
        
        // Scripts personalizados
        wp_enqueue_script(
            'ultima-milla-frontend',
            ULTIMA_MILLA_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'bootstrap-5', 'sweetalert2', 'datatables-bootstrap5'),
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
