<?php
// เรียกใช้คลาสเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/config/database.php';

// สร้างการเชื่อมต่อกับฐานข้อมูลที่ต้องสร้างไว้ก่อนแล้ว
$db = (new Database())->connect();

// เตรียมบัญชีทดลอง 3 บทบาทสำหรับทดสอบระบบ
$accounts = [
    ['admin', 'admin123', 'ผู้ดูแลระบบ', 'admin'],
    ['user', 'user123', 'ผู้ใช้งานทั่วไป', 'user'],
    ['tech', 'tech123', 'ช่างซ่อมแอร์', 'tech']
];

// เตรียมคำสั่งเพิ่มบัญชีหากยังไม่มี username นั้นอยู่
$stmt = $db->prepare('INSERT INTO users (username, password_hash, full_name, role) VALUES (:username, :password_hash, :full_name, :role) ON DUPLICATE KEY UPDATE username = username');

// วนสร้างบัญชีทดลองทีละบัญชี
foreach ($accounts as $account) {
    // เข้ารหัสรหัสผ่านด้วย password_hash ก่อนบันทึกฐานข้อมูล
    $hash = password_hash($account[1], PASSWORD_DEFAULT);
    // เพิ่มบัญชีลง MySQL โดยเก็บเฉพาะ hash ไม่เก็บรหัสผ่านตรง ๆ
    $stmt->execute([
        'username' => $account[0],
        'password_hash' => $hash,
        'full_name' => $account[2],
        'role' => $account[3]
    ]);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <!-- ตั้งค่าภาษาและการแสดงผลบนมือถือ -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ตั้งชื่อหน้าติดตั้ง -->
    <title>ติดตั้ง AirCare</title>
    <!-- ใช้ CSS หลักของระบบ -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- แสดงผลว่าการสร้างบัญชีทดลองสำเร็จ -->
    <main class="container narrow">
        <section class="panel form-panel">
            <h1>ติดตั้ง AirCare สำเร็จ</h1>
            <p>สร้างบัญชีทดลองเรียบร้อยแล้ว สามารถเข้าสู่ระบบได้</p>
            <p>แอดมิน: <code>admin</code> / <code>admin123</code></p>
            <p>ผู้ใช้: <code>user</code> / <code>user123</code></p>
            <p>ช่าง: <code>tech</code> / <code>tech123</code></p>
            <p><strong>เพื่อความปลอดภัย ให้ลบไฟล์ install.php หลังติดตั้งเสร็จ</strong></p>
            <a class="btn primary" href="index.php">ไปหน้าเข้าสู่ระบบ</a>
        </section>
    </main>
</body>
</html>
