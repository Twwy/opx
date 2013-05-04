<?php
class op_object extends model{

	public function objectList(){
		$sql = "SELECT * FROM opx_objects WHERE status = 1";
		return $this->db()->query($sql, 'array');
	}

	public function get($value, $type = 'obj_id'){
		$sqlArray = array(
			'obj_id' => "SELECT * FROM opx_objects WHERE status = 1 AND obj_id = '{$value}'",
			'name' => "SELECT * FROM opx_objects WHERE status = 1 AND name = '{$value}'",
		);
		return $this->db()->query($sqlArray[$type], 'row');
	}

	public function config($value = false){
		if($value == false){
			$sql = "SELECT * FROM opx_config";
			$result = $this->db()->query($sql, 'array');
			$return = array();
			foreach ($result as $key => $value) $return[$value['name']] = $value['value'];
			return $return;
		}else{
			$sql = "SELECT value FROM opx_config WHERE name = '{$value}'";
			$result = $this->db()->query($sql, 'row');
			if(empty($result)) return false;
			return $result['value'];
		}		
	}

	public function configSet($name, $value){
		$updateArray = array('value' => $value);
		return $this->db()->update('opx_config', $updateArray, "name = '{$name}'");	
	}

	public function update($obj_id, $updateArray = array()){
		return $this->db()->update('opx_objects', $updateArray, "obj_id = '{$obj_id}'");
	}

	public function insert($name){
		$sql = "SELECT obj_id FROM opx_objects WHERE status = 0 AND name = '{$name}'";
		$result = $this->db()->query($sql, 'row');
		if(empty($result)){
			$insertArray = array(
				'name' => $name,
				'creat_time' => time()
			);
			$result = $this->db()->insert('opx_objects', $insertArray);
			if($result == 0) return false;
			return $this->db()->insertId();
		}else{
			$insertArray['status'] = 1;
			$update = $this->db()->update('opx_objects', $insertArray, "obj_id = '{$result['obj_id']}'");
			if($update > 0) return $result['obj_id'];
			return false;
		}
	}

}
?>