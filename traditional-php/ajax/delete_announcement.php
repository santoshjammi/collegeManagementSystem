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

    if ($announcementId <= 0) {
        throw new Exception('Invalid announcement ID');
    }

    global $pdo;

    // Delete announcement and related notifications
    $pdo->beginTransaction();

    try {
        // Delete related notifications first
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE url LIKE ? OR message LIKE ?");
        $stmt->execute(['%announcement_id=' . $announcementId . '%', '%announcement%']);

        // Delete the announcement
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
        $stmt->execute([$announcementId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Announcement not found');
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Announcement deleted successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>