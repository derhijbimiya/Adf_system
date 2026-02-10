
<?php
/**
 * Database Manager Class
 * Handle master and business database operations
 * Methods for creating, copying, and managing databases
 */

defined('APP_ACCESS') or define('APP_ACCESS', true);

class DatabaseManager {
    private $pdo;
    private $host;
    private $user;
    private $pass;
    
    public function __construct($host = DB_HOST, $user = DB_USER, $pass = DB_PASS) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        
        try {
            // Connect to MySQL without selecting a database
            $this->pdo = new PDO(
                "mysql:host={$this->host}",
                $this->user,
                $this->pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check if database exists
     */
    public function databaseExists($dbName) {
        try {
            $stmt = $this->pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbName}'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Error checking database: " . $e->getMessage());
        }
    }
    
    /**
     * Create new database
     */
    public function createDatabase($dbName) {
        if ($this->databaseExists($dbName)) {
            throw new Exception("Database '{$dbName}' already exists!");
        }
        
        try {
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            return true;
        } catch (PDOException $e) {
            throw new Exception("Error creating database: " . $e->getMessage());
        }
    }
    
    /**
     * Create business database from template
     * Reads template SQL file and executes it in new database
     */
    public function createBusinessDatabase($dbName, $templatePath = null) {
        // Create the database first
        $this->createDatabase($dbName);
        
        // Use default path if not provided
        if (!$templatePath) {
            $templatePath = dirname(dirname(__FILE__)) . '/database/business_template.sql';
        }
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: {$templatePath}");
        }
        
        try {
            // Read template SQL
            $sql = file_get_contents($templatePath);
            
            // Connect to new database
            $dbPdo = new PDO(
                "mysql:host={$this->host};dbname={$dbName}",
                $this->user,
                $this->pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Execute SQL statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement) && strpos($statement, '--') !== 0) {
                    $dbPdo->exec($statement);
                }
            }
            
            return true;
        } catch (PDOException $e) {
            // Clean up - drop database if it failed
            try {
                $this->pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
            } catch (Exception $dropErr) {
                // Silently ignore
            }
            throw new Exception("Error creating business database: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize master database
     */
    public function initializeMasterDatabase($masterDbName = 'adf_system', $templatePath = null) {
        if ($this->databaseExists($masterDbName)) {
            throw new Exception("Master database '{$masterDbName}' already exists!");
        }
        
        if (!$templatePath) {
            $templatePath = dirname(dirname(__FILE__)) . '/database/adf_system_master.sql';
        }
        
        if (!file_exists($templatePath)) {
            throw new Exception("Master template file not found: {$templatePath}");
        }
        
        try {
            // Create database
            $this->createDatabase($masterDbName);
            
            // Read and execute SQL
            $sql = file_get_contents($templatePath);
            
            // Connect to new master database
            $dbPdo = new PDO(
                "mysql:host={$this->host};dbname={$masterDbName}",
                $this->user,
                $this->pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Execute SQL statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement) && strpos($statement, '--') !== 0) {
                    $dbPdo->exec($statement);
                }
            }
            
            return true;
        } catch (PDOException $e) {
            // Clean up
            try {
                $this->pdo->exec("DROP DATABASE IF EXISTS `{$masterDbName}`");
            } catch (Exception $dropErr) {
                // Silently ignore
            }
            throw new Exception("Error initializing master database: " . $e->getMessage());
        }
    }
    
    /**
     * Delete database
     * WARNING: This is destructive!
     */
    public function deleteDatabase($dbName, $confirmDelete = false) {
        if (!$confirmDelete) {
            throw new Exception("Database deletion requires confirmation. Set confirmDelete to true.");
        }
        
        if (!$this->databaseExists($dbName)) {
            throw new Exception("Database '{$dbName}' does not exist!");
        }
        
        try {
            $this->pdo->exec("DROP DATABASE `{$dbName}`");
            return true;
        } catch (PDOException $e) {
            throw new Exception("Error deleting database: " . $e->getMessage());
        }
    }
    
    /**
     * Get database size in MB
     */
    public function getDatabaseSize($dbName) {
        try {
            $stmt = $this->pdo->query(
                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
                 FROM information_schema.TABLES 
                 WHERE table_schema = '{$dbName}'"
            );
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['size_mb'] ?? 0;
        } catch (PDOException $e) {
            throw new Exception("Error getting database size: " . $e->getMessage());
        }
    }
    
    /**
     * Get list of all databases
     */
    public function getAllDatabases() {
        try {
            $stmt = $this->pdo->query("SHOW DATABASES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            throw new Exception("Error getting databases: " . $e->getMessage());
        }
    }
    
    /**
     * Get database statistics
     */
    public function getDatabaseStats($dbName) {
        try {
            $stmt = $this->pdo->query(
                "SELECT 
                    TABLE_NAME,
                    TABLE_ROWS,
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as size_mb
                 FROM information_schema.TABLES 
                 WHERE TABLE_SCHEMA = '{$dbName}'
                 ORDER BY TABLE_NAME"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error getting database stats: " . $e->getMessage());
        }
    }
    
    /**
     * Backup database (dump to SQL file)
     */
    public function backupDatabase($dbName, $outputPath) {
        try {
            $backupFile = $outputPath . '/backup_' . $dbName . '_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Use mysqldump command
            $command = "mysqldump -h {$this->host} -u {$this->user}" . 
                      (!empty($this->pass) ? " -p{$this->pass}" : "") . 
                      " {$dbName} > {$backupFile}";
            
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new Exception("Backup failed with error code: {$returnVar}");
            }
            
            return $backupFile;
        } catch (Exception $e) {
            throw new Exception("Error backing up database: " . $e->getMessage());
        }
    }
    
    /**
     * Restore database from backup file
     */
    public function restoreDatabase($dbName, $backupFile) {
        try {
            if (!file_exists($backupFile)) {
                throw new Exception("Backup file not found: {$backupFile}");
            }
            
            $sql = file_get_contents($backupFile);
            
            // Connect to the database
            $dbPdo = new PDO(
                "mysql:host={$this->host};dbname={$dbName}",
                $this->user,
                $this->pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Execute SQL
            $dbPdo->exec($sql);
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error restoring database: " . $e->getMessage());
        }
    }
    
    /**
     * Get PDO connection to specific database
     */
    public function getConnection($dbName) {
        try {
            return new PDO(
                "mysql:host={$this->host};dbname={$dbName}",
                $this->user,
                $this->pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("Error connecting to database: " . $e->getMessage());
        }
    }
    
    /**
     * Test connection
     */
    public function testConnection($dbName) {
        try {
            $conn = $this->getConnection($dbName);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

?>
