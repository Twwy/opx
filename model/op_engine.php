<?php

class op_engine extends model{
	public function all(){
		$sql = "SELECT * FROM opx_engines";
		return $this->db()->query($sql, 'array');
	}

	public function get($value){
		$sql = "SELECT * FROM opx_engines WHERE status = 1 AND eng_id = '{$value}'";
		return $this->db()->query($sql, 'row');
	}

}



?>