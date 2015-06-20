<?php 

// 请求
// http://hnwcf.59iedu.com/Home/Timing?
// usrId=63735c7761074d838b05c95f5ee5346a &
// medId=ba42ecce41e2458394d5299124806a31&
// callback=jsonp1433817771187&
// CurrentTimespan=53&
// Id=97cbbe2b8b38496aafe2d09a7544c41b&
// SscId=da33d7dac0394fce8341dcf4f9cbbeaf&
// SstId=f1edff6455364540b20556d6b64d7528&
// CourseSdlId=8c51fe48930a42b7a1bf680b91d4bd0f&
// TrainSdlId=7e69e97329cc4deb9c5f319d0da2e953&
// Type=2&
// CurrentLength=30 ++自增

//通过播放页面返回的html拼装需要请求的url
function GetRequestUrlByHtml($html)
{
	if( trim($html) == "" ) {
		echo "\n=======================\n";
		echo "需要解析的html为空，返回";
		return ;
		echo "\n=======================\n";
	}
	
	
	$verifyUrl = "";

	preg_match("/var timingUrl = (.*)(?=;)/", $html, $matches);

	$matches = explode("\"", $matches[1]);
	$verifyUrl .= $matches[1];		//url + usrId
	$verifyUrl .= $matches[3];		//medId
	//$verifyUrl .= "&callback=?";	//callback

	preg_match("/CurrentTimespan:(.*)(?=,)/", $html, $matches);
	$verifyUrl .= "&CurrentTimespan=" . trim($matches[1]);

	preg_match("/TrainSdlId:(.*)(?=,)/", $html, $matches);
	$verifyUrl .= "&TrainSdlId=" . trim($matches[1]);

	preg_match("/Id:(.*)(?=,)/", $html, $matches);
	$verifyUrl .= "&Id=" . trim($matches[1]);

	preg_match("/SscId:(.*)(?=,)/", $html, $matches);
	$verifyUrl .= "&SscId=" . trim($matches[1]);

	preg_match("/SstId:(.*)(?=,)/", $html, $matches);
	$verifyUrl .= "&SstId=" . trim($matches[1]);

	preg_match("/CourseSdlId:(.*)(?=,)/", $html, $matches);
	$verifyUrl .= "&CourseSdlId=" . trim($matches[1]);

	preg_match("/TrainSdlId:(.*)(?=,)/", $html, $matches);
	$verifyUrl .= "&TrainSdlId=" . trim($matches[1]);

	$verifyUrl = str_replace("\"", "", $verifyUrl);

	static $number = 0;
	$verifyUrl .= "&Type=2";
	$verifyUrl .="&CurrentLength=".$number++;


	return $verifyUrl;
}


//支持跳转的curl
function curl_redirect_exec($ch, &$redirects, $curlopt_returntransfer = false, $curlopt_maxredirs = 10, $curlopt_header = false) {
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($ch);

	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$exceeded_max_redirects = $curlopt_maxredirs > $redirects;
	$exist_more_redirects = false;
	if ($http_code == 301 || $http_code == 302) {
		if ($exceeded_max_redirects) {
			list($tmp, $header) = explode("\r\n\r\n", $data, 2);
			$matches = array();
			preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
			$url = trim(array_pop($matches));
			$url_parsed = parse_url($url);
			if (isset($url_parsed)) {

				if(strpos($url,'http') === false){
					echo $url = "http://xy.gkwlpx.com".$url;
				}
				curl_setopt($ch, CURLOPT_URL, $url);
				$redirects++;
				return curl_redirect_exec($ch, $redirects, $curlopt_returntransfer, $curlopt_maxredirs, $curlopt_header);
			}
		} else {
			$exist_more_redirects = true;
		}
	}
	if ($data !== false) {
		if (!$curlopt_header)
			list(,$data) = explode("\r\n\r\n", $data, 2);
		if ($exist_more_redirects) return false;
		if ($curlopt_returntransfer) {
			return $data;
		} else {
			if (curl_errno($ch) === 0) return true;
			else return false;
		}
	} else {
		return false;
	}
}

?>