<?php
session_start();
include("database.php");

// Check if user is logged in and is a supervisor
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
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

// SUPERVISOR LOGIC: Evaluation
if (isset($_POST['evaluate_project'])) {
    $sub_id = intval($_POST['sub_id']);
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
    $status = $_POST['status'];
    $grade = isset($_POST['grade']) ? mysqli_real_escape_string($conn, $_POST['grade']) : null;

    $sql = "UPDATE submissions SET feedback='$feedback', status='$status'";
    if ($grade !== null) $sql .= ", grade='$grade'";
    $sql .= " WHERE sub_id='$sub_id'";

    if ($conn->query($sql) === TRUE) {
        $msg = "<div class='alert success'>Feedback and Status updated!</div>";
    } else {
        $msg = "<div class='alert error'>Error updating record: " . $conn->error . "</div>";
    }
}

// Get statistics for dashboard
function getSupervisorStats($conn, $user_id)
{
    $stats = [];
    $stats['assigned_students'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE assigned_supervisor_id='$user_id'")->fetch_assoc()['count'];
    $stats['pending_reviews'] = $conn->query("SELECT COUNT(*) as count FROM submissions s JOIN users u ON s.student_id=u.user_id WHERE u.assigned_supervisor_id='$user_id' AND s.status='Pending'")->fetch_assoc()['count'];
    return $stats;
}

$stats = getSupervisorStats($conn, $_SESSION['user_id']);
$my_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard - AUHHC Project System</title>
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
                <a href="supervisor.php" class="active">Dashboard</a>
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
                        SUPERVISOR
                    </span>
                </div>


                <nav class="sidebar-nav">
                    <a href="#reviews" onclick="scrollToSection('reviews')">
                        <i class="fas fa-tasks"></i> Pending Reviews
                    </a>
                    <a href="#students" onclick="scrollToSection('students')">
                        <i class="fas fa-users"></i> My Students
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
                        <span class="badge badge-info">SUPERVISOR</span>
                    </div>
                    <p style="color: #64748b;">
                        Review assigned student submissions and provide feedback.
                    </p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #dbeafe; color: #1d4ed8;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['assigned_students'] ?></h3>
                            <p>Assigned Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['pending_reviews'] ?></h3>
                            <p>Pending Reviews</p>
                        </div>
                    </div>
                </div>

                <!-- Supervisor Review Section -->
                <div class="card" id="reviews">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-tasks"></i> Pending Submissions for Review
                        </h2>
                        <span class="badge badge-warning"><?= $stats['pending_reviews'] ?> Pending</span>
                    </div>
                    <?php
                    $pending = $conn->query("SELECT s.*, u.fullname, u.email FROM submissions s 
                                           JOIN users u ON s.student_id = u.user_id 
                                           WHERE u.assigned_supervisor_id='$my_id' AND s.status='Pending'
                                           ORDER BY s.created_at DESC");
                    if ($pending->num_rows > 0):
                        while ($p = $pending->fetch_assoc()): ?>
                            <div class="card" style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <div>
                                        <h3 style="margin-bottom: 0.25rem;"><?= htmlspecialchars($p['title']) ?></h3>
                                        <p style="color: #64748b; margin-bottom: 0.5rem;">
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($p['fullname']) ?> •
                                            <i class="fas fa-envelope"></i> <?= $p['email'] ?> •
                                            <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($p['created_at'])) ?>
                                        </p>
                                    </div>
                                    <span class="badge badge-info"><?= ucfirst($p['type']) ?></span>
                                </div>

                                <p style="margin-bottom: 1rem; padding: 0.75rem; background: #f8fafc; border-radius: 6px;">
                                    <?= htmlspecialchars($p['description']) ?>
                                </p>

                                <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                                    <a href="uploads/<?= $p['file_name'] ?>" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-file-download"></i> Download File
                                    </a>
                                    <a href="uploads/<?= $p['file_name'] ?>" target="_blank" class="btn">
                                        <i class="fas fa-eye"></i> Preview
                                    </a>
                                </div>

                                <form method="POST">
                                    <input type="hidden" name="sub_id" value="<?= $p['sub_id'] ?>">
                                    <div class="form-group">
                                        <label class="form-label">Feedback to Student</label>
                                        <textarea name="feedback" class="form-control" placeholder="Provide constructive feedback..." rows="3"></textarea>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                        <div>
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="Pending">Pending</option>
                                                <option value="Approved">Approve</option>
                                                <option value="Rejected">Reject</option>
                                                <option value="Needs Revision">Needs Revision</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Grade (Optional)</label>
                                            <input type="text" name="grade" maxlength="2" placeholder="Enter grade (e.g. A, B+)" class="form-control">
                                        </div>
                                    </div>
                                    <button type="submit" name="evaluate_project" class="btn btn-success">
                                        <i class="fas fa-check-circle"></i> Submit Evaluation
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #94a3b8;">
                            <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <h3>All Caught Up!</h3>
                            <p>No pending submissions to review at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Assigned Students -->
                <div class="card" id="students">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-users"></i> My Assigned Students
                        </h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Total Submissions</th>
                                    <th>Last Submission</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $students = $conn->query("SELECT u.*, 
                                    (SELECT COUNT(*) FROM submissions s WHERE s.student_id = u.user_id) as total_subs,
                                    (SELECT MAX(created_at) FROM submissions s WHERE s.student_id = u.user_id) as last_sub
                                    FROM users u WHERE u.assigned_supervisor_id='$my_id'");
                                while ($stu = $students->fetch_assoc()):
                                    $last_sub = $stu['last_sub'] ? date('M d, Y', strtotime($stu['last_sub'])) : 'Never';
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($stu['fullname']) ?></td>
                                        <td><?= $stu['email'] ?></td>
                                        <td><?= $stu['total_subs'] ?></td>
                                        <td><?= $last_sub ?></td>
                                        <td>
                                            <?php if ($stu['total_subs'] > 0): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">No Submissions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if ($students->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                            No students assigned yet.
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