<?php
require_once 'db.php';
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$sid = $_GET['sid'] ?? '';
$last_id = (int)($_GET['last_id'] ?? 0);
$dept = $_GET['dept'] ?? 'All';

try {
    $response = ['success' => true, 'messages' => [], 'sessions' => []];

    // Fetch new messages for active session
    if ($sid) {
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY id ASC");
        $stmt->execute([$sid, $last_id]);
        $response['messages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch session summaries for sidebar
    $dept_query = ($dept !== 'All') ? "WHERE s.department = :dept" : "";
    $q = "SELECT m.session_id, MAX(m.id) as max_id, MAX(m.created_at) as last_msg, s.customer_name, s.department,
          SUM(CASE WHEN m.sender = 'User' AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
          FROM chat_messages m
          LEFT JOIN chat_sessions s ON m.session_id = s.session_id
          $dept_query
          GROUP BY m.session_id ORDER BY last_msg DESC";
    $stmt = $pdo->prepare($q);
    if ($dept !== 'All') $stmt->execute(['dept' => $dept]); else $stmt->execute();
    $response['sessions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
