<?php
// เรียกใช้คลาสตรวจสอบสิทธิ์และคลาสจัดการคำขอแจ้งซ่อม
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/RepairRequest.php';
// อนุญาตเฉพาะแอดมินและช่างให้จัดการสถานะงาน
Auth::requireRole(['admin', 'tech']);

// สร้างคลาสและอ่านรหัสรายการจาก URL
$repo = new RepairRequest();
$id = (int)($_GET['id'] ?? 0);
// ค้นหารายการแจ้งซ่อมจาก MySQL
$request = $repo->find($id);
// ช่างเปิดดูได้เฉพาะรายการที่ถูกมอบหมายให้ตนเอง
if ($request && Auth::user()['role'] === 'tech' && (int)($request['assigned_tech_id'] ?? 0) !== (int)Auth::user()['id']) {
    http_response_code(403);
    exit('คุณไม่มีสิทธิ์จัดการงานนี้');
}

// ถ้าไม่มีรายการที่ค้นหา ให้แสดงสถานะ 404
if (!$request) {
    http_response_code(404);
    exit('ไม่พบรายการแจ้งซ่อม');
}

// เตรียมตัวแปรข้อความผิดพลาด
$error = '';

// ประมวลผลเมื่อส่งแบบฟอร์มอัปเดตสถานะ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // อ่านสถานะและหมายเหตุที่กรอก
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');

    // ตรวจสอบว่าสถานะอยู่ในตัวเลือกที่อนุญาต
    if (!in_array($status, ['รอดำเนินการ', 'มอบหมายแล้ว', 'กำลังซ่อม', 'เสร็จสิ้น'], true)) {
        $error = 'สถานะไม่ถูกต้อง';
    } else {
        // บันทึกสถานะและหมายเหตุลงฐานข้อมูล
        $repo->update($id, $status, $note, Auth::user()['role'] === 'tech' ? (int)Auth::user()['id'] : null);
        // กลับไปหน้าแดชบอร์ดหลังบันทึก
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <!-- กำหนด charset และ viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ตั้งชื่อหน้า -->
    <title>จัดการงานซ่อม | AirCare</title>
    <!-- เรียกใช้ CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- แถบเมนูส่วนบน -->
    <header class="topbar">
        <a class="logo" href="dashboard.php"><span>AC</span> AirCare</a>
        <a class="btn outline small" href="dashboard.php">← กลับแดชบอร์ด</a>
    </header>

    <!-- พื้นที่แสดงรายละเอียดงาน -->
    <main class="container narrow">
        <section class="welcome">
            <div><p class="eyebrow">REPAIR TICKET #<?= (int)$id ?></p><h1>รายละเอียดงานซ่อม</h1></div>
        </section>

        <!-- แสดงข้อมูลจากรายการแจ้งซ่อม -->
        <div class="panel detail-panel">
            <div class="detail-row"><span>ผู้แจ้ง</span><strong><?= htmlspecialchars($request['created_name'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="detail-row"><span>สถานที่</span><strong><?= htmlspecialchars($request['location'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="detail-row"><span>เบอร์ติดต่อ</span><strong><?= htmlspecialchars($request['phone'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="detail-row"><span>วันที่แจ้ง</span><strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['created_at'])), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="detail-row"><span>อาการเสีย</span><strong><?= nl2br(htmlspecialchars($request['issue'], ENT_QUOTES, 'UTF-8')) ?></strong></div>
            <!-- แสดงรูปที่ผู้ใช้แนบ หากมีไฟล์รูปอยู่จริง -->
            <?php if (!empty($request['image_path']) && is_file(__DIR__ . '/' . $request['image_path'])): ?>
                <div class="detail-row">
                    <span>รูปภาพแนบ</span>
                    <strong><a href="<?= htmlspecialchars($request['image_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                        <img class="repair-photo" src="<?= htmlspecialchars($request['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="รูปประกอบการแจ้งซ่อม">
                    </a></strong>
                </div>
            <?php else: ?>
                <div class="detail-row"><span>รูปภาพแนบ</span><strong>ไม่มีรูปภาพแนบ</strong></div>
            <?php endif; ?>
        </div>

        <!-- แสดงข้อความผิดพลาดจากแบบฟอร์ม -->
        <?php if ($error !== ''): ?>
            <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- แบบฟอร์มเปลี่ยนสถานะและบันทึกหมายเหตุ -->
        <form method="post" class="panel form-panel">
            <h2>อัปเดตงาน</h2>
            <label for="status">สถานะงาน</label>
            <select id="status" name="status">
                <option value="รอดำเนินการ" <?= $request['status'] === 'รอดำเนินการ' ? 'selected' : '' ?>>รอดำเนินการ</option>
                <option value="มอบหมายแล้ว" <?= $request['status'] === 'มอบหมายแล้ว' ? 'selected' : '' ?>>มอบหมายแล้ว</option>
                <option value="กำลังซ่อม" <?= $request['status'] === 'กำลังซ่อม' ? 'selected' : '' ?>>กำลังซ่อม</option>
                <option value="เสร็จสิ้น" <?= $request['status'] === 'เสร็จสิ้น' ? 'selected' : '' ?>>เสร็จสิ้น</option>
            </select>
            <label for="note">บันทึกจากช่าง / หมายเหตุ</label>
            <textarea id="note" name="note" rows="3" placeholder="บันทึกผลการตรวจสอบหรือซ่อม"><?= htmlspecialchars($request['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="form-actions">
                <a class="btn outline" href="dashboard.php">ยกเลิก</a>
                <button class="btn primary" type="submit">บันทึกสถานะ</button>
            </div>
        </form>
    </main>
    <!-- ส่วนท้ายของเว็บไซต์ -->
    <footer>AirCare · ระบบแจ้งซ่อมเครื่องปรับอากาศ</footer>
</body>
</html>
