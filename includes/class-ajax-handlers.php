<?php
/**
 * Handlers AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ultima_Milla_Ajax_Handlers {
    
    /**
     * Registrar handlers AJAX
     */
    public static function register() {
        // Para usuarios autenticados
        add_action('wp_ajax_um_crear_solicitud', array(__CLASS__, 'crear_solicitud'));
        add_action('wp_ajax_um_obtener_detalle_solicitud', array(__CLASS__, 'obtener_detalle_solicitud'));
        add_action('wp_ajax_um_actualizar_estado', array(__CLASS__, 'actualizar_estado'));
        add_action('wp_ajax_um_asignar_mensajero', array(__CLASS__, 'asignar_mensajero'));
        add_action('wp_ajax_um_guardar_campo_formulario', array(__CLASS__, 'guardar_campo_formulario'));
        add_action('wp_ajax_um_eliminar_campo_formulario', array(__CLASS__, 'eliminar_campo_formulario'));
        add_action('wp_ajax_um_guardar_formulario', array(__CLASS__, 'guardar_formulario'));
        add_action('wp_ajax_um_eliminar_formulario', array(__CLASS__, 'eliminar_formulario'));
        
        // Para usuarios no autenticados (si se permite)
        add_action('wp_ajax_nopriv_um_crear_solicitud', array(__CLASS__, 'crear_solicitud'));
    }
    
    /**
     * Crear nueva solicitud
     */
    public static function crear_solicitud() {
        check_ajax_referer('um_crear_solicitud', 'um_nonce');
        
        // Sanitizar datos
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $direccion_origen = isset($_POST['direccion_origen']) ? sanitize_text_field($_POST['direccion_origen']) : '';
        $direccion_destino = isset($_POST['direccion_destino']) ? sanitize_text_field($_POST['direccion_destino']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $fecha_programada = isset($_POST['fecha_programada']) ? sanitize_text_field($_POST['fecha_programada']) : '';
        
        // Validar
        if (empty($direccion_origen) || empty($direccion_destino)) {
            wp_send_json_error(array(
                'message' => __('Origen y destino son obligatorios', 'ultima-milla')
            ));
        }
        
        // Generar código de seguimiento único
        $codigo_seguimiento = self::generar_codigo_seguimiento();
        
        // Crear solicitud
        $post_id = wp_insert_post(array(
            'post_type' => 'um_solicitud',
            'post_title' => 'Solicitud ' . $codigo_seguimiento,
            'post_status' => 'publish',
            'post_author' => get_current_user_id() ?: 0
        ));
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array(
                'message' => __('Error al crear la solicitud', 'ultima-milla')
            ));
        }
        
        // Guardar metadatos
        update_post_meta($post_id, '_um_form_id', $form_id);
        update_post_meta($post_id, '_um_cliente_id', get_current_user_id() ?: 0);
        update_post_meta($post_id, '_um_codigo_seguimiento', $codigo_seguimiento);
        update_post_meta($post_id, '_um_direccion_origen', $direccion_origen);
        update_post_meta($post_id, '_um_direccion_destino', $direccion_destino);
        update_post_meta($post_id, '_um_descripcion', $descripcion);
        update_post_meta($post_id, '_um_estado', 'solicitado');
        update_post_meta($post_id, '_um_fecha_solicitud', current_time('mysql'));
        
        if (!empty($fecha_programada)) {
            update_post_meta($post_id, '_um_fecha_programada', $fecha_programada);
        }
        
        // Guardar campos personalizados del formulario
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'campo_') === 0) {
                $field_name = str_replace('campo_', '', $key);
                update_post_meta($post_id, '_um_campo_' . $field_name, sanitize_text_field($value));
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Solicitud creada exitosamente', 'ultima-milla'),
            'codigo' => $codigo_seguimiento,
            'solicitud_id' => $post_id
        ));
    }
    
    /**
     * Obtener detalle de solicitud
     */
    public static function obtener_detalle_solicitud() {
        // Verificar nonce (puede venir desde admin o frontend)
        $nonce_admin = isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ultima_milla_admin_nonce');
        $nonce_frontend = isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ultima_milla_nonce');
        
        if (!$nonce_admin && !$nonce_frontend) {
            wp_send_json_error(array('message' => __('Verificación de seguridad falló', 'ultima-milla')));
        }
        
        $solicitud_id = isset($_POST['solicitud_id']) ? intval($_POST['solicitud_id']) : 0;
        
        // Log para debugging
        error_log('Última Milla - Obtener detalle solicitud ID: ' . $solicitud_id);
        
        if (!$solicitud_id) {
            wp_send_json_error(array('message' => __('ID inválido', 'ultima-milla')));
        }
        
        $solicitud = get_post($solicitud_id);
        
        if (!$solicitud || $solicitud->post_type !== 'um_solicitud') {
            error_log('Última Milla - Solicitud no encontrada o tipo incorrecto. Tipo: ' . ($solicitud ? $solicitud->post_type : 'null'));
            wp_send_json_error(array('message' => __('Solicitud no encontrada', 'ultima-milla')));
        }
        
        // Verificar permisos
        $user_id = get_current_user_id();
        $cliente_id = get_post_meta($solicitud_id, '_um_cliente_id', true);
        $mensajero_id = get_post_meta($solicitud_id, '_um_mensajero_id', true);
        
        if (!current_user_can('manage_options') && 
            $user_id != $cliente_id && 
            $user_id != $mensajero_id) {
            wp_send_json_error(array('message' => __('No tienes permiso para ver esta solicitud', 'ultima-milla')));
        }
        
        // Obtener datos
        $estado = get_post_meta($solicitud_id, '_um_estado', true);
        $estados = Ultima_Milla_Post_Types::get_estados();
        $color = Ultima_Milla_Post_Types::get_estado_color($estado);
        
        $data = array(
            'codigo' => get_post_meta($solicitud_id, '_um_codigo_seguimiento', true),
            'fecha_solicitud' => get_post_meta($solicitud_id, '_um_fecha_solicitud', true),
            'direccion_origen' => get_post_meta($solicitud_id, '_um_direccion_origen', true),
            'direccion_destino' => get_post_meta($solicitud_id, '_um_direccion_destino', true),
            'descripcion' => get_post_meta($solicitud_id, '_um_descripcion', true),
            'estado' => $estado,
            'estado_label' => $estados[$estado],
            'estado_color' => $color,
            'fecha_programada' => get_post_meta($solicitud_id, '_um_fecha_programada', true),
            'fecha_entrega' => get_post_meta($solicitud_id, '_um_fecha_entrega', true)
        );
        
        // Información del mensajero si está asignado
        if ($mensajero_id) {
            $mensajero = get_userdata($mensajero_id);
            if ($mensajero) {
                $data['mensajero'] = $mensajero->display_name;
            }
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Actualizar estado de solicitud
     */
    public static function actualizar_estado() {
        check_ajax_referer('ultima_milla_admin_nonce', 'nonce');
        
        $solicitud_id = isset($_POST['solicitud_id']) ? intval($_POST['solicitud_id']) : 0;
        $nuevo_estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
        
        // Verificar permisos
        if (!current_user_can('um_actualizar_estado_solicitud') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso para actualizar el estado', 'ultima-milla')));
        }
        
        $estados_validos = array_keys(Ultima_Milla_Post_Types::get_estados());
        
        if (!in_array($nuevo_estado, $estados_validos)) {
            wp_send_json_error(array('message' => __('Estado inválido', 'ultima-milla')));
        }
        
        update_post_meta($solicitud_id, '_um_estado', $nuevo_estado);
        
        if ($nuevo_estado === 'entregado') {
            update_post_meta($solicitud_id, '_um_fecha_entrega', current_time('mysql'));
        }
        
        wp_send_json_success(array(
            'message' => __('Estado actualizado correctamente', 'ultima-milla')
        ));
    }
    
    /**
     * Asignar mensajero a solicitud
     */
    public static function asignar_mensajero() {
        check_ajax_referer('ultima_milla_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso', 'ultima-milla')));
        }
        
        $solicitud_id = isset($_POST['solicitud_id']) ? intval($_POST['solicitud_id']) : 0;
        $mensajero_id = isset($_POST['mensajero_id']) ? intval($_POST['mensajero_id']) : 0;
        
        update_post_meta($solicitud_id, '_um_mensajero_id', $mensajero_id);
        
        // Si se asigna mensajero, cambiar estado a "en_curso" si estaba en "solicitado"
        $estado_actual = get_post_meta($solicitud_id, '_um_estado', true);
        if ($estado_actual === 'solicitado') {
            update_post_meta($solicitud_id, '_um_estado', 'en_curso');
        }
        
        wp_send_json_success(array(
            'message' => __('Mensajero asignado correctamente', 'ultima-milla')
        ));
    }
    
    /**
     * Guardar campo de formulario
     */
    public static function guardar_campo_formulario() {
        check_ajax_referer('ultima_milla_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso', 'ultima-milla')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'um_form_fields';
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $field_type = isset($_POST['field_type']) ? sanitize_text_field($_POST['field_type']) : '';
        $field_label = isset($_POST['field_label']) ? sanitize_text_field($_POST['field_label']) : '';
        $field_name = isset($_POST['field_name']) ? sanitize_key($_POST['field_name']) : '';
        $field_required = isset($_POST['field_required']) ? intval($_POST['field_required']) : 0;
        $field_options = isset($_POST['field_options']) ? $_POST['field_options'] : array();
        
        // Obtener el último orden
        $max_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(field_order) FROM $table_name WHERE form_id = %d",
            $form_id
        ));
        
        $wpdb->insert($table_name, array(
            'form_id' => $form_id,
            'field_type' => $field_type,
            'field_label' => $field_label,
            'field_name' => $field_name,
            'field_required' => $field_required,
            'field_options' => json_encode($field_options),
            'field_order' => ($max_order + 1)
        ));
        
        wp_send_json_success(array(
            'message' => __('Campo agregado correctamente', 'ultima-milla'),
            'field_id' => $wpdb->insert_id
        ));
    }
    
    /**
     * Eliminar campo de formulario
     */
    public static function eliminar_campo_formulario() {
        check_ajax_referer('ultima_milla_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso', 'ultima-milla')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'um_form_fields';
        
        $field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
        
        $wpdb->delete($table_name, array('id' => $field_id));
        
        wp_send_json_success(array(
            'message' => __('Campo eliminado correctamente', 'ultima-milla')
        ));
    }
    
    /**
     * Guardar información del formulario
     */
    public static function guardar_formulario() {
        check_ajax_referer('ultima_milla_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso', 'ultima-milla')));
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';
        
        if (!$form_id || empty($title)) {
            wp_send_json_error(array('message' => __('Datos incompletos', 'ultima-milla')));
        }
        
        // Validar que el post existe y es del tipo correcto
        $form = get_post($form_id);
        if (!$form || $form->post_type !== 'um_formulario') {
            wp_send_json_error(array('message' => __('Formulario no encontrado', 'ultima-milla')));
        }
        
        // Actualizar el formulario
        $updated = wp_update_post(array(
            'ID' => $form_id,
            'post_title' => $title,
            'post_status' => in_array($status, array('draft', 'publish')) ? $status : 'draft'
        ));
        
        if (is_wp_error($updated)) {
            wp_send_json_error(array('message' => __('Error al guardar', 'ultima-milla')));
        }
        
        wp_send_json_success(array(
            'message' => __('Formulario guardado correctamente', 'ultima-milla')
        ));
    }
    
    /**
     * Eliminar formulario
     */
    public static function eliminar_formulario() {
        check_ajax_referer('ultima_milla_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permiso', 'ultima-milla')));
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id) {
            wp_send_json_error(array('message' => __('ID inválido', 'ultima-milla')));
        }
        
        // Validar que el post existe y es del tipo correcto
        $form = get_post($form_id);
        if (!$form || $form->post_type !== 'um_formulario') {
            wp_send_json_error(array('message' => __('Formulario no encontrado', 'ultima-milla')));
        }
        
        // Eliminar campos asociados
        global $wpdb;
        $table_name = $wpdb->prefix . 'um_form_fields';
        $wpdb->delete($table_name, array('form_id' => $form_id));
        
        // Eliminar el formulario
        $deleted = wp_delete_post($form_id, true); // true = eliminar permanentemente
        
        if (!$deleted) {
            wp_send_json_error(array('message' => __('Error al eliminar el formulario', 'ultima-milla')));
        }
        
        wp_send_json_success(array(
            'message' => __('Formulario eliminado correctamente', 'ultima-milla')
        ));
    }
    
    /**
     * Generar código de seguimiento único
     */
    private static function generar_codigo_seguimiento() {
        return 'UM-' . strtoupper(wp_generate_password(8, false));
    }
}
