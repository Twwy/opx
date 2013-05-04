<?php
/*Twwy's art*/

//inside engine!
$enginePath = './engine.php';

date_default_timezone_set('PRC');

//preg_match('/\/opx\/(.+)$/', $_SERVER['REQUEST_URI'], $match);
preg_match('/\/(.+)$/', $_SERVER['REQUEST_URI'], $match);
$uri = (empty($match)) ? 'default' : $match[1];

/*数据库*/
if(php_uname() == 'SAE LINUX ENVIRONMENT') require('./database-sae.php');
else require('./database.php');
$db = new database;

/*路由*/
$router = Array();
function router($path, $func){
	global $router;
	$router[$path] = $func;
}

/*视图*/
function view($page, $data = Array(), $onlyBody = false){
	foreach ($data as $key => $value) $$key = $value;
	if($onlyBody) return require("./view/{$page}");
	require("./view/header.html");
	require("./view/{$page}");
	require("./view/footer.html");
}

/*会话*/
session_start();

/*JSON格式*/
function json($result, $value){
	if($result) exit(json_encode(array('result' => true, 'data' => $value)));
	exit(json_encode(array('result' => false, 'msg' => $value)));
}

/*POST过滤器*/	//符合rule返回字符串，否则触发callback，optional为真则返回null
function filter($name, $rule, $callback, $optional = false){
	if(isset($_POST[$name]) && preg_match($rule, $post = trim($_POST[$name]))) return $post;
	elseif(!$optional){
		if(is_object($callback)) return $callback();
		else json(false, $callback);
	}
	return null;
}

/*模型*/
class model{
	function db(){
		global $db;
		return $db;
	}
}//model中转db类
function model($value){
	require("./model/{$value}.php");
	return new $value;
}

/*扩展函数*/
require('common.php');

/*================路由表<开始>========================*/

$inEngine = true;
require($enginePath);

$sql = "SELECT opx_objects.name as ob_name,opx_functions.name as func_name FROM opx_objects,opx_functions WHERE opx_objects.status = 1 AND opx_objects.obj_id = opx_functions.obj_id AND opx_functions.status = 1";
$result = $db->query($sql, 'array');

foreach ($result as $key => $value) {
	$ob_name =  $value['ob_name'];
	$func_name = $value['func_name'];
	router("({$ob_name}).({$func_name})(.{0,999})",function($matches){
		engine_parse($matches[1], $matches[2]);
		exit();
	});
}

router('default',function(){
	//$data = array('view' => 'about');
	$object = model('op_object');
	$list = $object->objectList();
	view('about.html', array('list' => $list));
});

/*router('test',function(){
	echo '<form action="engine.php" method="POST"><input name="name" value="blog"/><input name="func" value="test"/><input type="submit"/></form>';
});*/

router('object=([0-9]{1,9})',function($matches){
	$obj_id = $matches[1];

	$object = model('op_object');
	$ob = $object->get($obj_id);
	if(empty($ob)) exit('Undefind obj_id!');
	$list = $object->objectList();

	$function = model('op_function');
	$func = $function->funcList($obj_id);
	$langs = $function->langList('lang_id');

	$result = array(
		'list' => $list, 
		'ob' => $ob, 
		'func' => $func,
		'langs' => $langs
	);
	view('object.html', $result);
});

router('func=([0-9]{1,9})',function($matches){
	$func_id = $matches[1];

	$function = model('op_function');
	$object = model('op_object');
	$func = $function->get($func_id);
	$list = $object->objectList();
	$langs = $function->langList('lang_id');
	$cross = $object->config('cross_engine');
	$obj = $object->get($func['obj_id']);

	$result = array(
		'list' => $list, 
		'func' => $func,
		'langs' => $langs,
		'cross' => $cross,
		'obj' => $obj
	);
	view('func.html', $result);
});

router('setting',function(){

	$object = model('op_object');
	$engine = $object->config('cross_engine');
	$list = $object->objectList();
	$engineModel = model('op_engine');
	$function = model('op_function');
	$engList = $engineModel->all();
	$langs = $function->langList('id');
	$result = array(
		'list' => $list, 
		'engine' => $engine,
		'engList' => $engList,
		'langs' => $langs
	);
	view('config.html', $result);
});

router('engine.switch',function($matches){

	$status = filter('status', '/^1|0$/', 'status格式错误');

	$object = model('op_object');
	$object->configSet('cross_engine', $status);

	json(true, '更新成功');
});

router('engine.update',function($matches){

	$eng_id = filter('eng_id', '/^[0-9]{1,9}$/', 'ID格式错误');

	$engineModel = model('op_engine');
	$eng = $engineModel->get($eng_id);

	$url = $eng['path'];
	$ch = curl_init(); //初始化curl
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);//接收返回信息
	$response = json_decode($response, true);

	//print_r(expression)
	//json(true, '更新成功');
});

router('func.save',function($matches){

	$func_id = filter('func_id', '/^[0-9]{1,9}$/', 'ID格式错误');
	$lang = filter('lang', '/^[a-zA-Z0-9]{1,10}$/', '语言格式错误');
	$funcEval = filter('function', '/^[\s\S]*$/', 'function格式错误');

	$object = model('op_object');
	$cross = $object->config('cross_engine');
	if($cross == 0) $lang = 'php';
	$function = model('op_function');
	$func = $function->get($func_id);
	if(empty($func)) json(false, 'Undefind function!');
	$langs = $function->langList('name');
	$lang_id = $langs[$lang];

	$updateArray = array(
		'update_time' => time(),
		'lang_id' => $lang_id,
		'function' => $funcEval
	);
	$result = $function->update($func_id, $updateArray);

	if($result > 0){
		$updateArray = array(
			'update_time' => time(),
		);
		$object->update($func['obj_id'], $updateArray);
		json(true, '更新成功');
	}
	json(true, '未进行更改');

});

router('func.load',function(){
	$func_id = filter('func_id', '/^[0-9]{1,9}$/', 'ID格式错误');
	$function = model('op_function');
	$func = $function->get($func_id);
	if(empty($func)) json(false, 'Undefind function!');
	json(true, $func['function']);
});

router('func.add',function($matches){

	$obj_id = filter('obj_id', '/^[0-9]{1,9}$/', '对象ID格式错误');
	$name = filter('name', '/^[a-zA-Z0-9]{1,10}$/', '名称格式错误');

	$function = model('op_function');
	$object = model('op_object');
	$result = $object->get($obj_id);
	if(empty($result)) json(false,'该对象不存在');

	$func = $function->funcList($obj_id, $name);
	if(!empty($func)) json(false,'该方法已经存在');

	$result = $function->insert($obj_id, $name);
	if($result == 0) json(false,'方法新建失败');
	$object->update($obj_id, array('update_time' => time()));

	json(true,'方法新建成功');
});

router('func.remove',function($matches){

	$func_id = filter('func_id', '/^[0-9]{1,9}$/', '方法ID格式错误');

	$function = model('op_function');
	$object = model('op_object');
	$result = $function->get($func_id);
	if(empty($result)) json(false, '该方法不存在');

	$back = $function->update($func_id, array('status' => 0));
	if($back > 0){
		$object->update($result['obj_id'], array('update_time' => time()));
		json(true,'方法删除成功');
	}

	json(true,'方法未删除');
});

router('obj.add',function($matches){

	$name = filter('name', '/^[a-zA-Z0-9]{1,10}$/', '名称格式错误');

	$object = model('op_object');
	$result = $object->get($name, 'name');
	if(!empty($result)) json(false,'该对象已经存在');

	$result = $object->insert($name);
	if($result == 0) json(false,'对象新建失败');

	json(true, $result);
});

router('obj.remove',function($matches){

	$obj_id = filter('obj_id', '/^[0-9]{1,9}$/', '对象ID格式错误');

	$object = model('op_object');
	$function = model('op_function');
	$result = $object->get($obj_id);
	if(empty($result)) json(false,'该对象不存在');
	$object->update($obj_id, array('status' => 0));

	$funcs = $function->funcList($obj_id);
	foreach ($funcs as $key => $value) {
		$function->update($value['func_id'], array('status' => 0));
	}

	json(true, '对象删除成功');
});

/*================路由表<结束>========================*/


/*路由遍历*/
foreach ($router as $key => $value){
	if(preg_match('/^'.$key.'$/', $uri, $matches)) exit($value($matches));
}

/*not found*/
echo 'Page not fonud';

?>