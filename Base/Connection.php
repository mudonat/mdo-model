<?php
/**
 * https://github.com/mudonat/mdo-model
 * 
 * PDO connection class
 * @package Library
 * @subpackage Model
 * @uses PDO
 * @author Murat Deniz ONAT
 */
class MDO_Model_Base_Connection{
	
	/**
	 * Instance of MDO_Model_Base_Connection
	 * @var MDO_Model_Base_Connection
	 */
	protected static $_instance;
	
	/**
	 * PDO Connection
	 * @var PDO
	 */
	protected $connection;
	
	private $dbParams = array('hostname'=>'localhost',
							  'port'=>'3306',
							  'dbname'=>'test_db',
							  'username'=>'root',
							  'password'=>'');
	
	/**
	 * Insance of class itself
	 * @return MDO_Model_Base_Connection
	 */
	public static function getInstance(){
		if(!self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}
	
	/**
	 * Constructor class
	 * Sets configurations and creates connection
	 */
	public function __construct(){
		//@TODO when there is a better way, change this
		//$this->dbParams = MDO_Registry::get('config')->getDb();
		
		//create connection
		$this->connection = new PDO("mysql:host={$this->dbParams['hostname']};port={$this->dbParams['port']}dbname={$this->dbParams['dbname']}",$this->dbParams['username'],$this->dbParams['password'],array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	}
	
	/**
	 * Returns connection
	 * @return PDO
	 */
	public function getConnection(){
		return $this->connection;
	}
}
