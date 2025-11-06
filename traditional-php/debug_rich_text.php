<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Rich Text Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        body { padding: 20px; }
        .debug-info { background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Rich Text Editor</h1>

        <div class="debug-info">
            <strong>User:</strong> <?php echo $user ? $user['full_name'] . ' (' . $user['role_name'] . ')' : 'Not logged in'; ?><br>
            <strong>Role Check:</strong> <?php echo hasRole(['Admin', 'Administrative Staff', 'Faculty']) ? 'Has permission' : 'No permission'; ?><br>
            <strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h3>Test Modal</h3>
                <button class="btn btn-primary" onclick="showCreateAnnouncementModal()">Open Modal</button>
                <button class="btn btn-secondary" onclick="testTinyMCE()">Test TinyMCE</button>
            </div>
            <div class="col-md-6">
                <h3>Direct Editor Test</h3>
                <form id="directTestForm">
                    <div class="mb-3">
                        <label>Title:</label>
                        <input type="text" class="form-control" id="directTitle" value="Test Announcement">
                    </div>
                    <div class="mb-3">
                        <label>Content:</label>
                        <textarea class="form-control" id="directContent" rows="6">This is a <strong>test</strong> announcement with <em>formatting</em>.</textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Submit Direct</button>
                </form>
            </div>
        </div>

        <div id="debugOutput" class="mt-4"></div>
    </div>

    <script>
        // Test TinyMCE initialization
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('Initializing TinyMCE...');
            initTinyMCE();
        });

    <!-- Create Announcement Modal -->
    <div class="modal fade" id="createAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Announcement (Debug)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="announcementError"></div>
                    <form id="createAnnouncementForm">
                        <div class="mb-3">
                            <label for="announcementTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="announcementTitle" name="title" required value="Debug Test">
                        </div>
                        <div class="mb-3">
                            <label for="announcementContent" class="form-label">Content (Rich Text)</label>
                            <textarea class="form-control" id="announcementContent" name="content" rows="6" required>This is a <strong>test</strong> announcement.</textarea>
                            <small class="text-muted"><span id="charCount">1000</span> characters remaining</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="announcementPriority" class="form-label">Priority</label>
                                <select class="form-select" id="announcementPriority" name="priority" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="announcementAudience" class="form-label">Target Audience</label>
                                <select class="form-select" id="announcementAudience" name="target_audience" required>
                                    <option value="All" selected>All Users</option>
                                    <option value="Student">Students Only</option>
                                    <option value="Faculty">Faculty Only</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAnnouncement()">
                        <i class="bi bi-check-circle"></i> Create Announcement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let tinymceInitialized = false;

        // Initialize TinyMCE
        function initTinyMCE() {
            if (tinymceInitialized) return;

            debugLog('Initializing TinyMCE...');

            tinymce.init({
                selector: '#announcementContent, #directContent',
                height: 300,
                menubar: false,
                plugins: 'lists link code',
                toolbar: 'bold italic underline | bullist numlist | link | code | undo redo',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
                setup: function(editor) {
                    editor.on('init', function() {
                        debugLog('TinyMCE initialized successfully');
                        tinymceInitialized = true;
                    });
                    editor.on('change', function() {
                        updateCharCount();
                    });
                    editor.on('keyup', function() {
                        updateCharCount();
                    });
                }
            });
        }

        // Initialize TinyMCE when page loads
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('Page loaded, initializing TinyMCE...');
            initTinyMCE();
        });

        function showCreateAnnouncementModal() {
            debugLog('Opening modal...');
            $('#createAnnouncementModal').modal('show');
        }

        function testTinyMCE() {
            debugLog('Testing TinyMCE...');
            if (typeof tinymce !== 'undefined') {
                debugLog('TinyMCE is loaded');
                if (tinymce.activeEditor) {
                    debugLog('Active editor found');
                    const content = tinymce.activeEditor.getContent();
                    debugLog('Content: ' + content);
                } else {
                    debugLog('No active editor');
                }
            } else {
                debugLog('TinyMCE not loaded');
            }
        }

        function updateCharCount() {
            if (tinymce.activeEditor) {
                const content = tinymce.activeEditor.getContent({format: 'text'});
                const counter = document.getElementById('charCount');
                const remaining = 1000 - content.length;
                counter.textContent = remaining;
                counter.className = remaining < 100 ? 'text-danger' : (remaining < 200 ? 'text-warning' : 'text-muted');
            }
        }

        function submitAnnouncement() {
            debugLog('Submitting announcement...');

            const form = document.getElementById('createAnnouncementForm');
            const formData = new FormData(form);

            // Get rich text content from TinyMCE
            if (tinymce.activeEditor) {
                const richContent = tinymce.activeEditor.getContent();
                formData.set('content', richContent);
                debugLog('Rich content: ' + richContent);
            }

            fetch('ajax/create_announcement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                debugLog('Response status: ' + response.status);
                return response.json();
            })
            .then(data => {
                debugLog('Response data: ' + JSON.stringify(data));
                if (data.success) {
                    debugLog('Success! Announcement created with ID: ' + data.announcement_id);
                    $('#createAnnouncementModal').modal('hide');
                    location.reload();
                } else {
                    document.getElementById('announcementError').innerHTML =
                        '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                debugLog('Error: ' + error.message);
                document.getElementById('announcementError').innerHTML =
                    '<div class="alert alert-danger">Network error: ' + error.message + '</div>';
            });
        }

        // Direct form test
        document.getElementById('directTestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            debugLog('Direct form submitted');

            const formData = new FormData(this);
            formData.set('priority', 'Medium');
            formData.set('target_audience', 'All');

            if (tinymce.get('directContent')) {
                const content = tinymce.get('directContent').getContent();
                formData.set('content', content);
                debugLog('Direct content: ' + content);
            }

            fetch('ajax/create_announcement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                debugLog('Direct response: ' + JSON.stringify(data));
                if (data.success) {
                    debugLog('Direct success!');
                } else {
                    debugLog('Direct error: ' + data.message);
                }
            });
        });

        function debugLog(message) {
            const output = document.getElementById('debugOutput');
            const timestamp = new Date().toLocaleTimeString();
            output.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            console.log(`[${timestamp}] ${message}`);
        }
    </script>
</body>
</html>