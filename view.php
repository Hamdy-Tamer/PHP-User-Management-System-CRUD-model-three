<?php
include 'conn.php';

$id = $_GET['id'] ?? 0;

// Fetch user data for the given ID
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">User Details</h4>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($user['personal_image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($user['personal_image']); ?>" 
                                     alt="Profile Image" 
                                     class="profile-image rounded-circle mb-3">
                            <?php else: ?>
                                <div class="no-image-placeholder-large rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                            <h3 class="mt-3"><?php echo htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']); ?></h3>
                        </div>
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <strong><i class="bi bi-telephone"></i> Phone:</strong>
                                <p class="ms-3"><?php echo htmlspecialchars($user['phone']); ?></p>
                            </div>
                            <div class="col-12 mb-3">
                                <strong><i class="bi bi-envelope"></i> Email:</strong>
                                <p class="ms-3"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="col-12 mb-3">
                                <strong><i class="bi bi-gender-ambiguous"></i> Gender:</strong>
                                <p class="ms-3"><?php echo $user['gender']; ?></p>
                            </div>
                            <div class="col-12 mb-3">
                                <strong><i class="bi bi-calendar"></i> Created At:</strong>
                                <p class="ms-3"><?php echo $user['created_at']; ?></p>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="update.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="index.php" class="btn btn-primary">Back to List</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>