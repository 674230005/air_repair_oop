<?php
// เปิดใช้การตรวจสอบชนิดข้อมูลแบบเข้มงวด
declare(strict_types=1);

// เรียกใช้คลาส Database เพื่อเชื่อมต่อ MySQL
require_once __DIR__ . '/../config/database.php';

// คลาส RepairRequest รับผิดชอบการจัดการคำขอแจ้งซ่อมในฐานข้อมูล
class RepairRequest {
    // เก็บออบเจ็กต์ PDO ที่ใช้ติดต่อฐานข้อมูล
    private PDO $db;

    // สร้างการเชื่อมต่อเมื่อเริ่มใช้งานคลาส
    public function __construct() {
        // สร้างออบเจ็กต์ Database และเก็บการเชื่อมต่อไว้ในคลาสนี้
        $this->db = (new Database())->connect();
    }

    // ดึงรายการแจ้งซ่อมทั้งหมด โดยแสดงรายการใหม่ก่อน
    public function all(): array {
        // JOIN ตาราง users เพื่อให้ได้ชื่อผู้แจ้งจากบัญชีผู้ใช้
        $sql = 'SELECT r.*, u.full_name AS created_name, u.username AS created_by
                FROM repair_requests r
                INNER JOIN users u ON r.user_id = u.id
                ORDER BY r.id DESC';
        // เตรียมคำสั่ง SQL
        $stmt = $this->db->prepare($sql);
        // เรียกใช้งานคำสั่ง SQL
        $stmt->execute();
        // คืนค่ารายการทั้งหมดเป็น array
        return $stmt->fetchAll();
    }

    // ดึงเฉพาะรายการแจ้งซ่อมของผู้ใช้คนหนึ่ง
    public function allByUser(int $userId): array {
        // ค้นหาด้วย user_id เพื่อไม่ให้ผู้ใช้เห็นรายการของคนอื่น
        $sql = 'SELECT r.*, u.full_name AS created_name, u.username AS created_by
                FROM repair_requests r
                INNER JOIN users u ON r.user_id = u.id
                WHERE r.user_id = :user_id
                ORDER BY r.id DESC';
        // เตรียมคำสั่ง SQL
        $stmt = $this->db->prepare($sql);
        // ผูกค่า ID ผู้ใช้กับคำสั่ง SQL
        $stmt->execute(['user_id' => $userId]);
        // คืนเฉพาะรายการของผู้ใช้คนนี้
        return $stmt->fetchAll();
    }

    // ดึงรายการงานที่มอบหมายให้ช่างคนใดคนหนึ่งเท่านั้น
    public function allByTechnician(int $technicianId): array {
        // ค้นหางานโดยใช้รหัสช่างที่ได้รับมอบหมาย
        $sql = 'SELECT r.*, u.full_name AS created_name, u.username AS created_by
                FROM repair_requests r
                INNER JOIN users u ON r.user_id = u.id
                WHERE r.assigned_tech_id = :tech_id
                ORDER BY r.id DESC';
        // เตรียมคำสั่ง SQL แบบปลอดภัย
        $stmt = $this->db->prepare($sql);
        // ส่งรหัสช่างให้คำสั่ง SQL
        $stmt->execute(['tech_id' => $technicianId]);
        // คืนเฉพาะงานที่ช่างคนนี้รับผิดชอบ
        return $stmt->fetchAll();
    }

    // ดึงรายชื่อบัญชีที่มีบทบาทเป็นช่าง เพื่อให้แอดมินเลือกมอบหมายงาน
    public function technicians(): array {
        // เลือกเฉพาะบัญชีช่าง เรียงตามชื่อ
        $stmt = $this->db->prepare("SELECT id, full_name, username FROM users WHERE role = 'tech' ORDER BY full_name");
        // สั่งค้นหารายชื่อช่าง
        $stmt->execute();
        // ส่งรายชื่อช่างกลับไปยังหน้าเว็บ
        return $stmt->fetchAll();
    }

    // มอบหมายรายการแจ้งซ่อมให้ช่างที่เลือก
    public function assign(int $requestId, int $technicianId): bool {
        // ตรวจสอบว่ารหัสที่เลือกเป็นบัญชีช่างจริง
        $check = $this->db->prepare("SELECT id FROM users WHERE id = :id AND role = 'tech' LIMIT 1");
        // ค้นหาบัญชีช่างตามรหัส
        $check->execute(['id' => $technicianId]);
        // หากไม่พบช่าง ให้ยกเลิกการมอบหมาย
        if (!$check->fetch()) { return false; }
        // บันทึกรหัสช่างและเปลี่ยนสถานะเป็นมอบหมายแล้ว
        $stmt = $this->db->prepare("UPDATE repair_requests SET assigned_tech_id = :tech_id, status = 'มอบหมายแล้ว' WHERE id = :id AND status <> 'เสร็จสิ้น'");
        // ส่งรหัสช่างและรหัสงานให้ฐานข้อมูล
        $stmt->execute(['tech_id' => $technicianId, 'id' => $requestId]);
        // แจ้งผลว่ามีงานถูกมอบหมายหรือไม่
        return $stmt->rowCount() > 0;
    }

    // ค้นหารายการแจ้งซ่อมตามรหัสรายการ
    public function find(int $id): ?array {
        // JOIN ตารางผู้ใช้เพื่อดึงชื่อผู้แจ้ง
        $sql = 'SELECT r.*, u.full_name AS created_name, u.username AS created_by
                FROM repair_requests r
                INNER JOIN users u ON r.user_id = u.id
                WHERE r.id = :id LIMIT 1';
        // เตรียมคำสั่ง SQL
        $stmt = $this->db->prepare($sql);
        // ส่งรหัสรายการให้ฐานข้อมูล
        $stmt->execute(['id' => $id]);
        // ดึงข้อมูลรายการเดียว
        $request = $stmt->fetch();
        // คืน null เมื่อไม่พบรายการ
        return $request ?: null;
    }

    // เพิ่มรายการแจ้งซ่อม พร้อมตรวจสอบและจัดเก็บไฟล์รูปภาพ (ถ้ามี)
    public function create(array $user, string $location, string $issue, string $phone, ?array $upload = null): void {
        // กำหนดค่าเริ่มต้นว่าไม่มีรูปแนบ
        $imagePath = null;

        // ตรวจสอบว่าผู้ใช้เลือกไฟล์รูปภาพหรือไม่
        if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            // ตรวจสอบว่าการอัปโหลดเสร็จสมบูรณ์
            if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่');
            }

            // จำกัดขนาดรูปภาพไม่เกิน 5 เมกะไบต์
            if (($upload['size'] ?? 0) > 5 * 1024 * 1024) {
                throw new RuntimeException('รูปภาพต้องมีขนาดไม่เกิน 5 MB');
            }

            // ตรวจสอบชนิดไฟล์จากเนื้อหาจริงด้วย Fileinfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($upload['tmp_name']);
            // อนุญาตเฉพาะ JPG, PNG และ WEBP
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            // ปฏิเสธไฟล์ที่ไม่ใช่ชนิดภาพที่กำหนด
            if (!isset($allowed[$mime])) {
                throw new RuntimeException('รองรับเฉพาะไฟล์ JPG, PNG และ WEBP เท่านั้น');
            }

            // สร้างโฟลเดอร์ uploads สำหรับเก็บไฟล์ภาพ
            $uploadDir = __DIR__ . '/../uploads';
            // สร้างโฟลเดอร์เมื่อยังไม่มี
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ uploads ได้');
            }

            // สุ่มชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำและลดความเสี่ยงจากชื่อไฟล์อันตราย
            $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            // ย้ายไฟล์จากพื้นที่ชั่วคราวไปเก็บใน uploads
            if (!move_uploaded_file($upload['tmp_name'], $uploadDir . '/' . $filename)) {
                throw new RuntimeException('บันทึกรูปไม่สำเร็จ กรุณาตรวจสอบโฟลเดอร์ uploads');
            }

            // เก็บเฉพาะตำแหน่งไฟล์ลงฐานข้อมูล ไม่เก็บข้อมูลภาพทั้งก้อนใน SQL
            $imagePath = 'uploads/' . $filename;
        }

        // เตรียมคำสั่งเพิ่มข้อมูลโดยใช้ prepared statement
        $sql = 'INSERT INTO repair_requests (user_id, location, issue, phone, image_path, status, note, created_at)
                VALUES (:user_id, :location, :issue, :phone, :image_path, :status, :note, NOW())';
        // สร้างคำสั่ง SQL ที่ปลอดภัยต่อการส่งค่าจากแบบฟอร์ม
        $stmt = $this->db->prepare($sql);
        // ส่งข้อมูลคำขอและตำแหน่งรูปภาพเข้า MySQL
        $stmt->execute([
            'user_id' => $user['id'],
            'location' => $location,
            'issue' => $issue,
            'phone' => $phone,
            'image_path' => $imagePath,
            'status' => 'รอดำเนินการ',
            'note' => ''
        ]);
    }

    // เปลี่ยนสถานะและหมายเหตุของรายการแจ้งซ่อม
    public function update(int $id, string $status, string $note, ?int $technicianId = null): bool {
        // อนุญาตเฉพาะสถานะที่ระบบกำหนดไว้
        $allowedStatuses = ['รอดำเนินการ', 'มอบหมายแล้ว', 'กำลังซ่อม', 'เสร็จสิ้น'];
        // ป้องกันไม่ให้บันทึกสถานะที่ไม่ถูกต้อง
        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }
        // เตรียมคำสั่ง SQL และจำกัดช่างให้แก้เฉพาะงานที่ได้รับมอบหมาย
        if ($technicianId !== null) {
            $stmt = $this->db->prepare('UPDATE repair_requests SET status = :status, note = :note WHERE id = :id AND assigned_tech_id = :tech_id');
            $stmt->execute(['status' => $status, 'note' => $note, 'id' => $id, 'tech_id' => $technicianId]);
        } else {
            // แอดมินสามารถแก้ไขสถานะของรายการใดก็ได้
            $stmt = $this->db->prepare('UPDATE repair_requests SET status = :status, note = :note WHERE id = :id');
            $stmt->execute(['status' => $status, 'note' => $note, 'id' => $id]);
        }
        // คืน true เมื่อมีรายการถูกแก้ไข
        return $stmt->rowCount() > 0;
    }
}
