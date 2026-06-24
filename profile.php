<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$msg = "";

                        // Database connection
                        $servername = "sql205.infinityfree.com";
                        $port       = 3306;
                        $username   = "if0_42250571";
                        $password   = "Dame2030";
                        $dbname     = "if0_42250571_upcs";
                        if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user information
$user_info = $conn->query("SELECT * FROM users WHERE user_id='{$_SESSION['user_id']}'")->fetch_assoc();

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);

    // Handle profile picture upload
    $profile_picture = $user_info['profile_picture']; // Keep existing picture if not updated
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        $file_path = $target_dir . $file_name;
        $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_types) && $_FILES["profile_picture"]["size"] <= 2097152) { // 2MB
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $file_path)) {
                // Delete old profile picture if exists
                if ($user_info['profile_picture'] && file_exists($user_info['profile_picture'])) {
                    unlink($user_info['profile_picture']);
                }
                $profile_picture = $file_path;
            }
        }
    }

    // Update user profile
    $sql = "UPDATE users SET 
            fullname='$name', 
            phone='$phone', 
            department='$department', 
            bio='$bio',
            profile_picture='$profile_picture' 
            WHERE user_id='$user_id'";

    if ($conn->query($sql)) {
        $_SESSION['name'] = $name; // Update session name
        $msg = "<div class='alert success'>Profile updated successfully!</div>";
        // Refresh user info
        $user_info = $conn->query("SELECT * FROM users WHERE user_id='{$_SESSION['user_id']}'")->fetch_assoc();
    } else {
        $msg = "<div class='alert error'>Error updating profile: " . $conn->error . "</div>";
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if (password_verify($current_password, $user_info['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password='$hashed_password' WHERE user_id='{$_SESSION['user_id']}'";
                if ($conn->query($sql)) {
                    $msg = "<div class='alert success'>Password changed successfully!</div>";
                } else {
                    $msg = "<div class='alert error'>Error changing password!</div>";
                }
            } else {
                $msg = "<div class='alert error'>New password must be at least 6 characters!</div>";
            }
        } else {
            $msg = "<div class='alert error'>New passwords do not match!</div>";
        }
    } else {
        $msg = "<div class='alert error'>Current password is incorrect!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - AUHHC Project System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #004a99;
            --secondary: #003366;
            --accent: #0066cc;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
            --text: #1e293b;
            --card-bg: #ffffff;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Navigation */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            background: var(--bg);
            color: var(--primary);
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid var(--success);
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Profile Layout */
        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .profile-sidebar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }

        .profile-picture-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            margin-bottom: 1rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            border: 4px solid var(--primary);
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--text);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab:hover {
            color: var(--primary);
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 74, 153, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }

        .file-upload input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: block;
            padding: 0.75rem 1rem;
            border: 2px dashed var(--border);
            border-radius: 8px;
            text-align: center;
            color: #64748b;
            transition: all 0.3s;
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Footer */
        .footer {
            background: var(--secondary);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
            text-align: center;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                order: 2;
            }

            .tabs {
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .card {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <img src="./loogo.jpg"
                    alt="Ambo University Logo"
                    style="
                        width: 65px;
                        height: 65px;
                        border-radius: 100%;
                        object-fit: contain;
                        margin-right: 20px;
                        margin-left: 20px;
                     ">

                <span>AUHHC Project Cord</span>
            </a>

            <div class="nav-links">
                <a href="index.php">Home</a>
                <?php
                // Redirect to appropriate dashboard based on role
                $role = $_SESSION['role'];
                if ($role == 'student') {
                    echo '<a href="student.php">Dashboard</a>';
                } elseif ($role == 'supervisor') {
                    echo '<a href="supervisor.php">Dashboard</a>';
                } elseif ($role == 'hod' || $role == 'admin') {
                    echo '<a href="admin.php">Dashboard</a>';
                }
                ?>
                <a href="profile.php" class="active">Profile</a>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary); font-weight: 500;">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['name']) ?>
                    </span>
                    <a href="index.php?logout=true" class="btn btn-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <?= $msg ?>

        <div class="profile-layout">
            <!-- Profile Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-picture-container">
                    <?php if ($user_info['profile_picture']): ?>
                        <img src="<?= $user_info['profile_picture'] ?>" alt="Profile Picture" class="profile-picture">
                    <?php else: ?>
                        <div class="profile-avatar">
                            <?= strtoupper(substr($user_info['fullname'], 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                    <h3 style="margin-bottom: 0.25rem;"><?= htmlspecialchars($user_info['fullname']) ?></h3>
                    <p style="color: #64748b; margin-bottom: 0.5rem;"><?= $user_info['email'] ?></p>
                    <span class="badge badge-info"><?= strtoupper($user_info['role']) ?></span>
                </div>

                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Account Information</h4>
                    <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;">
                        <strong>Member Since:</strong> <?= date('F j, Y', strtotime($user_info['created_at'])) ?>
                    </p>
                    <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;">
                        <strong>Last Login:</strong> <?= date('F j, Y g:i a', strtotime($user_info['last_login'] ?? 'now')) ?>
                    </p>
                    <?php if ($user_info['department']): ?>
                        <p style="font-size: 0.875rem; color: #64748b;">
                            <strong>Department:</strong> <?= htmlspecialchars($user_info['department']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" onclick="showTab('profile-info')">
                        <i class="fas fa-user"></i> Profile Information
                    </button>
                    <button class="tab" onclick="showTab('security')">
                        <i class="fas fa-shield-alt"></i> Security
                    </button>
                    <button class="tab" onclick="showTab('preferences')">
                        <i class="fas fa-cog"></i> Preferences
                    </button>
                </div>

                <!-- Profile Information Tab -->
                <div id="profile-info" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-user-edit"></i> Edit Profile Information
                            </h2>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="fullname" class="form-control"
                                        value="<?= htmlspecialchars($user_info['fullname']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" value="<?= $user_info['email'] ?>" disabled>
                                    <small style="color: #64748b;">Email cannot be changed. Contact administrator if needed.</small>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control"
                                        value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>"
                                        placeholder="Enter phone number">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department</label>
                                    <input type="text" name="department" class="form-control"
                                        value="<?= htmlspecialchars($user_info['department'] ?? '') ?>"
                                        placeholder="Enter department">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Bio / About Me</label>
                                <textarea name="bio" class="form-control"
                                    placeholder="Tell us about yourself..." rows="4"><?= htmlspecialchars($user_info['bio'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Profile Picture</label>
                                <div class="file-upload">
                                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" onchange="updateFileName(this)">
                                    <label for="profile_picture" class="file-upload-label">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span id="file-name">Click to upload profile picture (Max 2MB)</span>
                                    </label>
                                </div>
                                <small style="color: #64748b;">Allowed formats: JPG, PNG, GIF. Maximum size: 2MB</small>
                            </div>

                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-outline" onclick="location.reload()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="security" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-key"></i> Change Password
                            </h2>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control"
                                    placeholder="Enter current password" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control"
                                    placeholder="Enter new password" required minlength="6">
                                <small style="color: #64748b;">Password must be at least 6 characters long</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control"
                                    placeholder="Confirm new password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-lock"></i> Change Password
                            </button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-shield-alt"></i> Two-Factor Authentication
                            </h2>
                        </div>
                        <p style="color: #64748b; margin-bottom: 1rem;">
                            Add an extra layer of security to your account by enabling two-factor authentication.
                        </p>
                        <button class="btn btn-outline" onclick="alert('2FA setup would open here')">
                            <i class="fas fa-mobile-alt"></i> Enable 2FA
                        </button>
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div id="preferences" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-bell"></i> Notification Preferences
                            </h2>
                        </div>
                        <form>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" checked style="width: 20px; height: 20px;">
                                    <span>Email notifications for new submissions</span>
                                </label>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" checked style="width: 20px; height: 20px;">
                                    <span>Email notifications for project reviews</span>
                                </label>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" style="width: 20px; height: 20px;">
                                    <span>SMS notifications for urgent updates</span>
                                </label>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="alert('Preferences saved!')">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-palette"></i> Theme Preferences
                            </h2>
                        </div>
                        <form>
                            <div class="form-group">
                                <label class="form-label">Select Theme</label>
                                <select class="form-control">
                                    <option>Light (Default)</option>
                                    <option>Dark</option>
                                    <option>System</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="alert('Theme updated!')">
                                <i class="fas fa-paint-brush"></i> Apply Theme
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">
                <i class="fas fa-graduation-cap"></i> AUHHC Project System
            </div>
            <p>Department of Information Technology | Undergraduate Project Coordination System</p>
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <a href="index.php" style="color: white; text-decoration: none;">Home</a> |
                <a href="index.php?view=about" style="color: white; text-decoration: none;">About</a> |
                <a href="#" style="color: white; text-decoration: none;" onclick="alert('Contact page would open here')">Contact</a> |
                <a href="#" style="color: white; text-decoration: none;" onclick="alert('Privacy policy would open here')">Privacy Policy</a>
            </div>
            <p style="margin-top: 1rem; font-size: 0.875rem;">
                &copy; <?= date('Y') ?> AUHHC University. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        // Tab switching
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabId).classList.add('active');

            // Update active tab button
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Update file name display
        function updateFileName(input) {
            const fileName = input.files[0]?.name || 'Click to upload profile picture (Max 2MB)';
            document.getElementById('file-name').textContent = fileName;
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>

</html>