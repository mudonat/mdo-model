<?php

/** 
 * https://github.com/mudonat/mdo-model
 * 
 * Single table based translation class
 * 
 * Table structure for table `translations`
 * 
 * CREATE TABLE IF NOT EXISTS `translations` (
 * `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 * `language_key` varchar(10) COLLATE utf8_general_ci NOT NULL,
 * `reference_table` varchar(255) COLLATE utf8_general_ci NOT NULL,
 * `reference_id` int(11) NOT NULL,
 * `field_name` varchar(255) COLLATE utf8_general_ci NOT NULL,
 * `field_value` text COLLATE utf8_general_ci NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;
 * 
 * @package Library
 * @subpackage Model
 * @uses MDO_Model_Base
 * @author Murat Deniz ONAT
 *
 */
class MDO_Model_Translations extends MDO_Model_Base{
	protected $tableName = 'translations';
	protected $primaryKey = 'id';
	protected $tableFields = array('id'=>'integer',
								   'language_key','string',
								   'reference_table'=>'string',
								   'reference_id'=>'integer',
								   'field_name'=>'string',
								   'field_value'=>'string');
	private $translateFields = array();
	
	/**
	 * @var MDO_Model_Translations
	 */
	private static $_instance;
	
	/**
	 * @return MDO_Model_Translations
	 */
	public static function getInstance(){
		if(!self::$_instance)
			self::$_instance = new self();
		
		return self::$_instance;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see MDO_Model_Base::init()
	 */
	public function init(){
		if(!$this->tableExists()){
			$sql = "CREATE TABLE IF NOT EXISTS `translations` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`language_key` varchar(10) COLLATE utf8_general_ci NOT NULL,
					`reference_table` varchar(255) COLLATE utf8_general_ci NOT NULL,
					`reference_id` int(11) NOT NULL,
					`field_name` varchar(255) COLLATE utf8_general_ci NOT NULL,
					`field_value` text COLLATE utf8_general_ci NOT NULL,
					PRIMARY KEY (`id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1 ;";
			if(!$this->query($sql))
				throw new Exception("Mysql error: " . $this->getError(), 500);
		}
	}
	
	/**
	 * Sets fields to translate
	 * @param array $fields
	 * @param boolean $reset Reset fields(optional)
	 * @return MDO_Model_Translations
	 */
	public function setTranslateFields(array $fields,$reset=true){
		if(true === $reset)
			$this->clearTranslateFields();
		
		foreach($fields as $field)
			$this->addTranslateField($field);
		
		return $this;
	}
	
	/**
	 * Resets translate fields
	 */
	public function clearTranslateFields(){
		$this->translateFields = array();
	}
	
	/**
	 * Gets translate fields
	 * @return array
	 */
	public function getTranslateFields(){
		return $this->translateFields;
	}
	
	/**
	 * Inserts translate field
	 * @param string $field
	 * @return MDO_Model_Translations
	 */
	public function addTranslateField($field){
		if(!in_array($field,$this->translateFields))
			$this->translateFields[] = $field;
		return $this;
	}
	
	/**
	 * Gets translation of given data
	 * @param string $languageKey
	 * @param string $referenceTable
	 * @param integer $referenceId
	 * @return array
	 */
	public function getTranslation($languageKey,$referenceTable,$referenceId){
		$sql = "SELECT `field_name`,`field_value` FROM `translations` WHERE `language_key`=? AND `reference_table`=? AND reference_id=?";
		$options = array($languageKey,$referenceTable,$referenceId);
		$results = $this->selectAll($sql,$options);
		$arrResults = array();
		if($results){
			$arrResults['language_key'] = $languageKey;
			foreach($results as $result)
				$arrResults[$result['field_name']] = $result['field_value'];
			
			$results = $arrResults;
		}
		return $results;
	}
	
	/**
	 * Inserts translation data to database
	 * @param string $languageKey
	 * @param string $referenceTable
	 * @param integer $referenceId
	 * @param array $params
	 * @return boolean status
	 */
	public function addTranslation($languageKey,$referenceTable,$referenceId,array $params){
		if($this->translateFields){
			$data = array();
			foreach($this->translateFields as $field){
				if(isset($params[$field]))
					$data[] = array('language_key'=>$languageKey,
									'reference_table'=>$referenceTable,
									'reference_id'=>$referenceId,
									'field_name'=>$field,
									'field_value'=>$params[$field]);
				
			}
			
			if($data && $this->insertAll($data))
				return true;
		}
		return false;
	}
	
	/**
	 * Deletes translation
	 * @param string $languageKey
	 * @param string $referenceTable
	 * @param integer $referenceId
	 * @return boolean
	 */
	public function deleteTranslation($languageKey,$referenceTable,$referenceId){
		$sql = "SELECT id FROM `{$this->tableName}` WHERE language_key=? AND reference_table=? AND reference_id=?";
		if($this->selectAll($sql,array($languageKey,$referenceTable,$referenceId)))
			if($this->deleteAll(array('language_key'=>$languageKey,'reference_table'=>$referenceTable,'reference_id'=>$referenceId)))
				return true;
		return false;
	}
}
