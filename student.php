<?php
session_start();
include("database.php");
// Check if user is logged in and is a student
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
//     header("Location: login.php");
//     exit();
// }

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

// STUDENT LOGIC: Proposal & Report Submission
if (isset($_POST['upload_project'])) {
    $student_id = $_SESSION['user_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $type = $_POST['submission_type'];

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_name = time() . "_" . $_SESSION['user_id'] . "_" . basename($_FILES["project_file"]["name"]);
    $file_path = $target_dir . $file_name;

    $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_types)) {
        $msg = "<div class='alert error'>Only PDF, DOC, DOCX, PPT, PPTX files are allowed.</div>";
    } elseif ($_FILES["project_file"]["size"] > 10485760) { // 10MB
        $msg = "<div class='alert error'>File size must be less than 10MB.</div>";
    } elseif (move_uploaded_file($_FILES["project_file"]["tmp_name"], $file_path)) {
        $conn->query("INSERT INTO submissions (student_id, file_name, title, description, type, status) 
                     VALUES ('$student_id', '$file_name', '$title', '$desc', '$type', 'Pending')");
        $msg = "<div class='alert success'>Submission successful! Awaiting review.</div>";
    }
}

// Get statistics for dashboard
function getStudentStats($conn, $user_id)
{
    $stats = [];
    $stats['total_submissions'] = $conn->query("SELECT COUNT(*) as count FROM submissions WHERE student_id='$user_id'")->fetch_assoc()['count'];
    $stats['approved'] = $conn->query("SELECT COUNT(*) as count FROM submissions WHERE student_id='$user_id' AND status='Approved'")->fetch_assoc()['count'];
    $stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM submissions WHERE student_id='$user_id' AND status='Pending'")->fetch_assoc()['count'];
    return $stats;
}

 $stats = getStudentStats($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - AUHHC Project System</title>
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
            max-width: 1400px;
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

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .stat-content p {
            color: #64748b;
            margin: 0;
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

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
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

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text);
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:hover {
            background: #f8fafc;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Dashboard Layout */
        .dashboard-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            height: fit-content;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .user-profile {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar-nav a {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: var(--bg);
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

        /* Animation for status changes */
        .status-update {
            animation: pulse 0.5s ease;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: sticky;
                top: 80px;
                z-index: 100;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .nav-links.active {
                display: flex;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
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

            <button class="mobile-menu-btn" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>

            <div class="nav-links" id="navLinks">
                <a href="index.php">Home</a>
                <a href="student.php" class="active">Dashboard</a>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="color: var(--primary); font-weight: 500;">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['name']) ?>
                    </span>
                    <a href="index.php?logout=true" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <?= $msg ?>

        <div class="dashboard-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="user-profile">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['name'], 0, 2)) ?>
                    </div>
                    <h3 style="margin-bottom: 0.25rem;"><?= htmlspecialchars($_SESSION['name']) ?></h3>
                    <p style="color: #64748b; margin-bottom: 0.5rem;"><?= $_SESSION['email'] ?></p>
                    <span class="badge" style="background: var(--primary); color: white;">
                        STUDENT
                    </span>
                </div>

                <nav class="sidebar-nav">
                    <a href="#submissions" onclick="scrollToSection('submissions')">
                        <i class="fas fa-upload"></i> My Submissions
                    </a>
                    <a href="index.php">
                        <i class="fas fa-home"></i> Back to Home
                    </a>

<a href="profile.php">
    <i class="fas fa-user-cog"></i> Profile Settings
</a>

                </nav>
            </aside>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Welcome Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-home"></i> Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!
                        </h2>
                        <span class="badge badge-info">STUDENT</span>
                    </div>
                    <p style="color: #64748b;">
                        Track your project submissions and feedback from supervisors.
                    </p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #dbeafe; color: #1d4ed8;">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['total_submissions'] ?></h3>
                            <p>Total Submissions</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['approved'] ?></h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['pending'] ?></h3>
                            <p>Pending Review</p>
                        </div>
                    </div>
                </div>

                <!-- Student Submission Form -->
                <div class="card" id="submissions">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-cloud-upload-alt"></i> Submit New Project File
                        </h2>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Project Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter project title" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description / Progress Note</label>
                            <textarea name="description" class="form-control" placeholder="Describe your project or progress..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Submission Type</label>
                            <select name="submission_type" class="form-control" required>
                                <option value="">Select type</option>
                                <option value="proposal">Project Proposal</option>
                                <option value="progress">Progress Report</option>
                                <option value="final">Final Report</option>
                                <option value="presentation">Presentation</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload File (PDF, DOC, PPT - Max 10MB)</label>
                            <input type="file" name="project_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx" required>
                        </div>
                        <button type="submit" name="upload_project" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload to Supervisor
                        </button>
                    </form>
                </div>

                <!-- Student Submissions Table -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-history"></i> My Submission History
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Feedback</th>
                                    <th>Grade</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $uid = $_SESSION['user_id'];
                                $res = $conn->query("SELECT * FROM submissions WHERE student_id='$uid' ORDER BY created_at DESC");
                                while ($row = $res->fetch_assoc()):
                                    $status_class = match ($row['status']) {
                                        'Approved' => 'badge-success',
                                        'Rejected' => 'badge-danger',
                                        'Needs Revision' => 'badge-warning',
                                        default => 'badge-info'
                                    };
                                ?>
                                    <tr class="status-update">
                                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                        <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                        <td><?= ucfirst($row['type']) ?></td>
                                        <td><span class="badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
                                        <td><?= $row['feedback'] ? htmlspecialchars($row['feedback']) : '<span style="color:#94a3b8;">Awaiting feedback...</span>' ?></td>
                                        <td>
                                            <?php if ($row['grade']): ?>
                                                <span class="badge" style="background:#4f46e5; color:white;"><?= $row['grade'] ?></span>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="uploads/<?= $row['file_name'] ?>" target="_blank" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if ($res->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                            No submissions yet. Upload your first project file above.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
        // Mobile menu toggle
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Scroll to section
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth'
                });
            }
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

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const fileInputs = form.querySelector('input[type="file"]');
                    if (fileInputs) {
                        const maxSize = 10 * 1024 * 1024; // 10MB
                        if (fileInputs.files[0] && fileInputs.files[0].size > maxSize) {
                            e.preventDefault();
                            alert('File size exceeds 10MB limit. Please choose a smaller file.');
                        }
                    }
                });
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const navLinks = document.getElementById('navLinks');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            if (!navLinks.contains(e.target) && !menuBtn.contains(e.target) && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
            }
        });
    </script>
</body>

</html>