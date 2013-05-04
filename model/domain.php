<?php
class domain extends model{

	public function add($domain, $user_id){
		$insertArray = array(
			'domain_name' => $domain, 
			'user_id' => $user_id,
			'creat_time' => time()
		);
		$result = $this->db()->insert('domain', $insertArray);
		if($result == 0) return false;
		return $this->db()->insertId();
	}

	public function get($value, $type = 'domain_id'){
		$whereArray = array(
			'domain_id' => " domain_id = '{$value}' ",
			'domain' => " domain_name = '{$value}' "
		);
		$sql = "SELECT * FROM domain WHERE {$whereArray[$type]}";
		return $this->db()->query($sql, 'row');
	}

	/*public function all(){
		$sql = "SELECT * FROM domain";
		return $this->db()->query($sql, 'array');
	}*/

	public function update($domain_id, $updateArray){
		return $this->db()->update('domain', $updateArray, "domain_id = '{$domain_id}'");
	}

	public function domainList($user_id, $start, $limit){
		$sql = "SELECT * FROM domain WHERE user_id = '{$user_id}' LIMIT {$start},{$limit}";
		return $this->db()->query($sql, 'array');
	}

	public function domainCount($where){
		$sql = "SELECT count(domain_id) FROM domain WHERE {$where}";
		$result = $this->db()->query($sql, 'row');
		return $result['count(domain_id)'];
	}



}
?>