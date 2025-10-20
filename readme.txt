=== WebDev Bundle Plugin ===
Contributors: Taurai Munodawafa
Tags: installer, bulk install, plugins, onboarding, setup, productivity
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress plugin installer that streamlines setting up essential plugins for new projects in just a few clicks.

== Description ==
WebDev Bundle Plugin provides a fast, two-step workflow to install and activate a curated set of essential plugins. It supports local plugin ZIPs, real-time progress, bulk operations, and a clean, responsive admin UI.

=== Key Features ===
- Two-step process: install then activate
- Fast installation with progress and logging
- Local plugin ZIP support with upload & management
- Categorized plugin lists (Staging, After Deployment, SEO, Security)
- Smart detection of installed/active plugins
- Bulk select/deselect for install and activate
- Keyboard shortcuts and accessibility-minded UI

=== Plugin Categories ===
During Staging: Elementor, TablePress, Colorlib Login Customizer, Fuse Social, WooCommerce, Envato Elements, Forminator

After Deployment: WP Activity Log, WP Hide Security Enhancer, LiteSpeed Cache

SEO: Yoast SEO, Google Site Kit

Security & Maintenance: Wordfence Security

== Installation ==
1. Upload the plugin to `/wp-content/plugins/` or install via Plugins → Add New → Upload Plugin.
2. Activate the plugin through the 'Plugins' screen.
3. Open WebDev Bundle in the admin menu.
4. Step 1: Select plugins to install → Install Selected Plugins.
5. Step 2: Select installed plugins to activate → Activate Selected Plugins.

== Frequently Asked Questions ==
= Can I use local plugin files? =
Yes. Go to WebDev Bundle → Plugin Manager to upload ZIP files. If a local ZIP matches a plugin slug, it is used instead of downloading from WordPress.org.

= What permissions are required? =
You need `install_plugins` to install and `activate_plugins` to activate. The UI requires `manage_options`.

= Does it work on all hosts? =
It relies on WordPress's built-in upgrader and filesystem API. Ensure your server can write to the plugins directory and that outbound HTTP requests are allowed.

== Screenshots ==
1. Main installer screen with categorized plugin lists
2. Bulk install/activate actions with progress bar
3. Plugin Manager for local ZIP uploads

== Changelog ==
= 1.1.0 =
- Performance improvements and stability tweaks

= 1.0.0 =
- Initial release with categorized installer, bulk install/activate, progress tracking, responsive admin UI, and keyboard shortcuts

== Upgrade Notice ==
= 1.1.0 =
Recommended update for improved progress handling and compatibility.

== Credits ==
Developed by Tau with AI. Special thanks to the WordPress community.
