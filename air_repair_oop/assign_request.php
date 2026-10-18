<?php
// เรียกใช้คลาสตรวจสอบสิทธิ์และคลาสจัดการงานซ่อม
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/RepairRequest.php';
// อนุญาตให้เฉพาะแอดมินมอบหมายงานให้ช่าง
Auth::requireRole('admin');
// รับรหัสงานและรหัสช่างจากแบบฟอร์ม
$requestId = (int)($_POST['request_id'] ?? 0);
$technicianId = (int)($_POST['technician_id'] ?? 0);
// ตรวจสอบว่าเป็นการส่งแบบฟอร์ม POST และรหัสเป็นค่าที่ถูกต้อง
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $requestId < 1 || $technicianId < 1) {
    header('Location: dashboard.php');
    exit;
}
// เรียกคลาสเพื่อบันทึกผู้รับผิดชอบลง MySQL
$repo = new RepairRequest();
// หากมอบหมายไม่ได้ ให้แจ้งข้อผิดพลาด
if (!$repo->assign($requestId, $technicianId)) {
    http_response_code(400);
    exit('มอบหมายงานไม่สำเร็จ ตรวจสอบว่ามีบัญชีช่างและงานยังไม่เสร็จ');
}
// กลับไปแดชบอร์ดเมื่อมอบหมายสำเร็จ
header('Location: dashboard.php?assigned=1');
exit;
