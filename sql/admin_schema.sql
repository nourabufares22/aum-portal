-- ============================================================
-- AUM E-Portal — Admin Schema Extension
-- Run AFTER schema.sql
-- ============================================================

USE aum_portal;

-- Extend application status to include 'under_review'
ALTER TABLE applications
    MODIFY COLUMN status
    ENUM('pending','under_review','accepted','rejected')
    NOT NULL DEFAULT 'pending';

-- Admin notes per application
CREATE TABLE IF NOT EXISTS admin_notes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    admin_id       INT NOT NULL,
    note           TEXT NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id)       REFERENCES users(id) ON DELETE CASCADE
);

-- ── Create a default admin account ──────────────────────────
-- Password: Admin@AUM2026  (change immediately after first login)
INSERT IGNORE INTO users (email, password, role) VALUES (
    'admin@aum.edu.jo',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@AUM2026
    'admin'
);
-- NOTE: The hash above is for the literal string "Admin@AUM2026".
-- Generate your own with: echo password_hash('YourPassword', PASSWORD_BCRYPT);
