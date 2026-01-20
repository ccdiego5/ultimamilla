<?php
/**
 * Gestión de roles y capacidades
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ultima_Milla_Roles {
    
    /**
     * Crear roles personalizados
     */
    public static function create_roles() {
        // Rol: Cliente
        add_role('um_cliente', __('Cliente Última Milla', 'ultima-milla'), array(
            'read' => true,
            'um_crear_solicitud' => true,
            'um_ver_propias_solicitudes' => true
        ));
        
        // Rol: Mensajero
        add_role('um_mensajero', __('Mensajero', 'ultima-milla'), array(
            'read' => true,
            'um_ver_solicitudes_asignadas' => true,
            'um_actualizar_estado_solicitud' => true
        ));
        
        // Agregar capacidades al administrador
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('um_gestionar_solicitudes');
            $admin->add_cap('um_asignar_mensajeros');
            $admin->add_cap('um_gestionar_formularios');
        }
    }
    
    /**
     * Eliminar roles personalizados
     */
    public static function remove_roles() {
        remove_role('um_cliente');
        remove_role('um_mensajero');
        
        // Remover capacidades del administrador
        $admin = get_role('administrator');
        if ($admin) {
            $admin->remove_cap('um_gestionar_solicitudes');
            $admin->remove_cap('um_asignar_mensajeros');
            $admin->remove_cap('um_gestionar_formularios');
        }
    }
    
    /**
     * Verificar si el usuario es cliente
     */
    public static function is_cliente($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $user = get_userdata($user_id);
        return $user && in_array('um_cliente', $user->roles);
    }
    
    /**
     * Verificar si el usuario es mensajero
     */
    public static function is_mensajero($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        $user = get_userdata($user_id);
        return $user && in_array('um_mensajero', $user->roles);
    }
    
    /**
     * Verificar si el usuario es administrador
     */
    public static function is_admin($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        return user_can($user_id, 'manage_options');
    }
}
