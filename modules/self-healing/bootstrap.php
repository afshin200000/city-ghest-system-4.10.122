<?php
/**
 * Self-Healing Monitor — isolated OOP module (Phase 1: report + runbook + stop/resume)
 * Does NOT auto-rewrite plugin files. Safe whitelist actions only after admin confirm.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'cgs_module_self_healing_enabled' ) ) {
	function cgs_module_self_healing_enabled() {
		$flags = get_option( 'cgs_module_flags', array() );
		if ( is_array( $flags ) && array_key_exists( 'self-healing', $flags ) ) {
			return ! empty( $flags['self-healing'] );
		}
		return true;
	}
}
if ( ! cgs_module_self_healing_enabled() ) { return; }

require_once __DIR__ . '/includes/class-engine.php';
CGS_SH_Engine::boot();
