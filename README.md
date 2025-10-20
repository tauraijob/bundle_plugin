# WebDev Bundle Plugin

A comprehensive WordPress plugin installer that provides easy installation of essential WordPress plugins for web development projects. This plugin streamlines the process of setting up a WordPress website with all the necessary plugins in just a few clicks.

## Features

- **Two-Step Process**: Separate installation and activation to prevent website breaks
- **Fast Installation**: Optimized installation process with faster progress tracking
- **Local Plugin Support**: Upload and store plugins locally for distribution
- **Categorized Plugins**: Organized into logical categories (Staging, After Deployment, SEO, Security)
- **Smart Detection**: Automatically detects already installed and active plugins
- **Progress Tracking**: Real-time installation/activation progress with detailed logging
- **Bulk Operations**: Select all or deselect all plugins for install/activate separately
- **Plugin Management**: Upload, view, and delete local plugin files
- **User-Friendly Interface**: Clean, modern admin interface with clear status indicators
- **Responsive Design**: Works perfectly on all devices

## Plugin Categories

### During Staging
- **Elementor** - Page builder for WordPress
- **TablePress** - Create and manage tables easily
- **Colorlib Login Customizer** - Customize WordPress login page
- **Fuse Social** - Floating social media buttons
- **WooCommerce** - E-commerce platform for WordPress
- **Envato Elements** - Access to premium design assets
- **Forminator** - Free form builder plugin

### After Deployment
- **WP Activity Log** - Monitor WordPress activity
- **WP Hide Security Enhancer** - Enhance WordPress security
- **LiteSpeed Cache** - High-performance caching solution

### SEO
- **Yoast SEO** - Complete SEO solution
- **Google Site Kit** - Official Google plugin for WordPress

### Security & Maintenance
- **Wordfence Security** - Comprehensive security solution

## Installation

1. **Upload the Plugin**:
   - Download the plugin files
   - Upload the `webdev-bundle-plugin` folder to your `/wp-content/plugins/` directory
   - Or install via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate the Plugin**:
   - Go to Plugins → Installed Plugins
   - Find "WebDev Bundle Plugin" and click "Activate"

3. **Install Required Plugins**:
   - After activation, you'll see a notice prompting you to install required plugins
   - Click "Install Required Plugins" or go to WebDev Bundle in your admin menu
   - **Step 1**: Select plugins you want to install and click "Install Selected Plugins"
   - **Step 2**: After installation, select plugins you want to activate and click "Activate Selected Plugins"

4. **Upload Local Plugins (Optional)**:
   - Go to WebDev Bundle → Plugin Manager
   - Upload ZIP files of plugins you want to distribute locally
   - Local plugins will be used instead of downloading from WordPress.org

## Usage

### Basic Usage

1. **Access the Plugin**:
   - Go to your WordPress admin dashboard
   - Click on "WebDev Bundle" in the admin menu

2. **Install Plugins**:
   - Browse through the categorized plugin lists
   - Check the boxes next to plugins you want to install
   - Use "Select All to Install" or "Deselect All" for bulk operations
   - Click "Install Selected Plugins"
   - Watch the real-time installation progress

3. **Activate Plugins**:
   - After installation, plugins will show as "Installed"
   - Check the boxes next to plugins you want to activate
   - Use "Select All to Activate" or "Deselect All" for bulk operations
   - Click "Activate Selected Plugins"
   - Watch the real-time activation progress

### Plugin Manager

1. **Upload Local Plugins**:
   - Go to WebDev Bundle → Plugin Manager
   - Select a ZIP file (max 50MB)
   - Click "Upload Plugin"
   - Watch the upload progress

2. **Manage Uploaded Plugins**:
   - View all uploaded plugins with file details
   - Delete plugins you no longer need
   - Plugin names should match the slug used in the plugin list

3. **Local vs Remote Installation**:
   - Local plugins are installed from uploaded files
   - Remote plugins are downloaded from WordPress.org
   - Local plugins take priority over remote ones

### Advanced Features

- **Keyboard Shortcuts**:
  - `Ctrl+I`: Select all plugins to install
  - `Ctrl+Shift+A`: Select all plugins to activate
  - `Escape`: Deselect all plugins

- **Plugin Status Indicators**:
  - **Gray "Not Installed"**: Plugin is not installed
  - **Blue "Installed"**: Plugin is installed but not active
  - **Green "Active"**: Plugin is installed and active
  - **Orange "Installing/Activating"**: Plugin is currently being processed
  - **Red "Error"**: Installation/activation failed

- **Progress Tracking**:
  - Real-time progress bar
  - Detailed installation log
  - Success/error messages for each plugin

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Administrator privileges
- Internet connection (for downloading plugins from WordPress.org)

## Permissions

This plugin requires the following WordPress capabilities:
- `manage_options` - To access the admin interface
- `install_plugins` - To install new plugins
- `activate_plugins` - To activate installed plugins

## Troubleshooting

### Common Issues

1. **"You do not have sufficient permissions"**:
   - Ensure you're logged in as an administrator
   - Check that your user account has the required capabilities

2. **Plugin installation fails**:
   - Check your internet connection
   - Verify that the plugin exists in the WordPress.org repository
   - Check your server's file permissions

3. **Plugins not appearing in the list**:
   - Some plugins may not be available in the WordPress.org repository
   - Premium plugins (like Elementor Pro) may require manual installation

### Debug Mode

To enable debug mode and see detailed error messages:

1. Add this to your `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. Check the `/wp-content/debug.log` file for detailed error information

## Customization

### Adding New Plugins

To add new plugins to the installer, edit the `get_plugin_categories()` method in the main plugin file:

```php
private function get_plugin_categories() {
    return array(
        'Your Category' => array(
            array(
                'name' => 'Plugin Name',
                'slug' => 'plugin-slug',
                'description' => 'Plugin description'
            ),
            // Add more plugins...
        ),
        // Add more categories...
    );
}
```

### Styling Customization

The plugin includes CSS classes that you can override in your theme:

- `.webdev-bundle-category` - Category containers
- `.webdev-bundle-plugin-item` - Individual plugin items
- `.webdev-bundle-actions` - Action buttons container
- `.progress-bar` - Installation progress bar

## Security

- All AJAX requests are protected with nonces
- User capabilities are checked before any plugin operations
- Input sanitization and validation on all user inputs
- No direct file access allowed

## Support

For support, feature requests, or bug reports:

1. Check the troubleshooting section above
2. Review the WordPress error logs
3. Contact your web developer or hosting provider

## Changelog

### Version 1.0.0
- Initial release
- Plugin categorization system
- Bulk installation functionality
- Progress tracking and logging
- Responsive admin interface
- Keyboard shortcuts
- Accessibility improvements

## License

This plugin is licensed under the GPL v2 or later.

## Credits
- Tau
Developed for streamlined WordPress development workflows. Special thanks to the WordPress community for the excellent plugin ecosystem.
