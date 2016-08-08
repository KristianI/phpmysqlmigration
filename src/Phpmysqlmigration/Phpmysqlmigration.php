<?php

namespace Phpmysqlmigration;

include_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Run mysql-migrations from folder
 *
 * @author Jens Just Iversen
 */
class Phpmysqlmigration {
    
    /**
     * The table name for logging migrations
     * 
     * @var string
     */
    public $dbTableName = 'migration_log';
    
    /**
     * The directory where the SQL-files are stored
     * @var string
     */
    public $directory = '';
    
    /**
     * Instance of PDO
     * @var object
     */
    public $pdo;
    
    /**
     * Instance of FluentPDO
     * 
     * @var object
     */
    public $fpdo;
    
    /**
     * Instance of \Phpmigration\SqlParser
     * 
     * @var object
     */
    public $sqlParser;
    
    /**
     * Constructor, where the dependencyinjection is made
     * 
     * @param object $sqlParser
     */
    public function __construct($sqlParser = false) {
        
        $this->sqlParser = $sqlParser ? $sqlParser : new \Phpmysqlmigration\SqlParser();
        
    }
    
    /**
     * Start running new .sql-files from $directory
     * 
     * @param string $directory Directory in which the .sql-files are located
     * @param array $mysqlConnectionData array('host' => '', 'port' => '', 'username' => '', 'password' => '', 'database' => '')
     * @param boolean $reset Default false. No migration, but write to log (mark everything up-to-date).
     * @return string
     */
    public static function start($directory, $mysqlConnectionData, $reset = false) {
        
        $self = new self();
        $self->initMysqlConnection($mysqlConnectionData);
        $self->directory = $directory . (!in_array(substr($directory, -1, 1), array('/','\'')) ? '/' : '' );
        
        $self->checkMigrationTableExistence();
        
        $newFiles = $self->getNewFiles();
        $self->migrateFiles($newFiles, $reset);
        
        return count($newFiles) . ' new files migrated.';
        
    }
    
    public static function reset($directory, $mysqlConnectionData) {
        
        return self::start($directory, $mysqlConnectionData, true);
        
    }
    
    /**
     * Initialize FluentPDO connection
     * 
     * @param array $mysqlConnectionData
     * @return object FLuentPDO instance
     */
    public function initMysqlConnection($mysqlConnectionData) {
        
        if (!empty($mysqlConnectionData['port'])) {
            $port = $mysqlConnectionData['port'];
        } else {
            $port = '3306';
        }
        
        $pdo = new \PDO("mysql:host=" . $mysqlConnectionData['host'] . ";port=" . $port . ";charset=UTF8;dbname=" . $mysqlConnectionData['database'], $mysqlConnectionData['username'], $mysqlConnectionData['password']);
        $fpdo = new \FluentPDO($pdo);
        $this->pdo = $pdo;
        $this->fpdo = $fpdo;
        return $fpdo;
        
    }
    
    /**
     * Create migration table if not exists
     * 
     */
    public function checkMigrationTableExistence() {
        
        $sql = "CREATE TABLE IF NOT EXISTS " . $this->dbTableName . " (
        ID INT( 11 ) AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR( 250 ) NOT NULL, 
        migrated_at_timestamp int(11) NOT NULL);";
        $this->pdo->exec($sql);
    }
    
    /**
     * Get files that aren't migrated yet
     * 
     * @return array files
     */
    public function getNewFiles() {
        
        $allFiles = $this->getFiles();
        $newFiles = array();
        
        foreach ((array) $allFiles as $file) {
            $query = $this->fpdo->from($this->dbTableName)->where('filename', $file);
            $rows = $query->fetchAll();
            
            if (count($query) === 0) {
                $newFiles[] = $file;
            }
        }
        sort($newFiles);
        
        return $newFiles;
    }
    
    /**
     * Get filenames from directory
     * 
     * @return type
     */
    public function getFiles() {
        
        $files = array();
        
        if ($handle = opendir($this->directory)) {
            while (false !== ($entry = readdir($handle))) {
                if (in_array($entry, array('.', '..')) || is_dir($this->directory.$entry) ) {
                    continue;
                }
                $files[] = $entry;
            }

            closedir($handle);
        }
        sort($files);
        
        return $files;
    }
    
    /**
     * Migrate files
     * 
     * @param array $files
     * @param boolean $onlyLog Default false
     * @return boolean
     */
    public function migrateFiles($files, $onlyLog = false) {
        foreach ((array) $files as $file) {
            if (!$onlyLog) { 
                $sql = file_get_contents($this->directory . $file);
                $sqls = $this->parseSql($sql);
                foreach ((array)$sqls as $sql) {
                    if (!$res = $this->pdo->exec($sql)) {
                        $error = $this->pdo->errorInfo();
                        if (!($error[0] === '00000' || $error[0] === '01000')) {
                            echo 'Error with ' . $file . ': ' . $error[2] . "\n\r";
                        }
                    }
                }
            }
            $this->addToMigrationLog($file);
        }
    }
    
    /**
     * Parse SQL file (remove comments, and split by each statement)
     * 
     * @param string $sql
     * @return array Array with statements
     */
    public function parseSql($sql) {
        
        $sql = $this->sqlParser->removeRemarks($sql);
        $sqls = $this->sqlParser->splitSqlFile($sql);
        
        return $sqls;
    }
    
    /**
     * Add file to timestamp
     * 
     * @param type $filename
     */
    public function addToMigrationLog($filename) {
        $this->fpdo->insertInto($this->dbTableName)->values(array('filename' => $filename, 'migrated_at_timestamp' => time()))->execute();
    }
    
}
