<?php
session_start();
include 'conn.php';

$id = $_GET['id'] ?? 0;

// Fetch user data to get image filename
$sql = "SELECT personal_image FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Delete user
$delete_sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Delete user's image file if exists and it's not the default image
    if (!empty($user['personal_image']) && $user['personal_image'] != 'user_image.jpg' && file_exists('uploads/' . $user['personal_image'])) {
        unlink('uploads/' . $user['personal_image']);
    }
    
    $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> User deleted successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
} else {
    $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> Failed to delete user.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
}

$_SESSION['alert'] = $alert;

$stmt->close();
$conn->close();

header("Location: index.php");
exit();
?>