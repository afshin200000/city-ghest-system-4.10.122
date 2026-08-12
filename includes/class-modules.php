<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Modular bootstrap: each module loads in try/catch.
 * Failure of one module does not stop others.
 */
class CGS_Modules {

    /** @var array<string,array{class:string,admin?:bool,file?:string}> */
    private static $registry = array();

    /** @var array<string,string> module => error message */
    private static $errors = array();

    /** @var array<string,bool> */
    private static $loaded = array();

    public static function register_defaults() {
        self::$registry = array(
            'validation'    => array( 'class' => 'CGS_Validation' ),
            'roles'         => array( 'class' => 'CGS_Roles' ),
            'security'      => array( 'class' => 'CGS_Security' ),
            'database'      => array( 'class' => 'CGS_Database' ),
            'form_builder'  => array( 'class' => 'CGS_Form_Builder' ),
            'conditional_logic' => array( 'class' => 'CGS_Conditional_Logic' ),
            'form_styles'   => array( 'class' => 'CGS_Form_Styles' ),
            'form_templates'=> array( 'class' => 'CGS_Form_Templates' ),
            'help'          => array( 'class' => 'CGS_Help' ),
            'application'   => array( 'class' => 'CGS_Application' ),
            'sms'           => array( 'class' => 'CGS_SMS' ),
            'digital_sign'  => array( 'class' => 'CGS_Digital_Sign' ),
            'plans'         => array( 'class' => 'CGS_Plans' ),
            'crm'           => array( 'class' => 'CGS_CRM' ),
            'query_monitor' => array( 'class' => 'CGS_Query_Monitor' ),
            'chat'          => array( 'class' => 'CGS_Chat' ),
            'member'        => array( 'class' => 'CGS_Member' ),
            'auto_verify'   => array( 'class' => 'CGS_Auto_Verify' ),
            'payment'       => array( 'class' => 'CGS_Payment' ),
            'installment_calculator' => array( 'class' => 'CGS_Installment_Calculator' ),
            'settlement'  => array( 'class' => 'CGS_Settlement' ),
            'credit_risk' => array( 'class' => 'CGS_Credit_Risk' ),
            'data_manager' => array( 'class' => 'CGS_Data_Manager', 'admin' => true ),
            'admin'         => array( 'class' => 'CGS_Admin', 'admin' => true ),
            'frontend'      => array( 'class' => 'CGS_Frontend' ),
            'seo'           => array( 'class' => 'CGS_SEO' ),
        );
    }

    public static function boot() {
        self::register_defaults();
        foreach ( self::$registry as $id => $meta ) {
            if ( ! empty( $meta['admin'] ) && ! is_admin() ) {
                continue;
            }
            self::load_module( $id );
        }
        if ( is_admin() && ! empty( self::$errors ) && current_user_can( 'manage_options' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'render_errors' ) );
        }
    }

    public static function load_module( $id ) {
        if ( ! empty( self::$loaded[ $id ] ) ) {
            return true;
        }
        if ( empty( self::$registry[ $id ] ) ) {
            return false;
        }
        $class = self::$registry[ $id ]['class'];
        try {
            if ( ! class_exists( $class ) ) {
                throw new RuntimeException( "Class {$class} not found" );
            }
            if ( method_exists( $class, 'init' ) ) {
                call_user_func( array( $class, 'init' ) );
            }
            self::$loaded[ $id ] = true;
            return true;
        } catch ( Throwable $e ) {
            self::$errors[ $id ] = $e->getMessage();
            self::$loaded[ $id ] = false;
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[CGS Module ' . $id . '] ' . $e->getMessage() );
            }
            return false;
        }
    }

    public static function is_loaded( $id ) {
        return ! empty( self::$loaded[ $id ] );
    }

    public static function get_errors() {
        return self::$errors;
    }

    public static function render_errors() {
        if ( empty( self::$errors ) ) {
            return;
        }
        echo '<div class="notice notice-warning"><p><strong>شهر قسط:</strong> برخی ماژول‌ها بارگذاری نشدند (بقیه فعال‌اند):</p><ul>';
        foreach ( self::$errors as $id => $msg ) {
            echo '<li><code>' . esc_html( $id ) . '</code> — ' . esc_html( $msg ) . '</li>';
        }
        echo '</ul></div>';
    }
}
