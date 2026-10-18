<?php
// เรียกใช้คลาสสำหรับจัดการ session
require_once __DIR__ . '/classes/Auth.php';
// ลบ session เพื่อออกจากระบบ
Auth::logout();
// กลับไปหน้าเข้าสู่ระบบ
header('Location: index.php');
// หยุดประมวลผลหลังเปลี่ยนหน้า
exit;
