<?php

//-------config-------------------------//
$dbHost = 'localhost';
$dbName = 'opx';
$dbUser = 'root';
$dbPass = '';
$eng_id = 2;		//从后台获取
//---------------------------------------//

//-----common--------------------------------//
function json_print($result, $value){		//JSON function
	if($result) exit(json_encode(array('result' => true, 'data' => $value)));
	exit(json_encode(array('result' => false, 'msg' => $value)));
}
function engine($url, $name, $func, $argu){
	$argu = json_encode($argu);
	$post = 'name='.urlencode($name).'&func='.urlencode($func).'&argu='.urlencode($argu);
	$ch = curl_init(); //初始化curl
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($ch);//接收返回信息
	$response = json_decode($response, true);
	if($response) return $response['data'];
	return false;
}
//----------------------------------------------//

//main version
if(isset($inEngine) && $inEngine){
	$run = false;
	function engine_parse($name, $func){
		global $oexec;
		$oexec->op($name)->op_func($func);
	}
}else{
	$run = true;
	$versionArray = array(
		'version' => PHP_VERSION,
		'lang' => 'php',
		'eng_id' => $eng_id,
		'mode' => 0
	);
	if(empty($_POST)) json_print(true, $versionArray);

	//post check
	if(isset($_POST['name']) && isset($_POST['func'])){
		$name = $_POST['name'];
		$func = $_POST['func'];
	}else exit('Undefined name or func!!');
	if(isset($_POST['argu'])){
		$argu = json_decode($_POST['argu'], true);
	}else $argu = array();
}

$errorFunc = false;

function op_error($errno, $errstr, $errfile, $errline){
	if (!(error_reporting() & $errno)) return;
	global $errorFunc;

	$errorArray = array(
		'1' => 'ERROR',
		'2' => 'WARNING',
		'4' => 'PARSE',
		'8' => 'NOTICE',
		'16' => 'CORE_ERROR',
		'32' => 'CORE_WARNING',
		'64' => 'COMPILE_ERROR',
		'128' => 'COMPILE_WARNING',
		'256' => 'USER_ERROR',
		'512' => 'USER_WARNING',
		'1024' => 'USER_NOTICE',
		'2048' => 'STRICT',
		'4096' => 'RECOVERABLE_ERROR'
	);
	if(isset($errorArray[$errno])) echo "[{$errorArray[$errno]}] ";
	else echo 'Unknown error ';

	if($errorFunc) echo "{$errorFunc} ";
	echo "{$errstr} Line:{$errline}\n";

	return true;
}

//set_error_handler('op_error');

class oexec{
	public $ob = array();
	public $name = false;
	public $langs = false;
	public $cross = false;
	public $prevName = false;

	//config
	public $dbHost;
	public $dbName;
	public $dbUser;
	public $dbPass;
	public $eng_id;

	public function op($name = false){
		if($name){
			$this->prevName = $this->name;
			if(!isset($this->ob[$name])){
				$langs = array();
				$object = array();
				$sql = "SELECT opx_functions.name AS func_name,opx_functions.argu,opx_functions.function,opx_functions.lang_id,opx_objects.name,opx_objects.obj_id FROM opx_objects,opx_functions WHERE opx_objects.name = '{$name}' AND opx_objects.status = '1' AND opx_objects.obj_id = opx_functions.obj_id AND opx_functions.status = '1'";
				$list = $this->op_query($sql, 'array');

				if(empty($list)) exit('The object is not exists or functions is none.');
				foreach ($list as $key => $value) {
					$object[$value['func_name']] = array(
						'func' => $value['function'],
						'lang_id' => $value['lang_id'],
						'argu' => $value['argu']
					);
					$langs[$value['lang_id']] = array();
				}
				$langCheck = true;
				if(!empty($this->langs)){
					$diff = array_diff_key($langs, $this->langs);
					if(empty($diff)) $langCheck = false;
				}
				if($langCheck){
					//查询lang、engine映射关系
					$langstr = implode(',' ,array_keys($langs));
					$sql = "SELECT eng_id,path,mode,protocol,lang_id FROM opx_engines WHERE lang_id in ({$langstr}) AND status = '1'";
					$langArray = $this->op_query($sql, 'array');
					foreach ($langArray as $key => $value) {
						$langs[$value['lang_id']][$value['eng_id']] = array(
							'path' => $value['path'],
							'mode' => $value['mode'],
							'protocol' => $value['protocol']
						);
					}
					$this->langs = $langs;
				}

				//对该对象以及该对象的所有方法进行缓存
				$this->ob[$name] = $object;
				$this->name = $list[0]['name'];
			}else $this->name = $name; 	//has read!
		}else{
			//$this->name = $this->prevName;
			if(empty($this->name)) exit('The object is not selected.');
		}
		return $this;
	}

	public function op_func($name, $argu = array()){
		if(!$this->name) exit('no object selected!');//return false;
		if(!isset($this->ob[$this->name][$name])) exit("Undefined function {$this->name}->{$name}()!");//return false;
		$langs = $this->langs[$this->ob[$this->name][$name]['lang_id']];
		if(isset($langs[$this->eng_id])){		//自己跑

			if(!empty($this->ob[$this->name][$name]['argu'])){		//函数输入参数匹配
				$setArgu = explode(',', $this->ob[$this->name][$name]['argu']);
				foreach ($setArgu as $key => $value) if(isset($argu[$key])) $$value = $argu[$key];
			}

			global $errorFunc;
			$errorFunc = "{$this->name}->{$name}()";
			$result = eval("?> {$this->ob[$this->name][$name]['func']} <?php ");
			$this->name = $this->prevName;
			$errorFunc = false;
			return $result;
		}else{		//其他引擎跑

			if($this->cross === false){
				$sql = "SELECT value FROM opx_config WHERE name = 'cross_engine'";
				$cross = $this->op_query($sql, 'row');
				$this->cross = (int)$cross['value'];
			}
			if($this->cross === 0) exit('Cross engine is disabled.');

			$engine = $langs[array_rand($langs,1)];
			$result = engine($engine['path'], $this->name, $name, $argu);
			echo $result['print'];
			$this->name = $this->prevName;	//将name返回prevName
			return $result['return'];
		}
	}

	public function op_query($sql, $type){
		$obj = new PDO("mysql:host={$this->dbHost};dbname={$this->dbName};charset=UTF8", $this->dbUser, $this->dbPass, Array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES'UTF8';"));
		$obj->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		switch($type){
			case 'array':
				$dbObj = $obj->query($sql);
				if(!$dbObj) return false;
				return $dbObj->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'row':
				$dbObj = $obj->query($sql);
				if(!$dbObj) return false;
				return $dbObj->fetch(PDO::FETCH_ASSOC);
				break;
		}
	}

	public function __call($name, $arguments){
		return $this->op_func($name, $arguments);
	}

}

$oexec = new oexec;
$oexec->dbHost = $dbHost;
$oexec->dbName = $dbName;
$oexec->dbUser = $dbUser;
$oexec->dbPass = $dbPass;
$oexec->eng_id = $eng_id;

if($run){
	$result = array();
	ob_start();
	$result['return'] = $oexec->op($name)->op_func($func, $argu);
	$result['print'] = ob_get_contents();
	ob_clean();

	json_print(true, $result);
}


?>