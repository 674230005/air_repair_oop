<?php
// เปิดใช้งาน strict types เพื่อให้ PHP ตรวจสอบชนิดข้อมูลอย่างเข้มงวด
declare(strict_types=1);

// คลาส Database รับผิดชอบการเชื่อมต่อฐานข้อมูล MySQL ผ่าน PDO
class Database {
    // กำหนดชื่อเครื่องฐานข้อมูล ซึ่ง XAMPP ในเครื่องตัวเองใช้ localhost
    private string $host = 'localhost';
    // กำหนดชื่อฐานข้อมูลที่สร้างจากไฟล์ database/air_repair_db.sql
    private string $dbname = 'air_repair_db';
    // กำหนดชื่อผู้ใช้ MySQL เริ่มต้นของ XAMPP
    private string $username = 'root';
    // กำหนดรหัสผ่าน MySQL ซึ่ง XAMPP แบบติดตั้งทั่วไปมักเว้นว่าง
    private string $password = '';
    // เก็บออบเจ็กต์ PDO หลังเชื่อมต่อสำเร็จ
    private ?PDO $connection = null;

    // คืนค่าการเชื่อมต่อ PDO และสร้างการเชื่อมต่อเพียงครั้งเดียวต่อออบเจ็กต์
    public function connect(): PDO {
        // ถ้ายังไม่มีการเชื่อมต่อ ให้สร้างการเชื่อมต่อใหม่
        if ($this->connection === null) {
            // กำหนด DSN พร้อม charset utf8mb4 เพื่อรองรับภาษาไทยและอีโมจิ
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            // ตั้งค่า PDO ให้รายงานข้อผิดพลาดเป็น exception และคืนผลลัพธ์เป็น associative array
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            // พยายามเชื่อมต่อฐานข้อมูลตามค่าที่กำหนด
            try {
                $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                // แสดงข้อความที่เข้าใจง่าย หากยังไม่ได้สร้างฐานข้อมูลหรือเชื่อมต่อไม่ได้
                exit('เชื่อมต่อฐานข้อมูลไม่ได้ กรุณาเปิด MySQL และนำเข้าไฟล์ database/air_repair_db.sql');
            }
        }
        // ส่งออบเจ็กต์ PDO กลับไปให้คลาสที่เรียกใช้
        return $this->connection;
    }
}
