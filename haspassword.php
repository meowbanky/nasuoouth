<?php
set_time_limit(300);
require_once('Connections/db_constants.php.php'); // Make sure this uses MySQLi


// Fetch all users
$sql = "SELECT UserID, PlainPassword FROM tblusers";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userId = $row['UserID'];
        $plainPassword = $row['PlainPassword']; // Assuming this is the current plaintext or inadequately hashed password

        // Hash the password
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        // Update the user's password in the database
        $updateStmt = $conn->prepare("UPDATE tblusers SET UPassword = ? WHERE UserID = ?");
        $updateStmt->bind_param("si", $hashedPassword, $userId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    echo "Passwords updated successfully.";
} else {
    echo "No users found.";
}

$conn->close();
