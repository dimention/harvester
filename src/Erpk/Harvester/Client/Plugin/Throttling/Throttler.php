<?php
namespace Erpk\Harvester\Client\Plugin\Throttling;

use PDO;

class Throttler
{
    protected $db;
    protected $prepared = false;
    protected $tableName = 'throttling';
    
    protected $requestsPerPeriod;
    protected $timePeriod;
    
    protected function createTableIfNotExists()
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver == 'mysql') {
            $sql =
                'CREATE TABLE IF NOT EXISTS `'.$this->tableName.'` ( '.
                   '`id` varchar(255) COLLATE utf8_unicode_ci NOT NULL, '.
                   '`timestamp` decimal(15,5) NOT NULL, '.
                   'KEY `timestamp` (`timestamp`), '.
                   'KEY `id_2` (`id`,`timestamp`) '.
                ') ENGINE=MEMORY DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;';
            
            $this->db->exec($sql);
        } elseif ($driver == 'sqlite') {
            $result = $this->db->exec(
                'CREATE TABLE ['.$this->tableName.'] ([id] VARCHAR NOT NULL, [timestamp] DECIMAL NOT NULL);'
            );
            
            if ($result !== false) {
                $this->db->exec(
                    'CREATE INDEX [id-time] ON [throttling] ([id], [timestamp]);'.
                    'CREATE INDEX [time] ON [throttling] ([timestamp]);'
                );
            }
        } else {
            throw new \RuntimeException('Driver '.$driver.' not supported.');
        }
    }
    
    protected function prepareDatabase()
    {
        $this->insertStmt = $this->db->prepare(
            'INSERT INTO '.$this->tableName.' (id, timestamp) VALUES (?, ?)'
        );
        
        $this->cleanStmt = $this->db->prepare(
            'DELETE FROM '.$this->tableName.' WHERE timestamp < ?'
        );
        
        $this->countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM '.$this->tableName.' '.
            'WHERE id = ? AND timestamp >= ?'
        );
        
        $this->prepared = true;
    }
    
    public function __construct(PDO $db, $tableName, $createTableIfNotExists = false)
    {
        $this->db = $db;
        $this->tableName = $tableName;
        
        if ($createTableIfNotExists) {
            $this->createTableIfNotExists();
        }
        $this->prepareDatabase();
    }
    
    public function setMaximumRequestsPerPeriod($count)
    {
        $this->requestsPerPeriod = $count;
    }
    
    public function setTimePeriod($period)
    {
        $this->timePeriod = $period;
    }
    
    public function getCurrentMaximumRequestsPerPeriod($id)
    {
        $this->countStmt->execute(
            array(
                $id,
                microtime(true)-$this->timePeriod
            )
        );
        
        return (int)$this->countStmt->fetch(PDO::FETCH_COLUMN);
    }
    
    public function push($id)
    {
        $this->insertStmt->execute(array($id, microtime(true)));
        $this->clean();
    }
    
    public function isOverloaded($id)
    {
        return $this->getCurrentMaximumRequestsPerPeriod($id) >= $this->requestsPerPeriod;
    }
    
    public function getLoad($id)
    {
        return $this->getCurrentMaximumRequestsPerPeriod($id) / $this->requestsPerPeriod;
    }
    
    public function clean()
    {
        $this->cleanStmt->execute(array(microtime(true)-$this->timePeriod));
    }
}
