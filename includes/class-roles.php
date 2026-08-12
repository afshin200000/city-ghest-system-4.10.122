<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGS_Roles {

    public static function init() {
        // Nothing heavy on every load
    }

    public static function create_roles() {
        // Member role - no admin capabilities
        add_role( 'cg_member', 'عضو شهر قسط', array(
            'read' => true,
        ) );

        // Optional: Manager role for future staff
        add_role( 'cg_manager', 'مدیر جذب شهر قسط', array(
            'read'                   => true,
            'cgs_manage_applications'=> true,
            'cgs_view_reports'       => true,
        ) );

        // Add capabilities to administrator
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'cgs_manage_applications' );
            $admin->add_cap( 'cgs_manage_forms' );
            $admin->add_cap( 'cgs_manage_settings' );
            $admin->add_cap( 'cgs_view_reports' );
            $admin->add_cap( 'cgs_manage_chat' );
        }
    }

    public static function remove_roles() {
        remove_role( 'cg_member' );
        remove_role( 'cg_manager' );
    }
}
