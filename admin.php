<?php
session_start();
include("database.php");
// Check if user is logged in and is an admin or HOD
// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'hod')) {
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

// ADMIN LOGIC: User Management with foreign key constraint fix
if (isset($_GET['delete_user']) && $_SESSION['role'] == 'admin') {
    $id = intval($_GET['delete_user']);
    // Delete related submissions first (cascading delete)
    $conn->query("DELETE FROM submissions WHERE student_id='$id'");
    // Then delete the user
    if ($conn->query("DELETE FROM users WHERE user_id='$id'")) {
        $msg = "<div class='alert success'>Account deleted successfully.</div>";
    } else {
        $msg = "<div class='alert error'>Error deleting account: " . $conn->error . "</div>";
    }
}

// HOD LOGIC: Supervisor Assignment
if (isset($_POST['assign_sup']) && $_SESSION['role'] == 'hod') {
    $sid = intval($_POST['student_id']);
    $sup_id = intval($_POST['supervisor_id']);
    $conn->query("UPDATE users SET assigned_supervisor_id='$sup_id' WHERE user_id='$sid'");
    $msg = "<div class='alert success'>Supervisor assigned to student.</div>";
}

// SUPERVISOR & HOD LOGIC: Evaluation
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

// HOD SPECIFIC LOGIC - NEW FUNCTIONS

// HOD: Approve project
if (isset($_POST['approve_project']) && $_SESSION['role'] == 'hod') {
    $sub_id = intval($_POST['sub_id']);
    $conn->query("UPDATE submissions SET status='Approved' WHERE sub_id='$sub_id'");
    $msg = "<div class='alert success'>Project approved successfully!</div>";
}

// HOD: Reject project with comments
if (isset($_POST['reject_project']) && $_SESSION['role'] == 'hod') {
    $sub_id = intval($_POST['sub_id']);
    $comments = mysqli_real_escape_string($conn, $_POST['comments']);
    $conn->query("UPDATE submissions SET status='Rejected', feedback='$comments' WHERE sub_id='$sub_id'");
    $msg = "<div class='alert success'>Project rejected with comments!</div>";
}

// HOD: Assign/reassign supervisor to submission
if (isset($_POST['assign_supervisor_submission']) && $_SESSION['role'] == 'hod') {
    $sub_id = intval($_POST['sub_id']);
    $supervisor_id = intval($_POST['supervisor_id']);

    // First get student ID from submission
    $result = $conn->query("SELECT student_id FROM submissions WHERE sub_id='$sub_id'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $student_id = $row['student_id'];

        // Update student's assigned supervisor
        $conn->query("UPDATE users SET assigned_supervisor_id='$supervisor_id' WHERE user_id='$student_id'");
        $msg = "<div class='alert success'>Supervisor assigned to student's submission!</div>";
    }
}

// HOD: Generate departmental project reports
if (isset($_POST['generate_report']) && $_SESSION['role'] == 'hod') {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // Store report parameters in session for display
    $_SESSION['report_data'] = [
        'type' => $report_type,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];

    $msg = "<div class='alert success'>Report generated successfully!</div>";
}

// Profile Update
if (isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);

    $conn->query("UPDATE users SET fullname='$name', phone='$phone', department='$department' WHERE user_id='$user_id'");
    $_SESSION['name'] = $name;
    $msg = "<div class='alert success'>Profile updated successfully!</div>";
}

// Get statistics for dashboard
function getAdminStats($conn, $user_id, $role)
{
    $stats = [];

    if ($role == 'hod') {
        $stats['total_students'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'")->fetch_assoc()['count'];
        $stats['total_supervisors'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='supervisor'")->fetch_assoc()['count'];
        $stats['total_submissions'] = $conn->query("SELECT COUNT(*) as count FROM submissions")->fetch_assoc()['count'];
        $stats['pending_reviews'] = $conn->query("SELECT COUNT(*) as count FROM submissions WHERE status='Pending'")->fetch_assoc()['count'];
        $stats['approved'] = $conn->query("SELECT COUNT(*) as count FROM submissions WHERE status='Approved'")->fetch_assoc()['count'];
        $stats['rejected'] = $conn->query("SELECT COUNT(*) as count FROM submissions WHERE status='Rejected'")->fetch_assoc()['count'];
    } elseif ($role == 'admin') {
        $stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
        $stats['active_submissions'] = $conn->query("SELECT COUNT(*) as count FROM submissions WHERE status='Pending'")->fetch_assoc()['count'];
    }

    return $stats;
}

$stats = getAdminStats($conn, $_SESSION['user_id'], $_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($_SESSION['role']) ?> Dashboard - AUHHC Project System</title>
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

        /* Tabs for HOD */
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
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
            white-space: nowrap;
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

        /* Modal for HOD actions */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text);
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

            .tabs {
                overflow-x: auto;
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
                <a href="admin.php" class="active">Dashboard</a>
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
                        <?= strtoupper($_SESSION['role']) ?>
                    </span>
                </div>

                <nav class="sidebar-nav">
                    <?php if ($_SESSION['role'] == 'hod'): ?>
                        <a href="#" onclick="showHODTab('hod-overview')" class="active">
                            <i class="fas fa-tachometer-alt"></i> HOD Overview
                        </a>
                        <a href="#" onclick="showHODTab('all-submissions')">
                            <i class="fas fa-file-alt"></i> All Submissions
                        </a>
                        <a href="#" onclick="showHODTab('supervisor-assignment')">
                            <i class="fas fa-users-cog"></i> Supervisor Assignment
                        </a>
                        <a href="#" onclick="showHODTab('hod-reports')">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    <?php elseif ($_SESSION['role'] == 'admin'): ?>
                        <a href="#users" onclick="scrollToSection('users')">
                            <i class="fas fa-users"></i> User Management
                        </a>
                    <?php endif; ?>
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
                        <span class="badge badge-info"><?= strtoupper($_SESSION['role']) ?></span>
                    </div>
                    <p style="color: #64748b;">
                        <?php if ($_SESSION['role'] == 'hod'): ?>
                            Manage supervisor assignments and monitor project progress.
                        <?php else: ?>
                            Manage system users and configurations.
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($_SESSION['role'] == 'hod'): ?>
                    <!-- HOD Tabs -->
                    <div class="tabs" id="hodTabs">
                        <button class="tab active" onclick="showHODTab('hod-overview')">Overview</button>
                        <button class="tab" onclick="showHODTab('all-submissions')">All Submissions</button>
                        <button class="tab" onclick="showHODTab('supervisor-assignment')">Supervisor Assignment</button>
                        <button class="tab" onclick="showHODTab('hod-reports')">Reports</button>
                    </div>

                    <!-- HOD Overview Tab -->
                    <div id="hod-overview" class="tab-content active">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: #dbeafe; color: #1d4ed8;">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?= $stats['total_students'] ?></h3>
                                    <p>Total Students</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?= $stats['total_supervisors'] ?></h3>
                                    <p>Supervisors</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?= $stats['total_submissions'] ?></h3>
                                    <p>Total Submissions</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: #e2e8f0; color: #475569;">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?= $stats['pending_reviews'] ?></h3>
                                    <p>Pending Reviews</p>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-chart-pie"></i> Submission Status Distribution
                                </h2>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">
                                <div style="text-align: center;">
                                    <span class="badge badge-success" style="display: block; margin-bottom: 0.5rem;">Approved</span>
                                    <span style="font-size: 1.5rem; font-weight: bold;"><?= $stats['approved'] ?></span>
                                </div>
                                <div style="text-align: center;">
                                    <span class="badge badge-warning" style="display: block; margin-bottom: 0.5rem;">Pending</span>
                                    <span style="font-size: 1.5rem; font-weight: bold;"><?= $stats['pending_reviews'] ?></span>
                                </div>
                                <div style="text-align: center;">
                                    <span class="badge badge-danger" style="display: block; margin-bottom: 0.5rem;">Rejected</span>
                                    <span style="font-size: 1.5rem; font-weight: bold;"><?= $stats['rejected'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Submissions Tab -->
                    <div id="all-submissions" class="tab-content">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-file-alt"></i> All Project Submissions
                                </h2>
                                <div>
                                    <select id="filterStatus" class="form-control" style="width: auto; display: inline-block;" onchange="filterSubmissions()">
                                        <option value="">All Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Rejected">Rejected</option>
                                        <option value="Needs Revision">Needs Revision</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="submissionsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Student</th>
                                            <th>Title</th>
                                            <th>Supervisor</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $all_submissions = $conn->query("
                                            SELECT s.*, u.fullname as student_name, 
                                                   sup.fullname as supervisor_name
                                            FROM submissions s 
                                            JOIN users u ON s.student_id = u.user_id 
                                            LEFT JOIN users sup ON u.assigned_supervisor_id = sup.user_id 
                                            ORDER BY s.created_at DESC
                                        ");
                                        while ($sub = $all_submissions->fetch_assoc()):
                                            $status_class = match ($sub['status']) {
                                                'Approved' => 'badge-success',
                                                'Rejected' => 'badge-danger',
                                                'Needs Revision' => 'badge-warning',
                                                default => 'badge-info'
                                            };
                                        ?>
                                            <tr data-status="<?= $sub['status'] ?>">
                                                <td>#<?= $sub['sub_id'] ?></td>
                                                <td><?= htmlspecialchars($sub['student_name']) ?></td>
                                                <td><?= htmlspecialchars($sub['title']) ?></td>
                                                <td><?= $sub['supervisor_name'] ? htmlspecialchars($sub['supervisor_name']) : 'Not Assigned' ?></td>
                                                <td><?= ucfirst($sub['type']) ?></td>
                                                <td><span class="badge <?= $status_class ?>"><?= $sub['status'] ?></span></td>
                                                <td><?= date('M d, Y', strtotime($sub['created_at'])) ?></td>
                                                <td>
                                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                        <button class="btn btn-sm btn-primary" onclick="viewSubmission(<?= $sub['sub_id'] ?>)">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <?php if ($sub['status'] == 'Pending' || $sub['status'] == 'Needs Revision'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="sub_id" value="<?= $sub['sub_id'] ?>">
                                                                <button type="submit" name="approve_project" class="btn btn-sm btn-success">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                            </form>
                                                            <button class="btn btn-sm btn-danger" onclick="openRejectModal(<?= $sub['sub_id'] ?>)">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if ($all_submissions->num_rows == 0): ?>
                                            <tr>
                                                <td colspan="8" style="text-align: center; padding: 2rem; color: #94a3b8;">
                                                    No submissions found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Supervisor Assignment Tab -->
                    <div id="supervisor-assignment" class="tab-content">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-users-cog"></i> Supervisor Assignment Management
                                </h2>
                            </div>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Email</th>
                                            <th>Current Supervisor</th>
                                            <th>Submissions</th>
                                            <th>Last Activity</th>
                                            <th>Assign Supervisor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $students = $conn->query("SELECT u.*, 
                                            (SELECT COUNT(*) FROM submissions s WHERE s.student_id = u.user_id) as total_subs,
                                            (SELECT MAX(created_at) FROM submissions s WHERE s.student_id = u.user_id) as last_act
                                            FROM users u WHERE u.role='student' ORDER BY u.fullname");
                                        while ($s = $students->fetch_assoc()):
                                            $sup_id = $s['assigned_supervisor_id'];
                                            $sup_res = $conn->query("SELECT fullname FROM users WHERE user_id='$sup_id'")->fetch_assoc();
                                            $last_act = $s['last_act'] ? date('M d, Y', strtotime($s['last_act'])) : 'Never';
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($s['fullname']) ?></td>
                                                <td><?= $s['email'] ?></td>
                                                <td><?= $sup_res['fullname'] ?? '<span style="color:#94a3b8;">Not Assigned</span>' ?></td>
                                                <td><?= $s['total_subs'] ?></td>
                                                <td><?= $last_act ?></td>
                                                <td>
                                                    <form method="POST" style="display:flex; gap:5px; align-items:center;">
                                                        <input type="hidden" name="student_id" value="<?= $s['user_id'] ?>">
                                                        <select name="supervisor_id" style="flex:1; padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px;">
                                                            <option value="">Select Supervisor</option>
                                                            <?php
                                                            $sups = $conn->query("SELECT * FROM users WHERE role='supervisor' ORDER BY fullname");
                                                            while ($su = $sups->fetch_assoc()):
                                                                $selected = ($sup_id == $su['user_id']) ? 'selected' : '';
                                                            ?>
                                                                <option value="<?= $su['user_id'] ?>" <?= $selected ?>>
                                                                    <?= htmlspecialchars($su['fullname']) ?> (<?= $su['email'] ?>)
                                                                </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                        <button type="submit" name="assign_sup" class="btn btn-primary" style="padding: 0.5rem 1rem;">
                                                            <i class="fas fa-user-plus"></i> Assign
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Supervisor Workload -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-chart-bar"></i> Supervisor Workload Distribution
                                </h2>
                            </div>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Supervisor</th>
                                            <th>Email</th>
                                            <th>Assigned Students</th>
                                            <th>Pending Reviews</th>
                                            <th>Completed Reviews</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $supervisors = $conn->query("
                                            SELECT u.*, 
                                                   COUNT(DISTINCT s.user_id) as student_count,
                                                   (SELECT COUNT(*) FROM submissions sub 
                                                    JOIN users stu ON sub.student_id = stu.user_id 
                                                    WHERE stu.assigned_supervisor_id = u.user_id 
                                                    AND sub.status = 'Pending') as pending_reviews,
                                                   (SELECT COUNT(*) FROM submissions sub 
                                                    JOIN users stu ON sub.student_id = stu.user_id 
                                                    WHERE stu.assigned_supervisor_id = u.user_id 
                                                    AND sub.status IN ('Approved', 'Rejected')) as completed_reviews
                                            FROM users u 
                                            LEFT JOIN users s ON u.user_id = s.assigned_supervisor_id 
                                            WHERE u.role = 'supervisor' 
                                            GROUP BY u.user_id 
                                            ORDER BY student_count DESC
                                        ");
                                        while ($sup = $supervisors->fetch_assoc()):
                                            $workload_class = $sup['student_count'] > 5 ? 'badge-warning' : 'badge-success';
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($sup['fullname']) ?></td>
                                                <td><?= $sup['email'] ?></td>
                                                <td>
                                                    <span class="badge <?= $workload_class ?>">
                                                        <?= $sup['student_count'] ?> students
                                                    </span>
                                                </td>
                                                <td><?= $sup['pending_reviews'] ?></td>
                                                <td><?= $sup['completed_reviews'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Tab -->
                    <div id="hod-reports" class="tab-content">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-chart-bar"></i> Generate Departmental Reports
                                </h2>
                            </div>
                            <form method="POST">
                                <div class="form-group">
                                    <label class="form-label">Report Type</label>
                                    <select name="report_type" class="form-control" required>
                                        <option value="">Select Report Type</option>
                                        <option value="status_summary">Project Status Summary</option>
                                        <option value="supervisor_performance">Supervisor Performance</option>
                                        <option value="student_progress">Student Progress Report</option>
                                        <option value="department_overview">Department Overview</option>
                                    </select>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="start_date" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="end_date" class="form-control">
                                    </div>
                                </div>
                                <button type="submit" name="generate_report" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Generate Report
                                </button>
                            </form>
                        </div>

                        <!-- Report Display Area -->
                        <?php if (isset($_SESSION['report_data'])): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="card-title">
                                        <i class="fas fa-file-alt"></i> Generated Report
                                    </h2>
                                    <button class="btn btn-primary" onclick="printReport()">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                                <div id="reportContent">
                                    <h3 style="color: var(--primary); margin-bottom: 1rem;">
                                        <?= ucwords(str_replace('_', ' ', $_SESSION['report_data']['type'])) ?> Report
                                    </h3>
                                    <?php if ($_SESSION['report_data']['start_date']): ?>
                                        <p><strong>Period:</strong> <?= $_SESSION['report_data']['start_date'] ?> to <?= $_SESSION['report_data']['end_date'] ?></p>
                                    <?php endif; ?>
                                    <p><strong>Generated:</strong> <?= date('F j, Y, g:i a') ?></p>
                                    <p><strong>Generated By:</strong> <?= htmlspecialchars($_SESSION['name']) ?> (HOD)</p>

                                    <div class="table-responsive" style="margin-top: 2rem;">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Status</th>
                                                    <th>Count</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $status_counts = $conn->query("
                                                    SELECT status, COUNT(*) as count 
                                                    FROM submissions 
                                                    GROUP BY status
                                                ");
                                                $total = $conn->query("SELECT COUNT(*) as total FROM submissions")->fetch_assoc()['total'];
                                                while ($row = $status_counts->fetch_assoc()):
                                                    $percentage = $total > 0 ? round(($row['count'] / $total) * 100, 1) : 0;
                                                ?>
                                                    <tr>
                                                        <td><?= $row['status'] ?></td>
                                                        <td><?= $row['count'] ?></td>
                                                        <td><?= $percentage ?>%</td>
                                                    </tr>
                                                <?php endwhile; ?>
                                                <tr style="font-weight: bold; background: #f8fafc;">
                                                    <td>Total</td>
                                                    <td><?= $total ?></td>
                                                    <td>100%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Supervisor Statistics -->
                                    <h4 style="color: var(--primary); margin: 2rem 0 1rem 0;">Supervisor Statistics</h4>
                                    <div class="table-responsive">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Supervisor</th>
                                                    <th>Assigned Students</th>
                                                    <th>Pending Reviews</th>
                                                    <th>Completed</th>
                                                    <th>Avg. Completion Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sup_stats = $conn->query("
                                                    SELECT u.fullname,
                                                           COUNT(DISTINCT s.user_id) as student_count,
                                                           (SELECT COUNT(*) FROM submissions sub 
                                                            JOIN users stu ON sub.student_id = stu.user_id 
                                                            WHERE stu.assigned_supervisor_id = u.user_id 
                                                            AND sub.status = 'Pending') as pending,
                                                           (SELECT COUNT(*) FROM submissions sub 
                                                            JOIN users stu ON sub.student_id = stu.user_id 
                                                            WHERE stu.assigned_supervisor_id = u.user_id 
                                                            AND sub.status IN ('Approved', 'Rejected')) as completed
                                                    FROM users u 
                                                    LEFT JOIN users s ON u.user_id = s.assigned_supervisor_id 
                                                    WHERE u.role = 'supervisor' 
                                                    GROUP BY u.user_id 
                                                    ORDER BY student_count DESC
                                                ");
                                                while ($stat = $sup_stats->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($stat['fullname']) ?></td>
                                                        <td><?= $stat['student_count'] ?></td>
                                                        <td><?= $stat['pending'] ?></td>
                                                        <td><?= $stat['completed'] ?></td>
                                                        <td><?= $stat['student_count'] > 0 ? round($stat['completed'] / $stat['student_count'], 1) : 0 ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php unset($_SESSION['report_data']); ?>
                        <?php endif; ?>
                    </div>

                <?php elseif ($_SESSION['role'] == 'admin'): ?>
                    <!-- Admin: User Management -->
                    <div class="card" id="users">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-users"></i> System User Management
                            </h2>
                            <a href="#" class="btn btn-primary" onclick="alert('User creation feature would open here')">
                                <i class="fas fa-user-plus"></i> Add New User
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Assigned To</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $users = $conn->query("SELECT u1.*, u2.fullname as supervisor_name 
                                        FROM users u1 
                                        LEFT JOIN users u2 ON u1.assigned_supervisor_id = u2.user_id
                                        ORDER BY u1.role, u1.fullname");
                                    while ($u = $users->fetch_assoc()):
                                        $role_class = match ($u['role']) {
                                            'admin' => 'badge-danger',
                                            'hod' => 'badge-warning',
                                            'supervisor' => 'badge-success',
                                            default => 'badge-info'
                                        };
                                    ?>
                                        <tr>
                                            <td>#<?= $u['user_id'] ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                        <?= strtoupper(substr($u['fullname'], 0, 1)) ?>
                                                    </div>
                                                    <?= htmlspecialchars($u['fullname']) ?>
                                                </div>
                                            </td>
                                            <td><?= $u['email'] ?></td>
                                            <td><span class="badge <?= $role_class ?>"><?= strtoupper($u['role']) ?></span></td>
                                            <td><?= $u['supervisor_name'] ? htmlspecialchars($u['supervisor_name']) : '-' ?></td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Active
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 5px;">
                                                    <button class="btn" style="padding: 0.25rem 0.5rem;" onclick="alert('Edit feature would open here')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                                        <a href="admin.php?delete_user=<?= $u['user_id'] ?>"
                                                            class="btn btn-danger"
                                                            style="padding: 0.25rem 0.5rem;"
                                                            onclick="return confirm('Are you sure you want to delete user: <?= addslashes($u['fullname']) ?>?\n\nThis will also delete all their submissions.')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- System Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-chart-line"></i> System Statistics
                            </h2>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <?php
                            $sys_stats = $conn->query("
                                SELECT 
                                    (SELECT COUNT(*) FROM users) as total_users,
                                    (SELECT COUNT(*) FROM submissions) as total_submissions,
                                    (SELECT COUNT(*) FROM submissions WHERE status='Approved') as approved,
                                    (SELECT COUNT(*) FROM submissions WHERE status='Pending') as pending,
                                    (SELECT COUNT(*) FROM submissions WHERE status='Rejected') as rejected
                            ")->fetch_assoc();
                            ?>
                            <div style="text-align: center; padding: 1rem; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
                                    <?= $sys_stats['total_users'] ?>
                                </div>
                                <div>Total Users</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
                                    <?= $sys_stats['total_submissions'] ?>
                                </div>
                                <div>Total Submissions</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
                                    <?= $sys_stats['pending'] ?>
                                </div>
                                <div>Pending Reviews</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border-radius: 8px;">
                                <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
                                    <?= $sys_stats['rejected'] ?>
                                </div>
                                <div>Rejected Submissions</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Reject Project Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Project</h3>
                <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="rejectSubId" name="sub_id">
                <div class="form-group">
                    <label class="form-label">Comments (Required)</label>
                    <textarea name="comments" class="form-control" placeholder="Enter reasons for rejection..." required rows="4"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" name="reject_project" class="btn btn-danger">
                        <i class="fas fa-times"></i> Confirm Rejection
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

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

        // HOD Tab Functions
        function showHODTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabId).classList.add('active');

            // Update active tab button
            document.querySelectorAll('#hodTabs .tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Filter submissions by status
        function filterSubmissions() {
            const filter = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#submissionsTable tbody tr');

            rows.forEach(row => {
                if (filter === '' || row.getAttribute('data-status') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Modal functions
        function openRejectModal(subId) {
            document.getElementById('rejectSubId').value = subId;
            document.getElementById('rejectModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // View submission details
        function viewSubmission(subId) {
            // This would typically open a modal or new page with submission details
            alert('Viewing submission #' + subId + '\n\nIn a full implementation, this would show:\n- Full submission details\n- Student information\n- File preview\n- Supervisor feedback\n- Grade details');
        }

        // Print report
        function printReport() {
            const printContent = document.getElementById('reportContent').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>AUHHC Project System - Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        h1, h2, h3 { color: #004a99; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>AUHHC IT Project System</h1>
                        <h2>Departmental Report</h2>
                        <p>Generated on: ${new Date().toLocaleDateString()}</p>
                    </div>
                    ${printContent}
                    <div class="footer">
                        <p>AUHHC University - Department of Information Technology</p>
                        <p>Confidential - For internal use only</p>
                    </div>
                </body>
                </html>
            `;

            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

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