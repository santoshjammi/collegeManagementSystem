<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'dashboard_layout' => $_POST['dashboard_layout'] ?? 'default',
        'theme' => $_POST['theme'] ?? 'light',
        'notifications_email' => isset($_POST['notifications_email']) ? 1 : 0,
        'notifications_sms' => isset($_POST['notifications_sms']) ? 1 : 0,
        'notifications_in_app' => isset($_POST['notifications_in_app']) ? 1 : 0,
        'language' => $_POST['language'] ?? 'en',
        'timezone' => $_POST['timezone'] ?? 'UTC',
        'items_per_page' => (int)($_POST['items_per_page'] ?? 25),
        'auto_refresh' => isset($_POST['auto_refresh']) ? 1 : 0,
        'compact_view' => isset($_POST['compact_view']) ? 1 : 0
    ];

    try {
        // Check if user settings exist
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_settings WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Update existing settings
            $stmt = $pdo->prepare("
                UPDATE user_settings SET
                    dashboard_layout = ?, theme = ?, notifications_email = ?,
                    notifications_sms = ?, notifications_in_app = ?, language = ?,
                    timezone = ?, items_per_page = ?, auto_refresh = ?, compact_view = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute(array_merge(array_values($settings), [$user['user_id']]));
        } else {
            // Insert new settings
            $stmt = $pdo->prepare("
                INSERT INTO user_settings
                (user_id, dashboard_layout, theme, notifications_email, notifications_sms,
                 notifications_in_app, language, timezone, items_per_page, auto_refresh, compact_view)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(array_merge([$user['user_id']], array_values($settings)));
        }

        $message = "Settings updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
$currentSettings = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $currentSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
} catch (Exception $e) {
    // Use defaults if table doesn't exist
}

// Set defaults
$defaults = [
    'dashboard_layout' => 'default',
    'theme' => 'light',
    'notifications_email' => 1,
    'notifications_sms' => 0,
    'notifications_in_app' => 1,
    'language' => 'en',
    'timezone' => 'UTC',
    'items_per_page' => 25,
    'auto_refresh' => 0,
    'compact_view' => 0
];

$settings = array_merge($defaults, $currentSettings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 2rem;
        }

        .settings-section {
            border-bottom: 1px solid #f8f9fa;
            padding: 2rem;
        }

        .settings-section:last-child {
            border-bottom: none;
        }

        .setting-group {
            margin-bottom: 2rem;
        }

        .setting-group:last-child {
            margin-bottom: 0;
        }

        .setting-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }

        .setting-description {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .theme-preview {
            display: inline-block;
            width: 60px;
            height: 40px;
            border-radius: 6px;
            margin-right: 1rem;
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .theme-preview:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }

        .theme-preview.light {
            background: linear-gradient(to bottom, #ffffff 50%, #f8f9fa 50%);
        }

        .theme-preview.dark {
            background: linear-gradient(to bottom, #343a40 50%, #212529 50%);
        }

        .theme-preview.auto {
            background: linear-gradient(45deg, #ffffff 50%, #343a40 50%);
        }

        .layout-preview {
            display: inline-block;
            width: 80px;
            height: 60px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            margin-right: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .layout-preview:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }

        .layout-preview.default {
            background: linear-gradient(to right, #e9ecef 33%, #dee2e6 33%, #dee2e6 66%, #adb5bd 66%);
        }

        .layout-preview.compact {
            background: linear-gradient(to bottom, #e9ecef 50%, #dee2e6 50%);
        }

        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 500;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .notification-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }

        .notification-toggle:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-mortarboard-fill me-2"></i>
                College Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Settings Header -->
        <div class="settings-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="bi bi-gear me-2"></i>
                        Settings & Preferences
                    </h2>
                    <p class="mb-0 opacity-75">Customize your dashboard experience and notification preferences</p>
                </div>
                <div class="col-md-4 text-end">
                    <i class="bi bi-palette" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Appearance Settings -->
            <div class="settings-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-palette me-2"></i>
                        Appearance
                    </h5>
                </div>

                <div class="settings-section">
                    <div class="setting-group">
                        <label class="setting-label">Theme</label>
                        <p class="setting-description">Choose your preferred color scheme</p>
                        <div class="d-flex align-items-center">
                            <input type="radio" class="btn-check" name="theme" id="theme-light"
                                   value="light" <?php echo $settings['theme'] === 'light' ? 'checked' : ''; ?>>
                            <label class="theme-preview light" for="theme-light"></label>

                            <input type="radio" class="btn-check" name="theme" id="theme-dark"
                                   value="dark" <?php echo $settings['theme'] === 'dark' ? 'checked' : ''; ?>>
                            <label class="theme-preview dark" for="theme-dark"></label>

                            <input type="radio" class="btn-check" name="theme" id="theme-auto"
                                   value="auto" <?php echo $settings['theme'] === 'auto' ? 'checked' : ''; ?>>
                            <label class="theme-preview auto" for="theme-auto"></label>
                        </div>
                        <small class="text-muted">Light, Dark, Auto (follows system preference)</small>
                    </div>

                    <div class="setting-group">
                        <label class="setting-label">Dashboard Layout</label>
                        <p class="setting-description">Choose how your dashboard is organized</p>
                        <div class="d-flex align-items-center">
                            <input type="radio" class="btn-check" name="dashboard_layout" id="layout-default"
                                   value="default" <?php echo $settings['dashboard_layout'] === 'default' ? 'checked' : ''; ?>>
                            <label class="layout-preview default" for="layout-default"></label>

                            <input type="radio" class="btn-check" name="dashboard_layout" id="layout-compact"
                                   value="compact" <?php echo $settings['dashboard_layout'] === 'compact' ? 'checked' : ''; ?>>
                            <label class="layout-preview compact" for="layout-compact"></label>
                        </div>
                        <small class="text-muted">Default (spacious), Compact (more content)</small>
                    </div>

                    <div class="setting-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="compact_view" name="compact_view"
                                   <?php echo $settings['compact_view'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="compact_view">
                                <strong>Compact View</strong>
                            </label>
                            <p class="setting-description mb-0">Show more information in less space</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="settings-card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-bell me-2"></i>
                        Notifications
                    </h5>
                </div>

                <div class="settings-section">
                    <p class="setting-description">Choose how you want to receive notifications</p>

                    <div class="notification-toggle">
                        <div>
                            <strong>Email Notifications</strong>
                            <p class="mb-0 text-muted">Receive notifications via email</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notifications_email" name="notifications_email"
                                   <?php echo $settings['notifications_email'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notifications_email"></label>
                        </div>
                    </div>

                    <div class="notification-toggle">
                        <div>
                            <strong>SMS Notifications</strong>
                            <p class="mb-0 text-muted">Receive important alerts via SMS</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notifications_sms" name="notifications_sms"
                                   <?php echo $settings['notifications_sms'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notifications_sms"></label>
                        </div>
                    </div>

                    <div class="notification-toggle">
                        <div>
                            <strong>In-App Notifications</strong>
                            <p class="mb-0 text-muted">Show notifications within the application</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notifications_in_app" name="notifications_in_app"
                                   <?php echo $settings['notifications_in_app'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notifications_in_app"></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Settings -->
            <div class="settings-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-sliders me-2"></i>
                        General
                    </h5>
                </div>

                <div class="settings-section">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="setting-group">
                                <label for="language" class="setting-label">Language</label>
                                <select class="form-select" id="language" name="language">
                                    <option value="en" <?php echo $settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="es" <?php echo $settings['language'] === 'es' ? 'selected' : ''; ?>>Español</option>
                                    <option value="fr" <?php echo $settings['language'] === 'fr' ? 'selected' : ''; ?>>Français</option>
                                    <option value="de" <?php echo $settings['language'] === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="setting-group">
                                <label for="timezone" class="setting-label">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                    <option value="America/Chicago" <?php echo $settings['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                    <option value="America/Denver" <?php echo $settings['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                    <option value="America/Los_Angeles" <?php echo $settings['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                    <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                    <option value="Europe/Paris" <?php echo $settings['timezone'] === 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                    <option value="Asia/Kolkata" <?php echo $settings['timezone'] === 'Asia/Kolkata' ? 'selected' : ''; ?>>India (IST)</option>
                                    <option value="Asia/Tokyo" <?php echo $settings['timezone'] === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="setting-group">
                                <label for="items_per_page" class="setting-label">Items Per Page</label>
                                <select class="form-select" id="items_per_page" name="items_per_page">
                                    <option value="10" <?php echo $settings['items_per_page'] == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo $settings['items_per_page'] == 25 ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $settings['items_per_page'] == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $settings['items_per_page'] == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="setting-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_refresh" name="auto_refresh"
                                           <?php echo $settings['auto_refresh'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_refresh">
                                        <strong>Auto-refresh Dashboard</strong>
                                    </label>
                                    <p class="setting-description mb-0">Automatically refresh dashboard data every 5 minutes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="text-center mb-4">
                <button type="submit" class="btn btn-save">
                    <i class="bi bi-check-circle me-2"></i>
                    Save Settings
                </button>
            </div>
        </form>

        <!-- Additional Actions -->
        <div class="settings-card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Additional Actions
                </h5>
            </div>

            <div class="settings-section">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-grid">
                            <button class="btn btn-outline-danger" onclick="resetSettings()">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>
                                Reset to Defaults
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="bi bi-person me-2"></i>
                                Back to Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
                // Reset form to defaults
                document.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.checked = radio.value === 'default' || radio.value === 'light' || radio.value === 'en' || radio.value === 'UTC';
                });
                document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = checkbox.name === 'notifications_email' || checkbox.name === 'notifications_in_app';
                });
                document.getElementById('items_per_page').value = '25';
                document.getElementById('language').value = 'en';
                document.getElementById('timezone').value = 'UTC';
            }
        }

        // Preview theme changes
        document.querySelectorAll('input[name="theme"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const theme = this.value;
                document.body.className = theme === 'dark' ? 'bg-dark text-light' : 'bg-light';
            });
        });
    </script>
</body>
</html>