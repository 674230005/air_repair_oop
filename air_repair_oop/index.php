<?php
// เรียกใช้คลาสจัดการบัญชีผู้ใช้
require_once __DIR__ . '/classes/Auth.php';
// เริ่ม session เพื่อดูว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
Auth::start();

// หากเข้าสู่ระบบแล้ว ให้ไปหน้าแดชบอร์ดทันที
if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

// เตรียมตัวแปรสำหรับข้อความผิดพลาด
$error = '';

// ประมวลผลเมื่อผู้ใช้ส่งแบบฟอร์ม Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับชื่อผู้ใช้และรหัสผ่านจากแบบฟอร์ม
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ตรวจสอบบัญชีผู้ใช้จากฐานข้อมูล
    if (Auth::login($username, $password)) {
        // เมื่อสำเร็จ ให้เปิดหน้าแดชบอร์ด
        header('Location: dashboard.php');
        exit;
    }
    // แสดงข้อความเมื่อชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <!-- กำหนดรหัสอักขระให้รองรับภาษาไทย -->
    <meta charset="UTF-8">
    <!-- ให้หน้าเว็บปรับตามขนาดหน้าจอ -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- กำหนดชื่อแท็บของเบราว์เซอร์ -->
    <title>เข้าสู่ระบบ | AirCare</title>
    <!-- เรียกใช้ CSS สำหรับธีมสีเหลือง-ดำ -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <!-- กล่องหลักของหน้า Login -->
    <div class="login-wrap">
        <!-- โลโก้ย่อของระบบ -->
        <div class="brand-mark">AC</div>
        <!-- คำอธิบายระบบเป็นภาษาอังกฤษ -->
        <p class="eyebrow">AIR CONDITIONER SERVICE</p>
        <!-- ชื่อระบบ -->
        <h1>เข้าสู่ระบบ <span>AirCare</span></h1>
        <!-- คำอธิบายระบบเป็นภาษาไทย -->
        <p class="muted">ระบบแจ้งซ่อมเครื่องปรับอากาศ</p>

        <!-- แสดงข้อความผิดพลาดเมื่อเข้าสู่ระบบไม่สำเร็จ -->
        <?php if ($error !== ''): ?>
            <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- แบบฟอร์มส่งชื่อผู้ใช้และรหัสผ่านไปยังไฟล์นี้ -->
        <form method="post" class="panel login-form">
            <!-- ช่องกรอกชื่อผู้ใช้ -->
            <label for="username">ชื่อผู้ใช้</label>
            <input id="username" name="username" required autocomplete="username" placeholder="กรอกชื่อผู้ใช้">
            <!-- ช่องกรอกรหัสผ่าน -->
            <label for="password">รหัสผ่าน</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="กรอกรหัสผ่าน">
            <!-- ปุ่มส่งแบบฟอร์มเข้าสู่ระบบ -->
            <button class="btn primary full" type="submit">เข้าสู่ระบบ</button>
        </form>

        <!-- แสดงบัญชีทดลองที่สร้างจาก install.php -->
        <div class="demo panel">
            <strong>บัญชีทดลอง</strong>
            <p>แอดมิน: <code>admin</code> / <code>admin123</code></p>
            <p>ผู้ใช้: <code>user</code> / <code>user123</code></p>
            <p>ช่าง: <code>tech</code> / <code>tech123</code></p>
        </div>
        <!-- หมายเหตุว่าเป็นตัวอย่างสำหรับการเรียนรู้ -->
        <small class="muted">ตัวอย่างโครงงาน PHP OOP และ OOAD</small>
    </div>
</body>
</html>
