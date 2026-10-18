<?php
// เปิดใช้การตรวจสอบชนิดข้อมูลแบบเข้มงวด
declare(strict_types=1);

// เรียกใช้คลาสเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config/database.php';

// คลาส Auth จัดการการเข้าสู่ระบบ สิทธิ์ผู้ใช้ และการออกจากระบบ
class Auth {
    // เริ่ม session สำหรับเก็บข้อมูลผู้ใช้ที่เข้าสู่ระบบ
    public static function start(): void {
        // เริ่ม session เฉพาะเมื่อยังไม่มี session ทำงานอยู่
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // ป้องกัน JavaScript อ่าน session cookie และจำกัดการส่ง cookie ข้ามเว็บไซต์
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
            // เริ่มต้น session
            session_start();
        }
    }

    // ตรวจสอบชื่อผู้ใช้และรหัสผ่านจากฐานข้อมูล
    public static function login(string $username, string $password): bool {
        // เริ่ม session ก่อนใช้งานข้อมูล session
        self::start();
        // สร้างการเชื่อมต่อฐานข้อมูล
        $db = (new Database())->connect();
        // เตรียมคำสั่ง SQL เพื่อค้นหาผู้ใช้ด้วย prepared statement
        $stmt = $db->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = :username LIMIT 1');
        // ส่งชื่อผู้ใช้เป็นพารามิเตอร์ ไม่ต่อข้อความเข้ากับ SQL โดยตรง
        $stmt->execute(['username' => $username]);
        // ดึงข้อมูลบัญชีผู้ใช้ที่ค้นพบ
        $account = $stmt->fetch();
        // ตรวจสอบว่ามีบัญชีและรหัสผ่านที่กรอกตรงกับ hash หรือไม่
        if (!$account || !password_verify($password, $account['password_hash'])) {
            // แจ้งผลว่าการเข้าสู่ระบบไม่สำเร็จ
            return false;
        }
        // สร้าง session ID ใหม่หลังเข้าสู่ระบบเพื่อช่วยป้องกัน session fixation
        session_regenerate_id(true);
        // เก็บเฉพาะข้อมูลที่จำเป็นไว้ใน session โดยไม่เก็บ hash รหัสผ่าน
        $_SESSION['user'] = [
            'id' => (int)$account['id'],
            'username' => $account['username'],
            'name' => $account['full_name'],
            'role' => $account['role']
        ];
        // ส่งผลสำเร็จกลับไปยังหน้าเข้าสู่ระบบ
        return true;
    }

    // ตรวจสอบว่ามีผู้ใช้เข้าสู่ระบบแล้วหรือยัง
    public static function check(): bool {
        // เริ่ม session หากจำเป็น
        self::start();
        // คืนค่า true เมื่อมีข้อมูลผู้ใช้ใน session
        return isset($_SESSION['user']);
    }

    // คืนค่าข้อมูลผู้ใช้ที่เข้าสู่ระบบอยู่
    public static function user(): array {
        // เริ่ม session ก่อนอ่านข้อมูล
        self::start();
        // คืนข้อมูลผู้ใช้ หรือ array ว่างเมื่อยังไม่ได้เข้าสู่ระบบ
        return $_SESSION['user'] ?? [];
    }

    // บังคับให้เข้าสู่ระบบก่อนเข้าหน้าสำคัญ
    public static function requireLogin(): void {
        // หากยังไม่ได้เข้าสู่ระบบ ให้กลับไปหน้า Login
        if (!self::check()) {
            header('Location: index.php');
            exit;
        }
    }

    // จำกัดสิทธิ์ให้เข้าถึงหน้าได้เฉพาะบทบาทที่กำหนด
    public static function requireRole(string|array $roles): void {
        // ตรวจสอบการเข้าสู่ระบบก่อน
        self::requireLogin();
        // แปลงบทบาทเป็น array เพื่อรองรับทั้งหนึ่งบทบาทและหลายบทบาท
        $roles = (array)$roles;
        // หากบทบาทของผู้ใช้ไม่อยู่ในรายการที่อนุญาต ให้ปฏิเสธการเข้าถึง
        if (!in_array(self::user()['role'], $roles, true)) {
            http_response_code(403);
            exit('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
    }

    // แปลงรหัสบทบาทให้เป็นข้อความภาษาไทยสำหรับแสดงบนหน้าเว็บ
    public static function roleLabel(string $role): string {
        // กำหนดชื่อภาษาไทยของแต่ละบทบาท
        return ['admin' => 'แอดมิน', 'user' => 'ผู้ใช้', 'tech' => 'ช่าง'][$role] ?? 'ผู้ใช้';
    }

    // ออกจากระบบและลบข้อมูล session
    public static function logout(): void {
        // เริ่ม session ก่อนลบข้อมูล
        self::start();
        // ล้างตัวแปร session ทั้งหมด
        $_SESSION = [];
        // ทำลาย session ปัจจุบัน
        session_destroy();
    }
}
