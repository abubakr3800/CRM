-- Update database to support enhanced roles
USE shortcircuit_crm;

-- Update users table to support new roles
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'worker', 'viewer') DEFAULT 'worker';

-- Create a sample manager user (password: manager123)
INSERT INTO users (username, email, password, full_name, role) VALUES 
('manager', 'manager@crm.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'Project Manager', 'manager');

-- Create a sample worker user (password: worker123)
INSERT INTO users (username, email, password, full_name, role) VALUES 
('worker', 'worker@crm.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'System Worker', 'worker');

-- Create a sample viewer user (password: viewer123)
INSERT INTO users (username, email, password, full_name, role) VALUES 
('viewer', 'viewer@crm.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'System Viewer', 'viewer');
