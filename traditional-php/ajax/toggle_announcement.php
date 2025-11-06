<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $user = getCurrentUser();
    if (!$user || !hasRole(['Admin', 'Administrative Staff', 'Faculty'])) {
        throw new Exception('Unauthorized access');
    }

    $announcementId = (int)($_POST['announcement_id'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);

    if ($announcementId <= 0) {
        throw new Exception('Invalid announcement ID');
    }

    global $pdo;

    // Update announcement status
    $stmt = $pdo->prepare("UPDATE announcements SET is_active = ? WHERE announcement_id = ?");
    $stmt->execute([$status, $announcementId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Announcement not found or no changes made');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Announcement status updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>