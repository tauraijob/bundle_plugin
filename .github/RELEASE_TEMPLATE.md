# Release Notes

Version: vX.Y.Z
Date: YYYY-MM-DD

## Changes
- Briefly summarize key changes.

## Checklist (before publishing)
- [ ] Update version in `webdev-bundle-plugin.php` and `readme.txt` Stable tag
- [ ] Update `Tested up to` in `readme.txt` if needed
- [ ] Verify `.distignore` excludes dev/CI files
- [ ] Ensure `.wordpress-org/` assets (icons/banners/screenshots) are present
- [ ] Confirm `readme.txt` sections (Description, Screenshots, Changelog, Upgrade Notice) are updated

## Deployment
- Tag this release using `vX.Y.Z` (must start with `v` to trigger the workflow)
- GitHub Action deploys to WordPress.org using repository secrets `SVN_USERNAME` and `SVN_PASSWORD`

## Notes
- If the plugin slug changes on WordPress.org, update the workflow `slug` input accordingly.
