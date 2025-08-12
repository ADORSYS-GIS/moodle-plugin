<?php
define('CLI_SCRIPT', true);
require(__DIR__.'/../../config.php'); // Only two levels up from plugin root

echo "--- Provider Debug Script ---\n";

// Check if the provider class exists
$provider_class = 'local_ai_openai_v1\\provider';
if (class_exists($provider_class)) {
    echo "✓ Provider class exists: {$provider_class}\n";
    
    try {
        $provider = new $provider_class();
        echo "✓ Provider instantiated successfully\n";
        
        echo "Supported actions: " . implode(', ', $provider->get_action_list()) . "\n";
        echo "Is configured: " . ($provider->is_provider_configured() ? 'YES' : 'NO') . "\n";
        
    } catch (Exception $e) {
        echo "✗ Error instantiating provider: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "✗ Provider class NOT FOUND: {$provider_class}\n";
    echo "Checking if the file exists...\n";
    $provider_file = __DIR__ . '/classes/provider.php';
    if (file_exists($provider_file)) {
        echo "✓ Provider file exists at: {$provider_file}\n";
        echo "  The class might not be autoloaded. Trying manual include...\n";
        require_once($provider_file);
        if (class_exists($provider_class)) {
            echo "✓ Class found after manual include\n";
        } else {
            echo "✗ Class still not found after manual include\n";
        }
    } else {
        echo "✗ Provider file NOT FOUND at: {$provider_file}\n";
    }
}

// Check what Moodle knows about plugins
echo "\nChecking plugin installation status...\n";
$pluginman = core_plugin_manager::instance();
$plugin_info = $pluginman->get_plugin_info('local_ai_openai_v1');
if ($plugin_info) {
    echo "✓ Plugin is installed\n";
    echo "  Version: " . $plugin_info->versiondb . "\n";
    echo "  Directory: " . $plugin_info->rootdir . "\n";
} else {
    echo "✗ Plugin is NOT installed in Moodle\n";
}

echo "\n--- Debug Complete ---\n";