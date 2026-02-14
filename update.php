<?php
session_start();
include 'conn.php';

$id = $_GET['id'] ?? 0;
$alert = "";

// Fetch user data for the given ID
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['alert'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Error! User not found.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    
    $personal_image = $user['personal_image']; // Keep current image by default

    // Handle file upload
    if (isset($_FILES['personal_image']) && $_FILES['personal_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['personal_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Create uploads directory if it doesn't exist
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Delete old image if exists and it's not the default
            if (!empty($user['personal_image']) && $user['personal_image'] != 'user_image.jpg' && file_exists('uploads/' . $user['personal_image'])) {
                unlink('uploads/' . $user['personal_image']);
            }
            
            $file_extension = pathinfo($_FILES['personal_image']['name'], PATHINFO_EXTENSION);
            $personal_image = uniqid() . '.' . $file_extension;
            $upload_path = 'uploads/' . $personal_image;
            
            if (!move_uploaded_file($_FILES['personal_image']['tmp_name'], $upload_path)) {
                $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Error! Failed to upload image.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
                $personal_image = $user['personal_image']; // Revert to old image on failure
            }
        } else {
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Error! Only JPG, JPEG, PNG & GIF files are allowed.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        }
    }

    if (empty($alert)) {
        // Check for unique phone and email excluding current user
        $check_sql = "SELECT id FROM users WHERE (phone = ? OR email = ?) AND id != ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ssi", $phone, $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Error! Phone number or email already exists.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        } else {
            // Update user
            $update_sql = "UPDATE users SET first_name=?, last_name=?, phone=?, email=?, gender=?, personal_image=? WHERE id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssssi", $first_name, $last_name, $phone, $email, $gender, $personal_image, $id);

            if ($stmt->execute()) {
                $_SESSION['alert'] = '<div class="alert alert-primary alert-dismissible fade show" role="alert">
                                        Success! User updated successfully.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                      </div>';
                header("Location: index.php");
                exit();
            } else {
                $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Error! Failed to update user.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Update User</h4>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Display Alert -->
                        <?php if (!empty($alert)): ?>
                            <?php echo $alert; ?>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gender</label><br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="Male" <?php echo ($user['gender'] == 'Male') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="Female" <?php echo ($user['gender'] == 'Female') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="other" value="Other" <?php echo ($user['gender'] == 'Other') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="other">Other</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="personal_image" class="form-label">Personal Image (Optional)</label>
                                <?php if (!empty($user['personal_image'])): ?>
                                    <div class="mb-2">
                                        <img src="uploads/<?php echo htmlspecialchars($user['personal_image']); ?>" 
                                             alt="Current Image" 
                                             class="current-image rounded">
                                        <div class="form-text">Current image</div>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="personal_image" name="personal_image" accept="image/*">
                                <div class="form-text">Choose a new image to update. Leave empty to keep current image.</div>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Update User
                                </button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>