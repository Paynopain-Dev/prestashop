<?php
/**
 * Unit test bootstrap: loads the module libraries without PrestaShop.
 *
 * Only the few PrestaShop pieces the libraries touch are stubbed (see tests/Stubs).
 * Development only: this folder is never shipped in the module zip.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

// Logger writes to _PS_MODULE_DIR_ . 'paylands/logs': point it to a throwaway folder.
$modulesDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'paylands-tests-' . getmypid() . DIRECTORY_SEPARATOR;
if (!is_dir($modulesDir . 'paylands/logs')) {
    mkdir($modulesDir . 'paylands/logs', 0777, true);
}
define('_PS_MODULE_DIR_', $modulesDir);
register_shutdown_function(function () use ($modulesDir) {
    foreach (glob($modulesDir . 'paylands/logs/*') ?: array() as $file) {
        unlink($file);
    }
    @rmdir($modulesDir . 'paylands/logs');
    @rmdir($modulesDir . 'paylands');
    @rmdir($modulesDir);
});

require __DIR__ . '/Stubs/Configuration.php';
require dirname(__DIR__) . '/libs/Logger.php';
require dirname(__DIR__) . '/libs/PaymentOrder.php';
