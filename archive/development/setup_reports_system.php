<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=localhost:3309;dbname=shortcircuit_crm;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Setting up Custom Reports System</h2>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "<p>⚠️ Users table not found. Creating reports without user references.</p>";
    } else {
        echo "<p>✅ Users table found</p>";
    }
    
    // Create the custom_reports table (without foreign key constraint for now)
    $sql = "
    CREATE TABLE IF NOT EXISTS custom_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_name VARCHAR(200) NOT NULL,
        description TEXT,
        report_type ENUM('accounts', 'contacts', 'projects', 'price_offers') NOT NULL,
        filters JSON NOT NULL,
        created_by INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_public BOOLEAN DEFAULT FALSE,
        INDEX idx_report_type (report_type),
        INDEX idx_created_by (created_by)
    )";
    
    $pdo->exec($sql);
    echo "<p>✅ custom_reports table created successfully</p>";
    
    // Create some sample reports
    $sampleReports = [
        [
            'report_name' => 'Active Projects',
            'description' => 'All projects that are currently in progress',
            'report_type' => 'projects',
            'filters' => json_encode(['filter_0' => ['field' => 'project_state', 'operator' => 'equals', 'value' => 'Started']]),
            'created_by' => 1,
            'is_public' => true
        ],
        [
            'report_name' => 'Cairo Accounts',
            'description' => 'All accounts located in Cairo',
            'report_type' => 'accounts',
            'filters' => json_encode(['filter_0' => ['field' => 'city', 'operator' => 'equals', 'value' => 'Cairo']]),
            'created_by' => 1,
            'is_public' => true
        ],
        [
            'report_name' => 'Project Managers',
            'description' => 'Contacts with Project Manager job title',
            'report_type' => 'contacts',
            'filters' => json_encode(['filter_0' => ['field' => 'job_title', 'operator' => 'contains', 'value' => 'Manager']]),
            'created_by' => 1,
            'is_public' => true
        ]
    ];
    
    $insertStmt = $pdo->prepare("
        INSERT INTO custom_reports (report_name, description, report_type, filters, created_by, is_public) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $inserted = 0;
    foreach ($sampleReports as $report) {
        try {
            $insertStmt->execute([
                $report['report_name'],
                $report['description'],
                $report['report_type'],
                $report['filters'],
                $report['created_by'],
                $report['is_public']
            ]);
            $inserted++;
        } catch (PDOException $e) {
            // Skip if already exists
            if ($e->getCode() != 23000) {
                echo "<p>Error inserting report '{$report['report_name']}': " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<p>✅ Created $inserted sample reports</p>";
    
    // Check if table was created successfully
    $stmt = $pdo->query("SHOW TABLES LIKE 'custom_reports'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Table verification successful</p>";
    } else {
        echo "<p>❌ Table verification failed</p>";
    }
    
    echo "<p><strong>Reports system setup complete!</strong></p>";
    echo "<p>You can now create custom reports with filtered data views.</p>";
    echo "<p><a href='reports.php' class='btn btn-primary'>Go to Reports Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
