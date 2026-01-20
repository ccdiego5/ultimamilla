<?php
/**
 * Gestión de Shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ultima_Milla_Shortcodes {
    
    /**
     * Registrar todos los shortcodes
     */
    public static function register() {
        add_shortcode('ultima_milla_form', array(__CLASS__, 'render_form'));
        add_shortcode('ultima_milla_mis_solicitudes', array(__CLASS__, 'render_mis_solicitudes'));
    }
    
    /**
     * Renderizar formulario de solicitud
     * Uso: [ultima_milla_form id="1"]
     */
    public static function render_form($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        $form_id = intval($atts['id']);
        
        if (!$form_id) {
            return '<div class="alert alert-danger">' . __('ID de formulario inválido', 'ultima-milla') . '</div>';
        }
        
        // Obtener el formulario
        $form = get_post($form_id);
        
        if (!$form || $form->post_type !== 'um_formulario' || $form->post_status !== 'publish') {
            return '<div class="alert alert-danger">' . __('Formulario no encontrado', 'ultima-milla') . '</div>';
        }
        
        // Obtener campos del formulario
        global $wpdb;
        $table_name = $wpdb->prefix . 'um_form_fields';
        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE form_id = %d ORDER BY field_order ASC",
            $form_id
        ));
        
        ob_start();
        ?>
        <div class="ultima-milla-form-wrapper">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4"><?php echo esc_html($form->post_title); ?></h3>
                    
                    <form id="um-solicitud-form" class="needs-validation" novalidate>
                        <?php wp_nonce_field('um_crear_solicitud', 'um_nonce'); ?>
                        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
                        
                        <?php if (!empty($fields)): ?>
                            <?php foreach ($fields as $field): ?>
                                <div class="mb-3">
                                    <label for="<?php echo esc_attr($field->field_name); ?>" class="form-label">
                                        <?php echo esc_html($field->field_label); ?>
                                        <?php if ($field->field_required): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    
                                    <?php self::render_field($field); ?>
                                    
                                    <?php if ($field->field_required): ?>
                                        <div class="invalid-feedback">
                                            <?php _e('Este campo es obligatorio', 'ultima-milla'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Campos estándar del sistema -->
                        <div class="mb-3">
                            <label for="direccion_origen" class="form-label">
                                <?php _e('Dirección de Origen', 'ultima-milla'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="direccion_origen" name="direccion_origen" required>
                            <div class="invalid-feedback">
                                <?php _e('Este campo es obligatorio', 'ultima-milla'); ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="direccion_destino" class="form-label">
                                <?php _e('Dirección de Destino', 'ultima-milla'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="direccion_destino" name="direccion_destino" required>
                            <div class="invalid-feedback">
                                <?php _e('Este campo es obligatorio', 'ultima-milla'); ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">
                                <?php _e('Descripción del Servicio', 'ultima-milla'); ?>
                            </label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fecha_programada" class="form-label">
                                <?php _e('Fecha Programada (Opcional)', 'ultima-milla'); ?>
                            </label>
                            <input type="datetime-local" class="form-control" id="fecha_programada" name="fecha_programada">
                        </div>
                        
                        <div id="um-form-messages"></div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                            <?php _e('Enviar Solicitud', 'ultima-milla'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Renderizar campo del formulario
     */
    private static function render_field($field) {
        $required = $field->field_required ? 'required' : '';
        $field_id = esc_attr($field->field_name);
        $field_name = 'campo_' . esc_attr($field->field_name);
        
        switch ($field->field_type) {
            case 'text':
                echo '<input type="text" class="form-control" id="' . $field_id . '" name="' . $field_name . '" ' . $required . '>';
                break;
                
            case 'email':
                echo '<input type="email" class="form-control" id="' . $field_id . '" name="' . $field_name . '" ' . $required . '>';
                break;
                
            case 'tel':
                echo '<input type="tel" class="form-control" id="' . $field_id . '" name="' . $field_name . '" ' . $required . '>';
                break;
                
            case 'number':
                echo '<input type="number" class="form-control" id="' . $field_id . '" name="' . $field_name . '" ' . $required . '>';
                break;
                
            case 'textarea':
                echo '<textarea class="form-control" id="' . $field_id . '" name="' . $field_name . '" rows="3" ' . $required . '></textarea>';
                break;
                
            case 'select':
                $options = !empty($field->field_options) ? json_decode($field->field_options, true) : array();
                echo '<select class="form-select" id="' . $field_id . '" name="' . $field_name . '" ' . $required . '>';
                echo '<option value="">Seleccione...</option>';
                if (!empty($options)) {
                    foreach ($options as $option) {
                        echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                    }
                }
                echo '</select>';
                break;
                
            case 'date':
                echo '<input type="date" class="form-control" id="' . $field_id . '" name="' . $field_name . '" ' . $required . '>';
                break;
                
            default:
                echo '<input type="text" class="form-control" id="' . $field_id . '" name="' . $field_name . '" ' . $required . '>';
        }
    }
    
    /**
     * Renderizar mis solicitudes
     * Uso: [ultima_milla_mis_solicitudes]
     */
    public static function render_mis_solicitudes($atts) {
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">' . __('Debes iniciar sesión para ver tus solicitudes', 'ultima-milla') . '</div>';
        }
        
        $user_id = get_current_user_id();
        
        // Obtener solicitudes del usuario
        $args = array(
            'post_type' => 'um_solicitud',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_um_cliente_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $solicitudes = get_posts($args);
        
        ob_start();
        ?>
        <div class="ultima-milla-solicitudes-wrapper">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4"><?php _e('Mis Solicitudes', 'ultima-milla'); ?></h3>
                    
                    <?php if (!empty($solicitudes)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?php _e('Código', 'ultima-milla'); ?></th>
                                        <th><?php _e('Fecha', 'ultima-milla'); ?></th>
                                        <th><?php _e('Origen', 'ultima-milla'); ?></th>
                                        <th><?php _e('Destino', 'ultima-milla'); ?></th>
                                        <th><?php _e('Estado', 'ultima-milla'); ?></th>
                                        <th><?php _e('Acciones', 'ultima-milla'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $solicitud): 
                                        $estado = get_post_meta($solicitud->ID, '_um_estado', true) ?: 'solicitado';
                                        $color = Ultima_Milla_Post_Types::get_estado_color($estado);
                                        $estados = Ultima_Milla_Post_Types::get_estados();
                                        $codigo = get_post_meta($solicitud->ID, '_um_codigo_seguimiento', true);
                                        $origen = get_post_meta($solicitud->ID, '_um_direccion_origen', true);
                                        $destino = get_post_meta($solicitud->ID, '_um_direccion_destino', true);
                                    ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($codigo); ?></strong></td>
                                            <td><?php echo date_i18n('d/m/Y H:i', strtotime($solicitud->post_date)); ?></td>
                                            <td><?php echo esc_html($origen); ?></td>
                                            <td><?php echo esc_html($destino); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo esc_attr($color); ?>">
                                                    <?php echo esc_html($estados[$estado]); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info ver-detalle" 
                                                        data-solicitud-id="<?php echo esc_attr($solicitud->ID); ?>">
                                                    <?php _e('Ver Detalle', 'ultima-milla'); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <?php _e('No tienes solicitudes registradas aún.', 'ultima-milla'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Modal para detalle -->
        <div class="modal fade" id="modalDetalleSolicitud" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php _e('Detalle de Solicitud', 'ultima-milla'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modal-detalle-content">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
