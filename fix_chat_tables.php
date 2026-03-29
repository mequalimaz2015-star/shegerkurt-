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
    echo "chat_sessions table created or already exists.<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(50),
            sender ENUM('User', 'Admin') DEFAULT 'User',
            message TEXT,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE
        );
    ");
    echo "chat_messages table created or already exists.<br>";
    
    echo "<h3>Success!</h3><p>The chat tables have been created successfully. You can now use the chatbot.</p>";
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Error creating tables!</h3>";
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
