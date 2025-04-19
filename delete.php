<?php
session_start(); // Start the session

// Include necessary files
require_once 'db.php'; // Database connection
require_once 'vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Initialize Twig
$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

// Initialize variables
$delete_errors = [];
$show_delete_modal = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate email
    $email = trim($_POST['delete_email']);
    $delete_errors = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $delete_errors['delete_email'] = 'Invalid email address.';
    }

    if (empty($delete_errors)) {
        // Check if email exists in the database
        $stmt = $db->prepare("SELECT id FROM members WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Email exists, proceed to delete
            $stmt->close();
            $stmt = $db->prepare("DELETE FROM members WHERE email = ?");
            $stmt->bind_param("s", $email);

            if ($stmt->execute()) {
                $_SESSION['delete_success'] = "Your registration has been successfully deleted.";
                header("Location: register.php");
                exit;
            } else {
                $delete_errors['delete_email'] = "Error deleting registration: " . $stmt->error;
            }
        } else {
            $delete_errors['delete_email'] = "No registration found with this email.";
        }
        $stmt->close();
    }

    // Store errors in session temporarily
    $_SESSION['delete_errors'] = $delete_errors;
    $show_delete_modal = true;
}

// Render the registration form
echo $twig->render('register.twig', [
    'delete_errors' => isset($_SESSION['delete_errors']) ? $_SESSION['delete_errors'] : [],
    'show_delete_modal' => $show_delete_modal,
    'delete_success' => isset($_SESSION['delete_success']) ? $_SESSION['delete_success'] : null
]);

// Clear session variables after rendering to prevent persistence
unset($_SESSION['delete_errors']);
unset($_SESSION['delete_success']);
exit;
?>
