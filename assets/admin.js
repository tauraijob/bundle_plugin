jQuery(document).ready(function ($) {
    'use strict';

    // Select All functionality for install
    $('#select-all-install').on('click', function () {
        $('.install-checkbox:not(:disabled)').prop('checked', true);
        updateInstallButton();
    });

    // Deselect All functionality for install
    $('#deselect-all-install').on('click', function () {
        $('.install-checkbox').prop('checked', false);
        updateInstallButton();
    });

    // Select All functionality for activate
    $('#select-all-activate').on('click', function () {
        $('.activate-checkbox:not(:disabled)').prop('checked', true);
        updateActivateButton();
    });

    // Deselect All functionality for activate
    $('#deselect-all-activate').on('click', function () {
        $('.activate-checkbox').prop('checked', false);
        updateActivateButton();
    });

    // Update install button state when checkboxes change
    $('.install-checkbox').on('change', function () {
        updateInstallButton();
    });

    // Update activate button state when checkboxes change
    $('.activate-checkbox').on('change', function () {
        updateActivateButton();
    });

    // Install plugins
    $('#install-plugins').on('click', function (e) {
        e.preventDefault();

        var selectedPlugins = $('.install-checkbox:checked').map(function () {
            return {
                slug: $(this).val(),
                name: $(this).data('plugin-name')
            };
        }).get();

        if (selectedPlugins.length === 0) {
            alert('Please select at least one plugin to install.');
            return;
        }

        installPlugins(selectedPlugins);
    });

    // Activate plugins
    $('#activate-plugins').on('click', function (e) {
        e.preventDefault();

        var selectedPlugins = $('.activate-checkbox:checked').map(function () {
            return {
                slug: $(this).val(),
                name: $(this).data('plugin-name')
            };
        }).get();

        if (selectedPlugins.length === 0) {
            alert('Please select at least one plugin to activate.');
            return;
        }

        activatePlugins(selectedPlugins);
    });

    function updateInstallButton() {
        var selectedCount = $('.install-checkbox:checked').length;
        var installButton = $('#install-plugins');

        if (selectedCount > 0) {
            installButton.prop('disabled', false).text('Install Selected Plugins (' + selectedCount + ')');
        } else {
            installButton.prop('disabled', true).text('Install Selected Plugins');
        }
    }

    function updateActivateButton() {
        var selectedCount = $('.activate-checkbox:checked').length;
        var activateButton = $('#activate-plugins');

        if (selectedCount > 0) {
            activateButton.prop('disabled', false).text('Activate Selected Plugins (' + selectedCount + ')');
        } else {
            activateButton.prop('disabled', true).text('Activate Selected Plugins');
        }
    }

    function installPlugins(plugins) {
        var progressContainer = $('#webdev-bundle-progress');
        var progressFill = $('.progress-fill');
        var progressText = $('#progress-text');
        var progressTitle = $('#progress-title');
        var installationLog = $('#installation-log');
        var installButton = $('#install-plugins');

        // Show progress container
        progressContainer.show();
        progressTitle.text('Installation Progress');
        installButton.prop('disabled', true);

        // Clear previous log
        installationLog.empty();

        // Initialize progress
        var currentPlugin = 0;
        var totalPlugins = plugins.length;

        function updateProgress() {
            var percentage = (currentPlugin / totalPlugins) * 100;
            progressFill.css('width', percentage + '%');
            progressText.text('Installing plugin ' + (currentPlugin + 1) + ' of ' + totalPlugins);
        }

        function logMessage(message, type) {
            var logEntry = $('<div class="log-entry ' + type + '">' + message + '</div>');
            installationLog.append(logEntry);
            installationLog.scrollTop(installationLog[0].scrollHeight);
        }

        function installNextPlugin() {
            if (currentPlugin >= totalPlugins) {
                // All plugins installed
                progressText.text('Installation completed!');
                installButton.prop('disabled', false).text('Install Selected Plugins');
                logMessage('All plugins have been installed successfully.', 'success');
                // Refresh page to update plugin statuses
                setTimeout(function () {
                    location.reload();
                }, 2000);
                return;
            }

            var plugin = plugins[currentPlugin];
            var pluginItem = $('.webdev-bundle-plugin-item[data-plugin-slug="' + plugin.slug + '"]');
            var pluginStatus = pluginItem.find('.plugin-status');
            var pluginCheckbox = pluginItem.find('.install-checkbox');

            // Update UI to show current plugin being installed
            updateProgress();
            logMessage('Installing ' + plugin.name + '...', 'info');

            // Update plugin status
            pluginStatus.removeClass('not-installed installed error').addClass('installing').text('Installing...');

            // Make AJAX request with timeout
            $.ajax({
                url: webdevBundle.ajaxurl,
                type: 'POST',
                timeout: 60000, // 60 second timeout
                data: {
                    action: 'webdev_install_plugin',
                    plugin_slug: plugin.slug,
                    nonce: webdevBundle.nonce
                },
                success: function (response) {
                    if (response.success) {
                        logMessage('✓ ' + plugin.name + ' installed successfully', 'success');
                        pluginStatus.removeClass('installing').addClass('installed').text('Installed');
                        pluginCheckbox.prop('disabled', true).prop('checked', false);

                        // Add activate checkbox
                        pluginItem.find('.plugin-actions').html(
                            '<input type="checkbox" name="activate_plugins[]" value="' + plugin.slug + '" ' +
                            'data-plugin-name="' + plugin.name + '" class="activate-checkbox">' +
                            '<span class="plugin-status installed">Installed</span>'
                        );
                    } else {
                        var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                        // Re-check status in case install actually succeeded
                        $.post(webdevBundle.ajaxurl, {
                            action: 'webdev_check_plugin_status',
                            plugin_slug: plugin.slug,
                            nonce: webdevBundle.nonce
                        }, function (statusResp) {
                            if (statusResp && statusResp.success && statusResp.data && statusResp.data.installed) {
                                logMessage('✓ ' + plugin.name + ' installed successfully (verified)', 'success');
                                pluginStatus.removeClass('installing error').addClass('installed').text('Installed');
                                pluginCheckbox.prop('disabled', true).prop('checked', false);
                                pluginItem.find('.plugin-actions').html(
                                    '<input type="checkbox" name="activate_plugins[]" value="' + plugin.slug + '" ' +
                                    'data-plugin-name="' + plugin.name + '" class="activate-checkbox">' +
                                    '<span class="plugin-status installed">Installed</span>'
                                );
                            } else {
                                logMessage('✗ Failed to install ' + plugin.name + ': ' + errorMessage, 'error');
                                pluginStatus.removeClass('installing').addClass('error').text('Error');
                            }
                        });
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'timeout') {
                        logMessage('✗ Timeout installing ' + plugin.name + ' - installation took too long', 'error');
                    } else {
                        logMessage('✗ Error installing ' + plugin.name + ': ' + error, 'error');
                    }
                    pluginStatus.removeClass('installing').addClass('error').text('Error');
                },
                complete: function () {
                    currentPlugin++;
                    setTimeout(installNextPlugin, 500); // Faster installation
                }
            });
        }

        // Start installation process
        logMessage('Starting installation of ' + totalPlugins + ' plugins...', 'info');
        installNextPlugin();
    }

    function activatePlugins(plugins) {
        var progressContainer = $('#webdev-bundle-progress');
        var progressFill = $('.progress-fill');
        var progressText = $('#progress-text');
        var progressTitle = $('#progress-title');
        var installationLog = $('#installation-log');
        var activateButton = $('#activate-plugins');

        // Show progress container
        progressContainer.show();
        progressTitle.text('Activation Progress');
        activateButton.prop('disabled', true);

        // Clear previous log
        installationLog.empty();

        // Initialize progress
        var currentPlugin = 0;
        var totalPlugins = plugins.length;

        function updateProgress() {
            var percentage = (currentPlugin / totalPlugins) * 100;
            progressFill.css('width', percentage + '%');
            progressText.text('Activating plugin ' + (currentPlugin + 1) + ' of ' + totalPlugins);
        }

        function logMessage(message, type) {
            var logEntry = $('<div class="log-entry ' + type + '">' + message + '</div>');
            installationLog.append(logEntry);
            installationLog.scrollTop(installationLog[0].scrollHeight);
        }

        function activateNextPlugin() {
            if (currentPlugin >= totalPlugins) {
                // All plugins activated
                progressText.text('Activation completed!');
                activateButton.prop('disabled', false).text('Activate Selected Plugins');
                logMessage('All plugins have been activated successfully.', 'success');
                // Refresh page to update plugin statuses
                setTimeout(function () {
                    location.reload();
                }, 2000);
                return;
            }

            var plugin = plugins[currentPlugin];
            var pluginItem = $('.webdev-bundle-plugin-item[data-plugin-slug="' + plugin.slug + '"]');
            var pluginStatus = pluginItem.find('.plugin-status');
            var pluginCheckbox = pluginItem.find('.activate-checkbox');

            // Update UI to show current plugin being activated
            updateProgress();
            logMessage('Activating ' + plugin.name + '...', 'info');

            // Update plugin status
            pluginStatus.removeClass('installed error').addClass('activating').text('Activating...');

            // Make AJAX request with timeout
            $.ajax({
                url: webdevBundle.ajaxurl,
                type: 'POST',
                timeout: 30000, // 30 second timeout for activation
                data: {
                    action: 'webdev_activate_plugin',
                    plugin_slug: plugin.slug,
                    nonce: webdevBundle.nonce
                },
                success: function (response) {
                    if (response.success) {
                        logMessage('✓ ' + plugin.name + ' activated successfully', 'success');
                        pluginStatus.removeClass('activating').addClass('active').text('Active');
                        pluginCheckbox.prop('disabled', true).prop('checked', false);

                        // Update to show active status
                        pluginItem.find('.plugin-actions').html(
                            '<span class="plugin-status active">Active</span>'
                        );
                    } else {
                        var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                        // Re-check status in case activation actually succeeded
                        $.post(webdevBundle.ajaxurl, {
                            action: 'webdev_check_plugin_status',
                            plugin_slug: plugin.slug,
                            nonce: webdevBundle.nonce
                        }, function (statusResp) {
                            if (statusResp && statusResp.success && statusResp.data && statusResp.data.active) {
                                logMessage('✓ ' + plugin.name + ' activated successfully (verified)', 'success');
                                pluginStatus.removeClass('activating error').addClass('active').text('Active');
                                pluginCheckbox.prop('disabled', true).prop('checked', false);
                                pluginItem.find('.plugin-actions').html(
                                    '<span class="plugin-status active">Active</span>'
                                );
                            } else {
                                logMessage('✗ Failed to activate ' + plugin.name + ': ' + errorMessage, 'error');
                                pluginStatus.removeClass('activating').addClass('error').text('Error');
                            }
                        });
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'timeout') {
                        logMessage('✗ Timeout activating ' + plugin.name + ' - activation took too long', 'error');
                    } else {
                        logMessage('✗ Error activating ' + plugin.name + ': ' + error, 'error');
                    }
                    pluginStatus.removeClass('activating').addClass('error').text('Error');
                },
                complete: function () {
                    currentPlugin++;
                    setTimeout(activateNextPlugin, 300); // Even faster activation
                }
            });
        }

        // Start activation process
        logMessage('Starting activation of ' + totalPlugins + ' plugins...', 'info');
        activateNextPlugin();
    }

    // Initialize button states
    updateInstallButton();
    updateActivateButton();

    // Add smooth scrolling for better UX
    $('a[href*="#"]').on('click', function (e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });

    // Add confirmation dialog for bulk actions
    $('#select-all-install, #select-all-activate').on('click', function () {
        var action = $(this).attr('id').includes('install') ? 'install' : 'activate';
        var availablePlugins = action === 'install' ?
            $('.install-checkbox:not(:disabled)').length :
            $('.activate-checkbox:not(:disabled)').length;

        if (availablePlugins > 0) {
            if (!confirm('This will select all ' + availablePlugins + ' available plugins for ' + action + '. Continue?')) {
                return false;
            }
        }
    });

    // Add tooltip functionality for plugin descriptions
    $('.plugin-description').each(function () {
        var $this = $(this);
        var description = $this.text();

        if (description.length > 100) {
            $this.attr('title', description);
            $this.text(description.substring(0, 100) + '...');
        }
    });

    // Add keyboard shortcuts
    $(document).on('keydown', function (e) {
        // Ctrl+I to select all install
        if (e.ctrlKey && e.keyCode === 73) {
            e.preventDefault();
            $('#select-all-install').click();
        }

        // Ctrl+Shift+A to select all activate
        if (e.ctrlKey && e.shiftKey && e.keyCode === 65) {
            e.preventDefault();
            $('#select-all-activate').click();
        }

        // Escape to deselect all
        if (e.keyCode === 27) {
            $('#deselect-all-install, #deselect-all-activate').click();
        }
    });

    // Add visual feedback for form interactions
    $('.webdev-bundle-plugin-item').on('mouseenter', function () {
        $(this).addClass('hover');
    }).on('mouseleave', function () {
        $(this).removeClass('hover');
    });

    // Enhanced error handling
    window.addEventListener('unhandledrejection', function (event) {
        console.error('Unhandled promise rejection:', event.reason);
        logMessage('An unexpected error occurred. Please try again.', 'error');
    });

    // Add accessibility improvements
    $('input[type="checkbox"]').on('focus', function () {
        $(this).closest('.webdev-bundle-plugin-item').addClass('focused');
    }).on('blur', function () {
        $(this).closest('.webdev-bundle-plugin-item').removeClass('focused');
    });

    // Add ARIA labels for screen readers
    $('.webdev-bundle-plugin-item').attr('role', 'listitem');
    $('input[type="checkbox"]').attr('aria-describedby', function () {
        return $(this).closest('.webdev-bundle-plugin-item').find('.plugin-description').attr('id') ||
            'plugin-' + $(this).val() + '-description';
    });

    // Initialize ARIA descriptions
    $('.plugin-description').each(function (index) {
        $(this).attr('id', 'plugin-description-' + index);
    });

    // Plugin Manager functionality
    if ($('#webdev-bundle-upload-form').length) {
        // File upload handling
        $('#webdev-bundle-upload-form').on('submit', function (e) {
            e.preventDefault();

            var fileInput = $('#plugin_file')[0];
            var file = fileInput.files[0];

            if (!file) {
                alert('Please select a file to upload.');
                return;
            }

            if (file.type !== 'application/zip' && !file.name.endsWith('.zip')) {
                alert('Please select a ZIP file.');
                return;
            }

            if (file.size > 50 * 1024 * 1024) {
                alert('File size too large. Maximum 50MB allowed.');
                return;
            }

            uploadPlugin(file);
        });

        // Delete plugin handling
        $(document).on('click', '.delete-plugin', function () {
            var filename = $(this).data('filename');
            var pluginItem = $(this).closest('.uploaded-plugin-item');

            if (confirm('Are you sure you want to delete "' + filename + '"? This action cannot be undone.')) {
                deletePlugin(filename, pluginItem);
            }
        });

        // Debug upload handling
        $('#debug-upload').on('click', function () {
            debugUpload();
        });
    }

    function uploadPlugin(file) {
        var formData = new FormData();
        formData.append('action', 'webdev_upload_plugin');
        formData.append('plugin_file', file);
        formData.append('nonce', webdevBundle.nonce);

        var progressContainer = $('#upload-progress');
        var progressFill = $('.upload-progress-fill');
        var statusText = $('#upload-status');
        var uploadButton = $('#webdev-bundle-upload-form button[type="submit"]');

        // Show progress
        progressContainer.show();
        uploadButton.prop('disabled', true);
        statusText.text('Uploading...');

        $.ajax({
            url: webdevBundle.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function (evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total * 100;
                        progressFill.css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function (response) {
                if (response.success) {
                    statusText.text('Upload completed successfully!');
                    progressFill.css('width', '100%');

                    // Refresh page after 2 seconds
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    statusText.text('Upload failed: ' + (response.data.message || 'Unknown error'));
                    progressFill.css('width', '0%');
                }
            },
            error: function (xhr, status, error) {
                statusText.text('Upload failed: ' + error);
                progressFill.css('width', '0%');
            },
            complete: function () {
                uploadButton.prop('disabled', false);
            }
        });
    }

    function deletePlugin(filename, pluginItem) {
        $.ajax({
            url: webdevBundle.ajaxurl,
            type: 'POST',
            data: {
                action: 'webdev_delete_uploaded_plugin',
                filename: filename,
                nonce: webdevBundle.nonce
            },
            success: function (response) {
                if (response.success) {
                    pluginItem.fadeOut(300, function () {
                        $(this).remove();
                    });
                } else {
                    alert('Failed to delete plugin: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function (xhr, status, error) {
                alert('Failed to delete plugin: ' + error);
            }
        });
    }

    function debugUpload() {
        $.ajax({
            url: webdevBundle.ajaxurl,
            type: 'POST',
            data: {
                action: 'webdev_debug_upload',
                nonce: webdevBundle.nonce
            },
            success: function (response) {
                if (response.success) {
                    var debugInfo = response.data;
                    var debugText = 'Upload Debug Information:\n\n';
                    debugText += 'Upload Directory: ' + debugInfo.upload_dir + '\n';
                    debugText += 'Plugin Directory: ' + debugInfo.plugin_dir + '\n';
                    debugText += 'Upload Dir Exists: ' + (debugInfo.upload_dir_exists ? 'Yes' : 'No') + '\n';
                    debugText += 'Upload Dir Writable: ' + (debugInfo.upload_dir_writable ? 'Yes' : 'No') + '\n';
                    debugText += 'Plugin Dir Exists: ' + (debugInfo.plugin_dir_exists ? 'Yes' : 'No') + '\n';
                    debugText += 'Plugin Dir Writable: ' + (debugInfo.plugin_dir_writable ? 'Yes' : 'No') + '\n';
                    debugText += 'PHP Upload Max Filesize: ' + debugInfo.php_upload_max_filesize + '\n';
                    debugText += 'PHP Post Max Size: ' + debugInfo.php_post_max_size + '\n';
                    debugText += 'PHP Max Execution Time: ' + debugInfo.php_max_execution_time + '\n';
                    debugText += 'PHP Memory Limit: ' + debugInfo.php_memory_limit + '\n';
                    debugText += 'File Uploads Enabled: ' + (debugInfo.file_uploads_enabled ? 'Yes' : 'No') + '\n';
                    debugText += 'Temp Directory: ' + debugInfo.temp_dir + '\n';
                    debugText += 'Temp Dir Writable: ' + (debugInfo.temp_dir_writable ? 'Yes' : 'No') + '\n';

                    alert(debugText);
                } else {
                    alert('Debug failed: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function (xhr, status, error) {
                alert('Debug request failed: ' + error);
            }
        });
    }
});