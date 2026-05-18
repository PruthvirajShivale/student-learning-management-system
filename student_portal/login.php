<?php 
session_start(); 
require 'config.php'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --brand-primary: #6366f1;
            --brand-dark: #0f172a;
            --brand-bg: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--brand-bg);
            margin: 0;
            overflow-x: hidden;
        }

        .main-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Split Design Container */
        .login-card {
            background: #fff;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            display: flex;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            margin: 20px;
        }

        /* Branding Side */
        .brand-side {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
        }

        .brand-side::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }

        /* Form Side */
        .form-side {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .welcome-text h2 {
            font-weight: 800;
            color: var(--brand-dark);
            margin-bottom: 10px;
        }

        .welcome-text p {
            color: #64748b;
            margin-bottom: 40px;
        }

        /* Input Styling */
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            padding: 14px 18px;
            border-radius: 14px;
            border: 2px solid #f1f5f9;
            background: #f8fafc;
            font-weight: 500;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            background: #fff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .btn-custom {
            background: var(--brand-primary);
            color: white;
            padding: 16px;
            border-radius: 14px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .btn-custom:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 991px) {
            .brand-side { display: none; }
            .login-card { max-width: 500px; }
            .form-side { padding: 40px; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="login-card">
        
        <div class="brand-side">
            <div class="brand-logo">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h1 class="display-5 fw-bold mb-3">Elevate Your Learning.</h1>
            <p class="fs-5 opacity-75">Access your lectures, track your assignments, and manage your student profile in one seamless platform.</p>
            <div class="mt-auto">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-group d-flex">
                        <i class="bi bi-shield-check fs-3 me-2"></i>
                        <span class="small fw-medium">Secure Institutional Access</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-side">
            <div class="welcome-text">
                <h2>Welcome Back</h2>
                <p>Please enter your credentials to continue</p>
            </div>

            <form action="login_process.php" method="post">
                
                <div class="mb-4">
                    <label class="form-label">Login As</label>
                    <select name="role" class="form-select" required>
                        <option value="student">Student</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="name@college.edu" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-custom w-100">
                    Sign In <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>
            
            <div class="mt-5 pt-4 border-top text-center text-muted small">
                Authorized access only. Use your institutional email.
            </div>
        </div>

    </div>
</div>

</body>
</html>