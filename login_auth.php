<?php
require_once('Connections/db_constants.php'); // Ensure this file uses MySQLi for database connection
session_start();
session_regenerate_id();

if (isset($_POST['username']) && isset($_POST['password'])) {
	$loginUsername = $mysqli->real_escape_string(trim($_POST['username']));
	$password = trim($_POST['password']);

	// Prepare statement to prevent SQL injection
	$stmt = $mysqli->prepare("SELECT * FROM tblusers WHERE Username = ? AND status = 'Active' AND access = 1");
	$stmt->bind_param("s", $loginUsername);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		// Verify password
		if (password_verify($password, $row['UPassword'])) {
			// Password is correct, start session


			$_SESSION['FirstName'] = $row['lastname'] . ", " . $row['firstname'];
			$_SESSION['UserID'] = $row['UserID'];

			echo json_encode(array("status" => "success", "message" => "Login successful"));
			exit;
		} else {
			header('Content-Type: application/json');
			echo json_encode(array("status" => "error", "message" => "Incorrect username or password"));

			exit;
		}
	} else {
		header('Content-Type: application/json');
		echo json_encode(array("status" => "error", "message" => "Incorrect username or password"));
	}

	$stmt->close();
}
$mysqli->close();
