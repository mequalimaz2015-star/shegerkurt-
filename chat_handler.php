<?php
require_once 'db.php';
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure tables exist before inserting/querying
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_sessions (
            session_id VARCHAR(50) PRIMARY KEY,
            customer_name VARCHAR(100),
            customer_email VARCHAR(100),
            customer_phone VARCHAR(50),
            department VARCHAR(50) DEFAULT 'Restaurant',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(50),
            sender ENUM('User', 'Admin') DEFAULT 'User',
            message TEXT,
            image_path VARCHAR(255) DEFAULT NULL,
            location_lat VARCHAR(50) DEFAULT NULL,
            location_lng VARCHAR(50) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE
        );
    ");
    // Safely add missing columns to any existing table
    try {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS location_lat VARCHAR(50) DEFAULT NULL");
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS location_lng VARCHAR(50) DEFAULT NULL");
    } catch (PDOException $e2) { }
} catch (PDOException $e) { }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send message
    $sid = $_POST['session_id'] ?? '';
    $msg = $_POST['message'] ?? '';
    $name = $_POST['customer_name'] ?? '';
    $phone = $_POST['customer_phone'] ?? '';
    $dept = $_POST['department'] ?? 'Restaurant';

    if (!$sid) $sid = bin2hex(random_bytes(8));

    try {
        // Upsert session
        $check = $pdo->prepare("SELECT COUNT(*) FROM chat_sessions WHERE session_id = ?");
        $check->execute([$sid]);
        if ($check->fetchColumn() == 0) {
            $pdo->prepare("INSERT INTO chat_sessions (session_id, customer_name, customer_phone, department) VALUES (?, ?, ?, ?)")
                ->execute([$sid, $name, $phone, $dept]);
        }

        // Insert message
        $pdo->prepare("INSERT INTO chat_messages (session_id, sender, message, is_read) VALUES (?, 'User', ?, 0)")
            ->execute([$sid, $msg]);

        // Handle auto-reply if present
        if (isset($_POST['auto_reply'])) {
            $pdo->prepare("INSERT INTO chat_messages (session_id, sender, message, is_read) VALUES (?, 'Admin', ?, 1)")
                ->execute([$sid, $_POST['auto_reply']]);
        }

        echo json_encode(['success' => true, 'session_id' => $sid]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // Fetch messages
    $sid = $_GET['session_id'] ?? '';
    $last_id = (int)($_GET['last_id'] ?? 0);

    if ($sid) {
        try {
            // Mark admin messages as read when fetched by user
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender = 'Admin'")->execute([$sid]);

            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY id ASC");
            $stmt->execute([$sid, $last_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'messages' => $messages]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No session ID']);
    }
}
?>
