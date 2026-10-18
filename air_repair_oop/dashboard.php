<?php
// เรียกใช้คลาสตรวจสอบสิทธิ์และคลาสจัดการคำขอแจ้งซ่อม
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/RepairRequest.php';
// บังคับให้ผู้ใช้เข้าสู่ระบบก่อนดูแดชบอร์ด
Auth::requireLogin();

// ดึงข้อมูลผู้ใช้ที่เข้าสู่ระบบ
$user = Auth::user();
// สร้างคลาสสำหรับเรียกข้อมูลจาก MySQL
$repo = new RepairRequest();

// ผู้ใช้ทั่วไปเห็นเฉพาะงานของตน ส่วนแอดมินและช่างเห็นทั้งหมด
$requests = match ($user['role']) {
    'user' => $repo->allByUser((int)$user['id']), // ผู้ใช้เห็นเฉพาะรายการของตนเอง
    'tech' => $repo->allByTechnician((int)$user['id']), // ช่างเห็นเฉพาะงานที่ได้รับมอบหมาย
    default => $repo->all() // แอดมินเห็นรายการทั้งหมด
};
// เตรียมรายชื่อช่างสำหรับแบบฟอร์มมอบหมายงานของแอดมิน
$technicians = $user['role'] === 'admin' ? $repo->technicians() : [];

// เตรียมตัวนับจำนวนงานแต่ละสถานะ
$counts = ['ทั้งหมด' => count($requests), 'รอดำเนินการ' => 0, 'มอบหมายแล้ว' => 0, 'กำลังซ่อม' => 0, 'เสร็จสิ้น' => 0];
// นับจำนวนรายการตามสถานะที่บันทึกไว้
foreach ($requests as $request) {
    if (isset($counts[$request['status']])) {
        $counts[$request['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <!-- กำหนดภาษาและรหัสอักขระ -->
    <meta charset="UTF-8">
    <!-- รองรับหน้าจอมือถือ -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ชื่อหน้าแดชบอร์ด -->
    <title>แดชบอร์ด | AirCare</title>
    <!-- โหลดไฟล์ตกแต่งเว็บไซต์ -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- แถบเมนูด้านบน -->
    <header class="topbar">
        <a class="logo" href="dashboard.php"><span>AC</span> AirCare</a>
        <div class="top-user">
            <!-- แสดงชื่อและบทบาทของผู้ใช้ -->
            <span><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(Auth::roleLabel($user['role']), ENT_QUOTES, 'UTF-8') ?></span>
            <!-- ลิงก์ออกจากระบบ -->
            <a class="btn outline small" href="logout.php">ออกจากระบบ</a>
        </div>
    </header>

    <!-- เนื้อหาหลักของแดชบอร์ด -->
    <main class="container">
        <section class="welcome">
            <div>
                <p class="eyebrow">SERVICE OVERVIEW</p>
                <h1>แดชบอร์ด</h1>
                <p class="muted">จัดการงานแจ้งซ่อมเครื่องปรับอากาศได้จากที่นี่</p>
            </div>
            <!-- ผู้ใช้ทั่วไปสามารถสร้างคำขอแจ้งซ่อมใหม่ -->
            <?php if ($user['role'] === 'user'): ?>
                <a class="btn primary" href="create_request.php">＋ แจ้งซ่อมใหม่</a>
            <?php endif; ?>
        </section>

        <!-- แสดงจำนวนงานแยกตามสถานะ -->
        <section class="stats">
            <?php foreach ($counts as $label => $count): ?>
                <div class="stat-card">
                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= (int)$count ?></strong>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- กล่องแสดงรายการแจ้งซ่อม -->
        <section class="panel table-panel">
            <div class="section-heading">
                <div>
                    <h2><?= $user['role'] === 'user' ? 'รายการแจ้งซ่อมของฉัน' : 'รายการแจ้งซ่อมทั้งหมด' ?></h2>
                    <p class="muted">ตรวจสอบรายละเอียดและสถานะการซ่อม</p>
                </div>
            </div>

            <!-- แสดงข้อความเมื่อยังไม่มีรายการ -->
            <?php if (!$requests): ?>
                <div class="empty">
                    <div class="empty-icon">✓</div>
                    <h3>ยังไม่มีรายการแจ้งซ่อม</h3>
                    <p class="muted">รายการใหม่จะแสดงในส่วนนี้</p>
                </div>
            <?php else: ?>
                <!-- ตารางเลื่อนแนวนอนได้บนหน้าจอขนาดเล็ก -->
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>รหัส</th><th>ผู้แจ้ง</th><th>สถานที่ / อาการ</th><th>วันที่</th><th>สถานะ</th>
                                <?php if ($user['role'] === 'admin'): ?><th>มอบหมายช่าง</th><?php elseif ($user['role'] === 'tech'): ?><th>จัดการ</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- วนแสดงรายการแจ้งซ่อมทีละรายการ -->
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><strong>#<?= (int)$request['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($request['created_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($request['location'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small class="subline"><?= htmlspecialchars($request['issue'], ENT_QUOTES, 'UTF-8') ?></small>
                                        <!-- แสดงลิงก์รูปภาพเมื่อรายการนี้มีรูปแนบอยู่ -->
                                        <?php if (!empty($request['image_path']) && is_file(__DIR__ . '/' . $request['image_path'])): ?>
                                            <a class="image-link" href="<?= htmlspecialchars($request['image_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">📷 ดูรูปที่แนบ</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($request['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="status <?= $request['status'] === 'เสร็จสิ้น' ? 'done' : ($request['status'] === 'กำลังซ่อม' ? 'working' : 'pending') ?>">
                                            <?= htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <!-- แอดมินและช่างสามารถเปิดหน้าจัดการงาน -->
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <td>
                                            <!-- แอดมินเลือกช่างแล้วส่งฟอร์มไปบันทึกการมอบหมาย -->
                                            <form method="post" action="assign_request.php" class="assign-form">
                                                <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                                <select name="technician_id" required aria-label="เลือกช่างผู้รับผิดชอบ">
                                                    <option value="">เลือกช่าง</option>
                                                    <?php foreach ($technicians as $tech): ?>
                                                        <option value="<?= (int)$tech['id'] ?>" <?= (int)($request['assigned_tech_id'] ?? 0) === (int)$tech['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tech['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn primary small" type="submit">มอบหมาย</button>
                                            </form>
                                        </td>
                                    <?php elseif ($user['role'] === 'tech'): ?>
                                        <td><a class="btn dark small" href="update_request.php?id=<?= (int)$request['id'] ?>">เปิดงาน</a></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- แสดงคำอธิบายสิทธิ์ของแต่ละบทบาท -->
        <?php if ($user['role'] === 'admin'): ?>
            <section class="note"><strong>สิทธิ์แอดมิน</strong><p>ดูรายการแจ้งซ่อมทั้งหมดและปรับสถานะงานได้</p></section>
        <?php elseif ($user['role'] === 'tech'): ?>
            <section class="note"><strong>สิทธิ์ช่าง</strong><p>ตรวจสอบงานและอัปเดตสถานะการซ่อมได้</p></section>
        <?php endif; ?>
    </main>
    <!-- ส่วนท้ายเว็บไซต์ -->
    <footer>AirCare · ระบบแจ้งซ่อมเครื่องปรับอากาศ</footer>
</body>
</html>
