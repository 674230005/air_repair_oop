<?php
// เรียกใช้คลาสตรวจสอบสิทธิ์และคลาสจัดการแจ้งซ่อม
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/RepairRequest.php';
// จำกัดหน้าแจ้งซ่อมให้เฉพาะผู้ใช้ทั่วไป
Auth::requireRole('user');

// เก็บข้อมูลผู้ใช้ปัจจุบันและเตรียมคลาสจัดการคำขอ
$user = Auth::user();
$repo = new RepairRequest();
// ตัวแปรสำหรับข้อความแจ้งข้อผิดพลาด
$error = '';

// ตรวจสอบเมื่อมีการส่งแบบฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // อ่านข้อมูลจากช่องกรอกข้อความ
    $location = trim($_POST['location'] ?? '');
    $issue = trim($_POST['issue'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // ตรวจสอบช่องข้อมูลที่จำเป็น
    if ($location === '' || $issue === '' || $phone === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } else {
        try {
            // ส่งข้อมูลพร้อมไฟล์ภาพที่แนบไปให้คลาสบันทึกลง MySQL
            $repo->create($user, $location, $issue, $phone, $_FILES['image'] ?? null);
            // กลับไปหน้าแดชบอร์ดหลังบันทึกสำเร็จ
            header('Location: dashboard.php');
            exit;
        } catch (RuntimeException $e) {
            // แสดงข้อความเมื่อไฟล์ภาพไม่ผ่านการตรวจสอบหรือบันทึกไม่ได้
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <!-- กำหนดรหัสอักขระภาษาไทย -->
    <meta charset="UTF-8">
    <!-- ปรับขนาดหน้าเว็บให้รองรับมือถือ -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ตั้งชื่อแท็บ -->
    <title>แจ้งซ่อมใหม่ | AirCare</title>
    <!-- โหลด CSS ของระบบ -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- ส่วนหัวของเว็บไซต์ -->
    <header class="topbar">
        <a class="logo" href="dashboard.php"><span>AC</span> AirCare</a>
        <a class="btn outline small" href="dashboard.php">← กลับแดชบอร์ด</a>
    </header>

    <!-- ส่วนเนื้อหาหลัก -->
    <main class="container narrow">
        <section class="welcome">
            <div>
                <p class="eyebrow">NEW SERVICE REQUEST</p>
                <h1>แจ้งซ่อมแอร์</h1>
                <p class="muted">กรอกรายละเอียดเพื่อส่งคำขอให้ทีมช่าง</p>
            </div>
        </section>

        <!-- กล่องแจ้งข้อผิดพลาดเมื่อมีปัญหา -->
        <?php if ($error !== ''): ?>
            <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- ต้องกำหนด enctype แบบ multipart เพื่อให้ส่งไฟล์ภาพได้ -->
        <form method="post" enctype="multipart/form-data" class="panel form-panel">
            <!-- ช่องกรอกตำแหน่งติดตั้งหรือสถานที่พบปัญหา -->
            <label for="location">สถานที่ติดตั้ง / จุดที่พบปัญหา</label>
            <input id="location" name="location" required
                   value="<?= htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="เช่น ห้อง 204 อาคาร A">

            <!-- ช่องกรอกหมายเลขโทรศัพท์ที่ติดต่อได้ -->
            <label for="phone">เบอร์โทรติดต่อ</label>
            <input id="phone" name="phone" type="tel" required
                   value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="เบอร์โทรศัพท์">

            <!-- ช่องแนบภาพเครื่องปรับอากาศที่เสีย -->
            <label for="image">แนบรูปภาพแอร์ที่เสีย (ไม่บังคับ)</label>
            <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <!-- บอกชนิดไฟล์และขนาดสูงสุดที่รองรับ -->
            <small class="muted upload-help">รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 5 MB</small>

            <!-- ช่องอธิบายอาการที่พบ -->
            <label for="issue">อาการเสีย / รายละเอียด</label>
            <textarea id="issue" name="issue" required rows="5"
                      placeholder="เช่น แอร์ไม่เย็น มีน้ำหยด หรือมีเสียงดัง"><?= htmlspecialchars($_POST['issue'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <!-- ปุ่มยกเลิกและส่งคำขอ -->
            <div class="form-actions">
                <a class="btn outline" href="dashboard.php">ยกเลิก</a>
                <button class="btn primary" type="submit">ส่งคำขอแจ้งซ่อม</button>
            </div>
        </form>
    </main>
    <!-- ส่วนท้ายของเว็บไซต์ -->
    <footer>AirCare · ระบบแจ้งซ่อมเครื่องปรับอากาศ</footer>
</body>
</html>
