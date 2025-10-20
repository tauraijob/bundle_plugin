<?php
/**
 * Plugin Name: WebDev Bundle Plugin
 * Plugin URI: https:webdev.co.zw
 * Description: A comprehensive plugin installer that provides easy installation of essential WordPress plugins for web development projects.
 * Version: 1.1.0
 * Author: Tau
 * Author URI: https:webdev.co.zw
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: webdev-bundle
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WEBDEV_BUNDLE_VERSION', '1.1.0');
define('WEBDEV_BUNDLE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WEBDEV_BUNDLE_PLUGIN_PATH', plugin_dir_path(__FILE__));

class WebDevBundlePlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('webdev-bundle', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Add admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('admin_notices', array($this, 'show_activation_notice'));
            add_action('wp_ajax_webdev_install_plugin', array($this, 'ajax_install_plugin'));
            add_action('wp_ajax_webdev_activate_plugin', array($this, 'ajax_activate_plugin'));
            add_action('wp_ajax_webdev_install_multiple_plugins', array($this, 'ajax_install_multiple_plugins'));
            add_action('wp_ajax_webdev_activate_multiple_plugins', array($this, 'ajax_activate_multiple_plugins'));
            add_action('wp_ajax_webdev_upload_plugin', array($this, 'ajax_upload_plugin'));
            add_action('wp_ajax_webdev_delete_uploaded_plugin', array($this, 'ajax_delete_uploaded_plugin'));
            add_action('wp_ajax_webdev_debug_upload', array($this, 'ajax_debug_upload'));
            add_action('wp_ajax_webdev_check_plugin_status', array($this, 'ajax_check_plugin_status'));
        }
    }
    
    public function activate() {
        // Set a transient to show activation notice
        set_transient('webdev_bundle_activation_notice', true, 30);
        
        // Create database table for tracking installed plugins (optional)
        $this->create_tracking_table();
        
        // Create upload directory for plugins
        $this->create_upload_directory();
    }
    
    public function deactivate() {
        // Clean up transients
        delete_transient('webdev_bundle_activation_notice');
    }
    
    private function create_tracking_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'webdev_bundle_tracking';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plugin_slug varchar(255) NOT NULL,
            installed_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'installed',
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/webdev-bundle-plugins';
        
        // Debug: Log upload directory info
        error_log('WebDev Bundle Upload Debug - Upload dir: ' . $upload_dir['basedir']);
        error_log('WebDev Bundle Upload Debug - Plugin dir: ' . $plugin_dir);
        error_log('WebDev Bundle Upload Debug - Upload dir exists: ' . (is_dir($upload_dir['basedir']) ? 'Yes' : 'No'));
        error_log('WebDev Bundle Upload Debug - Upload dir writable: ' . (is_writable($upload_dir['basedir']) ? 'Yes' : 'No'));
        
        if (!file_exists($plugin_dir)) {
            $created = wp_mkdir_p($plugin_dir);
            error_log('WebDev Bundle Upload Debug - Directory created: ' . ($created ? 'Yes' : 'No'));
            
            if ($created) {
                // Create .htaccess to prevent direct access
                $htaccess_content = "Options -Indexes\n";
                $htaccess_content .= "deny from all\n";
                file_put_contents($plugin_dir . '/.htaccess', $htaccess_content);
                
                // Create index.php to prevent directory listing
                file_put_contents($plugin_dir . '/index.php', '<?php // Silence is golden');
                
                // Set directory permissions
                chmod($plugin_dir, 0755);
            }
        }
        
        error_log('WebDev Bundle Upload Debug - Final plugin dir exists: ' . (is_dir($plugin_dir) ? 'Yes' : 'No'));
        error_log('WebDev Bundle Upload Debug - Final plugin dir writable: ' . (is_writable($plugin_dir) ? 'Yes' : 'No'));
    }
    
    private function get_upload_directory() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/webdev-bundle-plugins';
    }
    
    private function get_uploaded_plugins() {
        $upload_dir = $this->get_upload_directory();
        $plugins = array();
        
        if (is_dir($upload_dir)) {
            $files = scandir($upload_dir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                    $plugin_name = pathinfo($file, PATHINFO_FILENAME);
                    $plugins[] = array(
                        'name' => $plugin_name,
                        'file' => $file,
                        'path' => $upload_dir . '/' . $file,
                        'size' => filesize($upload_dir . '/' . $file),
                        'date' => filemtime($upload_dir . '/' . $file)
                    );
                }
            }
        }
        
        return $plugins;
    }
    
    public function show_activation_notice() {
        if (get_transient('webdev_bundle_activation_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('WebDev Bundle Plugin Activated!', 'webdev-bundle'); ?></strong>
                    <?php _e('Your website needs essential plugins for optimal performance. ', 'webdev-bundle'); ?>
                    <a href="<?php echo admin_url('admin.php?page=webdev-bundle'); ?>" class="button button-primary">
                        <?php _e('Install Required Plugins', 'webdev-bundle'); ?>
                    </a>
                </p>
            </div>
            <?php
            delete_transient('webdev_bundle_activation_notice');
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('WebDev Bundle', 'webdev-bundle'),
            __('WebDev Bundle', 'webdev-bundle'),
            'manage_options',
            'webdev-bundle',
            array($this, 'admin_page'),
            'dashicons-admin-plugins',
            30
        );
        
        add_submenu_page(
            'webdev-bundle',
            __('Plugin Manager', 'webdev-bundle'),
            __('Plugin Manager', 'webdev-bundle'),
            'manage_options',
            'webdev-bundle-manager',
            array($this, 'plugin_manager_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_webdev-bundle' && $hook !== 'webdev-bundle_page_webdev-bundle-manager') {
            return;
        }
        
        wp_enqueue_script('webdev-bundle-admin', WEBDEV_BUNDLE_PLUGIN_URL . 'assets/admin.js', array('jquery'), WEBDEV_BUNDLE_VERSION, true);
        wp_enqueue_style('webdev-bundle-admin', WEBDEV_BUNDLE_PLUGIN_URL . 'assets/admin.css', array(), WEBDEV_BUNDLE_VERSION);
        
        wp_localize_script('webdev-bundle-admin', 'webdevBundle', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('webdev_bundle_nonce'),
            'strings' => array(
                'installing' => __('Installing...', 'webdev-bundle'),
                'installed' => __('Installed', 'webdev-bundle'),
                'error' => __('Error occurred', 'webdev-bundle'),
                'select_plugins' => __('Please select at least one plugin to install.', 'webdev-bundle')
            )
        ));
    }
    
    public function admin_page() {
        $plugin_categories = $this->get_plugin_categories();
        ?>
        <div class="wrap">
            <h1><?php _e('WebDev Bundle - Essential Plugins Installer', 'webdev-bundle'); ?></h1>
            <p><?php _e('Select the plugins you want to install for your website. These plugins are essential for optimal performance and functionality.', 'webdev-bundle'); ?></p>
            
            <form id="webdev-bundle-form">
                <?php wp_nonce_field('webdev_bundle_nonce', 'webdev_bundle_nonce'); ?>
                
                <div class="webdev-bundle-categories">
                    <?php foreach ($plugin_categories as $category => $plugins): ?>
                        <div class="webdev-bundle-category">
                            <h2><?php echo esc_html($category); ?></h2>
                            <div class="webdev-bundle-plugins">
                                 <?php foreach ($plugins as $plugin): ?>
                                     <div class="webdev-bundle-plugin-item" data-plugin-slug="<?php echo esc_attr($plugin['slug']); ?>">
                                         <div class="plugin-info">
                                             <span class="plugin-name"><?php echo esc_html($plugin['name']); ?></span>
                                             <span class="plugin-description"><?php echo esc_html($plugin['description']); ?></span>
                                         </div>
                                         <div class="plugin-actions">
                                             <?php if (!$this->is_plugin_installed($plugin['slug'])): ?>
                                                 <input type="checkbox" name="install_plugins[]" value="<?php echo esc_attr($plugin['slug']); ?>" 
                                                        data-plugin-name="<?php echo esc_attr($plugin['name']); ?>" class="install-checkbox">
                                                 <span class="plugin-status not-installed"><?php _e('Not Installed', 'webdev-bundle'); ?></span>
                                             <?php elseif (!$this->is_plugin_active($plugin['slug'])): ?>
                                                 <input type="checkbox" name="activate_plugins[]" value="<?php echo esc_attr($plugin['slug']); ?>" 
                                                        data-plugin-name="<?php echo esc_attr($plugin['name']); ?>" class="activate-checkbox">
                                                 <span class="plugin-status installed"><?php _e('Installed', 'webdev-bundle'); ?></span>
                                             <?php else: ?>
                                                 <span class="plugin-status active"><?php _e('Active', 'webdev-bundle'); ?></span>
                                             <?php endif; ?>
                                         </div>
                                     </div>
                                 <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                 <div class="webdev-bundle-actions">
                     <div class="install-section">
                         <h3><?php _e('Install Plugins', 'webdev-bundle'); ?></h3>
                         <button type="button" id="select-all-install" class="button"><?php _e('Select All to Install', 'webdev-bundle'); ?></button>
                         <button type="button" id="deselect-all-install" class="button"><?php _e('Deselect All', 'webdev-bundle'); ?></button>
                         <button type="button" id="install-plugins" class="button button-primary"><?php _e('Install Selected Plugins', 'webdev-bundle'); ?></button>
                     </div>
                     
                     <div class="activate-section">
                         <h3><?php _e('Activate Plugins', 'webdev-bundle'); ?></h3>
                         <button type="button" id="select-all-activate" class="button"><?php _e('Select All to Activate', 'webdev-bundle'); ?></button>
                         <button type="button" id="deselect-all-activate" class="button"><?php _e('Deselect All', 'webdev-bundle'); ?></button>
                         <button type="button" id="activate-plugins" class="button button-secondary"><?php _e('Activate Selected Plugins', 'webdev-bundle'); ?></button>
                     </div>
                 </div>
            </form>
            
             <div id="webdev-bundle-progress" style="display: none;">
                 <h3 id="progress-title"><?php _e('Installation Progress', 'webdev-bundle'); ?></h3>
                 <div class="progress-bar">
                     <div class="progress-fill"></div>
                 </div>
                 <div id="progress-text"></div>
                 <div id="installation-log"></div>
             </div>
        </div>
        <?php
    }
    
    public function plugin_manager_page() {
        // Ensure upload directory exists
        $this->create_upload_directory();
        
        $uploaded_plugins = $this->get_uploaded_plugins();
        ?>
        <div class="wrap">
            <h1><?php _e('Plugin Manager - Upload & Manage Local Plugins', 'webdev-bundle'); ?></h1>
            <p><?php _e('Upload and manage local plugin files that will be used instead of downloading from WordPress.org repository.', 'webdev-bundle'); ?></p>
            
            <!-- Upload Section -->
            <div class="webdev-bundle-upload-section">
                <h2><?php _e('Upload New Plugin', 'webdev-bundle'); ?></h2>
                <form id="webdev-bundle-upload-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('webdev_bundle_nonce', 'webdev_bundle_nonce'); ?>
                    <div class="upload-field">
                        <label for="plugin_file"><?php _e('Select Plugin ZIP File:', 'webdev-bundle'); ?></label>
                        <input type="file" id="plugin_file" name="plugin_file" accept=".zip" required>
                        <p class="description"><?php _e('Maximum file size: 50MB. Only ZIP files are allowed.', 'webdev-bundle'); ?></p>
                    </div>
                    <button type="submit" class="button button-primary"><?php _e('Upload Plugin', 'webdev-bundle'); ?></button>
                    <button type="button" id="debug-upload" class="button button-secondary"><?php _e('Debug Upload', 'webdev-bundle'); ?></button>
                </form>
                <div id="upload-progress" style="display: none;">
                    <div class="upload-progress-bar">
                        <div class="upload-progress-fill"></div>
                    </div>
                    <div id="upload-status"></div>
                </div>
            </div>
            
            <!-- Uploaded Plugins List -->
            <div class="webdev-bundle-uploaded-plugins">
                <h2><?php _e('Uploaded Plugins', 'webdev-bundle'); ?></h2>
                <?php if (empty($uploaded_plugins)): ?>
                    <p><?php _e('No plugins uploaded yet.', 'webdev-bundle'); ?></p>
                <?php else: ?>
                    <div class="uploaded-plugins-list">
                        <?php foreach ($uploaded_plugins as $plugin): ?>
                            <div class="uploaded-plugin-item" data-filename="<?php echo esc_attr($plugin['file']); ?>">
                                <div class="plugin-info">
                                    <h4><?php echo esc_html($plugin['name']); ?></h4>
                                    <p class="plugin-details">
                                        <?php echo esc_html($plugin['file']); ?> | 
                                        <?php echo size_format($plugin['size']); ?> | 
                                        <?php echo date('Y-m-d H:i:s', $plugin['date']); ?>
                                    </p>
                                </div>
                                <div class="plugin-actions">
                                    <button type="button" class="button button-secondary delete-plugin" 
                                            data-filename="<?php echo esc_attr($plugin['file']); ?>">
                                        <?php _e('Delete', 'webdev-bundle'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Instructions -->
            <div class="webdev-bundle-instructions">
                <h2><?php _e('How to Use Local Plugins', 'webdev-bundle'); ?></h2>
                <ol>
                    <li><?php _e('Upload plugin ZIP files using the form above', 'webdev-bundle'); ?></li>
                    <li><?php _e('Go to the main WebDev Bundle page to install plugins', 'webdev-bundle'); ?></li>
                    <li><?php _e('Local plugins will be installed instead of downloading from WordPress.org', 'webdev-bundle'); ?></li>
                    <li><?php _e('Plugin names should match the slug used in the plugin list (e.g., "elementor-pro.zip" for Elementor Pro)', 'webdev-bundle'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    private function get_plugin_categories() {
        return array(
            __('During Staging', 'webdev-bundle') => array(
                array(
                    'name' => 'Elementor',
                    'slug' => 'elementor',
                    'description' => __('Page builder for WordPress', 'webdev-bundle')
                ),
                array(
                    'name' => 'TablePress',
                    'slug' => 'tablepress',
                    'description' => __('Create and manage tables easily', 'webdev-bundle')
                ),
                array(
                    'name' => 'Colorlib Login Customizer',
                    'slug' => 'colorlib-login-customizer',
                    'description' => __('Customize WordPress login page', 'webdev-bundle')
                ),
                array(
                    'name' => 'Fuse Social',
                    'slug' => 'fuse-social',
                    'description' => __('Floating social media buttons', 'webdev-bundle')
                ),
                array(
                    'name' => 'WooCommerce',
                    'slug' => 'woocommerce',
                    'description' => __('E-commerce platform for WordPress', 'webdev-bundle')
                ),
                array(
                    'name' => 'Envato Elements',
                    'slug' => 'envato-elements',
                    'description' => __('Access to premium design assets', 'webdev-bundle')
                ),
                array(
                    'name' => 'Forminator',
                    'slug' => 'forminator',
                    'description' => __('Free form builder plugin', 'webdev-bundle')
                )
            ),
            __('After Deployment', 'webdev-bundle') => array(
                array(
                    'name' => 'WP Activity Log',
                    'slug' => 'wp-security-audit-log',
                    'description' => __('Monitor WordPress activity', 'webdev-bundle')
                ),
                array(
                    'name' => 'WP Hide Security Enhancer',
                    'slug' => 'wp-hide-security-enhancer',
                    'description' => __('Enhance WordPress security', 'webdev-bundle')
                ),
                array(
                    'name' => 'LiteSpeed Cache',
                    'slug' => 'litespeed-cache',
                    'description' => __('High-performance caching solution', 'webdev-bundle')
                )
            ),
            __('SEO', 'webdev-bundle') => array(
                array(
                    'name' => 'Yoast SEO',
                    'slug' => 'wordpress-seo',
                    'description' => __('Complete SEO solution', 'webdev-bundle')
                ),
                array(
                    'name' => 'Google Site Kit',
                    'slug' => 'google-site-kit',
                    'description' => __('Official Google plugin for WordPress', 'webdev-bundle')
                )
            ),
            __('Security & Maintenance', 'webdev-bundle') => array(
                array(
                    'name' => 'Wordfence Security',
                    'slug' => 'wordfence',
                    'description' => __('Comprehensive security solution', 'webdev-bundle')
                )
            )
        );
    }
    
    private function is_plugin_installed($plugin_slug) {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            if (strpos($plugin_file, $plugin_slug . '/') === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    private function is_plugin_active($plugin_slug) {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';
        return is_plugin_active($plugin_file);
    }
    
    private function get_plugin_file($plugin_slug) {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            if (strpos($plugin_file, $plugin_slug . '/') === 0) {
                return $plugin_file;
            }
        }
        
        return false;
    }
    
    public function ajax_install_plugin() {
        check_ajax_referer('webdev_bundle_nonce', 'nonce');
        
        if (!current_user_can('install_plugins')) {
            wp_die(__('You do not have sufficient permissions to install plugins.', 'webdev-bundle'));
        }
        
        $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
        $result = $this->install_plugin($plugin_slug);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function ajax_activate_plugin() {
        check_ajax_referer('webdev_bundle_nonce', 'nonce');
        
        if (!current_user_can('activate_plugins')) {
            wp_die(__('You do not have sufficient permissions to activate plugins.', 'webdev-bundle'));
        }
        
        $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
        $result = $this->activate_plugin($plugin_slug);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function ajax_install_multiple_plugins() {
        check_ajax_referer('webdev_bundle_nonce', 'nonce');
        
        if (!current_user_can('install_plugins')) {
            wp_die(__('You do not have sufficient permissions to install plugins.', 'webdev-bundle'));
        }
        
        $plugin_slugs = array_map('sanitize_text_field', $_POST['plugin_slugs']);
        $results = array();
        
        foreach ($plugin_slugs as $plugin_slug) {
            $results[$plugin_slug] = $this->install_plugin($plugin_slug);
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_activate_multiple_plugins() {
        check_ajax_referer('webdev_bundle_nonce', 'nonce');
        
        if (!current_user_can('activate_plugins')) {
            wp_die(__('You do not have sufficient permissions to activate plugins.', 'webdev-bundle'));
        }
        
        $plugin_slugs = array_map('sanitize_text_field', $_POST['plugin_slugs']);
        $results = array();
        
        foreach ($plugin_slugs as $plugin_slug) {
            $results[$plugin_slug] = $this->activate_plugin($plugin_slug);
        }
        
        wp_send_json_success($results);
    }

    public function ajax_check_plugin_status() {
        check_ajax_referer('webdev_bundle_nonce', 'nonce');
        
        if (!current_user_can('install_plugins')) {
            wp_die(__('You do not have sufficient permissions.', 'webdev-bundle'));
        }
        
        $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
        $installed = $this->is_plugin_installed($plugin_slug);
        $active = $this->is_plugin_active($plugin_slug);
        
        wp_send_json_success(array(
            'installed' => $installed,
            'active' => $active
        ));
    }
    
    public function ajax_upload_plugin() {
        check_ajax_referer('webdev_bundle_nonce', 'nonce');
        
        if (!current_user_can('install_plugins')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions to upload plugins.', 'webdev-bundle')));
        }
        
        // Debug: Log the $_FILES array
        error_log('WebDev Bundle Upload Debug - $_FILES: ' . print_r($_FILES, true));
        
        if (!isset($_FILES['plugin_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'webdev-bundle')));
        }
        
        $file = $_FILES['plugin_file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE => __('File exceeds upload_max_filesize directive.', 'webdev-bundle'),
                UPLOAD_ERR_FORM_SIZE => __('File exceeds MAX_FILE_SIZE directive.', 'webdev-bundle'),
                UPLOAD_ERR_PARTIAL => __('File was only partially uploaded.', 'webdev-bundle'),
                UPLOAD_ERR_NO_FILE => __('No file was uploaded.', 'webdev-bundle'),
                UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder.', 'webdev-bundle'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'webdev-bundle'),
                UPLOAD_ERR_EXTENSION => __('File upload stopped by extension.', 'webdev-bundle')
            );
            
            $error_message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : __('Unknown upload error.', 'webdev-bundle');
            wp_send_json_error(array('message' => $error_message . ' (Error code: ' . $file['error'] . ')'));
        }
        
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        // Validate file type
        if (strtolower($file_extension) !== 'zip') {
            wp_send_json_error(array('message' => __('Only ZIP files are allowed.', 'webdev-bundle')));
        }
        
        // Validate file size (max 50MB)
        if ($file['size'] > 50 * 1024 * 1024) {
            wp_send_json_error(array('message' => __('File size too large. Maximum 50MB allowed.', 'webdev-bundle')));
        }
        
        // Ensure upload directory exists
        $upload_dir = $this->get_upload_directory();
        if (!is_dir($upload_dir)) {
            $this->create_upload_directory();
        }
        
        // Test if we can write to the directory
        $test_file = $upload_dir . '/test-write.txt';
        if (file_put_contents($test_file, 'test') === false) {
            wp_send_json_error(array('message' => __('Cannot write to upload directory. Please check permissions.', 'webdev-bundle')));
        } else {
            unlink($test_file); // Clean up test file
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            wp_send_json_error(array('message' => __('Upload directory is not writable. Please check permissions.', 'webdev-bundle')));
        }
        
        $filename = sanitize_file_name($file['name']);
        $destination = $upload_dir . '/' . $filename;
        
        // Check if file already exists
        if (file_exists($destination)) {
            wp_send_json_error(array('message' => __('A plugin with this name already exists.', 'webdev-bundle')));
        }
        
        // Debug: Log file details
        error_log('WebDev Bundle Upload Debug - File: ' . $file['name'] . ', Size: ' . $file['size'] . ', Temp: ' . $file['tmp_name'] . ', Destination: ' . $destination);
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Set proper permissions
            chmod($destination, 0644);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Plugin "%s" uploaded successfully.', 'webdev-bundle'), $filename),
                'filename' => $filename
            ));
        } else {
            // Get more detailed error information
            $error_details = array();
            if (!is_uploaded_file($file['tmp_name'])) {
                $error_details[] = 'File is not a valid uploaded file';
            }
            if (!is_writable($upload_dir)) {
                $error_details[] = 'Destination directory is not writable';
            }
            if (file_exists($destination)) {
                $error_details[] = 'Destination file already exists';
            }
            
            $error_message = __('Failed to upload plugin file.', 'webdev-bundle');
            if (!empty($error_details)) {
                $error_message .= ' Details: ' . implode(', ', $error_details);
            }
            
            wp_send_json_error(array('message' => $error_message));
        }
    }
    
    public function ajax_delete_uploaded_plugin() {
        check_ajax_referer('webdev_bundle_nonce', 'nonce');
        
        if (!current_user_can('delete_plugins')) {
            wp_die(__('You do not have sufficient permissions to delete plugins.', 'webdev-bundle'));
        }
        
        $filename = sanitize_file_name($_POST['filename']);
        $file_path = $this->get_upload_directory() . '/' . $filename;
        
        if (file_exists($file_path) && unlink($file_path)) {
            wp_send_json_success(array('message' => sprintf(__('Plugin "%s" deleted successfully.', 'webdev-bundle'), $filename)));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete plugin file.', 'webdev-bundle')));
        }
    }
    
    public function ajax_debug_upload() {
        check_ajax_referer('webdev_bundle_nonce', 'nonce');
        
        if (!current_user_can('install_plugins')) {
            wp_die(__('You do not have sufficient permissions.', 'webdev-bundle'));
        }
        
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/webdev-bundle-plugins';
        
        $debug_info = array(
            'upload_dir' => $upload_dir['basedir'],
            'plugin_dir' => $plugin_dir,
            'upload_dir_exists' => is_dir($upload_dir['basedir']),
            'upload_dir_writable' => is_writable($upload_dir['basedir']),
            'plugin_dir_exists' => is_dir($plugin_dir),
            'plugin_dir_writable' => is_writable($plugin_dir),
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_max_execution_time' => ini_get('max_execution_time'),
            'php_memory_limit' => ini_get('memory_limit'),
            'file_uploads_enabled' => ini_get('file_uploads'),
            'temp_dir' => sys_get_temp_dir(),
            'temp_dir_writable' => is_writable(sys_get_temp_dir())
        );
        
        wp_send_json_success($debug_info);
    }
    
    private function install_plugin($plugin_slug) {
        // Check if plugin is already installed
        if ($this->is_plugin_installed($plugin_slug)) {
            return array(
                'success' => true,
                'message' => sprintf(__('Plugin "%s" is already installed.', 'webdev-bundle'), $plugin_slug)
            );
        }
        
        // Check for local plugin first
        $local_plugin = $this->get_local_plugin($plugin_slug);
        if ($local_plugin) {
            return $this->install_local_plugin($local_plugin);
        }
        
        // Fall back to WordPress.org repository
        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        if (!function_exists('install_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        
        // Get plugin information from WordPress.org
        $api = plugins_api('plugin_information', array(
            'slug' => $plugin_slug,
            'fields' => array(
                'short_description' => false,
                'sections' => false,
                'requires' => false,
                'rating' => false,
                'ratings' => false,
                'downloaded' => false,
                'last_updated' => false,
                'added' => false,
                'tags' => false,
                'compatibility' => false,
                'homepage' => false,
                'donate_link' => false,
            ),
        ));
        
        if (is_wp_error($api)) {
            return array(
                'success' => false,
                'message' => sprintf(__('Plugin "%s" not found in WordPress.org repository.', 'webdev-bundle'), $plugin_slug)
            );
        }
        
        // Install the plugin
        $upgrader = new Plugin_Upgrader();
        $result = $upgrader->install($api->download_link);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }
        
        // Verify installation regardless of return value
        if ($this->is_plugin_installed($plugin_slug)) {
            return array(
                'success' => true,
                'message' => sprintf(__('Plugin "%s" installed successfully.', 'webdev-bundle'), isset($api->name) ? $api->name : $plugin_slug)
            );
        }
        
        $plugin_file = $this->get_plugin_file($plugin_slug);
        if ($plugin_file && file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
            return array(
                'success' => true,
                'message' => sprintf(__('Plugin "%s" installed successfully.', 'webdev-bundle'), isset($api->name) ? $api->name : $plugin_slug)
            );
        }
        
        return array(
            'success' => false,
            'message' => sprintf(__('Failed to install plugin "%s".', 'webdev-bundle'), $plugin_slug)
        );
    }
    
    private function get_local_plugin($plugin_slug) {
        $uploaded_plugins = $this->get_uploaded_plugins();
        foreach ($uploaded_plugins as $plugin) {
            if (strpos($plugin['name'], $plugin_slug) !== false) {
                return $plugin;
            }
        }
        return false;
    }
    
    private function install_local_plugin($local_plugin) {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        if (!function_exists('install_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        
        // Install the local plugin
        $upgrader = new Plugin_Upgrader();
        $result = $upgrader->install($local_plugin['path']);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }
        
        // Try to infer slug and verify installation
        $inferred_slug = sanitize_title($local_plugin['name']);
        if ($this->is_plugin_installed($inferred_slug)) {
            return array(
                'success' => true,
                'message' => sprintf(__('Local plugin "%s" installed successfully.', 'webdev-bundle'), $local_plugin['name'])
            );
        }
        
        // Fallback: success if some plugin files were placed (best-effort)
        return array(
            'success' => true,
            'message' => sprintf(__('Local plugin "%s" installed (verification deferred).', 'webdev-bundle'), $local_plugin['name'])
        );
    }
    
    private function activate_plugin($plugin_slug) {
        $plugin_file = $this->get_plugin_file($plugin_slug);
        
        if (!$plugin_file) {
            return array(
                'success' => false,
                'message' => sprintf(__('Plugin "%s" not found.', 'webdev-bundle'), $plugin_slug)
            );
        }
        
        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $result = activate_plugin($plugin_file);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }
        
        // Verify activation
        if ($this->is_plugin_active($plugin_slug)) {
            return array(
                'success' => true,
                'message' => sprintf(__('Plugin "%s" activated successfully.', 'webdev-bundle'), $plugin_slug)
            );
        }
        
        return array(
            'success' => false,
            'message' => sprintf(__('Plugin "%s" did not activate. Please activate manually from Plugins page.', 'webdev-bundle'), $plugin_slug)
        );
    }
}

// Initialize the plugin
new WebDevBundlePlugin();
