<?php
require_once 'Database.php';
require_once 'Logger.php';

class Backup {
    private $db;
    private $logger;
    private $backupPath;
    
    public function __construct() {
        $this->db = new Database();
        $this->logger = new Logger();
        $this->backupPath = BACKUP_PATH;
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }
    
    public function createBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $filepath = $this->backupPath . $filename;
            
            // Get database connection details
            $host = DB_HOST;
            $username = DB_USER;
            $password = DB_PASS;
            $database = DB_NAME;
            
            // Create mysqldump command
            $command = "mysqldump --host={$host} --user={$username} --password={$password} {$database} > {$filepath}";
            
            // Execute backup command
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($filepath)) {
                // Compress the backup file
                $this->compressBackup($filepath);
                
                // Update last backup setting
                $this->db->update('system_settings', 
                    ['setting_value' => date('Y-m-d H:i:s')], 
                    'setting_key = ?', 
                    ['last_backup']
                );
                
                $this->logger->info("Database backup created successfully", ['filename' => $filename]);
                
                // Clean old backups
                $this->cleanOldBackups();
                
                return $filename;
            } else {
                throw new Exception("Backup command failed with return code: {$returnCode}");
            }
            
        } catch (Exception $e) {
            $this->logger->error("Backup creation failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function compressBackup($filepath) {
        $compressedFile = $filepath . '.gz';
        
        $fp_out = gzopen($compressedFile, 'wb9');
        $fp_in = fopen($filepath, 'rb');
        
        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 1024 * 512));
        }
        
        fclose($fp_in);
        gzclose($fp_out);
        
        // Remove original file
        unlink($filepath);
    }
    
    public function restoreBackup($filename) {
        try {
            $filepath = $this->backupPath . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception("Backup file not found: {$filename}");
            }
            
            // Decompress if it's a .gz file
            if (substr($filename, -3) === '.gz') {
                $decompressedFile = $this->decompressBackup($filepath);
                $filepath = $decompressedFile;
            }
            
            // Read and execute SQL file
            $sql = file_get_contents($filepath);
            $statements = explode(';', $sql);
            
            $this->db->beginTransaction();
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $this->db->query($statement);
                }
            }
            
            $this->db->commit();
            
            $this->logger->info("Database restored from backup", ['filename' => $filename]);
            
            // Clean up decompressed file if it was created
            if (isset($decompressedFile) && $decompressedFile !== $filepath) {
                unlink($decompressedFile);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->logger->error("Backup restore failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function decompressBackup($filepath) {
        $decompressedFile = str_replace('.gz', '', $filepath);
        
        $fp_in = gzopen($filepath, 'rb');
        $fp_out = fopen($decompressedFile, 'wb');
        
        while (!gzeof($fp_in)) {
            fwrite($fp_out, gzread($fp_in, 1024 * 512));
        }
        
        gzclose($fp_in);
        fclose($fp_out);
        
        return $decompressedFile;
    }
    
    public function cleanOldBackups() {
        try {
            $files = glob($this->backupPath . 'backup_*.sql.gz');
            $retentionDays = BACKUP_RETENTION_DAYS;
            $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                    $this->logger->info("Old backup file deleted", ['filename' => basename($file)]);
                }
            }
            
        } catch (Exception $e) {
            $this->logger->error("Failed to clean old backups", ['error' => $e->getMessage()]);
        }
    }
    
    public function getBackupList() {
        $files = glob($this->backupPath . 'backup_*.sql.gz');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by creation time (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });
        
        return $backups;
    }
    
    public function scheduleWeeklyBackup() {
        // This would typically be called by a cron job
        $lastBackup = $this->db->fetchOne(
            "SELECT setting_value FROM system_settings WHERE setting_key = 'last_backup'"
        );
        
        $lastBackupTime = $lastBackup ? strtotime($lastBackup['setting_value']) : 0;
        $weekAgo = time() - (7 * 24 * 60 * 60);
        
        if ($lastBackupTime < $weekAgo) {
            $this->createBackup();
        }
    }
}
?>
