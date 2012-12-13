<?
// Get Bilibili douga - No Account needed.

/*
   脚本运行环境:	php
   需要的版本号:	不知道 =-=
   运行要求:		支持 cURL 系列函数
   编写:			jixun66
   声明:			谢绝跨省。
*/

/*
 *   配置脚本使其工作
 */

///////////////////////////////// 必须配置 \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\

$err_msg    = 'Server error.';		// 错误信息
$useragent  = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6';
$ckfile     = 	dirname(__FILE__) . '/'	. 
				'cookies.txt';		// Cookie 保存文件名, 自己配置 .htaccess 保护它。

////////////////////////////////           \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\


///////////////////////////////// 可选配置 \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\

$CookieOnly = false;
// 如果设定 CookieOnly 为 True 则不需要登陆信息
$login_info = array(
	array ( 'un' => 'Bilibili 用户名1', 'pw' => 'Bilibili 密码1' ),
	array ( 'un' => 'Bilibili 用户名2', 'pw' => 'Bilibili 密码2' ),
	array ( 'un' => 'Bilibili 用户名3', 'pw' => 'Bilibili 密码3' ),
	array ( 'un' => 'Bilibili 用户名4', 'pw' => 'Bilibili 密码4' ),
	array ( 'un' => 'Bilibili 用户名5', 'pw' => 'Bilibili 密码5' ),
);

$usecache   = true;
// 如果设定 usecache 为 True 则需要填写下列信息 ( 缓存是个好东西 >.>
$db_host    = 'localhost';			// 数据库地址
$db_port    = '3306';				// 数据库端口，默认 3306
$db_user    = 'root';				// 用户名
$db_pass    = '';					// 密码
$db_name    = '';					// 数据库名
$db_table   = '';					// 数据表名
$db_charset = 'UTF-8';				// 数据库编码

////////////////////////////////           \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\


/*
 *   ↓ 代码, 除非你知道你在干什么否则请不要乱改..
 */
$base  = 'http://bilibili.tv/';
$g_out = '';
/*
 *  Func::gS
 *    Return: (g)lobal (S)tring for output
 */
function gS ()  { return '{ "cid": %d, "avId": %d, "title": "%s", "desc": "%s", "userId": %d, "userName": "%s" }'; }
/*
 *  Func::gT
 *    Return: (g)lobal s(T)ring replacement
 */
function gT ($S) { return str_replace('"', '\"', $S); }
srand ((double)microtime()*1000000);
function getEntry ($str) {
	global $g_out;
	preg_match('/cid=(?P<c>[\d]+)&aid=(?P<a>[\d]+)"/i', $str, $matches);
	// print_r ($str);
	if (  (!(isset($matches ['c']) && isset($matches ['a']))) || 
		   !is_numeric($matches ['c']) || !is_numeric($matches ['a']))
			 { return ''; }
	preg_match('/<title>(?P<t>.+?) - 嗶哩嗶哩 - \( ゜- ゜\)つロ  乾杯~  - bilibili.tv<\/title>/i', $str, $title);
	preg_match('/<div class="intro">\n[\s]+(?P<d>.+?)<\/div>/i', $str, $desc);
	preg_match('/<a href=\'http:\/\/space.bilibili.tv\/(?P<i>.+?)\' card="(?P<u>.+?)" target=\'_blank\'>/i', $str, $user);
	$g_out = sprintf(gS(), $matches ['c'], $matches ['a'], $title ['t'], gT(@$desc ['d']), gT(@$user ['i']), gT(@$user ['u']));
	return (sprintf('%d-%d-%s-%s-%d-%s', $matches ['c'], $matches ['a'], base64_encode($title ['t']), base64_encode(@$desc ['d']), 
	base64_encode($user ['i']), base64_encode($user ['u'])
	));
}
function getLoginDetail (&$un, &$pw) {
	global $login_info;
	if (count($login_info) == 1) { $num = 0; }
	 else { $num = rand(0, count($login_info)); }
	/*  Simple fix..
	 *  ret = rand(0, 0);
	 *   where ret is 1... wtf?!
	 */
	$userset = $login_info [$num];
	$un = $userset ['un'];
	$pw = $userset ['pw'];
}
function allopt ($ch) {
	global $ckfile, $useragent;
	curl_setopt ($ch, CURLOPT_HEADER, 1);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt ($ch, CURLOPT_USERAGENT, $useragent);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt ($ch, CURLOPT_ENCODING, "identity");
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_COOKIEJAR,  $ckfile);
	curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
}
function doLogin () {
	getLoginDetail ($username, $password);
	$loginpage = 'https://secure.bilibili.tv/login';
	$postdata  = 'act=login&gourl=&keeptime=999999999&userid=' . $username . '&pwd=' . $password;
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $loginpage);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_REFERER, $loginpage);
	allopt ($ch);
	curl_exec ($ch);
	curl_close($ch);
	return;
}

function isEntryInvalid ($instr){
	global $err_msg;
	if ( trim($instr) === '' ) { return true; }
	return(false);
}

$ckfile = dirname(__FILE__) . '/cookies.txt';
$avId = @$_GET['avId'];
if ($avId == '' || !is_numeric($avId)) {die('Need avId !');}
$pId = @$_GET['pId'];
if ($pId == '' || !is_numeric($pId)) { $pId = 1; }

if ($usecache) {
	$conn   = mysql_connect($db_host . ':' . $db_port, $db_user, $db_pass, true) or die($err_msg);
	mysql_select_db($db_name, $conn) or die($err_msg);
	mysql_query("set names " . $db_charset, $conn);
	$result = mysql_query("SELECT * FROM " . $db_table . " WHERE id='" . mysql_real_escape_string($avId) . "'", $conn);
	if($result === false){
		mysql_close($conn);
		die($err_msg);
	}
	$row = mysql_fetch_array($result);
	if (isset($row[0])) {
		preg_match('/(?P<c>[\d]+)-(?P<a>[\d]+)-(?P<t>[A-Za-z0-9+\/]+)-(?P<d>[A-Za-z0-9+\/]+)-(?P<i>[\d]+)-(?P<u>[A-Za-z0-9+\/]+)/i', base64_decode($row ['rep']), $matches);
		echo (sprintf(gS(), 
				@$matches ['c'],
				@$matches ['a'],
				base64_decode(@$matches ['t']),
				gT(base64_decode(@$matches ['d'])),
				gT(base64_decode(@$matches ['i'])),
				gT(base64_decode(@$matches ['u']))
				));
		mysql_close($conn);
		exit();
	}
}
$ch = curl_init ($base . 'video/av' . $avId . '/index_' . $pId . '.html');
allopt ($ch);
$output = getEntry(curl_exec ($ch));
curl_close($ch);
if (isEntryInvalid($output)) {
	doLogin ();
	$ch = curl_init ($base . 'video/av' . $avId . '/index_' . $pId . '.html');
	allopt ($ch);
	$output = getEntry(curl_exec ($ch));
	curl_close($ch);
}
if (isEntryInvalid($output)) {
	die ($err_msg);
}
if ($usecache) {
	$sql = 'insert into `' . $db_table . '` set ' . 
			'`id`=' . mysql_real_escape_string ($avId) . 
			',`rep`=\'' . mysql_real_escape_string (base64_encode($output)) . '\''.
			',`time`=' . mysql_real_escape_string(time());
	$result = mysql_query($sql, $conn);
	mysql_close($conn);
}
echo ($g_out);
?>