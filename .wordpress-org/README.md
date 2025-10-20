This directory holds WordPress.org listing assets when deploying via GitHub Actions.

Add the following files before tagging a release:
- icon-128x128.png (required)
- icon-256x256.png (recommended)
- banner-772x250.png (standard banner)
- banner-1544x500.png (HiDPI banner)
- screenshot-1.png, screenshot-2.png, ... (optional; referenced in readme.txt)

Notes:
- These assets are not loaded by the plugin; they are only for the .org listing.
- The GitHub Action (10up/action-wordpress-plugin-deploy) will sync this folder to the SVN /assets directory.
