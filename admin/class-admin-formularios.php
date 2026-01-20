<?php
/**
 * Página de administración de formularios
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ultima_Milla_Admin_Formularios {
    
    /**
     * Inicializar la clase
     */
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'handle_actions'));
    }
    
    /**
     * Manejar acciones (crear formulario, etc)
     */
    public static function handle_actions() {
        // Solo en nuestra página
        if (!isset($_GET['page']) || $_GET['page'] !== 'ultima-milla-formularios') {
            return;
        }
        
        // Manejar creación rápida de formulario
        if (isset($_GET['crear_nuevo']) && isset($_GET['nonce'])) {
            if (wp_verify_nonce($_GET['nonce'], 'um_crear_formulario_rapido')) {
                $post_id = wp_insert_post(array(
                    'post_type' => 'um_formulario',
                    'post_title' => __('Nuevo Formulario', 'ultima-milla'),
                    'post_status' => 'draft'
                ));
                
                if (!is_wp_error($post_id)) {
                    wp_safe_redirect(admin_url('admin.php?page=ultima-milla-formularios&action=edit&form_id=' . $post_id));
                    exit;
                }
            }
        }
    }
    
    /**
     * Renderizar página
     */
    public static function render_page() {
        // Detectar acción
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        switch ($action) {
            case 'edit':
                self::render_edit_form($form_id);
                break;
                
            case 'create':
                self::render_create_form();
                break;
                
            default:
                self::render_list();
                break;
        }
    }
    
    /**
     * Renderizar lista de formularios
     */
    private static function render_list() {
        // Obtener formularios
        $formularios = get_posts(array(
            'post_type' => 'um_formulario',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $crear_url = wp_nonce_url(
            admin_url('admin.php?page=ultima-milla-formularios&crear_nuevo=1'),
            'um_crear_formulario_rapido',
            'nonce'
        );
        
        ?>
        <div class="wrap ultima-milla-admin">
            <h1 class="wp-heading-inline">
                <?php _e('Formularios de Solicitud', 'ultima-milla'); ?>
            </h1>
            
            <a href="<?php echo esc_url($crear_url); ?>" class="page-title-action">
                <?php _e('Añadir Nuevo', 'ultima-milla'); ?>
            </a>
            
            <hr class="wp-header-end">
            
            <div class="postbox">
                <div class="inside">
                    <?php if (!empty($formularios)): ?>
                        <table id="tabla-formularios" class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 60px;"><?php _e('ID', 'ultima-milla'); ?></th>
                                    <th><?php _e('Nombre del Formulario', 'ultima-milla'); ?></th>
                                    <th><?php _e('Shortcode', 'ultima-milla'); ?></th>
                                    <th style="width: 100px;"><?php _e('Estado', 'ultima-milla'); ?></th>
                                    <th style="width: 120px;"><?php _e('Fecha Creación', 'ultima-milla'); ?></th>
                                    <th style="width: 100px;"><?php _e('Acciones', 'ultima-milla'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($formularios as $formulario): ?>
                                    <tr>
                                                <td class="text-primary"><strong>#<?php echo esc_html($formulario->ID); ?></strong></td>
                                                <td><strong><?php echo esc_html($formulario->post_title); ?></strong></td>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <code style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px; font-size: 12px; flex: 1;">[ultima_milla_form id="<?php echo esc_attr($formulario->ID); ?>"]</code>
                                                        <button type="button" class="button button-small copiar-shortcode"
                                                                data-shortcode='[ultima_milla_form id="<?php echo esc_attr($formulario->ID); ?>"]'
                                                                title="<?php _e('Copiar shortcode', 'ultima-milla'); ?>">
                                                            <span class="dashicons dashicons-admin-page"></span>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($formulario->post_status === 'publish'): ?>
                                                        <span class="um-badge success"><?php _e('Publicado', 'ultima-milla'); ?></span>
                                                    <?php else: ?>
                                                        <span class="um-badge secondary"><?php _e('Borrador', 'ultima-milla'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date_i18n('d/m/Y', strtotime($formulario->post_date)); ?></td>
                                                <td>
                                                    <div class="button-group">
                                                        <a href="<?php echo admin_url('admin.php?page=ultima-milla-formularios&action=edit&form_id=' . $formulario->ID); ?>" 
                                                           class="button button-primary button-small"
                                                           title="<?php _e('Editar formulario', 'ultima-milla'); ?>">
                                                            <span class="dashicons dashicons-edit"></span>
                                                        </a>
                                                        <button type="button" class="button button-small eliminar-formulario" 
                                                                data-form-id="<?php echo esc_attr($formulario->ID); ?>"
                                                                data-form-title="<?php echo esc_attr($formulario->post_title); ?>"
                                                                title="<?php _e('Eliminar formulario', 'ultima-milla'); ?>">
                                                            <span class="dashicons dashicons-trash" style="color: #b32d2e;"></span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                    <?php else: ?>
                        <div class="notice notice-info inline">
                            <p><?php _e('No hay formularios creados aún. Haz clic en "Añadir Nuevo" para crear tu primer formulario.', 'ultima-milla'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar editor de formulario
     */
    private static function render_edit_form($form_id) {
        if (!$form_id) {
            echo '<div class="notice notice-error"><p>' . __('ID de formulario inválido', 'ultima-milla') . '</p></div>';
            return;
        }
        
        $form = get_post($form_id);
        
        if (!$form || $form->post_type !== 'um_formulario') {
            echo '<div class="notice notice-error"><p>' . __('Formulario no encontrado', 'ultima-milla') . '</p></div>';
            return;
        }
        
        // Obtener campos
        $fields = Ultima_Milla_Form_Builder::get_form_fields($form_id);
        $field_types = Ultima_Milla_Form_Builder::get_field_types();
        
        ?>
        <div class="wrap ultima-milla-admin">
            <h1 class="wp-heading-inline">
                <?php _e('Editar Formulario', 'ultima-milla'); ?>
            </h1>
            
            <a href="<?php echo admin_url('admin.php?page=ultima-milla-formularios'); ?>" class="page-title-action">
                <?php _e('← Volver a la lista', 'ultima-milla'); ?>
            </a>
            
            <hr class="wp-header-end">
            
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Nombre del Formulario -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Información del Formulario', 'ultima-milla'); ?></h2>
                            <div class="inside">
                                <input type="hidden" id="form-id" value="<?php echo esc_attr($form_id); ?>">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="form-title"><?php _e('Nombre del Formulario', 'ultima-milla'); ?> <span class="text-danger">*</span></label>
                                        </th>
                                        <td>
                                            <input type="text" id="form-title" class="regular-text" value="<?php echo esc_attr($form->post_title); ?>" required>
                                            <p class="description"><?php _e('Este nombre es solo para tu referencia interna.', 'ultima-milla'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="form-status"><?php _e('Estado', 'ultima-milla'); ?></label>
                                        </th>
                                        <td>
                                            <select id="form-status" class="regular-text">
                                                <option value="draft" <?php selected($form->post_status, 'draft'); ?>><?php _e('Borrador', 'ultima-milla'); ?></option>
                                                <option value="publish" <?php selected($form->post_status, 'publish'); ?>><?php _e('Publicado', 'ultima-milla'); ?></option>
                                            </select>
                                            <p class="description"><?php _e('Solo los formularios publicados pueden usarse en el sitio.', 'ultima-milla'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                <p class="submit" style="padding-top: 10px; border-top: 1px solid #c3c4c7;">
                                    <button type="button" class="button button-primary button-large" id="btn-guardar-formulario">
                                        <span class="dashicons dashicons-saved" style="vertical-align: middle;"></span>
                                        <?php _e('Guardar Cambios', 'ultima-milla'); ?>
                                    </button>
                                    <button type="button" class="button button-large eliminar-formulario-edit" 
                                            data-form-id="<?php echo esc_attr($form_id); ?>"
                                            data-form-title="<?php echo esc_attr($form->post_title); ?>"
                                            style="margin-left: 10px;">
                                        <span class="dashicons dashicons-trash" style="vertical-align: middle; color: #b32d2e;"></span>
                                        <?php _e('Eliminar Formulario', 'ultima-milla'); ?>
                                    </button>
                                    <span id="save-status" style="margin-left: 15px; font-weight: 600;"></span>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Constructor de campos -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Campos Personalizados', 'ultima-milla'); ?></h2>
                            <div class="inside">
                                <div id="form-fields-container">
                                    <?php if (!empty($fields)): ?>
                                        <?php foreach ($fields as $field): ?>
                                            <div class="um-form-field-item" data-field-id="<?php echo esc_attr($field->id); ?>">
                                                <div class="um-form-field-info">
                                                    <h4><?php echo esc_html($field->field_label); ?></h4>
                                                    <p>
                                                        <strong><?php _e('Tipo:', 'ultima-milla'); ?></strong> <?php echo esc_html($field_types[$field->field_type]); ?> |
                                                        <strong><?php _e('Nombre:', 'ultima-milla'); ?></strong> <code><?php echo esc_html($field->field_name); ?></code>
                                                        <?php if ($field->field_required): ?>
                                                            | <span class="um-badge danger"><?php _e('Obligatorio', 'ultima-milla'); ?></span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <button type="button" class="button button-small eliminar-campo" 
                                                        data-field-id="<?php echo esc_attr($field->id); ?>"
                                                        title="<?php _e('Eliminar campo', 'ultima-milla'); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="notice notice-info inline">
                                            <p><?php _e('No hay campos personalizados aún. Los campos Origen, Destino y Descripción son estándar y siempre se mostrarán.', 'ultima-milla'); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="mt-3">
                                    <button type="button" class="button button-primary button-large" id="btn-abrir-nuevo-campo">
                                        <span class="dashicons dashicons-plus-alt"></span> <?php _e('Añadir Campo', 'ultima-milla'); ?>
                                    </button>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Información del shortcode -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Shortcode', 'ultima-milla'); ?></h2>
                            <div class="inside">
                                <p><?php _e('Usa este shortcode para mostrar el formulario en cualquier página:', 'ultima-milla'); ?></p>
                                <div class="um-shortcode-box">
                                    <code>[ultima_milla_form id="<?php echo esc_attr($form_id); ?>"]</code>
                                </div>
                                <p>
                                    <button type="button" class="button button-primary button-large copiar-shortcode" style="width: 100%;"
                                            data-shortcode='[ultima_milla_form id="<?php echo esc_attr($form_id); ?>"]'>
                                        <span class="dashicons dashicons-admin-page"></span> <?php _e('Copiar Shortcode', 'ultima-milla'); ?>
                                    </button>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Campos estándar -->
                        <div class="postbox">
                            <h2 class="hndle"><?php _e('Campos Estándar', 'ultima-milla'); ?></h2>
                            <div class="inside">
                                <p class="description">
                                    <?php _e('Estos campos siempre se mostrarán en el formulario:', 'ultima-milla'); ?>
                                </p>
                                <ul>
                                    <li><span class="dashicons dashicons-yes-alt text-success"></span> <?php _e('Dirección de Origen', 'ultima-milla'); ?></li>
                                    <li><span class="dashicons dashicons-yes-alt text-success"></span> <?php _e('Dirección de Destino', 'ultima-milla'); ?></li>
                                    <li><span class="dashicons dashicons-yes-alt text-success"></span> <?php _e('Descripción', 'ultima-milla'); ?></li>
                                    <li><span class="dashicons dashicons-yes-alt text-success"></span> <?php _e('Fecha Programada', 'ultima-milla'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Backdrop del Modal -->
        <div class="um-modal-backdrop" id="modal-backdrop"></div>
        
        <!-- Modal: Nuevo Campo -->
        <div class="um-modal" id="modalNuevoCampo">
            <div class="um-modal-header">
                <h2><?php _e('Añadir Nuevo Campo', 'ultima-milla'); ?></h2>
                <button type="button" class="um-modal-close" data-modal-close="modalNuevoCampo">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="um-modal-body">
                <input type="hidden" id="nuevo-campo-form-id" value="<?php echo esc_attr($form_id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nuevo-campo-tipo"><?php _e('Tipo de Campo', 'ultima-milla'); ?></label>
                        </th>
                        <td>
                            <select id="nuevo-campo-tipo" class="regular-text">
                                <?php foreach ($field_types as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="nuevo-campo-label"><?php _e('Etiqueta del Campo', 'ultima-milla'); ?> <span class="text-danger">*</span></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="nuevo-campo-label" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="nuevo-campo-name"><?php _e('Nombre del Campo', 'ultima-milla'); ?> <span class="text-danger">*</span></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="nuevo-campo-name" required>
                            <p class="description"><?php _e('Sin espacios. Ejemplo: telefono_contacto', 'ultima-milla'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Obligatorio', 'ultima-milla'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="nuevo-campo-required">
                                <?php _e('Campo obligatorio', 'ultima-milla'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr id="campo-opciones-container" style="display: none;">
                        <th scope="row">
                            <label for="nuevo-campo-opciones"><?php _e('Opciones', 'ultima-milla'); ?></label>
                        </th>
                        <td>
                            <textarea id="nuevo-campo-opciones" rows="4" class="large-text"></textarea>
                            <p class="description"><?php _e('Una opción por línea', 'ultima-milla'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="um-modal-footer">
                <button type="button" class="button" data-modal-close="modalNuevoCampo"><?php _e('Cancelar', 'ultima-milla'); ?></button>
                <button type="button" class="button button-primary" id="btn-guardar-campo"><?php _e('Añadir Campo', 'ultima-milla'); ?></button>
            </div>
        </div>
        <?php
    }
}
