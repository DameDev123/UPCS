<?php
session_start();
$msg = "";

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

                        // Database connection
                        $servername = "sql205.infinityfree.com";
                        $port       = 3306;
                        $username   = "if0_42250571";
                        $password   = "Dame2030";
                        $dbname     = "if0_42250571_upcs";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$view = isset($_GET['view']) ? $_GET['view'] : 'home';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUHHC IT Project System</title>
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

        .info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid var(--primary);
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

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 4rem 0;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.125rem;
            color: #64748b;
            max-width: 800px;
            margin: 0 auto 2rem;
        }

        /* Responsive Design */
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

            .hero h1 {
                font-size: 2rem;
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

            .hero {
                padding: 2rem 1rem;
            }

            .hero h1 {
                font-size: 1.75rem;
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
                <a href="index.php?view=about">About</a>
                <?php if (isset($_SESSION['user_id'])): ?>
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
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="color: var(--primary); font-weight: 500;">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['name']) ?>
                        </span>
                        <a href="index.php?logout=true" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <?= $msg ?>

        <?php if ($view == 'home'): ?>
            <!-- Hero Section -->
            <section class="hero">
                <h1>Undergraduate Project Coordination System</h1>
                <p>Welcome to the Department of IT Management Portal - Streamlining project submissions, reviews, and evaluations for students, supervisors, and faculty.</p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to System
                        </a>
                        <a href="signup.php" class="btn btn-outline">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Features Grid -->
            <div class="stats-grid">
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-rocket" style="color: #3b82f6;"></i>
                        <h3>For Students</h3>
                    </div>
                    <ul style="padding-left: 1.5rem; color: #64748b;">
                        <li>Submit project proposals and reports</li>
                        <li>Track submission status</li>
                        <li>Receive feedback from supervisors</li>
                        <li>Monitor progress</li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-chalkboard-teacher" style="color: #10b981;"></i>
                        <h3>For Supervisors</h3>
                    </div>
                    <ul style="padding-left: 1.5rem; color: #64748b;">
                        <li>Review student submissions</li>
                        <li>Provide feedback and grades</li>
                        <li>Track assigned students</li>
                        <li>Manage multiple projects</li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-user-tie" style="color: #8b5cf6;"></i>
                        <h3>For HOD & Admin</h3>
                    </div>
                    <ul style="padding-left: 1.5rem; color: #64748b;">
                        <li>Assign supervisors to students</li>
                        <li>Monitor overall progress</li>
                        <li>Generate reports</li>
                        <li>System administration</li>
                    </ul>
                </div>
            </div>

        <?php elseif ($view == 'about'): ?>
            <div class="card">
                <h1 style="color: var(--primary); margin-bottom: 1rem;">About AUHHC Project System</h1>
                <p style="color: #64748b; line-height: 1.8; margin-bottom: 1.5rem;">
                    The AUHHC Undergraduate Project Coordination System is designed to streamline the process
                    of project management for IT students, supervisors, and department heads. This platform
                    facilitates seamless submission, review, and tracking of academic projects throughout
                    their lifecycle.
                </p>

                <h2 style="color: var(--primary); margin-bottom: 1rem;">Key Features</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <h3 style="color: var(--primary); margin-bottom: 0.5rem;">For Students</h3>
                        <ul style="color: #64748b; padding-left: 1.5rem;">
                            <li>Project proposal submission</li>
                            <li>Progress report tracking</li>
                            <li>Real-time feedback</li>
                            <li>Document management</li>
                        </ul>
                    </div>
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <h3 style="color: var(--primary); margin-bottom: 0.5rem;">For Supervisors</h3>
                        <ul style="color: #64748b; padding-left: 1.5rem;">
                            <li>Student assignment management</li>
                            <li>Submission review system</li>
                            <li>Grading and feedback</li>
                            <li>Progress monitoring</li>
                        </ul>
                    </div>
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <h3 style="color: var(--primary); margin-bottom: 0.5rem;">For HOD/Admin</h3>
                        <ul style="color: #64748b; padding-left: 1.5rem;">
                            <li>Supervisor assignment</li>
                            <li>System-wide analytics</li>
                            <li>User management</li>
                            <li>Report generation</li>
                        </ul>
                    </div>
                </div>

                <h2 style="color: var(--primary); margin-bottom: 1rem;">Contact Information</h2>
                <p style="color: #64748b;">
                    <i class="fas fa-building" style="color: var(--primary); margin-right: 0.5rem;"></i>
                    Department of Information Technology<br>
                    AUHHC University<br>
                    <i class="fas fa-phone" style="color: var(--primary); margin-right: 0.5rem;"></i>
                    Phone: +1 (234) 567-8900<br>
                    <i class="fas fa-envelope" style="color: var(--primary); margin-right: 0.5rem;"></i>
                    Email: it-department@auhhc.edu
                </p>
            </div>

        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <div style="text-align: center; padding: 4rem 1rem;">
                <h1 style="color: var(--primary); margin-bottom: 1rem;">Access Denied</h1>
                <p style="color: #64748b; margin-bottom: 2rem;">
                    Please log in to access the dashboard.
                </p>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
        <?php endif; ?>
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