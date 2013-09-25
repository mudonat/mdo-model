<?php
class MDO_Example_Model extends MDO_Model_Base{
	protected $tableName = 'example_model';
	protected $primaryKey = 'example_model_id';
	protected $tableFields = array('example_model_id'=>'integer',
								   'example_text'=>'text',
								   'example_active'=>'tinyint',//1,0
								   'example_datetime'=>'datetime');
	public function init(){
		if(!$this->tableExists()){
			$sql = "CREATE TABLE ...";
			
			if(!$this->query($sql))
				throw new Exception("Boom!", 500);
		}
		
		$this->activateLogger(); //optional
	}
	
	public function getRowById($id){
		return $this->selectOneByExampleModelId($id);//magic function
	}
	
	public function updateRow($rowId, array $newData){
		$data = $this->getRowById($rowId);
		$status = false;
		if($data){
			$status = $this->update($rowId, $newData);
			if($status)
				$this->getLogger()->addLog($_SESSION['current_user_idblabla'], $this->getTableName(), $rowId, MDO_Model_Logger::UPDATE, $data, $newData);
			
		}
		return $status;
	}
	
	public function getActiveRows(){
		return $this->selectAllByExampleActive(1);//all rows that has example_active = 1
	}
	
	public function deleteRow($rowId){
		return $this->deleteByExampleModelId($rowId);
	}
	
	public function deleteInactive(){
		return $this->deleteAll(array('example_active'=>0));
	}
	
	public function getQueriedRows($query=''){
		/**
		 * Use selectAll for multiple results and selectOne for single result.
		 */
		return $this->selectAll('SELECT `example_model_id`,`example_text`,`example_active`,`example_datetime` 
									FROM `example_model` 
									WHERE `example_text` LIKE ?', array("%{$query}%"));//generates prepared query
		
	}
}