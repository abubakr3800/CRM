<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=localhost:3309;dbname=shortcircuit_crm;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Setting up Project-Contacts Relationship</h2>";
    
    // Create the project_contacts table for many-to-many relationship
    $sql = "
    CREATE TABLE IF NOT EXISTS project_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        contact_id INT NOT NULL,
        role VARCHAR(100) DEFAULT 'Contact',
        is_primary BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
        UNIQUE KEY unique_project_contact (project_id, contact_id)
    )";
    
    $pdo->exec($sql);
    echo "<p>✅ project_contacts table created successfully</p>";
    
    // Migrate existing contact_id from projects table to project_contacts
    $stmt = $pdo->query("SELECT id, contact_id FROM projects WHERE contact_id IS NOT NULL AND contact_id > 0");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    foreach ($projects as $project) {
        try {
            $insertStmt = $pdo->prepare("INSERT INTO project_contacts (project_id, contact_id, is_primary) VALUES (?, ?, TRUE)");
            $insertStmt->execute([$project['id'], $project['contact_id']]);
            $migrated++;
        } catch (PDOException $e) {
            // Skip if already exists
            if ($e->getCode() != 23000) {
                echo "<p>Error migrating project {$project['id']}: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<p>✅ Migrated $migrated existing project-contact relationships</p>";
    echo "<p><strong>Setup complete!</strong> You can now attach multiple contacts to projects.</p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
