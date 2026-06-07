-- ============================================================
-- AUM E-Portal for Employment — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS aum_portal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE aum_portal;

-- ── Users ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('staff','admin') NOT NULL DEFAULT 'staff',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── Staff Profiles ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS staff_profiles (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    first_name     VARCHAR(100)  DEFAULT NULL,
    last_name      VARCHAR(100)  DEFAULT NULL,
    phone          VARCHAR(20)   DEFAULT NULL,
    department     VARCHAR(100)  DEFAULT NULL,
    nationality    VARCHAR(100)  DEFAULT NULL,
    linkedin       VARCHAR(255)  DEFAULT NULL,
    profile_pic    VARCHAR(255)  DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Qualifications ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS qualifications (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    degree           VARCHAR(150) NOT NULL,
    university       VARCHAR(200) NOT NULL,
    graduation_year  YEAR NOT NULL,
    certification    VARCHAR(255) DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Publications ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS publications (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    title             VARCHAR(500) NOT NULL,
    journal           VARCHAR(200) NOT NULL,
    publication_year  YEAR NOT NULL,
    research_interest VARCHAR(200) DEFAULT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Experiences ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS experiences (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    institution         VARCHAR(200) NOT NULL,
    position            VARCHAR(150) NOT NULL,
    years_of_experience INT NOT NULL DEFAULT 0,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Skills ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS skills (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    skill_name  VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Documents ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS documents (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    document_type  ENUM('cv','certificate','recommendation','supporting') NOT NULL,
    original_name  VARCHAR(255) NOT NULL,
    file_name      VARCHAR(255) NOT NULL,
    file_path      VARCHAR(500) NOT NULL,
    file_size      INT NOT NULL,
    uploaded_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Jobs (admin-managed, staff-viewable) ─────────────────────
CREATE TABLE IF NOT EXISTS jobs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    department   VARCHAR(100) DEFAULT NULL,
    description  TEXT,
    requirements TEXT,
    deadline     DATE DEFAULT NULL,
    status       ENUM('active','closed') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── Applications ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS applications (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    job_id        INT NOT NULL,
    status        ENUM('pending','under_review','accepted','rejected') NOT NULL DEFAULT 'pending',
    cover_letter  TEXT DEFAULT NULL,
    applied_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_application (user_id, job_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE
);

-- ── Admin Notes ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_notes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    admin_id       INT NOT NULL,
    note           TEXT NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id)       REFERENCES users(id) ON DELETE CASCADE
);

-- ── Default Admin Account ────────────────────────────────────
-- Password: Admin@AUM2026  (change immediately after first login)
-- Generate a new hash with: echo password_hash('YourPassword', PASSWORD_BCRYPT);
INSERT IGNORE INTO users (email, password, role) VALUES (
    'admin@aum.edu.jo',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- ── Sample Job Listings ──────────────────────────────────────
INSERT INTO jobs (title, department, description, requirements, deadline, status) VALUES
(
    'Assistant Professor — Computer Science',
    'Computer Science',
    'We are seeking a motivated and qualified Assistant Professor to join our Computer Science department. The successful candidate will teach undergraduate and graduate courses, conduct research, and contribute to departmental activities.',
    'PhD in Computer Science or related field\nMinimum 2 years of teaching experience\nStrong research background with publications in peer-reviewed journals\nExcellent communication and leadership skills',
    '2026-12-31', 'active'
),
(
    'Lecturer — Mathematics & Statistics',
    'Mathematics',
    'The Department of Mathematics is looking for an experienced Lecturer to teach undergraduate mathematics and statistics courses, including calculus, linear algebra, and probability.',
    'Masters or PhD in Mathematics or Statistics\nTeaching experience at university level\nStrong communication and interpersonal skills\nAbility to work collaboratively in a multicultural environment',
    '2026-11-30', 'active'
),
(
    'Associate Professor — Business Administration',
    'Business Administration',
    'Join our dynamic Business Administration department as an Associate Professor. You will lead courses in management, strategy, and organizational behavior, and contribute to program accreditation efforts.',
    'PhD in Business Administration or related field\nMinimum 5 years academic experience\nStrong record of research publications\nIndustry experience preferred',
    '2026-10-31', 'active'
),
(
    'Assistant Professor — English Language',
    'Languages & Translation',
    'We seek a passionate educator for our English Language program. The role involves curriculum development, teaching ESL and academic writing courses, and supervising student research.',
    'PhD in English, Linguistics, or TESOL\nExperience in curriculum design\nStrong academic writing and research skills\nTESOL/TEFL certification preferred',
    '2026-09-30', 'active'
),
(
    'Research Fellow — Engineering',
    'Engineering',
    'An exciting opportunity for a Research Fellow to join our Engineering faculty. The role focuses on applied research projects and collaboration with industry partners on sustainable technology.',
    'PhD in Engineering (any discipline)\nResearch experience with funding track record\nStrong publication portfolio\nProject management and grant-writing skills',
    '2026-08-31', 'active'
);
