<?php
// +----------------------------------------------------------------------
// | RPCMS
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.rpcms.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: ralap <www.rpcms.cn>
// +----------------------------------------------------------------------

/**
* 基础函数库
*/
function autoLoadClass($class){
	$classArr=explode('\\',$class);
	$controller=array_pop($classArr);
	$class=ltrim(join('/',$classArr),'\\');
	$class= preg_replace('/\brp\b/', LIBPATH, $class);
	$class= preg_replace('/\bplugin\b/', PLUGINPATH, $class);
	$class= preg_replace('/\btemplate\b/', TMPPATH, $class);
	$class = strtolower(str_replace('\\','/',$class));
	if(empty($class)){
		$class = LIBPATH;
	}
	$controller = ucfirst($controller);
	if(file_exists($class . '/'.$controller.'.class.php')){
		require_once($class . '/'.$controller.'.class.php');
	}elseif (file_exists($class . '/'.$controller.'.lib.php')){
		require_once($class . '/'.$controller.'.lib.php');
	}else{
		rpMsg($controller . '控制器加载失败。');
	}
}

function isLogin(){
	return !empty(session('MEADMIN')) ? true : false;
}

function psw($str){
	$appkey=rp\Config::get('app_key');
	return sha1(md5($str.$appkey).$appkey);
}

function _encrypt($string){
	$appkey=rp\Config::get('app_key');
	$data = openssl_encrypt($string, 'AES-128-ECB', $appkey, OPENSSL_RAW_DATA);
	$data = strtolower(bin2hex($data));
	return $data;
}

function _decrypt($string){
	$appkey=rp\Config::get('app_key');
	return openssl_decrypt(hex2bin($string), 'AES-128-ECB', $appkey, OPENSSL_RAW_DATA);
}

function getGuid(){
	return str_replace('.', '',uniqid('', true));
}
	
function input($name, $default=''){
	$nameArr=explode('.',$name);
	if(isset($nameArr[1])){
		switch(strtoupper($nameArr[0])){
			case 'POST':$da=$_POST;break;
			case 'GET':$da=$_GET;break;
			case 'REQUEST':$da=$_REQUEST;break;
			case 'SERVER':$da=$_SERVER;break;
			case 'COOKIE':$da=$_COOKIE;break;
			case 'SESSION':$da=$_SESSION;break;
		}
		$na=$nameArr[1];
	}else{
		$da=$_REQUEST;
		$na=$name;
	}
	if(empty($na)){
		return $da;
	}
	return isset($da[$na]) ? $da[$na] : $default;
}

function session($name,$value=''){
	if($value !== ''){
		$_SESSION[$name]=$value;
		if($value == null) unset($_SESSION[$name]);
		return true;
	}
	return isset($_SESSION[$name]) ? strDeep($_SESSION[$name]) : '';
}

function cookie($name,$value='',$expire=0,$path='/'){
	if($value !== ''){
		setcookie($name, $value, time() + $expire, $path);
		return true;
	}elseif($value === NULL){
		setcookie($name, NULL, time() - 31536000, $path);
		return true;
	}
	return isset($_COOKIE[$name]) ? strDeep($_COOKIE[$name]) : '';
}

function json($data=array()){
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data);
	exit;
}

/**
* 去除多余的转义字符
*/
function doStrslashes(){
	$_GET = strDeep($_GET);
	$_POST = strDeep($_POST);
	$_COOKIE = strDeep($_COOKIE);
	$_REQUEST = strDeep($_REQUEST);
}

/**
* 递归去除转义字符
*/
function strDeep($value){
	if(is_array($value)){
		$value=array_map('strDeep', $value);
	}else{
		$value=stripslashes(trim($value));
		if(!function_exists('get_magic_quotes_gpc') || !get_magic_quotes_gpc()){
			$value=addslashes($value);
		}
	}
	return $value;
}

/**
*验证数据
*/
function checkForm($type,$val){
	$pattern=array(
		'url'=>"/^((https|http|ftp|rtsp|mms)?:\/\/)(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z_!~*'()-]+\.)*([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.[a-z]{2,6})(:[0-9]{1,4})?((\/?)|(\/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+\/?)$/i",
		'email'=>'/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/i',
		'phone'=>'/^1[3,4,5,6,7,8]\d{9}$/i',
		'telephone'=>'/^0(([1,2]\d)|([3-9]\d{2}))-\d{7,8}$/i',
		'en'=>'/^[a-z][A-Z]$/i',
	);
	if(isset($pattern[$type]) && !preg_match($pattern[$type], $val)){
		return false;
	}
	return true;
}

/**
* 获取用户ip地址
*/
function ip(){
	global $App;
	$proxyHeader=array('HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP','REMOTE_ADDR');
	$ip='';
	foreach($proxyHeader as $v) {
		$ip=$App::server($v);
		if(!empty($ip)){
			$ip=trim(explode(',', $ip)[0]);
			break;
		}
	}
	if(!filter_var($ip, FILTER_VALIDATE_IP)){
		$ip = '';
	}
	return $ip;
}

/**
*判断是否是wap
*/
function isMobile(){
	global $App;
	if($App::server('HTTP_VIA') && stristr($App::server('HTTP_VIA'), "wap")){
		return true;
	}elseif($App::server('HTTP_ACCEPT') && strpos(strtoupper($App::server('HTTP_ACCEPT')), "VND.WAP.WML")){
		return true;
	}elseif($App::server('HTTP_X_WAP_PROFILE') || $App::server('HTTP_PROFILE')){
		return true;
	}elseif($App::server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $App::server('HTTP_USER_AGENT'))){
		return true;
	}
	return false;
}

/**
* 页面跳转
*/
function redirect($url,$code=302){
	rp\Url::setCode($code)->redirect($url);
}

function url($url){
	return rp\Url::setUrl($url);
}

function urlOther($type,$url,$page=NULL){
	return rp\Url::other($type,$url,$page);
}

/*验证插件是否启用*/
function pluginCheck($name){
	global $App;
	return in_array($name, $App->allPlugin);
}

function pluginConfig($name){
	$config=(new rp\Plugin())->getConfig($name);
	return $config;
}

function pluginDo($name,$controller='index',$action='index',$data=array()){
	return (new rp\Plugin())->doPlugin($name,$controller,$action,$data);
}


/*后台分页函数*/
function pageInation($count, $perlogs, $page, $url='', $anchor = '') {
	global $App;
	$pnums = @ceil($count / $perlogs);
	$page = @min($pnums,$page);
	$prepg=$page-1;
	$nextpg=($page==$pnums ? 0 : $page+1);
	$re = '';
	if(empty($url)){
		$url=$App->nowUrl() .(!empty($anchor) ? '?'.$anchor.'&' : '?').'page=';
	}
	$urlHome = preg_replace("|[\?&/][^\./\?&=]*page[=/\-]|", "", $url);
	if($pnums<=1) return false;
	if($page!=1) $re .=' <a href="'.$urlHome.'">首页</a> '; 
	if($prepg) $re .=' <a href="'.$url.$prepg.'" >‹‹</a> ';
	for ($i = $page-2;$i <= $page+2 && $i <= $pnums; $i++){
		if ($i > 0){
			if ($i == $page){
				$re .= ' <span>'.$i.'</span> ';
			}elseif($i == 1){
				$re .= ' <a href="'.$urlHome.'">'.$i.'</a> ';
			}else{
				$re .= ' <a href="'.$url.$i.'">'.$i.'</a> ';
			}
		}
	}
	if($nextpg) $re .=' <a href="'.$url.$nextpg.'">››</a> '; 
	if($page!=$pnums) $re.=' <a href="'.$url.$pnums.'" title="尾页">尾页</a>';
	return $re;
}

/*前台分页函数*/
function pageInationHome($count, $perlogs, $page, $mode='index', $data=NULL) {
	global $App;
	$pnums=@ceil($count / $perlogs);
	$page=@min($pnums,$page);
	$prepg=$page-1;
	$nextpg=($page==$pnums ? 0 : $page+1);
	$re = '';
	$urlHome=rp\Url::other($mode,$data);
	if($pnums <= 1) return false;
	if($page != 1) $re .= ' <a href="'.$urlHome.'">首页</a> '; 
	if($prepg) $re .= ' <a href="'. rp\Url::other($mode,$data,$prepg) .'">‹‹</a> ';
	for ($i = $page-2;$i <= $page+2 && $i <= $pnums; $i++){
		if ($i > 0){
			if ($i == $page){
				$re .= ' <span>'.$i.'</span> ';
			}elseif($i == 1){
				$re .= ' <a href="'.$urlHome.'">'.$i.'</a> ';
			}else{
				$re .= ' <a href="'. rp\Url::other($mode,$data,$i) .'">'.$i.'</a> ';
			}
		}
	}
	if($nextpg) $re .= ' <a href="'. rp\Url::other($mode,$data,$nextpg) .'">››</a> '; 
	if($page!=$pnums) $re.=' <a href="'.  rp\Url::other($mode,$data,$pnums) .'" title="尾页">尾页</a>';
	return $re;
}

/**
* 删除连续空格
* @param $s
* @return null|string|string[]
*/
function RemoveSpaces($str){
	return preg_replace("/\s(?=\s)/", "\\1", $str);
}

/**
* 二维数组去除空元素（不包含：0，false，$v是数组有一个不为空）
* @params array $array 需要去空的数组
* @params string $field 根据字段去空
*/
function array_filter_key($array, $field = NULL){
	foreach($array as $k=>$v){
		if(is_array($v)){
			$val=(!empty($field) && isset($v[$field])) ? $v[$field] : join("",$v);
		}else{
			$val=$v;
		}
		if($val === "" || $val === NULL) unset($array[$k]);
	}
	return $array;
}

/**
* 二维数组根据字段进行排序
* @params array $array 需要排序的数组
* @params string $field 排序的字段
* @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
*/
function arraySequence($array, $field, $sort ='SORT_DESC'){
	$arrSort = array();
	foreach ($array as $uniqid => $row) {
		foreach ($row as $key => $value) {
			$arrSort[$key][$uniqid] = $value;
		}
	}
	array_multisort($arrSort[$field], constant($sort), $array);
	return $array;
}

/*文件夹/文件权限*/
function GetFilePermsOct($file){
	if(!file_exists($file)){
		return;
	}
	return substr(sprintf('%o', fileperms($file)), -4);
}

/*截取指定长度字符*/
function getContentByLength($content, $strlen = 180){
	$content = preg_replace('/\s/u','',strip_tags($content));
	return subString($content, 0, $strlen);
}

/*文件大小格式化*/
function formatBysize($size) { 
	$units = array(' B', ' KB', ' MB', ' GB', ' TB'); 
	for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024; 
	return round($size, 2).$units[$i]; 
}

/*日期格式化*/
function formatDate($time,$level=7,$format='Y-m-d H:i:s'){
	$time = strtotime($time);
	$etime = time() - $time;
	if($etime < 1){return '刚刚';}
	$interval = array(
		array('time'=>1,'str'=>'秒前'),
		array('time'=>60,'str'=>'分钟前'),
		array('time'=>(60 * 60),'str'=>'小时前'),
		array('time'=>(24 * 60 * 60),'str'=>'天前'),
		array('time'=>(7 * 24 * 60 * 60),'str'=>'周前(%date%)'),
		array('time'=>(30 * 24 * 60 * 60),'str'=>'月前(%date%)'),
		array('time'=>(12 * 30 * 24 * 60 * 60),'str'=>'年前(%date%)'),
	);
	$level=$level-1;
	foreach($interval as $k=>$v){
		if($k <= $level && $v['time'] <= $etime && $etime < $interval[$k+1]['time']){
			$d = $etime / $v['time'];
			return str_replace('%date%',date($format,$time),round($d) . $v['str']);
		}
	}
	return date($format,$time);
}


/*标签关键字替换*/
function content2keyword($content,$limit=1){
	$tages =rp\Cache::read('tages');
	if(!is_array($tages) || empty($tages)){
		return $content;
	}
	preg_match_all('/<a(\s[^>]*)?>.*<\/a>|<img\s[^>]+>/Uis', $content, $matches);
	if(is_array($matches[0])){
		foreach($matches[0] as $key=>$val ){
			$flag = "{*=*=*=*{$key}*=*=*=*}";
			$content = str_replace($val, $flag, $content);
		}
	}
	$content='<div>'. PHP_EOL .$content. PHP_EOL .'</div>';
	foreach($tages as $val){
		$tagurl = '<a title="'.$val['tagName'].'" href="'.rp\Url::tag($val['id']).'" target="_blank">'.$val['tagName'].'</a>';
		$content = preg_replace('/('.$val['tagName'].')(?=[^<>]*<)/', $tagurl, $content, $limit);
	}
	$content=trim(ltrim(rtrim($content,'</div>'),'<div>'));
	//$content=trim(rtrim(ltrim($content,'<div>'),'</div>'));
	if(is_array($matches[0])){
		foreach($matches[0] as $key=>$val){
			$flag = "{*=*=*=*{$key}*=*=*=*}";
			$content = str_replace($flag, $val, $content);
		}
	}
	return $content;
}

/**
* 截取编码为utf8的字符串
*
* @param string $strings 预处理字符串
* @param int $start 开始处 eg:0
* @param int $length 截取长度
*/
function subString($strings, $start, $length) {
	if (function_exists('mb_substr') && function_exists('mb_strlen')) {
		$sub_str = mb_substr($strings, $start, $length, 'utf8');
		return mb_strlen($sub_str, 'utf8') < mb_strlen($strings, 'utf8') ? $sub_str . '...' : $sub_str;
	}
	$str = substr($strings, $start, $length);
	$char = 0;
	for ($i = 0; $i < strlen($str); $i++) {
		if (ord($str[$i]) >= 128)
			$char++;
	}
	$str2 = substr($strings, $start, $length + 1);
	$str3 = substr($strings, $start, $length + 2);
	if ($char % 3 == 1) {
		if ($length <= strlen($strings)) {
			$str3 = $str3 .= '...';
		}
		return $str3;
	}
	if ($char % 3 == 2) {
		if ($length <= strlen($strings)) {
			$str2 = $str2 .= '...';
		}
		return $str2;
	}
	if ($char % 3 == 0) {
		if ($length <= strlen($strings)) {
			$str = $str .= '...';
		}
		return $str;
	}
}

/**
* 生成一个随机的字符串
*
* @param int $length
* @param boolean $special_chars
* @return string
*/
function randStr($length = 12, $special_chars = false, $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
	if ($special_chars) {
		$chars .= '!@#$%^&*()';
	}
	$randStr = '';
	for ($i = 0; $i < $length; $i++) {
		$randStr .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	}
	return $randStr;
}

/*
*过滤格式化1,2,3类型字符串
*@param data 过滤的内容
*/
function arrayIdFilter($data = "") {
	if(is_array($data)){
		$isArray=true;
		$list=$data;
	}else{
		$isArray=false;
		$list=explode(',', $data);
	}
	foreach($list as $k=>&$v) {
		$v=intval($v);
	}
	$list=array_unique(array_filter($list));
	return $isArray ? $list : join(',', $list);
}

/*
*过滤指定HTML标签
*@param content 过滤的内容
*@param arr 过滤的标签
*/
function clear_html($content,$arr){
	$preg=array(
		"script"=>array("@<script(.*?)</script>@is","/javascript/si","/vbscript/si","/ on([a-z]+)=\"([^\"]*)\"/si"),
		"iframe"=>"@<iframe(.*?)</iframe>@is",
		"style"=>"@<style(.*?)</style>@is",
		"all"=>"@<(.*?)>@is",
	);
	foreach($arr as $k=>$v){
		if(!empty($preg[$v])){
			if(is_array($preg[$v])){
				foreach($preg[$v] as $kk=>$vv){
					$content = preg_replace($preg[$v][$kk], "", $content);
				}
			}else{
				$content = preg_replace($preg[$v], "", $content);
			}
		}
		return $content;
	}
}

function uploadFiles($file,$logId=0,$pageId=0){
	if(!empty($file) && !$file['error'] && !empty($file['name'])){
		if($file['error'] == 1){
			return array('code'=>-1,'msg'=>'文件大小超过系统限制');
		}elseif($file['error'] > 1){
			return array('code'=>-1,'msg'=>'上传文件失败');
		}
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); 
		$types=explode(',',rp\Config::get('webConfig.fileTypes'));
		if(!in_array($ext,$types) || preg_match('/php/i', $ext) || preg_match('/pht(ml)?(\d*)|phar/i', $ext)){
			return array('code'=>-1,'msg'=>'附件类型错误');
		}
		if($file['size'] > (rp\Config::get('webConfig.fileSize') * 1024 * 1024)){
			return array('code'=>-1,'msg'=>'最大'. rp\Config::get('webConfig.fileSize') .'MB');
		}
		$filepath='/'. UPLOADPATH .'/'. date('Ym');
		$fileName=md5(uniqid(microtime(true), true)).'.'.$ext;
		$upload = new rp\upload;
		$upload->oriName = $file['name'];
		$upload->fileSizes = $file['size'];
		$upload->fileTypes = '.'.$ext;
		$upload->fullName = $filepath.'/'.$fileName;
		$upload->filePath = CMSPATH . $upload->fullName;
		$upload->fileName = $fileName;
		$upload->dirNames = dirname($upload->filePath);
		$upload->logId = $logId;
		$upload->pageId = $pageId;
		$res=$upload->saveFile($file['tmp_name']);
		if($res['code'] == 200){
			if(!empty($logId) || !empty($pageId)){
				$upload->saveAttr();
			}
			return array('code'=>200, 'msg'=>'success',  'data'=>$res['data']);
		}else{
			return array('code'=>-1, 'msg'=>$res['msg']);
		}
	}
	return array('code'=>-1,'msg'=>'上传文件失败');
}

/**
* 获取目录下文件夹列表.
* @param string $dir 目录
* @return array 文件夹列表
*/
function getDirsInDir($dir){
	if(!file_exists($dir) || !is_dir($dir)){
		return array();
	}
	$dirs = array();
	$dir = str_replace('\\', '/', $dir);
	if(substr($dir, -1) !== '/'){
		$dir .= '/';
	}
	if(function_exists('scandir')){
		foreach(scandir($dir, 0) as $d){
			if(is_dir($dir . $d)) {
				if(($d != '.') && ($d != '..')){
					$dirs[] = $d;
				}
			}
		}
	}else{
		if($handle = opendir($dir)){
			while(false !== ($file = readdir($handle))){
				if($file != "." && $file != ".."){
					if(is_dir($dir . $file)){
						$dirs[] = $file;
					}
				}
			}
			closedir($handle);
		}
	}
	return $dirs;
}

/**
* 删除文件或目录
* @param string $file 目录
*/
function deleteFile($file){
	if(empty($file)) return false;
	if(@is_file($file)){
		return @unlink($file);
	}
	$res = true;
	$noFile=0;
	if($handle = @opendir($file)){
		while($filename = @readdir($handle)){
			if($filename == '.' || $filename == '..') continue;
			if(!deleteFile($file . '/' . $filename)){
				$res = false;
				$noFile++;
			}
		}
	}else{
		$res = false;
	}
	@closedir($handle);
	if($noFile > 0 || (file_exists($file) && !rmdir($file))){
		$res = false;
	}
	return $res;
}

/*获取远程数据*/
function get_contents($url){
	if(ini_get("allow_url_fopen") == 1){
		$response = file_get_contents($url);
	}else{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response =  curl_exec($ch);
		$status = curl_getinfo($ch);
		curl_close($ch);
		if(intval($status["http_code"]) != 200){
			$response=false;
		}
	}
	return $response;
}

/*post数据*/
function http_post($url,$param,$header=array()){
	$ch = curl_init();
	if(extension_loaded('zlib')){
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	if(stripos($url,"https://")!==FALSE){
		curl_setopt($ch, CURLOPT_SSLVERSION, 1);
	}
	if (is_string($param)) {
		$poststr = $param;
	}else {
		$poststr=http_build_query($param);
	}
	if(!empty($header)){
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt($ch, CURLOPT_POST,true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$poststr);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$response = curl_exec($ch);
	$status = curl_getinfo($ch);
	curl_close($ch);
	if(intval($status["http_code"])==200){
		return $response;
	}else{
		return false;
	}
}

function me_createCateOption($cateId=''){
	$category=rp\Cache::read('category');
	$html='';
	foreach($category as $key=>$value){
		if($value['topId'] != 0){
			 continue;
		}
		$html.='<option value="'.$value['id'].'" '.($value["id"] == $cateId ? "selected" : "").'>'.$value['cate_name'].'</option>';
		foreach($value['children'] as $skey){
			$svalue = $category[$skey];
			$html.='<option value="'.$svalue['id'].'" '.($svalue["id"] == $cateId ? "selected" : "").'>└&nbsp;&nbsp;'.$svalue['cate_name'].'</option>';
		}
	}
	return $html;
}
function me_createCateCheckbox($cateId=''){
	$category=rp\Cache::read('category');
	$html='';
	foreach($category as $key=>$value){
		if($value['topId'] != 0){
			 continue;
		}
		$html.='<div class="me_input"><input type="checkbox" name="cateIds[]" value="'.$value["id"].'" class="me_cate_ids"/><label>'.$value['cate_name'].'</label></div>';
		foreach($value['children'] as $skey){
			$svalue = $category[$skey];
			$html.='<div class="me_input"><span style="float: left;height: 2.4rem;line-height: 2.4rem;">└&nbsp;&nbsp;</span><input type="checkbox" name="cateIds[]" value="'.$svalue["id"].'" class="me_cate_ids"/><label>'.$svalue['cate_name'].'</label></div>';
		}
	}
	return $html;
}
function me_createAuthorOption($authorId=''){
	$author=rp\Cache::read('user');
	$html='';
	foreach($author as $value){
		$html.='<option value="'.$value['id'].'" '.($value["id"] == $authorId ? "selected" : "").'>'.$value['nickname'].'</option>';
	}
	return $html;
}
function me_createSpecialOption($specialId=''){
	$special=rp\Cache::read('special');
	$html='<option value="0">选择专题</option>';
	foreach($special as $value){
		$html.='<option value="'.$value['id'].'" '.($value["id"] == $specialId ? "selected" : "").'>'.$value['title'].'</option>';
	}
	return $html;
}

function Debug_Shutdown_Handler(){
	$error=error_get_last();
	if(!empty($error)){
		switch($error['type']){
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:  
				ob_end_clean();
				Debug_Error_Handler($error['type'],$error['message'],$error['file'],$error['line']);
				break;
		}
	}
}
function Debug_Error_Handler($errno, $errstr, $errfile, $errline){
	if(!rp\Config::get('webConfig.isDevelop')){
		$errstr=str_replace(CMSPATH,'',$errstr);
		$errfile=str_replace(CMSPATH,'',$errfile);
	}
	rpMsg($errstr.'<br>'.$errfile.'&nbsp;&nbsp;&nbsp;&nbsp;Line:  '.$errline);
}
function Debug_Exception_Handler($exception){
	rpMsg(rp\Config::get('webConfig.isDevelop') ? $exception->getMessage() : '未找到页面');
}

/**
* 显示系统信息
*
* @param string $msg 信息
* @param string $url 返回地址
* @param boolean $isAuto 是否自动返回 true false
*/
function rpMsg($msg, $url = 'javascript:history.back(-1);', $isAuto = false){
	if (!headers_sent()){
		rp\Url::setCode(500);
		if(ob_get_length() > 0) ob_end_clean();
	}
	if ($msg == '404') {
		rp\Url::setCode(404);
		$msg = '抱歉，你所请求的页面不存在！';
	}
	if(rp\Config::get('webConfig.isDevelop')){
		$error=$msg;
		$heading="Error Occurred";
		$message=array();
		$trace = debug_backtrace();
		if(isset($trace[1]['args'][0]) && is_object($trace[1]['args'][0]) && method_exists($trace[1]['args'][0],'getTrace')){
			$error.='<br>'.$trace[1]['args'][0]->getFile().'&nbsp;&nbsp;&nbsp;&nbsp;Line:  '.$trace[1]['args'][0]->getLine();
			$trace=$trace[1]['args'][0]->getTrace();
		}
		foreach ($trace as $call){
			if (isset($call['file'])){
				if (DIRECTORY_SEPARATOR !== '/'){
					$call['file'] = str_replace('\\', '/', $call['file']);
				}
				$message[] = 'Filename: '.$call['file'].'&nbsp;&nbsp;&nbsp;&nbsp;Line: '.$call['line'];
			}
		}
		$message = '<p>'.(is_array($message) ? implode('</p><p>', $message) : $message).'</p>';
		echo "<h3>".$heading."</h3>";
		echo "<div style='border: 1px solid #ccc;padding: 10px;color: #313131;font-size: 15px;'>".$error."</div><div style='font-size: 13px;color: #444444;line-height: 13px;'>".$message."</div>";
	}else{
		global $App;
		if(\rp\View::checkTemp('404')){
			\rp\View::assign('message',$msg);
			\rp\View::assign('url',$url);
			\rp\View::assign('isAuto',$isAuto);
			echo \rp\View::display('/404');
			exit;
		}
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN">
			<head><meta http-equiv="proma" content="no-cache"/><meta http-equiv="cache-control" content="no cache"/><meta http-equiv="expires" content="0"/>';
		if($isAuto){
			echo '<meta http-equiv="refresh" content="2;url='.$url.'" />';
		}
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>提示信息-未找到页面-'. \rp\Config::get('webConfig.webName') .'</title>
				<style type="text/css">
					body{background:#F7F7F7;font-family: Arial;font-size: 12px;}
					a{text-decoration:none;}
					.main{background:#fff;color: #666;width:800px;margin:10% auto 0px;padding:2rem;position: relative;}
					.main img{float: right;}
					.main p{margin: 0;line-height: 1.5;}
					.main .errorCode{font-size:6rem;}
					.main .errorText{font-size:2rem;color: #19afdc;}
					.main .btns{margin-top: 2rem;clear:both;}
					.main .btns a{background: #19afdc;padding: 0.5rem 1rem;color: #fff;}
				</style>
			</head>
			<body>
				<div class="main">
					<img src="'.$App->appPath.'/static/images/404.png"/>
					<span class="errorCode">404</span>
					<p class="errorText">'.$msg.'</p>'.($url != 'none' ? '<p class="btns"><a href="' . $url . '">点击返回</a></p>' : '').'
				</div>
			</body>
		</html>';
	}
	exit;
}