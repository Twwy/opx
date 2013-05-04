<?php
class op_function extends model{

	public function funcList($obj_id, $name = false){
		if(!$name){		//search for name
			$sql = "SELECT * FROM opx_functions WHERE status = 1 AND obj_id = '{$obj_id}'";
			return $this->db()->query($sql, 'array');
		}else{
			$sql = "SELECT * FROM opx_functions WHERE status = 1 AND obj_id = '{$obj_id}' AND name = '{$name}'";
			return $this->db()->query($sql, 'row');
		}
	}

	public function langList($type = 'name'){
		$sql = "SELECT * FROM opx_langs";
		$result = $this->db()->query($sql, 'array');
		$return = array();
		foreach ($result as $key => $value) {
			if($type == 'name') $return[$value['name']] = $value['lang_id'];
			else $return[$value['lang_id']] = $value['name'];
		}
		return $return;
	}
	
	public function get($value, $type = 'func_id'){
		$sqlArray = array(
			'func_id' => "SELECT * FROM opx_functions WHERE status = 1 AND func_id = '{$value}'",
			'name' => "SELECT * FROM opx_functions WHERE status = 1 AND name = '{$value}'",
		);
		return $this->db()->query($sqlArray[$type], 'row');
	}

	public function update($func_id, $updateArray = array()){
		return $this->db()->update('opx_functions', $updateArray, "func_id = '{$func_id}'");
	}

	public function insert($obj_id, $func, $lang_id = 1){
		$sql = "SELECT func_id FROM opx_functions WHERE status = 0 AND obj_id = '{$obj_id}' AND name = '{$func}'";
		$result = $this->db()->query($sql, 'row');
		$function = '<?php echo \'This is a new Framework!\'; ?>';
		if(empty($result)){
			$insertArray = array(
				'obj_id' => $obj_id, 
				'name' => $func,
				'creat_time' => time(),
				'update_time' => time(),
				'lang_id' => $lang_id,
				'function' => $function
			);
			$result = $this->db()->insert('opx_functions', $insertArray);
			if($result == 0) return false;
			return $this->db()->insertId();
		}else{
			$insertArray['status'] = 1;
			$update = $this->db()->update('opx_functions', $insertArray, "func_id = '{$result['func_id']}'");
			if($update > 0) return $result['func_id'];
			return false;
		}
	}

}
?>