<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['full_name'] ?? '';
    $email = $_POST['email_address'] ?? '';
    $persons = $_POST['total_person'] ?? '';
    $date = $_POST['booking_date'] ?? '';
    $time = $_POST['reservation_time'] ?? date('H:i');
    $table_id = $_POST['table_id'] ?? null;
    $message = $_POST['message'] ?? '';
    
    // Extract numeric portion from persons string
    $guests = (int)filter_var($persons, FILTER_SANITIZE_NUMBER_INT) ?: 1;

    try {
        // Prevent booking in the past
        $today = date('Y-m-d');
        if ($date < $today) {
            echo "<script>alert('Please select a forward date. You cannot book a table for a past date.'); window.history.back();</script>";
            exit;
        }

        if ($table_id) {
            // Check for overlapping reservations for the same table on the same date
            // We consider a table reserved if another booking exists within 2 hours
            $check = $pdo->prepare("SELECT reservation_time FROM reservations WHERE table_id = ? AND reservation_date = ? AND status != 'Rejected'");
            $check->execute([$table_id, $date]);
            
            foreach ($check->fetchAll() as $res) {
                $existing_time = strtotime($res['reservation_time']);
                $new_time = strtotime($time);
                
                // If the new booking is within 2 hours (7200 seconds) of an existing booking
                if (abs($existing_time - $new_time) < 7200) {
                    echo "<script>alert('Please select another table. This table is already reserved closely around this time!'); window.history.back();</script>";
                    exit;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO reservations (customer_name, email, reservation_date, reservation_time, guests, table_id, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$name, $email, $date, $time, $guests, $table_id, $message]);
        
        echo "<script>alert('Reservation submitted successfully! We will contact you soon.'); window.location.href='index.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
    }
} else {
    header('Location: index.php');
}
?>
