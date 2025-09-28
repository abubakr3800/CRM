-- Database Fixes for CRM System
-- Run these commands to fix identified issues

-- 1. Add file attachments system
CREATE TABLE project_attachments (
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

CREATE TABLE task_attachments (
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

-- 2. Add taxes and discounts to price offers
ALTER TABLE price_offers 
ADD COLUMN tax_rate DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN tax_amount DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN subtotal DECIMAL(10,2) DEFAULT 0.00;

-- 3. Add direct contact-project relationship
CREATE TABLE project_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    contact_id INT,
    role VARCHAR(100) DEFAULT 'Contact',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_contact (project_id, contact_id)
);

-- 4. Add sales pipeline tracking
CREATE TABLE sales_pipeline (
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

-- 5. Add project-specific activity log view
CREATE VIEW project_activity_log AS
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

-- 6. Add indexes for performance
CREATE INDEX idx_project_attachments_project ON project_attachments(project_id);
CREATE INDEX idx_task_attachments_task ON task_attachments(task_id);
CREATE INDEX idx_project_contacts_project ON project_contacts(project_id);
CREATE INDEX idx_project_contacts_contact ON project_contacts(contact_id);
CREATE INDEX idx_sales_pipeline_account ON sales_pipeline(account_id);
CREATE INDEX idx_sales_pipeline_project ON sales_pipeline(project_id);
CREATE INDEX idx_sales_pipeline_stage ON sales_pipeline(stage);

-- 7. Update price offer calculation trigger
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
