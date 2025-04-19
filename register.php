<?php
session_start(); // Start the session

// Include necessary files
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'db.php';  // Database connection

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Initialize Twig
$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate Form Data
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $studyLevel = $_POST['study_level'];
    $subjectInterest = $_POST['subject_interest'];
    $numberGuests = $_POST['guests'];

    // Initialize errors array
    $errors = [];

    // --- Validation logic ---
    if (empty($fullName)) {
        $errors['full_name'] = 'Full name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email address.';
    }

    // If there are errors, display the form with error messages
    if (!empty($errors)) {
        echo $twig->render('register.twig', [
            'errors' => $errors,
            'fullName' => $fullName,
            'email' => $email
        ]);
        exit;
    }

    // 2. Check if Email Already Exists
    $stmt = $db->prepare("SELECT id FROM members WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $errors['email'] = 'Email address already registered.';
        echo $twig->render('register.twig', [
            'errors' => $errors,
            'fullName' => $fullName,
            'email' => $email
        ]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // 3. Generate Unique Registration Code
    $registrationCode = bin2hex(random_bytes(32));

    // 4. Store User Data in Database
    $stmt = $db->prepare("INSERT INTO members (full_name, email, study_level, subject_interest, number_of_guests, registration_code) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $fullName, $email, $studyLevel, $subjectInterest, $numberGuests, $registrationCode);

    if ($stmt->execute()) {
        // 5. Send Verification Email
        $verificationLink = "verify.php?code=" . $registrationCode;
        $subject = "Open Day Registration Verification";
        $message = "Please click the following link to verify your registration: " . $verificationLink;
        $headers = "From: noreply@yourdomain.com";

        if (mail($email, $subject, $message, $headers)) {
            $_SESSION['registration_email'] = $email;
            header("Location: register.php");
            exit;
        } else {
            echo "Error sending verification email.";
        }
    } else {
        echo "Database error: " . $stmt->error;
    }
    $stmt->close();
    $db->close();
} else {
    // Display the registration form
    $templateVars = [];
    if (isset($_SESSION['delete_success'])) {
        $templateVars['success_message'] = $_SESSION['delete_success'];
        unset($_SESSION['delete_success']);
    }
    echo $twig->render('register.twig', $templateVars);
}
?>