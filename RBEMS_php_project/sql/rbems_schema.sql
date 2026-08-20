-- =====================================================================
-- Rare Blood Emergency and Matching System (RBEMS)
-- Database Schema | CSE 2302 - Database Management System Lab
-- University of Liberal Arts Bangladesh
-- =====================================================================

DROP DATABASE IF EXISTS rbems;
CREATE DATABASE rbems;
USE rbems;

-- ---------------------------------------------------------------------
-- 1. HOSPITALS
-- ---------------------------------------------------------------------
CREATE TABLE hospitals (
    hospital_id      INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(120) NOT NULL,
    address           VARCHAR(255),
    latitude          DECIMAL(10,6) NOT NULL,
    longitude         DECIMAL(10,6) NOT NULL,
    contact_phone     VARCHAR(20),
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- 2. SYSTEM ADMINISTRATOR (single fixed account)
-- ---------------------------------------------------------------------
CREATE TABLE admins (
    admin_id          INT AUTO_INCREMENT PRIMARY KEY,
    username          VARCHAR(50) UNIQUE NOT NULL,
    password_hash     VARCHAR(255) NOT NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- 2. PATIENTS  (role-based sign-up / login)
-- ---------------------------------------------------------------------
CREATE TABLE patient_users (
    patient_id        INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    username          VARCHAR(50) NOT NULL UNIQUE,
    dob               DATE NOT NULL,
    weight            DECIMAL(5,2) NOT NULL,
    blood_group       ENUM('O','A','B','AB') NOT NULL,
    address           VARCHAR(255) NOT NULL,
    phone             VARCHAR(20) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NOT NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- 3. DONOR USERS  (role-based sign-up / login, admin approval)
-- ---------------------------------------------------------------------
CREATE TABLE donor_users (
    donor_user_id     INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    username          VARCHAR(50) NOT NULL UNIQUE,
    dob               DATE NOT NULL,
    address           VARCHAR(255) NOT NULL,
    phone             VARCHAR(20) NOT NULL UNIQUE,
    habits            VARCHAR(255),
    password_hash     VARCHAR(255) NOT NULL,
    is_approved       TINYINT(1) NOT NULL DEFAULT 0,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- 4. DONOR APPOINTMENTS / HOSPITAL MEETUP CONFIRMATIONS
-- ---------------------------------------------------------------------
CREATE TABLE donor_appointments (
    appointment_id    INT AUTO_INCREMENT PRIMARY KEY,
    donor_user_id     INT NOT NULL,
    hospital_id       INT NOT NULL,
    patient_id        INT NULL,
    appointment_status ENUM('Pending','Confirmed','Completed') NOT NULL DEFAULT 'Pending',
    notes             VARCHAR(255),
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_user_id) REFERENCES donor_users(donor_user_id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id),
    FOREIGN KEY (patient_id) REFERENCES patient_users(patient_id) ON DELETE SET NULL
);

-- ---------------------------------------------------------------------
-- 5. DONORS  (with rare phenotype profile)
-- ---------------------------------------------------------------------
CREATE TABLE donors (
    donor_id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name         VARCHAR(100) NOT NULL,
    phone             VARCHAR(20) NOT NULL,
    email             VARCHAR(100),
    gender            ENUM('Male','Female','Other') NOT NULL,
    dob               DATE NOT NULL,
    blood_group       ENUM('O','A','B','AB') NOT NULL,
    rh_type           ENUM('Positive','Negative','Rh-null') NOT NULL DEFAULT 'Positive',
    kell_status       ENUM('Positive','Negative') NOT NULL DEFAULT 'Negative',
    bombay_phenotype  TINYINT(1) NOT NULL DEFAULT 0,
    address           VARCHAR(255),
    last_donation_date DATE NULL,
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status            ENUM('Active','Inactive','Deferred') NOT NULL DEFAULT 'Active'
);

-- ---------------------------------------------------------------------
-- 3. DONATIONS
-- ---------------------------------------------------------------------
CREATE TABLE donations (
    donation_id       INT AUTO_INCREMENT PRIMARY KEY,
    donor_id          INT NOT NULL,
    hospital_id       INT NOT NULL,
    donation_date     DATE NOT NULL,
    volume_ml         INT NOT NULL DEFAULT 450,
    notes             VARCHAR(255),
    FOREIGN KEY (donor_id) REFERENCES donors(donor_id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
);

-- ---------------------------------------------------------------------
-- 4. BLOOD INVENTORY  (shelf-life & cryopreservation tracking)
-- ---------------------------------------------------------------------
CREATE TABLE blood_inventory (
    unit_id           INT AUTO_INCREMENT PRIMARY KEY,
    donation_id       INT NULL,
    blood_group       ENUM('O','A','B','AB') NOT NULL,
    rh_type           ENUM('Positive','Negative','Rh-null') NOT NULL,
    kell_status       ENUM('Positive','Negative') NOT NULL DEFAULT 'Negative',
    bombay_phenotype  TINYINT(1) NOT NULL DEFAULT 0,
    storage_type      ENUM('Fresh','Cryopreserved') NOT NULL DEFAULT 'Fresh',
    collection_date   DATE NOT NULL,
    expiry_date       DATE NULL,
    hospital_id       INT NOT NULL,
    status            ENUM('Available','Reserved','Used','Expired','Discarded') NOT NULL DEFAULT 'Available',
    FOREIGN KEY (donation_id) REFERENCES donations(donation_id) ON DELETE SET NULL,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
);

-- ---------------------------------------------------------------------
-- 5. HOSPITAL REQUESTS  (emergency rare-blood requests)
-- ---------------------------------------------------------------------
CREATE TABLE hospital_requests (
    request_id        INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id       INT NOT NULL,
    blood_group       ENUM('O','A','B','AB') NOT NULL,
    rh_type           ENUM('Positive','Negative','Rh-null') NOT NULL,
    kell_status       ENUM('Positive','Negative') NOT NULL DEFAULT 'Negative',
    bombay_phenotype  TINYINT(1) NOT NULL DEFAULT 0,
    units_needed      INT NOT NULL DEFAULT 1,
    priority          ENUM('Emergency','Urgent','Routine') NOT NULL DEFAULT 'Routine',
    status            ENUM('Pending','Approved','Fulfilled','Rejected') NOT NULL DEFAULT 'Pending',
    request_date      DATETIME DEFAULT CURRENT_TIMESTAMP,
    needed_by         DATETIME,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id)
);

-- ---------------------------------------------------------------------
-- 6. TRANSFERS  (inter-hospital unit transfer / donor mobilization)
-- ---------------------------------------------------------------------
CREATE TABLE transfers (
    transfer_id        INT AUTO_INCREMENT PRIMARY KEY,
    request_id         INT NOT NULL,
    unit_id             INT NULL,
    donor_id            INT NULL,
    source_hospital_id  INT NULL,
    destination_hospital_id INT NOT NULL,
    quantity            INT NOT NULL DEFAULT 1,
    status               ENUM('Pending','Approved','In-Transit','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    approved_by          VARCHAR(50),
    transfer_date        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES hospital_requests(request_id),
    FOREIGN KEY (unit_id) REFERENCES blood_inventory(unit_id),
    FOREIGN KEY (donor_id) REFERENCES donors(donor_id),
    FOREIGN KEY (source_hospital_id) REFERENCES hospitals(hospital_id),
    FOREIGN KEY (destination_hospital_id) REFERENCES hospitals(hospital_id)
);

-- ---------------------------------------------------------------------
-- 7. NOTIFICATIONS
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    notification_id   INT AUTO_INCREMENT PRIMARY KEY,
    request_id         INT NULL,
    donor_id            INT NULL,
    hospital_id         INT NULL,
    message              VARCHAR(255) NOT NULL,
    type                 ENUM('Request','Approval','Transfer','Expiry','Eligibility') NOT NULL,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read              TINYINT(1) DEFAULT 0,
    FOREIGN KEY (request_id) REFERENCES hospital_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES donors(donor_id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE
);

-- =====================================================================
-- TRIGGERS
-- =====================================================================

DELIMITER $$

-- Update donor's last_donation_date automatically after a donation
CREATE TRIGGER trg_update_last_donation
AFTER INSERT ON donations
FOR EACH ROW
BEGIN
    UPDATE donors
    SET last_donation_date = NEW.donation_date
    WHERE donor_id = NEW.donor_id;
END$$

-- Auto-compute expiry_date on new inventory unit based on storage type
-- Fresh whole blood: 42 days shelf life. Cryopreserved: 10 years.
CREATE TRIGGER trg_inventory_expiry
BEFORE INSERT ON blood_inventory
FOR EACH ROW
BEGIN
    IF NEW.expiry_date IS NULL THEN
        IF NEW.storage_type = 'Cryopreserved' THEN
            SET NEW.expiry_date = DATE_ADD(NEW.collection_date, INTERVAL 10 YEAR);
        ELSE
            SET NEW.expiry_date = DATE_ADD(NEW.collection_date, INTERVAL 42 DAY);
        END IF;
    END IF;
END$$

-- Notify hospital when a new emergency/urgent request is created
CREATE TRIGGER trg_request_notification
AFTER INSERT ON hospital_requests
FOR EACH ROW
BEGIN
    INSERT INTO notifications (request_id, hospital_id, message, type)
    VALUES (
        NEW.request_id,
        NEW.hospital_id,
        CONCAT(NEW.priority, ' request #', NEW.request_id, ' submitted for ',
               NEW.blood_group, NEW.rh_type, ' (', NEW.units_needed, ' unit(s))'),
        'Request'
    );
END$$

-- When a transfer is completed, mark the inventory unit Used and update request status
CREATE TRIGGER trg_transfer_completed
AFTER UPDATE ON transfers
FOR EACH ROW
BEGIN
    IF NEW.status = 'Completed' AND OLD.status <> 'Completed' THEN
        IF NEW.unit_id IS NOT NULL THEN
            UPDATE blood_inventory SET status = 'Used' WHERE unit_id = NEW.unit_id;
        END IF;
        UPDATE hospital_requests SET status = 'Fulfilled' WHERE request_id = NEW.request_id;
        INSERT INTO notifications (request_id, hospital_id, message, type)
        VALUES (NEW.request_id, NEW.destination_hospital_id,
                CONCAT('Transfer #', NEW.transfer_id, ' completed for request #', NEW.request_id),
                'Transfer');
    END IF;
END$$

DELIMITER ;

-- =====================================================================
-- VIEWS
-- =====================================================================

-- Donors currently eligible to donate (interval rule: 90 days male, 120 days female)
CREATE VIEW v_eligible_donors AS
SELECT d.*,
    CASE
        WHEN d.last_donation_date IS NULL THEN TRUE
        WHEN d.gender = 'Male'   AND DATEDIFF(CURDATE(), d.last_donation_date) >= 90  THEN TRUE
        WHEN d.gender <> 'Male'  AND DATEDIFF(CURDATE(), d.last_donation_date) >= 120 THEN TRUE
        ELSE FALSE
    END AS is_eligible,
    CASE
        WHEN d.last_donation_date IS NULL THEN 0
        WHEN d.gender = 'Male'  THEN GREATEST(0, 90  - DATEDIFF(CURDATE(), d.last_donation_date))
        ELSE GREATEST(0, 120 - DATEDIFF(CURDATE(), d.last_donation_date))
    END AS days_until_eligible
FROM donors d
WHERE d.status = 'Active';

-- Units expiring within the next 7 days (wastage prevention)
CREATE VIEW v_expiring_soon AS
SELECT bi.*, h.name AS hospital_name
FROM blood_inventory bi
JOIN hospitals h ON h.hospital_id = bi.hospital_id
WHERE bi.status = 'Available'
  AND DATEDIFF(bi.expiry_date, CURDATE()) <= 7
ORDER BY bi.expiry_date ASC;

-- Available inventory with hospital info
CREATE VIEW v_available_inventory AS
SELECT bi.*, h.name AS hospital_name, h.latitude AS hospital_lat, h.longitude AS hospital_lng
FROM blood_inventory bi
JOIN hospitals h ON h.hospital_id = bi.hospital_id
WHERE bi.status = 'Available' AND bi.expiry_date >= CURDATE();

-- Pending requests ordered by priority (emergency first)
CREATE VIEW v_pending_requests AS
SELECT r.*, h.name AS hospital_name
FROM hospital_requests r
JOIN hospitals h ON h.hospital_id = r.hospital_id
WHERE r.status = 'Pending'
ORDER BY FIELD(r.priority, 'Emergency', 'Urgent', 'Routine'), r.request_date ASC;

-- =====================================================================
-- STORED PROCEDURES
-- =====================================================================

DELIMITER $$

-- Find compatible AVAILABLE inventory units for a requested phenotype
CREATE PROCEDURE sp_find_compatible_inventory (
    IN p_group VARCHAR(2), IN p_rh VARCHAR(10),
    IN p_kell VARCHAR(10), IN p_bombay TINYINT(1)
)
BEGIN
    IF p_bombay = 1 THEN
        -- Bombay phenotype recipients can ONLY receive Bombay phenotype blood
        SELECT * FROM v_available_inventory WHERE bombay_phenotype = 1;
    ELSEIF p_rh = 'Rh-null' THEN
        -- Rh-null recipients can ONLY receive Rh-null blood
        SELECT * FROM v_available_inventory WHERE rh_type = 'Rh-null';
    ELSE
        SELECT * FROM v_available_inventory
        WHERE bombay_phenotype = 0
          AND rh_type <> 'Rh-null'
          AND (
                (p_group = 'AB' AND blood_group IN ('A','B','AB','O'))
             OR (p_group = 'A'  AND blood_group IN ('A','O'))
             OR (p_group = 'B'  AND blood_group IN ('B','O'))
             OR (p_group = 'O'  AND blood_group = 'O')
          )
          AND (p_rh = 'Positive' OR rh_type = 'Negative')
          AND (p_kell = 'Positive' OR kell_status = 'Negative')
        ORDER BY expiry_date ASC;
    END IF;
END$$

-- Eligibility check for a single donor
CREATE PROCEDURE sp_check_eligibility (IN p_donor_id INT)
BEGIN
    SELECT donor_id, full_name, gender, last_donation_date, is_eligible, days_until_eligible
    FROM v_eligible_donors WHERE donor_id = p_donor_id;
END$$

DELIMITER ;


INSERT INTO hospitals (name, address, latitude, longitude, contact_phone) VALUES
('Dhaka Medical College Hospital', 'Bakshibazar, Dhaka', 23.7269, 90.3985, '02-55165088'),
('Square Hospital', 'Panthapath, Dhaka', 23.7519, 90.3865, '10616'),
('Evercare Hospital', 'Bashundhara, Dhaka', 23.8135, 90.4394, '10678');

INSERT INTO admins (username, password_hash) VALUES
('admin1', SHA2('12345678', 256));


INSERT INTO blood_inventory (donation_id, blood_group, rh_type, kell_status, bombay_phenotype, storage_type, collection_date, hospital_id, status) VALUES
 (NULL, 'O', 'Negative', 'Negative', 0, 'Fresh', '2026-05-01', 1, 'Available'),
 (NULL, 'B', 'Negative', 'Positive', 0, 'Cryopreserved', '2026-06-15', 2, 'Available'),
(NULL, 'O', 'Rh-null', 'Negative', 0, 'Cryopreserved', '2026-01-10', 1, 'Available'),
(NULL, 'AB', 'Positive', 'Negative', 1, 'Cryopreserved', '2025-12-01', 3, 'Available'),
(NULL, 'A', 'Positive', 'Negative', 0, 'Fresh', '2026-08-01', 3, 'Available');

INSERT INTO hospital_requests (hospital_id, blood_group, rh_type, kell_status, bombay_phenotype, units_needed, priority, needed_by) VALUES
(2, 'O', 'Rh-null', 'Negative', 0, 1, 'Emergency', DATE_ADD(NOW(), INTERVAL 6 HOUR)),
(3, 'A', 'Positive', 'Negative', 0, 2, 'Routine', DATE_ADD(NOW(), INTERVAL 3 DAY));
