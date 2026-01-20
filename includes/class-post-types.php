<?php
/**
 * Registro de Custom Post Types
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ultima_Milla_Post_Types {
    
    /**
     * Registrar todos los post types
     */
    public static function register() {
        self::register_solicitud();
        self::register_formulario();
    }
    
    /**
     * Registrar CPT: Solicitud
     */
    private static function register_solicitud() {
        $labels = array(
            'name' => __('Solicitudes', 'ultima-milla'),
            'singular_name' => __('Solicitud', 'ultima-milla'),
            'add_new' => __('Añadir Nueva', 'ultima-milla'),
            'add_new_item' => __('Añadir Nueva Solicitud', 'ultima-milla'),
            'edit_item' => __('Editar Solicitud', 'ultima-milla'),
            'new_item' => __('Nueva Solicitud', 'ultima-milla'),
            'view_item' => __('Ver Solicitud', 'ultima-milla'),
            'search_items' => __('Buscar Solicitudes', 'ultima-milla'),
            'not_found' => __('No se encontraron solicitudes', 'ultima-milla'),
            'not_found_in_trash' => __('No hay solicitudes en la papelera', 'ultima-milla')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false
        );
        
        register_post_type('um_solicitud', $args);
    }
    
    /**
     * Registrar CPT: Formulario
     */
    private static function register_formulario() {
        $labels = array(
            'name' => __('Formularios', 'ultima-milla'),
            'singular_name' => __('Formulario', 'ultima-milla'),
            'add_new' => __('Añadir Nuevo', 'ultima-milla'),
            'add_new_item' => __('Añadir Nuevo Formulario', 'ultima-milla'),
            'edit_item' => __('Editar Formulario', 'ultima-milla'),
            'new_item' => __('Nuevo Formulario', 'ultima-milla'),
            'view_item' => __('Ver Formulario', 'ultima-milla'),
            'search_items' => __('Buscar Formularios', 'ultima-milla'),
            'not_found' => __('No se encontraron formularios', 'ultima-milla'),
            'not_found_in_trash' => __('No hay formularios en la papelera', 'ultima-milla')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false
        );
        
        register_post_type('um_formulario', $args);
    }
    
    /**
     * Estados de solicitud disponibles
     */
    public static function get_estados() {
        return array(
            'solicitado' => __('Solicitado', 'ultima-milla'),
            'en_curso' => __('En Curso', 'ultima-milla'),
            'entregado' => __('Entregado', 'ultima-milla'),
            'cancelado' => __('Cancelado', 'ultima-milla')
        );
    }
    
    /**
     * Obtener color del estado
     */
    public static function get_estado_color($estado) {
        $colores = array(
            'solicitado' => 'warning',
            'en_curso' => 'info',
            'entregado' => 'success',
            'cancelado' => 'danger'
        );
        
        return isset($colores[$estado]) ? $colores[$estado] : 'secondary';
    }
}
