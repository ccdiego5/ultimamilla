<?php
/**
 * Constructor de formularios
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ultima_Milla_Form_Builder {
    
    /**
     * Tipos de campos disponibles
     */
    public static function get_field_types() {
        return array(
            'text' => __('Texto', 'ultima-milla'),
            'email' => __('Email', 'ultima-milla'),
            'tel' => __('Teléfono', 'ultima-milla'),
            'number' => __('Número', 'ultima-milla'),
            'textarea' => __('Área de texto', 'ultima-milla'),
            'select' => __('Lista desplegable', 'ultima-milla'),
            'date' => __('Fecha', 'ultima-milla')
        );
    }
    
    /**
     * Obtener campos de un formulario
     */
    public static function get_form_fields($form_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'um_form_fields';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE form_id = %d ORDER BY field_order ASC",
            $form_id
        ));
    }
}
