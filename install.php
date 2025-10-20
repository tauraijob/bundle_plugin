<?php
/**
 * Installation script for WebDev Bundle
 * This file can be used to create a zip package for easy distribution
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress context, create a simple zip package
    if (php_sapi_name() === 'cli') {
        echo "Creating WebDev Bundle package...\n";
        
        $files = [
            'webdev-bundle.php',
            'assets/admin.css',
            'assets/admin.js',
            'README.md'
        ];
        
        $zip = new ZipArchive();
        $zipFile = 'webdev-bundle-v1.1.0.zip';
        
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, 'webdev-bundle/' . $file);
                    echo "Added: $file\n";
                } else {
                    echo "Warning: $file not found\n";
                }
            }
            
            // Add instructions for local plugins
            $instructions = "LOCAL_PLUGINS_INSTRUCTIONS.txt";
            $content = "WebDev Bundle - Local Plugins Instructions\n";
            $content .= "================================================\n\n";
            $content .= "To include local plugins in your distribution:\n\n";
            $content .= "1. Create a 'local-plugins' folder in the plugin directory\n";
            $content .= "2. Add your plugin ZIP files to this folder\n";
            $content .= "3. Name the files to match plugin slugs (e.g., 'elementor-pro.zip')\n";
            $content .= "4. The plugin will automatically use local files when available\n\n";
            $content .= "Example structure:\n";
            $content .= "webdev-bundle/\n";
            $content .= "├── webdev-bundle.php\n";
            $content .= "├── assets/\n";
            $content .= "├── local-plugins/\n";
            $content .= "│   ├── elementor-pro.zip\n";
            $content .= "│   ├── gravityforms.zip\n";
            $content .= "│   └── other-plugin.zip\n";
            $content .= "└── README.md\n\n";
            $content .= "Note: Local plugins will be copied to the uploads directory on activation.\n";
            
            $zip->addFromString($instructions, $content);
            echo "Added: $instructions\n";
            
            $zip->close();
            echo "Package created: $zipFile\n";
            echo "You can now distribute this plugin with local plugins included!\n";
        } else {
            echo "Error: Could not create zip file\n";
        }
    } else {
        exit('Direct access not allowed.');
    }
}
?>
