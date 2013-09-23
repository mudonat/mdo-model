<?php
/**
 * Basic PDO model class.
 * Configuration parameters set in application.ini like;
 * 
 * [db]
 * resources.db.adapter = "pdo_mysql"
 * resources.db.params.host = "{host}"
 * resources.db.params.username = "{username}"
 * resources.db.params.password = "{password}"
 * resources.db.params.dbname = "{database_name}"
 * 
 * Set tableName and tableFields in your model.
 * tableName is a string with database table name.
 * tableFields is an array with {fieldName}=>{fieldType}
 * If you set tableName, you can use magic functions such as selectOneBy{fieldName}, deleteBy{fieldName}.
 * You can use camel cased field names with magic functions like, selectAllByControllerName('test'). 
 * This will create a sql string with "SELECT {table_fields} FROM {table_name} WHERE controller_name = 'test'" and returns result.
 * 
 * @package Library
 * @subpackage Model
 * @uses PDO
 * @uses MDO_Model_Logger
 * @author Murat Deniz ONAT
 */
class MDO_Model_Base{

	/**
	 * PDO connection object
	 * @var PDO
	 */
	protected $connection;

	/**
	 * @var $sql string
	 */
	protected $sql;

	/**
	 * Table name for model
	 * @var string
	 */
	protected $tableName;
	
	/**
	 * Primary key for table
	 * @var string
	 */
	protected $primaryKey;
	
	/**
	 * Field names of table
	 * @var array
	 */
	protected $tableFields;
	
	/**
	 * Model logger
	 * @var MDO_Model_Logger
	 */
	protected $logger;
	
	/**
	 * Sql errors
	 * @var array
	 */
	protected $error;
	
	/**
	 * Construct of base model.
	 */
	public function __construct(){
		$this->connection = MDO_Model_Base_Connection::getInstance()->getConnection();
		$this->init();
	}

	/**
	 * Empty function for later use
	 */
	public function init(){}
	
	/**
	 * Executes query with given options
	 * @param string $sql sql string
	 * @param array $options sql options(optional)
	 * @param boolean $execute(optional)
	 * @return PDOStatement
	 */
	public function query($sql,$options=array(),$execute=true){
		$statement = $this->connection->prepare($sql);
		$this->sql = $statement->queryString;
		if($execute){
			if(get_magic_quotes_gpc()){
				$options = array_map('stripslashes', $options);
			}
			if(!$statement->execute($options)){
				$this->error = $statement->errorInfo();
				return false;
			}
		}
		return $statement;
	}

	/**
	 * Fetchs all rows 
	 * @param PDOStatement $statement
	 * @param integer $type fetch type(optional)
	 * @throws Exception
	 * @return array query result
	 */
	public function fetchAll($statement, $type=PDO::FETCH_ASSOC){
		if($statement instanceof PDOStatement){
			if((integer)$statement->errorCode()){
				$errorInfo = $statement->errorInfo();
				throw new Exception($errorInfo[2]);
			}
			return $statement->fetchAll($type);
		}else{
			throw new Exception("Wrong query: {$this->getSql()}\n {$this->getError()}");
		}
	}

	/**
	 * Fetchs single row
	 * @param PDOStatement $statement
	 * @param integer $type fetch type(optional)
	 * @throws Exception
	 * @return array query result
	 */
	public function fetch(PDOStatement $statement,$type=PDO::FETCH_ASSOC){
		if((integer)$statement->errorCode()){
			$errorInfo = $statement->errorInfo();
			throw new Exception($errorInfo[2]);
		}
		return $statement->fetch($type);
	}

	/**
	 * Runs a limit 1 select query and returns data
	 * @param string $sql sql string
	 * @param array $options sql options(optional)
	 * @param integer $type fetch type(optional)
	 * @throws Exception
	 * @return array query result
	 */
	public function selectOne($sql, $options=array(), $type=PDO::FETCH_ASSOC){
		$stmt = $this->query($sql, $options);
		if($stmt instanceof PDOStatement){
			return $this->fetch($stmt, $type);
		}
		throw new Exception("Wrong query: {$this->getSql()}\n {$this->getError()}");
	}

	/**
	 * Runs a select query and returns data
	 * @param string $sql sql string
	 * @param array $options sql options
	 * @param integer $type fetch type
	 * @return array query result
	 */
	public function selectAll($sql, $options=array(), $type=PDO::FETCH_ASSOC){
		$stmt = $this->query($sql, $options);
		return $this->fetchAll($stmt, $type);
	}

	/**
	 * Inserts a record to table
	 * @param array $queryOptions query options
	 * @param string $tableName table name for query(optional)
	 * @throws Exception
	 * @return boolean
	 */
	public function insert($queryOptions,$tableName=null){
		$tableName = $tableName === null ? $this->tableName : $tableName;
		if(!$tableName){
			throw new Exception('Table name has to be specified');
		}

		if(is_array($queryOptions) && count($queryOptions)>0){
			$keys = implode(',',array_keys($queryOptions));
			$valuePlaces = implode(',',array_fill(0,count($queryOptions),'?'));
			$values = array_values($queryOptions);
			$query = "INSERT INTO `{$tableName}` ({$keys}) VALUES ({$valuePlaces})";
				
			if($this->query($query, $values)){
				return $this->getLastInsertId();
			}
			return false;
		}else{
			throw new Exception('Query options has to be key value array');
		}
	}
	
	/**
	 * Inserts multiple rows with transaction
	 * @param array $queryOptions Array of query options arrays
	 * @param string $tableName Database table name(optional)
	 * @throws Exception
	 * return boolean Status
	 */
	public function insertAll(array $queryOptions,$tableName=null){
		$tableName = $tableName === null ? $this->tableName : $tableName;
		if(!$tableName){
			throw new Exception('Table name has to be specified');
		}
		
		if(sizeof($queryOptions)>0){
			$this->connection->beginTransaction();
			foreach ($queryOptions as $qo){
				if(!is_array($qo)) continue;
				$keys = implode(',',array_keys($qo));
				$values = array_values($qo);
				$valuePlaces = implode(',',array_fill(0,count($qo),'?'));
				$query = "INSERT INTO `{$tableName}` ({$keys}) VALUES ({$valuePlaces})";
				$this->query($query, $values);
			}
			return $this->connection->commit();
		}else{
			throw new Exception('Query options has to be an array of arrays');
		}
	}
	
	/**
	 * Deletes all records from database with given parameters
	 * @param array $params Delete parameters
	 * @param string $tableName Database table name(optional)
	 * @throws Exception
	 * @return boolean Status
	 */
	public function deleteAll(array $params,$tableName=null){
		$tableName = $tableName === null ? $this->tableName : $tableName;
		if(!$tableName){
			throw new Exception('Table name has to be specified');
		}
		
		$whereKeys = array();
		foreach ($params as $key=>$value){
			$whereKeys[] = "`{$key}`=?";
		}
		
		$qWhere = '';
		if(sizeof($whereKeys)){
			$qWhere = " WHERE " . implode(' AND ',$whereKeys);
		}
		
		$this->sql = "DELETE FROM `{$this->tableName}`" . $qWhere;
		if($this->query($this->sql,array_values($params))){
			return true;
		}
		return false;
	}
	
	/**
	 * Updates single row with given id
	 * @param integer $id row id
	 * @param array $modifiedData modified data array
	 * @param string $tableName table name(optional)
	 * @throws Exception
	 * @return boolean
	 */
	public function update($id,$modifiedData,$tableName=null){
		$tableName = $tableName === null ? $this->tableName : $tableName;
		if(!$tableName){
			throw new Exception('Table name has to be specified');
		}

		if((integer)$id > 0){
			$data = $this->{'selectOneBy' . $this->primaryKey}((integer)$id);
			if($data){
				if(is_array($modifiedData)){
					$updateKeys = $updateValues = $sqlParts = array();
					if($modifiedData){
						foreach ($modifiedData as $mdKey => $mdValue){
							$updateKeys[] = "{$mdKey} = ?";
							$updateValues[] = $mdValue;
						}
						if(array_key_exists('modified', $data)){
							$updateKeys[] = "modified = ?";
							$updateValues[] = date('Y-m-d H:i:s');
						}
						$this->sql = "UPDATE `{$tableName}` SET " . implode(',', $updateKeys) . " WHERE {$this->primaryKey} = ?";
						$updateValues[] = $data[$this->primaryKey];

						if($this->query($this->sql, $updateValues) !== false){
							return true;
						}
					}
					return false;
				}else{
					throw new Exception('Modified data has to be key value array');
				}
			}
		}
	}

	/**
	 * Delete row(s)
	 * @param string $field table field
	 * @param string $type one|all
	 * @param string $value field value
	 * @return boolean status
	 */
	protected function deleteBy_($field, $value){
		if(!$this->tableName){
			throw new Exception('Table name has to be specified');
		}
		if($field && $value){
			$query = "DELETE FROM `{$this->tableName}` WHERE {$field}= ?";
			if($this->query($query,$value) !== false){
				return true;
			}
			return false;
		}else{
			throw new Exception("{$field} is not valid");
		}
	}

	/**
	 * Gets sql string
	 * @return string
	 */
	public function getSql(){
		return $this->sql;
	}

	/**
	 * Call function for selectAllBy{field name}, selectOneBy{field name}, deleteOneBy{field name}
	 * @param string $name method name 
	 * @param array $args method arguments
	 * @return array query result
	 */
	public function __call($name,$args){
		switch (true){
			case stripos($name, 'selectAllBy') === 0:
				$field = implode('_',$this->explodeCase(substr($name,strlen('selectAllBy'))));
				return $this->selectBy_('all',$field,$args);
				break;
			case stripos($name, 'selectOneBy') === 0:
				$field = implode('_',$this->explodeCase(substr($name,strlen('selectOneBy'))));
				return $this->selectBy_('one',$field,$args);
				break;
			case stripos($name, 'deleteBy') === 0:
				$field = implode('_',$this->explodeCase(substr($name,strlen('deleteBy'))));
				return $this->deleteBy_($field,$args);
				break;
			default:
				throw new Exception("Method MDO_Model_Base::{$name} does not exists.");
				break;
		}
	}

	/**
	 * SelectAllBy{field name} and SelectOneBy{field name} uses this method
	 * Internal use only
	 * @param string $type all|one execution type
	 * @param string $field field name
	 * @param array $values query options
	 * @throws Exception
	 * @return array query result
	 */
	protected function selectBy_($type,$field,$values){
		if(isset($this->tableName)){
			if($type === 'all' || $type === 'one'){
				$queryWhere = '';
				$queryValue = null;
				if($values){
					$queryWhere = "WHERE {$field} = ?";
					$queryValue = current($values);
				}
				$fields = $this->getFields() ? implode(',',$this->getFields()) : '*'; 
				$query = "SELECT {$fields} FROM `{$this->tableName}`";
				$query = implode(' ',array($query,$queryWhere));
				$options = $queryValue !== null ? array($queryValue) : array();
				return $this->{'select' . $type}($query, $options);
			}else{
				throw new Exception('Wrong query parameter');
			}
		}else{
			throw new Exception('define $tableName on model');
		}
	}

	/**
	 * Splits up a string into an array similar to the explode() function but according to CamelCase.
	 * Uppercase characters are treated as the separator but returned as part of the respective array elements.
	 * @author Charl van Niekerk <charlvn@charlvn.za.net>
	 * @param string $string The original string
	 * @param bool $lower Should the uppercase characters be converted to lowercase in the resulting array?
	 * @return array The given string split up into an array according to the case of the individual characters.
	 */
	protected function explodeCase($string, $lower = true){
		// Split up the string into an array according to the uppercase characters
		$array = preg_split('/([A-Z][^A-Z]*)/', $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		// Convert all the array elements to lowercase if desired
		if ($lower) {
			$array = array_map('strtolower', $array);
		}

		// Return the resulting array
		return $array;
	}
	
	/**
	 * Gets last inserted id
	 * @return integer
	 */
	public function getLastInsertId(){
		return $this->connection->lastInsertId();
	}
	
	/**
	 * Sets table name for model
	 * @param string $tableName table name
	 */
	public function setTable($tableName){
		$this->tableName = (string) $tableName;
	}
	
	/**
	 * Gets table name of model
	 * @return string  table name
	 */
	public function getTableName(){
		return $this->tableName;
	}
	
	/**
	 * Gets field names of table
	 * @return array
	 */
	public function getFields(){
		if(is_array($this->tableFields)){
			return array_keys($this->tableFields);
		}
		return array();
	}
	
	/**
	 * Gets table fields.
	 * @return array
	 */
	public function getTableFields(){
		return $this->tableFields;
	}
	
	/**
	 * Activates logging options
	 * @param string $tableName log table name. if it isnot set, log table name is `logs`(optional)
	 * @param string $loggerClass logger class name. if it isnot set, logger class name is MDO_Model_Logger(optional)
	 * @throws Exception
	 */
	public function activateLogger($tableName='logs', $loggerClass='MDO_Model_Logger'){
		//Set logger to object
		$logger = new $loggerClass();
		if($logger instanceof MDO_Model_Logger){
		$this->logger = $logger;
		
		//Set log table name
		$this->logger->setTable($tableName);
		}else{
			throw new Exception('Logger class must be an instance of MDO_Model_Logger');
		}
	}
	
	/**
	 * Gets logger object
	 * @return MDO_Model_Logger
	 */
	public function getLogger(){
		return $this->logger;
	}
	
	/**
	 * Error list as string. Use for lazyness
	 * @return string
	 */
	public function getError(){
		return implode(',',$this->error);
	}
	
	/**
	 * Checks if current or the given table exists in database.
	 * @param string $tableName (optional)
	 * @return boolean
	 */
	public function tableExists($tableName=null){
		$tableName = $tableName ? $tableName : $this->tableName;
		if(!$this->query("DESCRIBE {$tableName}") && $this->error[1] == 1146)
			return false;
		return true;
	}
}
