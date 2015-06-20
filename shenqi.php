<?php
include "PhpSimple/HtmlDomParser.php";
include "function.php";


$baseUrl = "http://xy.gkwlpx.com";

$url = "http://hn.gkwlpx.com/Public/Control/GetValidateCode?time=".time().rand(100,999);
$ch = curl_init();
$cookie_jar = tempnam('./tmp','JSESSIONID');  
//设置文件读取并提交的cookie路径  
curl_setopt ( $ch, CURLOPT_COOKIESESSION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);  
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
//打印获得的数据
//print_r($output);


//写入图片
$fp=fopen("./verifyCode.jpg",'wb');
fwrite($fp, $output);
fclose($fp);


echo "请查看图片并输入\n";
$verify = trim(fgets(STDIN));	//接收用户输入  

$loginAccount = "";
$loginPassword = "";

echo "请输入身份证号\n";
$loginAccount = trim(fgets(STDIN));	//接收用户输入
$loginPassword = substr($loginAccount, -6);
echo "默认密码身份证后6位 : " . $loginPassword;

//{{{ 登录
$loginUrl = "http://hn.gkwlpx.com/Home/Login/HngkDoLoginByRgn?rgnId=411400";
//参数
$params = array("X-Requested-With"=>"XMLHttpRequest", "btnSubmit"=>"立即登录", "LoginType"=>0, "LoginAccount"=>$loginAccount, "LoginPassword"=>$loginPassword, "LoginValCode"=>$verify);

curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// post数据
curl_setopt($ch, CURLOPT_POST, 1);
// post的变量
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
$output = curl_exec($ch);
echo $output;
//}}}


$homePage = "http://hn.gkwlpx.com/Home/RegionHome?rgnId=411400&tmp=".date("Y-m-d", time() );
curl_setopt($ch, CURLOPT_URL, $homePage);
$output= curl_exec($ch);


//跳转
$studentCenter = "http://hn.gkwlpx.com/Public/ShareSso/ToStudy";

curl_setopt($ch, CURLOPT_URL, $studentCenter);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, true);


$times = 0;
$output = curl_redirect_exec($ch, $times, true);


//进入课程页面
$courseList = "http://xy.gkwlpx.com/Course/MyCourse/Index";
curl_setopt($ch, CURLOPT_URL, $courseList);
$output = curl_exec($ch);

echo "\n==================================== \n";
echo $output;
echo "\n==================================== \n";



use Sunra\PhpSimple\HtmlDomParser;

//$str = file_get_contents("./a.html");

$html = HtmlDomParser::str_get_html($output);
$elems =  $html->find("div[id='tabsLearning'] table thead tr th a");
//var_dump( count( $elems ) );

$courseListUrl = array();
foreach( $elems as $e ) {
	//有两种链接，功能一样，过滤掉一种
	// /Course/MyCourse/MediaList?sscId=a311526918bc412181872c2c3f6b7c5b
	// /Course/MyCourse/CourseStudy?sscId=a311526918bc412181872c2c3f6b7c5b
	if( strpos($e->href, "MediaList") !== false ) {
		$courseListUrl[] = $baseUrl.$e->href;	//没有根地址，添加上
	}
}

echo "============课程列表请求地址==========\n";
var_dump($courseListUrl);


echo "============课程播放页面列表==========\n";
$coursePlayList = array();

foreach( $courseListUrl as $c ) {
	curl_setopt($ch, CURLOPT_URL, $c);
	$output = curl_exec($ch);
	$html = HtmlDomParser::str_get_html($output);
	$elems =  $html->find("tr");
	foreach($elems as $e) {
		$html = HtmlDomParser::str_get_html($e->innertext);
		$tmpStr = $html->find("embed", 0)->src;
		list($_, $progress) = explode("=", $tmpStr);
		if(intval($progress)==100) continue;	//进度满了，跳过
		
		$coursePlayList[] = str_replace("&amp;", "&", $baseUrl.$html->find("a",0)->href);		//过滤htmlencode
	}
}

var_dump($coursePlayList);

foreach($coursePlayList as $playUrl)
{
	curl_setopt($ch, CURLOPT_URL, $playUrl);
	//打开一个页面
	$tmp = 0;
	
	$playerPageHtml = curl_redirect_exec($ch, $tmp, true);
	
	$errorLogs = "";
	while(true) {
	
		try {
			// 开始发校验包
			//reloadSession
			$reloadSessionUrl = $baseUrl . "/Public/MessageNotice/ReloadSession";
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_URL, $reloadSessionUrl);
			$output = curl_exec($ch);
			//var_dump($output);
			
			//另外一个校验包
			$requestUrl = GetRequestUrlByHtml($playerPageHtml);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_URL, $requestUrl);
			$output = curl_exec($ch);
			$json = str_replace( ")", "", str_replace("?(", "", $output));
			
			$result = json_decode($json, true);
			//var_dump($result);
			
			if($result["State"]!=1) {
				echo "\n========================================\n";
				echo " 数据异常:校验包返回值是:\n";
				echo "\nplayerPageHtml\n";
				var_dump($playerPageHtml);
				echo "\nresult\n";
				var_dump($result);
				echo "\nrequestUrl\n";
				var_dump($requestUrl);
				
				echo "\n========================================\n";
				//throw new Exception();
				// 切换视频时有时候会出现空，只记录不再抛异常 
				// 可能是在进程sleep的时候 正好看到了100% 
				// 结果while没有跳出，导致数据异常，现在发现数据不正常就直接break去看下一个视频
				break;
				
			} else {
				echo "\n========================================\n";
				echo " 完成进度 ".$result["Value"]["Process"]."%   " . date("Y-m-d H:i:s");
				echo "\n========================================\n";
				
				if( $result["Value"]["Process"] >= 100) {	//进度完成，继续下一个
					break;
				}
			}
			
			sleep(5);
			
		} catch ( Exception $e ) {
			
			echo "\n========================================\n";
			echo "返回的数据包异常！！！";
			echo "程序结束！！！";
			echo "\n========================================\n";
			break 2;
		}
	}
}


curl_close($ch);










