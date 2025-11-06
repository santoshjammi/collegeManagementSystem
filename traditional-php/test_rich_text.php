<?php
// Quick test for rich text announcement creation
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Rich Text Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Test Rich Text Announcement Creation</h1>
        <p>User: <?php echo $user ? $user['full_name'] . ' (' . $user['role_name'] . ')' : 'Not logged in'; ?></p>

        <div id="testResult"></div>

        <form id="testAnnouncementForm" class="mt-4">
            <div class="mb-3">
                <label for="testTitle" class="form-label">Title</label>
                <input type="text" class="form-control" id="testTitle" name="title" required>
            </div>
            <div class="mb-3">
                <label for="testContent" class="form-label">Content (Rich Text)</label>
                <textarea class="form-control" id="testContent" name="content" rows="6" required></textarea>
            </div>
            <div class="mb-3">
                <label for="testPriority" class="form-label">Priority</label>
                <select class="form-select" id="testPriority" name="priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Create Test Announcement</button>
        </form>
    </div>

    <script>
        // Initialize TinyMCE
        tinymce.init({
            selector: '#testContent',
            height: 300,
            menubar: false,
            plugins: 'lists link image code',
            toolbar: 'bold italic underline | bullist numlist | link | code | undo redo',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }'
        });

        // Handle form submission
        document.getElementById('testAnnouncementForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Get rich text content from TinyMCE
            if (tinymce.activeEditor) {
                const richContent = tinymce.activeEditor.getContent();
                formData.set('content', richContent);
            }

            fetch('ajax/create_announcement.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('testResult');
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">✓ Announcement created successfully! ID: ' + data.announcement_id + '</div>';
                    // Reset form
                    this.reset();
                    if (tinymce.activeEditor) {
                        tinymce.activeEditor.setContent('');
                    }
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">❌ Error: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('testResult').innerHTML = '<div class="alert alert-danger">❌ Network error: ' + error.message + '</div>';
            });
        });
    </script>
</body>
</html>