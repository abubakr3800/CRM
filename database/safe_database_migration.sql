-- SAFE DATABASE MIGRATION SCRIPT
-- This script adds new features WITHOUT causing data loss
-- Run this on your existing database to add missing features

-- ==============================================
-- STEP 1: BACKUP YOUR DATABASE FIRST!
-- ==============================================
-- mysqldump -u username -p database_name > backup_before_migration.sql

-- ==============================================
-- STEP 2: ADD NEW TABLES (NO DATA LOSS)
-- ==============================================

-- 1. Add file attachments system (NEW TABLES - NO DATA LOSS)
CREATE TABLE IF NOT EXISTS project_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(100),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS task_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(100),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 2. Add direct contact-project relationship (NEW TABLE - NO DATA LOSS)
CREATE TABLE IF NOT EXISTS project_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    contact_id INT,
    role VARCHAR(100) DEFAULT 'Contact',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_contact (project_id, contact_id)
);

-- 3. Add sales pipeline tracking (NEW TABLE - NO DATA LOSS)
CREATE TABLE IF NOT EXISTS sales_pipeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT,
    project_id INT,
    stage ENUM('Lead', 'Qualified', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost') DEFAULT 'Lead',
    probability DECIMAL(5,2) DEFAULT 0.00,
    expected_value DECIMAL(10,2),
    expected_close_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- ==============================================
-- STEP 3: ADD NEW COLUMNS TO EXISTING TABLES
-- ==============================================

-- Add tax and discount fields to price_offers (SAFE - ADDS COLUMNS WITH DEFAULTS)
ALTER TABLE price_offers 
ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS discount_percentage DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) DEFAULT 0.00;

-- ==============================================
-- STEP 4: CREATE VIEWS (NO DATA LOSS)
-- ==============================================

-- Create project-specific activity log view
CREATE OR REPLACE VIEW project_activity_log AS
SELECT 
    al.id,
    al.user_id,
    al.action,
    al.table_name,
    al.record_id,
    al.old_values,
    al.new_values,
    al.created_at,
    u.full_name as user_name,
    CASE 
        WHEN al.table_name = 'projects' THEN al.record_id
        WHEN al.table_name = 'tasks' THEN t.project_id
        WHEN al.table_name = 'price_offers' THEN po.project_id
        ELSE NULL
    END as project_id
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.id
LEFT JOIN tasks t ON al.table_name = 'tasks' AND al.record_id = t.id
LEFT JOIN price_offers po ON al.table_name = 'price_offers' AND al.record_id = po.id
WHERE al.table_name IN ('projects', 'tasks', 'price_offers');

-- ==============================================
-- STEP 5: ADD INDEXES (NO DATA LOSS)
-- ==============================================

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_project_attachments_project ON project_attachments(project_id);
CREATE INDEX IF NOT EXISTS idx_task_attachments_task ON task_attachments(task_id);
CREATE INDEX IF NOT EXISTS idx_project_contacts_project ON project_contacts(project_id);
CREATE INDEX IF NOT EXISTS idx_project_contacts_contact ON project_contacts(contact_id);
CREATE INDEX IF NOT EXISTS idx_sales_pipeline_account ON sales_pipeline(account_id);
CREATE INDEX IF NOT EXISTS idx_sales_pipeline_project ON sales_pipeline(project_id);
CREATE INDEX IF NOT EXISTS idx_sales_pipeline_stage ON sales_pipeline(stage);

-- ==============================================
-- STEP 6: UPDATE EXISTING DATA (SAFE)
-- ==============================================

-- Update existing price offers to calculate subtotals
UPDATE price_offers po 
SET subtotal = (
    SELECT COALESCE(SUM(subtotal), 0) 
    FROM price_offer_items poi 
    WHERE poi.offer_id = po.id
)
WHERE subtotal = 0 OR subtotal IS NULL;

-- Update existing price offers to calculate tax amounts
UPDATE price_offers 
SET tax_amount = subtotal * (tax_rate / 100)
WHERE tax_amount = 0 OR tax_amount IS NULL;

-- Update existing price offers to calculate total amounts
UPDATE price_offers 
SET total_amount = subtotal + tax_amount - discount_amount
WHERE total_amount = 0 OR total_amount IS NULL;

-- ==============================================
-- STEP 7: CREATE TRIGGERS (NO DATA LOSS)
-- ==============================================

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS update_price_offer_totals;
DROP TRIGGER IF EXISTS update_price_offer_totals_update;
DROP TRIGGER IF EXISTS update_price_offer_totals_delete;

-- Create triggers for automatic calculations
DELIMITER //
CREATE TRIGGER update_price_offer_totals 
AFTER INSERT ON price_offer_items
FOR EACH ROW
BEGIN
    UPDATE price_offers 
    SET 
        subtotal = (
            SELECT COALESCE(SUM(subtotal), 0) 
            FROM price_offer_items 
            WHERE offer_id = NEW.offer_id
        ),
        tax_amount = subtotal * (tax_rate / 100),
        total_amount = subtotal + tax_amount - discount_amount
    WHERE id = NEW.offer_id;
END//

CREATE TRIGGER update_price_offer_totals_update 
AFTER UPDATE ON price_offer_items
FOR EACH ROW
BEGIN
    UPDATE price_offers 
    SET 
        subtotal = (
            SELECT COALESCE(SUM(subtotal), 0) 
            FROM price_offer_items 
            WHERE offer_id = NEW.offer_id
        ),
        tax_amount = subtotal * (tax_rate / 100),
        total_amount = subtotal + tax_amount - discount_amount
    WHERE id = NEW.offer_id;
END//

CREATE TRIGGER update_price_offer_totals_delete 
AFTER DELETE ON price_offer_items
FOR EACH ROW
BEGIN
    UPDATE price_offers 
    SET 
        subtotal = (
            SELECT COALESCE(SUM(subtotal), 0) 
            FROM price_offer_items 
            WHERE offer_id = OLD.offer_id
        ),
        tax_amount = subtotal * (tax_rate / 100),
        total_amount = subtotal + tax_amount - discount_amount
    WHERE id = OLD.offer_id;
END//
DELIMITER ;

-- ==============================================
-- MIGRATION COMPLETE!
-- ==============================================

-- Verify the migration
SELECT 'Migration completed successfully!' as status;
SELECT COUNT(*) as existing_accounts FROM accounts;
SELECT COUNT(*) as existing_projects FROM projects;
SELECT COUNT(*) as existing_tasks FROM tasks;
SELECT COUNT(*) as existing_contacts FROM contacts;
SELECT COUNT(*) as existing_price_offers FROM price_offers;
