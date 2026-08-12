<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Advanced conditional logic for form fields (Gravity Forms–style).
 *
 * Rule shape stored in field validation JSON under key "conditions":
 * {
 *   "enabled": true,
 *   "action": "show"|"hide",
 *   "logic": "and"|"or",
 *   "rules": [
 *     { "field": "person_type", "op": "equals"|"not_equals"|"contains"|"empty"|"not_empty", "value": "legal" }
 *   ]
 * }
 */
class CGS_Conditional_Logic {

    public static function init() {
        add_action( 'wp_ajax_cgs_save_field_conditions', array( __CLASS__, 'ajax_save' ) );
    }

    public static function evaluate_rules( $rules, $logic, array $values ) {
        if ( empty( $rules ) || ! is_array( $rules ) ) {
            return true;
        }
        $results = array();
        foreach ( $rules as $rule ) {
            $field = $rule['field'] ?? '';
            $op    = $rule['op'] ?? 'equals';
            $want  = isset( $rule['value'] ) ? (string) $rule['value'] : '';
            $have  = isset( $values[ $field ] ) ? (string) $values[ $field ] : '';
            $ok    = false;
            switch ( $op ) {
                case 'equals':
                    $ok = ( $have === $want );
                    break;
                case 'not_equals':
                    $ok = ( $have !== $want );
                    break;
                case 'contains':
                    $ok = ( $want !== '' && strpos( $have, $want ) !== false );
                    break;
                case 'empty':
                    $ok = ( $have === '' );
                    break;
                case 'not_empty':
                    $ok = ( $have !== '' );
                    break;
                default:
                    $ok = true;
            }
            $results[] = $ok;
        }
        if ( ( $logic ?? 'and' ) === 'or' ) {
            return in_array( true, $results, true );
        }
        return ! in_array( false, $results, true );
    }

    /**
     * Returns whether field should be visible given current values.
     */
    public static function is_visible( array $field, array $values ) {
        $val = array();
        if ( ! empty( $field['validation'] ) ) {
            $val = is_array( $field['validation'] )
                ? $field['validation']
                : ( json_decode( $field['validation'], true ) ?: array() );
        }
        $cond = $val['conditions'] ?? null;
        if ( empty( $cond['enabled'] ) || empty( $cond['rules'] ) ) {
            return true;
        }
        $match = self::evaluate_rules( $cond['rules'], $cond['logic'] ?? 'and', $values );
        $action = $cond['action'] ?? 'show';
        return ( $action === 'show' ) ? $match : ! $match;
    }

    public static function ajax_save() {
        if ( class_exists( 'CGS_Ajax' ) ) {
            CGS_Ajax::verify();
        } else {
            check_ajax_referer( 'cgs_admin_nonce', 'nonce' );
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( 'دسترسی' );
            }
        }
        $field_id = absint( $_POST['field_id'] ?? 0 );
        $raw      = wp_unslash( $_POST['conditions'] ?? '{}' );
        $cond     = json_decode( $raw, true );
        if ( ! $field_id || ! is_array( $cond ) ) {
            wp_send_json_error( 'داده نامعتبر' );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'cgs_form_fields';
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT validation FROM $table WHERE id = %d", $field_id ), ARRAY_A );
        if ( ! $row ) {
            wp_send_json_error( 'فیلد یافت نشد' );
        }
        $val = json_decode( $row['validation'] ?: '{}', true );
        if ( ! is_array( $val ) ) {
            $val = array();
        }
        $val['conditions'] = array(
            'enabled' => ! empty( $cond['enabled'] ),
            'action'  => in_array( $cond['action'] ?? 'show', array( 'show', 'hide' ), true ) ? $cond['action'] : 'show',
            'logic'   => ( ( $cond['logic'] ?? 'and' ) === 'or' ) ? 'or' : 'and',
            'rules'   => array_values( array_map( function ( $r ) {
                return array(
                    'field' => sanitize_key( $r['field'] ?? '' ),
                    'op'    => sanitize_key( $r['op'] ?? 'equals' ),
                    'value' => sanitize_text_field( $r['value'] ?? '' ),
                );
            }, is_array( $cond['rules'] ?? null ) ? $cond['rules'] : array() ) ),
        );
        $wpdb->update( $table, array( 'validation' => wp_json_encode( $val ) ), array( 'id' => $field_id ) );
        if ( class_exists( 'CGS_Database' ) ) {
            CGS_Database::invalidate_fields_cache();
        }
        wp_send_json_success( array( 'message' => 'منطق شرطی ذخیره شد' ) );
    }
}
