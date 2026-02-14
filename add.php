<?php
session_start();
include 'conn.php';

$alert = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? '';
    
    // Set default image if no image uploaded
    $personal_image = 'user_image.jpg';

    // Validate required fields
    if (empty($first_name)) {
        $errors['first_name'] = "First name is required";
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $first_name)) {
        $errors['first_name'] = "First name can only contain letters, spaces, apostrophes and hyphens";
    } elseif (strlen($first_name) < 2) {
        $errors['first_name'] = "First name must be at least 2 characters long";
    } elseif (strlen($first_name) > 30) {
        $errors['first_name'] = "First name cannot exceed 30 characters";
    }
    
    if (empty($last_name)) {
        $errors['last_name'] = "Last name is required";
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $last_name)) {
        $errors['last_name'] = "Last name can only contain letters, spaces, apostrophes and hyphens";
    } elseif (strlen($last_name) < 2) {
        $errors['last_name'] = "Last name must be at least 2 characters long";
    } elseif (strlen($last_name) > 30) {
        $errors['last_name'] = "Last name cannot exceed 30 characters";
    }
    
    if (empty($phone)) {
        $errors['phone'] = "Phone number is required";
    } elseif (!preg_match("/^[0-9+\-\s()]+$/", $phone)) {
        $errors['phone'] = "Phone number can only contain digits, +, -, spaces, and parentheses";
    } elseif (strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
        $errors['phone'] = "Phone number must contain at least 10 digits";
    } elseif (strlen(preg_replace('/[^0-9]/', '', $phone)) > 15) {
        $errors['phone'] = "Phone number cannot exceed 15 digits";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address";
    } elseif (strlen($email) > 50) {
        $errors['email'] = "Email address cannot exceed 50 characters";
    } elseif (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $email)) {
        $errors['email'] = "Please enter a valid email format";
    }
    
    if (empty($gender)) {
        $errors['gender'] = "Please select a gender";
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors['gender'] = "Invalid gender selection";
    }

    // Handle file upload if no validation errors yet
    if (empty($errors) && isset($_FILES['personal_image']) && $_FILES['personal_image']['error'] == 0) {
        
        // Check file size (limit to 5MB)
        if ($_FILES['personal_image']['size'] > 5 * 1024 * 1024) {
            $errors['personal_image'] = "File size must be less than 5MB";
        } else {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $file_type = $_FILES['personal_image']['type'];
            $file_extension = strtolower(pathinfo($_FILES['personal_image']['name'], PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($file_type, $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
                $errors['personal_image'] = "Only JPG, JPEG, PNG & GIF files are allowed";
            } else {
                // Create uploads directory if it doesn't exist
                if (!is_dir('uploads')) {
                    if (!mkdir('uploads', 0777, true)) {
                        $errors['personal_image'] = "Failed to create upload directory";
                    }
                }
                
                // Check if directory is writable
                if (!is_writable('uploads')) {
                    $errors['personal_image'] = "Upload directory is not writable";
                }
                
                if (empty($errors)) {
                    // Generate unique filename
                    $personal_image = uniqid() . '.' . $file_extension;
                    $upload_path = 'uploads/' . $personal_image;
                    
                    // Additional security: get imagesize to verify it's actually an image
                    $image_info = getimagesize($_FILES['personal_image']['tmp_name']);
                    if ($image_info === false) {
                        $errors['personal_image'] = "Uploaded file is not a valid image";
                    } elseif (!move_uploaded_file($_FILES['personal_image']['tmp_name'], $upload_path)) {
                        $errors['personal_image'] = "Failed to upload image. Please try again.";
                        $personal_image = 'user_image.jpg'; // Fallback to default
                    }
                }
            }
        }
    } elseif (isset($_FILES['personal_image']) && $_FILES['personal_image']['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => "File exceeds upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "File exceeds MAX_FILE_SIZE directive in the HTML form",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "File upload stopped by extension"
        ];
        
        $error_code = $_FILES['personal_image']['error'];
        $errors['personal_image'] = $upload_errors[$error_code] ?? "Unknown upload error occurred";
    }

    if (empty($errors)) {
        // Check for unique phone and email
        $check_sql = "SELECT id FROM users WHERE phone = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $phone, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Phone or email already exists
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Error!</strong> Phone number or email already exists.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
        } else {
            // Insert new user
            $insert_sql = "INSERT INTO users (first_name, last_name, phone, email, gender, personal_image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssss", $first_name, $last_name, $phone, $email, $gender, $personal_image);

            if ($stmt->execute()) {
                $_SESSION['alert'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Success!</strong> User added successfully.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                      </div>';
                header("Location: index.php");
                exit();
            } else {
                $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error!</strong> Failed to add user.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
            }
        }
        $stmt->close();
    } else {
        // Display validation errors alert
        $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Validation Error!</strong> Please check the form and fix the errors below.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
            background-image: none !important;
        }
        .is-invalid:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }
        .invalid-feedback {
            display: block;
            font-size: 0.875em;
            color: #dc3545;
            margin-top: 0.25rem;
        }
        .error-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #dc3545;
        }
        .position-relative {
            position: relative;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>
                            Add New User
                        </h4>
                        <a href="index.php" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Display Alert -->
                        <?php if (!empty($alert)): ?>
                            <?php echo $alert; ?>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label fw-bold">
                                        <i class="fas fa-user me-1"></i>First Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" 
                                               class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                               id="first_name" 
                                               name="first_name" 
                                               value="<?php echo htmlspecialchars($first_name ?? ''); ?>" 
                                               placeholder="Enter first name"
                                               maxlength="30">
                                        <?php if (isset($errors['first_name'])): ?>
                                            <div class="invalid-feedback">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <?php echo $errors['first_name']; ?>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Max 30 characters, letters only</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label fw-bold">
                                        <i class="fas fa-user me-1"></i>Last Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" 
                                               class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                               id="last_name" 
                                               name="last_name" 
                                               value="<?php echo htmlspecialchars($last_name ?? ''); ?>" 
                                               placeholder="Enter last name"
                                               maxlength="30">
                                        <?php if (isset($errors['last_name'])): ?>
                                            <div class="invalid-feedback">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <?php echo $errors['last_name']; ?>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Max 30 characters, letters only</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label fw-bold">
                                    <i class="fas fa-phone me-1"></i>Phone Number
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="tel" 
                                           class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                                           placeholder="Enter phone number (e.g., +1234567890)"
                                           maxlength="15">
                                    <?php if (isset($errors['phone'])): ?>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php echo $errors['phone']; ?>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">Format: +1234567890, min 10 digits</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">
                                    <i class="fas fa-envelope me-1"></i>Email
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="email" 
                                           class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                           placeholder="Enter email address (e.g., user@example.com)"
                                           maxlength="50">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php echo $errors['email']; ?>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">Enter a valid email address</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-venus-mars me-1"></i>Gender
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <div class="border rounded p-3 <?php echo isset($errors['gender']) ? 'is-invalid' : ''; ?>" style="background-color: #f8f9fa;">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="gender" 
                                                   id="male" 
                                                   value="Male" 
                                                   <?php echo (isset($gender) && $gender == 'Male') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="male">
                                                <i class="fas fa-mars text-primary me-1"></i>Male
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="gender" 
                                                   id="female" 
                                                   value="Female"
                                                   <?php echo (isset($gender) && $gender == 'Female') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="female">
                                                <i class="fas fa-venus text-danger me-1"></i>Female
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="gender" 
                                                   id="other" 
                                                   value="Other"
                                                   <?php echo (isset($gender) && $gender == 'Other') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="other">
                                                <i class="fas fa-genderless text-secondary me-1"></i>Other
                                            </label>
                                        </div>
                                    </div>
                                    <?php if (isset($errors['gender'])): ?>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php echo $errors['gender']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="personal_image" class="form-label fw-bold">
                                    <i class="fas fa-image me-1"></i>Personal Image
                                    <small class="text-muted">(Optional)</small>
                                </label>
                                <div class="position-relative">
                                    <input type="file" 
                                           class="form-control <?php echo isset($errors['personal_image']) ? 'is-invalid' : ''; ?>" 
                                           id="personal_image" 
                                           name="personal_image" 
                                           accept="image/jpeg,image/jpg,image/png,image/gif">
                                    <?php if (isset($errors['personal_image'])): ?>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php echo $errors['personal_image']; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Allowed types: JPG, JPEG, PNG, GIF. Max size: 5MB. 
                                            If no image is selected, a default image will be used.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <!-- Image preview -->
                                <div id="imagePreview" class="mt-2" style="display: none;">
                                    <img src="" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Fields marked with <span class="text-danger">*</span> are required.
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-1"></i> Add User
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Image preview script -->
    <script>
        document.getElementById('personal_image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const previewImg = preview.querySelector('img');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                previewImg.src = '';
            }
        });

        // Real-time validation for phone number (optional enhancement)
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value;
            // Allow only digits, +, -, spaces, and parentheses
            value = value.replace(/[^0-9+\-\s()]/g, '');
            e.target.value = value;
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>