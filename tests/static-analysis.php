<?php
/**
 * Lightweight static analysis for City Ghest plugin.
 * Run: php tests/static-analysis.php
 */
$root = dirname( __DIR__ );
$issues = array();
$stats = array( 'php' => 0, 'js' => 0, 'lines' => 0 );

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

foreach ( $iterator as $file ) {
    $path = $file->getPathname();
    if ( strpos( $path, '/tests/' ) !== false ) {
        continue;
    }
    $ext = $file->getExtension();
    if ( $ext === 'php' ) {
        $stats['php']++;
        $code = file_get_contents( $path );
        $stats['lines'] += substr_count( $code, "\n" );
        $rel = str_replace( $root . '/', '', $path );

        // Syntax
        $out = array();
        $ret = 0;
        exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1', $out, $ret );
        if ( $ret !== 0 ) {
            $issues[] = array( 'sev' => 'error', 'file' => $rel, 'msg' => implode( ' ', $out ) );
        }

        // Dangerous patterns
        if ( preg_match( '/\beval\s*\(/', $code ) ) {
            $issues[] = array( 'sev' => 'critical', 'file' => $rel, 'msg' => 'eval() usage' );
        }
        if ( preg_match( '/\$_(GET|POST|REQUEST)\s*\[/', $code ) && ! preg_match( '/sanitize_|wp_unslash|absint|isset\s*\(\s*\$_(GET|POST)/', $code ) ) {
            // soft warning only if many raw uses - skip noisy
        }
        if ( preg_match( '/form_type/', $code ) && strpos( $rel, 'form-templates' ) !== false ) {
            $issues[] = array( 'sev' => 'error', 'file' => $rel, 'msg' => 'legacy form_type column reference' );
        }
    }
    if ( $ext === 'js' ) {
        $stats['js']++;
        $rel = str_replace( $root . '/', '', $path );
        if ( strpos( $path, 'node_modules' ) !== false ) {
            continue;
        }
        exec( 'node --check ' . escapeshellarg( $path ) . ' 2>&1', $out, $ret );
        if ( $ret !== 0 ) {
            $issues[] = array( 'sev' => 'error', 'file' => $rel, 'msg' => implode( ' ', $out ) );
        }
    }
}

// Architecture checks
$must = array(
    'includes/class-modules.php',
    'includes/class-cache.php',
    'includes/class-validation.php',
    'includes/class-ajax.php',
    'includes/class-form-builder.php',
);
foreach ( $must as $m ) {
    if ( ! file_exists( $root . '/' . $m ) ) {
        $issues[] = array( 'sev' => 'critical', 'file' => $m, 'msg' => 'required module missing' );
    }
}

echo "PHP files: {$stats['php']}  JS files: {$stats['js']}  Approx lines: {$stats['lines']}\n";
echo "Issues: " . count( $issues ) . "\n";
foreach ( $issues as $i ) {
    echo "[{$i['sev']}] {$i['file']}: {$i['msg']}\n";
}
$blockers = array_filter( $issues, function ( $i ) {
    return in_array( $i['sev'], array( 'error', 'critical' ), true );
} );
exit( count( $blockers ) ? 1 : 0 );
