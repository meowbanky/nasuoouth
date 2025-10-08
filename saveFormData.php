<?php
session_start();
require_once('class/DataBaseHandler.php');
require_once('sendSmsNewMember.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming you've sanitized and validated your inputs
    $dbHandler = new DataBaseHandler();

    $staffNo = $_POST['staff_no'];
    $title = $_POST['sfxname'];
    $firstName = $_POST['Fname'];
    $middleName = $_POST['Mname'] ?? ''; // Optional field
    $lastName = $_POST['Lname'];
    $gender = $_POST['gender'];
    $dob = date('Y-m-d', strtotime($_POST['DOB'])); // Formatting to SQL date
    $address = $_POST['Address'];
    $address2 = $_POST['Address2'] ?? ''; // Optional field
    $city = $_POST['City'];
    $stateId = $_POST['State'];
    $mobilePhone = $_POST['MobilePhone'];
    $emailAddress = $_POST['EmailAddress'] ?? ''; // Optional field
    $status = isset($_POST['statusToggle']) ? 1 : 0; // Assuming 1 for Active, 0 for Inactive
    $nokName = $_POST['NOkName'];
    $nokRelationship = $_POST['NOKRelationship'];
    $nokPhone = $_POST['NOKPhone'];
    $nokAddress = $_POST['NOKAddress'];


    // Generate a 5-character random password
    $plainPassword = $dbHandler->generateRandomPassword(5);
    $hashedPassword = $dbHandler->hashPassword($plainPassword);
    $contributions = $dbHandler->getContributionSettings();
    // Save to database
    $saved = $dbHandler->saveFormData($contributions, $plainPassword, $hashedPassword, $staffNo, $title, $firstName, $middleName, $lastName, $gender, $dob, $address, $address2, $city, $stateId, $mobilePhone, $emailAddress, $status, $nokName, $nokRelationship, $nokPhone, $nokAddress);


    if ($saved) {
        $sentMessageMessage = 'Kindly download OOUTH NASU mobile App via http://www.emmaggi.com/download/nasuwel.apk. Your username: ' .
            $staffNo . ' Password:  ' . $plainPassword . ' Change your password after login';
        doSendMessage($mobilePhone, $sentMessageMessage);
        $_SESSION['message'] = "Registration successful.";
    } else {
        $_SESSION['error'] = "There was a problem with the registration.";
    }

   // header('Location: registrationForm.php'); // Redirect back to the form or to a confirmation page
    exit;
}
