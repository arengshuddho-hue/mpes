<?php
// api/notifications.php
// API endpoint for fetching and updating notifications for the logged-in user.

session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Helper function to calculate relative time
function timeAgo($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$string) {
        return 'just now';
    }
    
    $string = array_slice($string, 0, 1);
    return implode(', ', $string) . ' ago';
}

try {
    if ($method === 'GET') {
        // Fetch unread count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $unread_count = (int)$stmt->fetchColumn();

        // Fetch recent 10 notifications
        $stmt = $pdo->prepare("SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll();

        // Format relative timestamps
        foreach ($notifications as &$n) {
            $n['time_ago'] = timeAgo($n['created_at']);
            $n['is_read'] = (bool)$n['is_read'];
        }

        echo json_encode([
            'success' => true,
            'unread_count' => $unread_count,
            'notifications' => $notifications
        ]);
        exit;
    } 
    
    elseif ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'mark_read') {
            $notification_id = $_POST['notification_id'] ?? null;
            if (!$notification_id) {
                echo json_encode(['success' => false, 'message' => 'Missing notification ID.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $user_id]);

            echo json_encode(['success' => true, 'message' => 'Notification marked as read.']);
            exit;
        } 
        
        elseif ($action === 'mark_all_read') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);

            echo json_encode(['success' => true, 'message' => 'All notifications marked as read.']);
            exit;
        } 
        
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            exit;
        }
    } 
    
    else {
        header('HTTP/1.1 405 Method Not Allowed');
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
