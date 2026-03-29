<?php
require_once 'db.php';

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
    echo "chat_sessions table: OK<br>";

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
    echo "chat_messages table: OK<br>";

    // Add missing columns if they don't exist yet
    try {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL");
        echo "image_path column: OK<br>";
    } catch (PDOException $e) { echo "image_path: " . $e->getMessage() . "<br>"; }
    
    try {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS location_lat VARCHAR(50) DEFAULT NULL");
        echo "location_lat column: OK<br>";
    } catch (PDOException $e) { echo "location_lat: " . $e->getMessage() . "<br>"; }
    
    try {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS location_lng VARCHAR(50) DEFAULT NULL");
        echo "location_lng column: OK<br>";
    } catch (PDOException $e) { echo "location_lng: " . $e->getMessage() . "<br>"; }
    
    echo "<h3 style='color:green;'>✅ Success!</h3>";
    echo "<p>All chat tables and columns are set up correctly. Admin replies will now be delivered to customers.</p>";
    echo "<p><a href='admin.php?tab=chatbot'>Go to Chatbot →</a></p>";
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Error!</h3>";
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
