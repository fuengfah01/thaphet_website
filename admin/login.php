<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- css login -->
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Sarabun', sans-serif;
        }

        body {
            margin: 0;
            height: 100vh;
            background: linear-gradient(135deg, #0f7b3f, #1fa463);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: #ffffff;
            width: 360px;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .login-box h2 {
            text-align: center;
            color: #0f7b3f;
            margin-bottom: 25px;
            font-weight: 700;
        }

        .login-box label {
            font-weight: 500;
            color: #333;
        }

        .login-box input {
            width: 100%;
            padding: 12px 14px;
            margin-top: 6px;
            margin-bottom: 18px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            transition: 0.3s;
        }

        .login-box input:focus {
            outline: none;
            border-color: #0f7b3f;
            box-shadow: 0 0 0 2px rgba(15, 123, 63, 0.15);
        }

        .login-box button {
            width: 100%;
            padding: 12px;
            background: #0f7b3f;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-box button:hover {
            background: #0c6433;
            transform: translateY(-1px);
        }

        .error {
            background: #ffecec;
            color: #c0392b;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .footer-text {
            text-align: center;
            margin-top: 18px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <h2>Admin Login</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="error">ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง</div>
        <?php endif; ?>

        <form action="login_process.php" method="post">
            <label>Username</label>
            <input type="text" name="admin_name" required>

            <label>Password</label>
            <input type="password" name="admin_password" required>

            <button type="submit">เข้าสู่ระบบ</button>
        </form>

        <div class="footer-text">
            © Admin Panel
        </div>
    </div>

</body>
</html>
