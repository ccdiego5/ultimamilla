<?php
/**
 * Página de administración de solicitudes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ultima_Milla_Admin_Solicitudes {
    
    /**
     * Renderizar página
     */
    public static function render_page() {
        $user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $is_mensajero = Ultima_Milla_Roles::is_mensajero();
        
        // Filtros
        $estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
        
        // Construir query
        $args = array(
            'post_type' => 'um_solicitud',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Filtrar según rol
        if (!$is_admin) {
            if ($is_mensajero) {
                // Mensajero: solo sus solicitudes asignadas
                $args['meta_query'] = array(
                    array(
                        'key' => '_um_mensajero_id',
                        'value' => $user_id,
                        'compare' => '='
                    )
                );
            } else {
                // Cliente: solo sus solicitudes
                $args['meta_query'] = array(
                    array(
                        'key' => '_um_cliente_id',
                        'value' => $user_id,
                        'compare' => '='
                    )
                );
            }
        }
        
        // Filtro por estado
        if (!empty($estado_filtro)) {
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = array();
            }
            $args['meta_query'][] = array(
                'key' => '_um_estado',
                'value' => $estado_filtro,
                'compare' => '='
            );
        }
        
        $solicitudes = get_posts($args);
        $estados = Ultima_Milla_Post_Types::get_estados();
        
        // Obtener lista de mensajeros si es admin
        $mensajeros = array();
        if ($is_admin) {
            $mensajeros = get_users(array('role' => 'um_mensajero'));
        }
        
        ?>
        <div class="wrap ultima-milla-admin">
            <h1 class="wp-heading-inline">
                <?php _e('Solicitudes de Última Milla', 'ultima-milla'); ?>
            </h1>
            
            <hr class="wp-header-end">
            
            <div class="postbox">
                <div class="inside">
                    <!-- Filtros rápidos -->
                    <div class="um-filter-buttons">
                        <strong><?php _e('Filtro rápido por estado:', 'ultima-milla'); ?></strong>
                        <button type="button" class="button filtro-estado-dt active" data-estado="">
                            <?php _e('Todos', 'ultima-milla'); ?>
                        </button>
                        <button type="button" class="button filtro-estado-dt" data-estado="Solicitado">
                            <?php _e('Solicitado', 'ultima-milla'); ?>
                        </button>
                        <button type="button" class="button filtro-estado-dt" data-estado="En Curso">
                            <?php _e('En Curso', 'ultima-milla'); ?>
                        </button>
                        <button type="button" class="button filtro-estado-dt" data-estado="Entregado">
                            <?php _e('Entregado', 'ultima-milla'); ?>
                        </button>
                        <button type="button" class="button filtro-estado-dt" data-estado="Cancelado">
                            <?php _e('Cancelado', 'ultima-milla'); ?>
                        </button>
                    </div>
                    
                    <!-- Tabla de solicitudes -->
                    <table id="tabla-solicitudes" class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Código', 'ultima-milla'); ?></th>
                                        <th><?php _e('Fecha Solicitud', 'ultima-milla'); ?></th>
                                        <th><?php _e('Origen', 'ultima-milla'); ?></th>
                                        <th><?php _e('Destino', 'ultima-milla'); ?></th>
                                        <?php if ($is_admin): ?>
                                            <th><?php _e('Cliente', 'ultima-milla'); ?></th>
                                            <th><?php _e('Mensajero', 'ultima-milla'); ?></th>
                                        <?php endif; ?>
                                        <th><?php _e('Estado', 'ultima-milla'); ?></th>
                                        <th data-orderable="false"><?php _e('Acciones', 'ultima-milla'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                        <?php foreach ($solicitudes as $solicitud): 
                                            $solicitud_id = $solicitud->ID;
                                            $codigo = get_post_meta($solicitud_id, '_um_codigo_seguimiento', true);
                                            $origen = get_post_meta($solicitud_id, '_um_direccion_origen', true);
                                            $destino = get_post_meta($solicitud_id, '_um_direccion_destino', true);
                                            $estado = get_post_meta($solicitud_id, '_um_estado', true) ?: 'solicitado';
                                            $color = Ultima_Milla_Post_Types::get_estado_color($estado);
                                            $cliente_id = get_post_meta($solicitud_id, '_um_cliente_id', true);
                                            $mensajero_id = get_post_meta($solicitud_id, '_um_mensajero_id', true);
                                            
                                            $cliente = get_userdata($cliente_id);
                                            $mensajero = $mensajero_id ? get_userdata($mensajero_id) : null;
                                        ?>
                                            <tr>
                                                <td><strong class="text-primary"><?php echo esc_html($codigo); ?></strong></td>
                                                <td data-order="<?php echo strtotime($solicitud->post_date); ?>">
                                                    <?php echo date_i18n('d/m/Y H:i', strtotime($solicitud->post_date)); ?>
                                                </td>
                                                <td><?php echo esc_html($origen); ?></td>
                                                <td><?php echo esc_html($destino); ?></td>
                                                <?php if ($is_admin): ?>
                                                    <td><?php echo $cliente ? esc_html($cliente->display_name) : '-'; ?></td>
                                                    <td>
                                                        <?php if ($mensajero): ?>
                                                            <?php echo esc_html($mensajero->display_name); ?>
                                                        <?php else: ?>
                                                            <span class="um-badge secondary"><?php _e('Sin asignar', 'ultima-milla'); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <span class="um-badge <?php echo esc_attr($color); ?>">
                                                        <?php echo esc_html($estados[$estado]); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="button-group">
                                                        <button type="button" class="button button-small ver-detalle-admin" 
                                                                data-solicitud-id="<?php echo esc_attr($solicitud_id); ?>"
                                                                title="<?php _e('Ver Detalle', 'ultima-milla'); ?>">
                                                            <span class="dashicons dashicons-visibility"></span>
                                                        </button>
                                                        
                                                        <?php if ($is_admin || $is_mensajero): ?>
                                                            <button type="button" class="button button-small cambiar-estado" 
                                                                    data-solicitud-id="<?php echo esc_attr($solicitud_id); ?>"
                                                                    data-estado-actual="<?php echo esc_attr($estado); ?>"
                                                                    title="<?php _e('Cambiar Estado', 'ultima-milla'); ?>">
                                                                <span class="dashicons dashicons-update"></span>
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($is_admin): ?>
                                                            <button type="button" class="button button-small asignar-mensajero" 
                                                                    data-solicitud-id="<?php echo esc_attr($solicitud_id); ?>"
                                                                    data-mensajero-actual="<?php echo esc_attr($mensajero_id); ?>"
                                                                    title="<?php _e('Asignar Mensajero', 'ultima-milla'); ?>">
                                                                <span class="dashicons dashicons-groups"></span>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                </div>
            </div>
        </div>
        
        <!-- Backdrop del Modal -->
        <div class="um-modal-backdrop" id="modal-backdrop"></div>
        
        <!-- Modal: Cambiar Estado -->
        <div class="um-modal" id="modalCambiarEstado">
            <div class="um-modal-header">
                <h2><?php _e('Cambiar Estado', 'ultima-milla'); ?></h2>
                <button type="button" class="um-modal-close" data-modal-close="modalCambiarEstado">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="um-modal-body">
                <input type="hidden" id="modal-solicitud-id">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="modal-nuevo-estado"><?php _e('Nuevo Estado', 'ultima-milla'); ?></label>
                        </th>
                        <td>
                            <select id="modal-nuevo-estado" class="regular-text">
                                <?php foreach ($estados as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="um-modal-footer">
                <button type="button" class="button" data-modal-close="modalCambiarEstado"><?php _e('Cancelar', 'ultima-milla'); ?></button>
                <button type="button" class="button button-primary" id="btn-guardar-estado"><?php _e('Guardar', 'ultima-milla'); ?></button>
            </div>
        </div>
        
        <!-- Modal: Asignar Mensajero -->
        <?php if ($is_admin): ?>
        <div class="um-modal" id="modalAsignarMensajero">
            <div class="um-modal-header">
                <h2><?php _e('Asignar Mensajero', 'ultima-milla'); ?></h2>
                <button type="button" class="um-modal-close" data-modal-close="modalAsignarMensajero">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="um-modal-body">
                <input type="hidden" id="modal-asignar-solicitud-id">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="modal-mensajero"><?php _e('Mensajero', 'ultima-milla'); ?></label>
                        </th>
                        <td>
                            <select id="modal-mensajero" class="regular-text">
                                <option value=""><?php _e('Sin asignar', 'ultima-milla'); ?></option>
                                <?php foreach ($mensajeros as $mensajero): ?>
                                    <option value="<?php echo esc_attr($mensajero->ID); ?>">
                                        <?php echo esc_html($mensajero->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="um-modal-footer">
                <button type="button" class="button" data-modal-close="modalAsignarMensajero"><?php _e('Cancelar', 'ultima-milla'); ?></button>
                <button type="button" class="button button-primary" id="btn-guardar-mensajero"><?php _e('Asignar', 'ultima-milla'); ?></button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Modal: Ver Detalle -->
        <div class="um-modal" id="modalDetalleAdmin" style="max-width: 800px;">
            <div class="um-modal-header">
                <h2><?php _e('Detalle de Solicitud', 'ultima-milla'); ?></h2>
                <button type="button" class="um-modal-close" data-modal-close="modalDetalleAdmin">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="um-modal-body" id="modal-detalle-admin-content">
                <p style="text-align: center;">
                    <span class="um-spinner"></span>
                    <?php _e('Cargando...', 'ultima-milla'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
