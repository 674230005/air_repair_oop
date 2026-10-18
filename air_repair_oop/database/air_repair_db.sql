-- สร้างฐานข้อมูลสำหรับระบบแจ้งซ่อมเครื่องปรับอากาศ
CREATE DATABASE IF NOT EXISTS air_repair_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- เลือกฐานข้อมูลที่จะใช้สร้างตาราง
USE air_repair_db;

-- ตาราง users เก็บบัญชีผู้ใช้ทุกบทบาท
CREATE TABLE IF NOT EXISTS users (
    -- รหัสผู้ใช้หลัก เพิ่มค่าอัตโนมัติ
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ชื่อบัญชีต้องไม่ซ้ำกัน
    username VARCHAR(50) NOT NULL UNIQUE,
    -- เก็บรหัสผ่านที่ผ่านการ hash แล้ว
    password_hash VARCHAR(255) NOT NULL,
    -- ชื่อที่แสดงในระบบ
    full_name VARCHAR(100) NOT NULL,
    -- บทบาทผู้ใช้ จำกัดเป็น admin, user หรือ tech
    role ENUM('admin', 'user', 'tech') NOT NULL DEFAULT 'user',
    -- เวลาที่สร้างบัญชี
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตาราง repair_requests เก็บรายละเอียดการแจ้งซ่อม
CREATE TABLE IF NOT EXISTS repair_requests (
    -- รหัสรายการแจ้งซ่อมหลัก เพิ่มค่าอัตโนมัติ
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- ID ของผู้แจ้ง เชื่อมกับตาราง users
    user_id INT UNSIGNED NOT NULL,
    -- รหัสช่างผู้รับผิดชอบ อาจเว้นว่างได้จนกว่าแอดมินจะมอบหมายงาน
    assigned_tech_id INT UNSIGNED NULL,
    -- สถานที่ติดตั้งหรือจุดที่พบปัญหา
    location VARCHAR(255) NOT NULL,
    -- รายละเอียดอาการเสีย
    issue TEXT NOT NULL,
    -- เบอร์โทรติดต่อกลับ
    phone VARCHAR(30) NOT NULL,
    -- ที่อยู่ไฟล์รูปภาพในโฟลเดอร์ uploads เก็บเป็นข้อความ
    image_path VARCHAR(255) NULL,
    -- สถานะการดำเนินงาน
    status ENUM('รอดำเนินการ', 'มอบหมายแล้ว', 'กำลังซ่อม', 'เสร็จสิ้น') NOT NULL DEFAULT 'รอดำเนินการ',
    -- หมายเหตุจากช่างหรือผู้ดูแลระบบ
    note TEXT NULL,
    -- วันและเวลาที่สร้างคำขอ
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- เชื่อมรายการแจ้งซ่อมกับเจ้าของคำขอ
    CONSTRAINT fk_repair_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    -- ดัชนีช่วยให้ค้นหารายการของผู้ใช้ได้เร็วขึ้น
    INDEX idx_repair_user (user_id),
    -- ดัชนีช่วยกรองรายการตามสถานะได้เร็วขึ้น
    INDEX idx_repair_status (status)
) ENGINE=InnoDB;
