<?php
/**
 * Logger class for MDO_Model_Base
 * You can activate logging option with your model using activateLogger() method.
 * You can expand this logger with your class but to use your logger with MDO_Model_Base, you have to extend your logger class with MDO_Model_Logger.
 * Works like a regular model file.
 * Sql string for log table is below.
 * 
 * Log table structure
 * CREATE TABLE IF NOT EXISTS `logs` (
 * `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 * `user_id` int(11) NOT NULL,
 * `table_name` varchar(255) COLLATE utf8_general_ci NOT NULL,
 * `row_id` int(11) NOT NULL,
 * `action` varchar(20) COLLATE utf8_general_ci NOT NULL,
 * `old_data` text COLLATE utf8_general_ci,
 * `new_data` text COLLATE utf8_general_ci,
 * `applied` datetime NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;
 * 
 * @example $this->getLogger()->addLog($userId, $tableName, $rowId, MDO_Model_Logger::UPDATE, $oldData, $modifiedData);
 * 
 * @package Library
 * @subpackage Model
 * @uses MDO_Model_Base
 * @author Murat Deniz ONAT
 */
class MDO_Model_Logger extends MDO_Model_Base{
	const INSERT = 'insert';
	const UPDATE = 'update';
	const DELETE = 'delete';
	
	protected $primaryKey = 'id';
	
	protected $tableFields = array(	'id'=>'integer',
									'user_id'=>'integer',
									'table_name'=>'string',
									'row_id'=>'integer',
									'action'=>'string',
									'old_data'=>'string',
									'new_data'=>'string',
									'applied'=>'string');
	
	/**
	 * (non-PHPdoc)
	 * @see MDO_Model_Base::init()
	 */
	public function init(){
		if(!$this->tableExists()){
			$sql = "CREATE TABLE IF NOT EXISTS `logs` (
					 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					 `user_id` int(11) NOT NULL,
					 `table_name` varchar(255) COLLATE utf8_general_ci NOT NULL,
					 `row_id` int(11) NOT NULL,
					 `action` varchar(20) COLLATE utf8_general_ci NOT NULL,
					 `old_data` text COLLATE utf8_general_ci,
					 `new_data` text COLLATE utf8_general_ci,
					 `applied` datetime NOT NULL,
					 PRIMARY KEY (`id`)
					 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;";
			if(!$this->query($sql))
				throw new Exception("Mysql error: " . $this->getError(), 500);
		}
	}
	
	/**
	 * Surprisingly, adds log.
	 * @param integer $userId
	 * @param string $tableName
	 * @param integer $rowId
	 * @param string $action
	 * @param mixed $oldData
	 * @param array $newData
	 * @return boolean
	 */
	public function addLog($userId, $tableName, $rowId, $action,$oldData,$newData){
		$isValid = true;
		if($action == MDO_Model_Logger::UPDATE){
			$isValid = false;
			if(!is_array($newData))
				return false;
			foreach($newData as $key=>$value){
				if(isset($oldData[$key]) && $oldData[$key] != $value){
					$isValid = true;
					break;
				}
			}
		}
		
		return $isValid && $this->insert(array(	'user_id'=>$userId,
												'table_name'=>$tableName,
												'row_id'=>$rowId,
												'action'=>$action,
												'old_data'=>serialize($oldData),
												'new_data'=>serialize($newData),
												'applied'=>date('Y-m-d H:i:s')));
	}
	
	/**
	 * Returns all logs
	 * @return array
	 */
	public function getLogs(){
		$fields = implode(',',$this->getFields());
		return $this->selectAll("SELECT {$fields} FROM {$this->getTableName()}");
	}
	
	/**
	 * Gets logs for a specific date
	 * @param string $date Y-m-d formatted date string
	 * @return array
	 */
	public function getLogsByDate($date=null){
		$fields = implode(',',$this->getFields());
		$date = strtotime($date) ? $date : date('Y-m-d'); 
		return $this->selectAll("SELECT {$fields} FROM {$this->getTableName()} WHERE applied = ?",array($date));
	}
	
	/**
	 * Gets logs by a date range
	 * @param string $startDate Y-m-d formatted date string
	 * @param string $endDate Y-m-d formatted date string(optional)
	 * @return array
	 */
	public function getLogsByDateRange($startDate, $endDate=null){
		if(!$startDate || !strtotime($startDate))
			return false;
		
		$fields = implode(',',$this->getFields());
		$startDate = date('Y-m-d',strtotime($startDate));
		$endDate = $endDate ? date('Y-m-d',strtotime($endDate)) : date('Y-m-d');
		return $this->selectAll("SELECT {$fields} FROM {$this->getTableName()} WHERE applied >= ? AND applied <= ?", array($startDate,$endDate));
	}
	
	/**
	 * Gets log by ig
	 * @param integer $id
	 * @return array
	 */
	public function getLogById($id){
		return $this->selectOneById((integer) $id);
	}
	
	/**
	 * Gets logs by given action
	 * @param integer $action
	 * @return array
	 */
	public function getLogsByAction($action){
		return $this->selectAllByAction((string) $action);
	}
	
	/**
	 * Gets logs by table
	 * @param string $tableName
	 * @return array
	 */
	public function getLogsByTable($tableName){
		return $this->selectAllByTableName((string) $tableName);
	}
	
	/**
	 * Gets logs by given user id
	 * @param integer $userId
	 * @return array
	 */
	public function getLogsByUser($userId){
		return $this->selectAllByUserId((integer)$userId);
	}
}
