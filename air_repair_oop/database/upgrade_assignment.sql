-- ไฟล์ปรับปรุงฐานข้อมูลเดิมเพื่อรองรับการมอบหมายงานให้ช่าง
-- ให้เลือกฐานข้อมูล air_repair_db ใน phpMyAdmin ก่อนรันไฟล์นี้

-- เพิ่มคอลัมน์รหัสช่างผู้รับผิดชอบ โดยปล่อย NULL ได้สำหรับงานเก่าที่ยังไม่มอบหมาย
ALTER TABLE repair_requests
    ADD COLUMN assigned_tech_id INT UNSIGNED NULL AFTER user_id;

-- สร้างดัชนีเพื่อค้นหางานตามช่างได้รวดเร็วขึ้น
CREATE INDEX idx_repair_assigned_tech ON repair_requests (assigned_tech_id);

-- เชื่อมรหัสช่างกับตาราง users เพื่อป้องกันรหัสช่างที่ไม่มีอยู่จริง
ALTER TABLE repair_requests
    ADD CONSTRAINT fk_repair_technician
    FOREIGN KEY (assigned_tech_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- เพิ่มสถานะมอบหมายแล้วเข้าไปในรายการสถานะที่อนุญาต
ALTER TABLE repair_requests
    MODIFY status ENUM('รอดำเนินการ', 'มอบหมายแล้ว', 'กำลังซ่อม', 'เสร็จสิ้น')
    NOT NULL DEFAULT 'รอดำเนินการ';
