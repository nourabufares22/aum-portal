-- ============================================================
-- AUM E-Portal — Migration v2
-- Run this against an existing aum_portal database to apply
-- the changes introduced with the Admin / Job Management update.
-- Safe to run multiple times (uses IF NOT EXISTS / IF EXISTS).
-- ============================================================

USE aum_portal;

-- 1. Add admin role to users if not already present
-- (role column already has 'admin' in the original schema — no change needed)

-- 2. Extend applications status enum to include under_review
ALTER TABLE applications
    MODIFY COLUMN status
        ENUM('pending','under_review','accepted','rejected')
        NOT NULL DEFAULT 'pending';

-- 3. Create admin_notes table (used by application_view.php)
CREATE TABLE IF NOT EXISTS admin_notes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    application_id  INT NOT NULL,
    admin_id        INT NOT NULL,
    note            TEXT NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id)       REFERENCES users(id)        ON DELETE CASCADE
);
