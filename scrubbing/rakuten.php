<?php
// http://php-archive.net/php/dom-scraping/
error_reporting(E_ERROR);
set_time_limit(0);

/**
 * HTMLとして取り出す
 */
function getInnerHtml($node) {
    $children = $node->childNodes;
    $html = '';
    foreach($children as $child){
        $html .= $node->ownerDocument->saveHTML($child);
    }
    return $html;
}

/**
 * curlでhtmlコンテンツ取得
 */
function getUrlHtml($url) {

	$ch = curl_init();

	// ロボットチェックを避ける
	$headers = array(
	    "HTTP/1.0",
	    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
	    "Connection:keep-alive",
	    "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:26.0) Gecko/20100101 Firefox/26.0"
	    );

	//オプション
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//ヘッダー追加オプション
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	return curl_exec($ch);
}

/**
 * xpathオブジェクト取得
 */
function getXpath($url) {

	$html = getUrlHtml($url);

	$dom = new DOMDocument('1.0', 'UTF-8');
	$html = mb_convert_encoding($html, "HTML-ENTITIES", 'auto');
	$dom->loadHTML($html);

	$xpath = new DOMXPath($dom);
	$xpath->registerNamespace("php", "http://php.net/xpath");
	$xpath->registerPHPFunctions();
	return $xpath;
}

/**
 * 会社概要情報取得
 */
function getCompanyInfo($url) {

	$xpath = getXpath($url);

	// 会社名
	$company_name = 
		getInnerHtml($xpath->query('//*[@id="companyInfo"]/div[2]/div[1]/dl[1]/dt[1]/h1[1]')->item(0));

	// 郵便番号、住所
	$info_line = getInnerHtml($xpath->query('//*[@id="companyInfo"]/div[2]/div[1]/dl[1]/dd[1]')->item(0));
	$info_line_pattern = array();
	$info_line_pattern[] = '/<[\/]*dd>/';
	$info_line_pattern[] = '/<[\/]*span[^<]*>/';
	$info_line_pattern[] = '/<[\/]*a[^<]*>/';
	$info_line_pattern[] = '/[ ]{1,}/';
	$info_line = preg_replace($info_line_pattern, '', $info_line);

	// 郵便番号
	$post_cd = array();
	preg_match('/[-|\d]+/', $info_line, $post_cd);
	$post_cd = ($post_cd.length >= 0) ? $post_cd[0] : '';

	// 住所
	$address = array();
	preg_match('/.*/', $info_line, $address);
	$address = substr($address[0], strpos($info_line, $post_cd) + strlen($post_cd));

	// TEL
	$tel = array();
	preg_match('/TEL:[\d|-]+/i', $info_line, $tel);
	$tel = ($tel.length >= 0) ? preg_replace('/[a-z|A-Z|\:]/', '', $tel[0]) : '';
	
	// FAX
	$fax = array();
	preg_match('/FAX:[\d|-]+/i', $info_line, $fax);
	$fax = ($fax.length >= 0) ? preg_replace('/[a-z|A-Z|\:]/', '', $fax[0]) : '';

	// 代表者
	$recp = array();
	preg_match('/代表者.+/', $info_line, $recp);
	$recp = ($recp.length >= 0) ? preg_replace('/代表者:/', '', $recp[0]) : '';

	// 店舗運営責任者
	$resp = array();
	preg_match('/店舗運営責任者.+/', $info_line, $resp);
	$resp = ($resp.length >= 0) ? preg_replace('/店舗運営責任者:/', '', $resp[0]) : '';

	// 店舗セキュリティ責任者
	$sec = array();
	preg_match('/店舗セキュリティ責任者.+/', $info_line, $sec);
	$sec = ($sec.length >= 0) ? preg_replace('/店舗セキュリティ責任者:/', '', $sec[0]) : '';

	// 店舗連絡先
	$contact = array();
	preg_match('/店舗連絡先.+/', $info_line, $contact);
	$contact = ($contact.length >= 0) ? preg_replace('/店舗連絡先:/', '', $contact[0]) : '';

	// echo "info_line:{$info_line}<br>";
	// echo "post_cd:{$post_cd},address:{$address},tel:{$tel},fax:{$fax},recp:{$recp}" .
		// ",resp:{$resp},sec:{$sec},contact:{$contact}<br>";

	return array($company_name, $post_cd, $address, $tel, $fax, $recp, $resp, $sec, $contact);
}

/**
 * csvファイル出力
 */
function outPutCsv($data_array, $file_name) {
	$file = fopen($file_name, "a");
	if ($file) {
		foreach($data_array as $line) {
			fputcsv($file, $line);
		}
	}
	fclose($file);
}

/**
 * ジャンル指定で店舗詳細情報取得
 */
function getCateDetail($url, $cate_name) {

	$xpath = getXpath($url);

	// 総件数取得
	$num = getInnerHtml($xpath->query('//body/table[7]/tr[1]/td[2]/table[2]/tr[1]/td[2]/table[3]/tr
		[1]/td[1]/font[1]')->item(0));

	$result = array();
	preg_match('/全.+店舗/', $num, $result);

	preg_match_all('/\d+/', $result[0], $result);
	$shop_num = (int)implode($result[0]);
	$page_num = ceil($shop_num / 30);

	echo "[店舗件数：{$shop_num},ページ数：{$page_num}]<br>";

	// file作成
	$file_name = "{$cate_name}.csv";
	touch($file_name);

	for ($page = 1; $page <= $page_num; $page++) {

		$cate_url = "https://directory.rakuten.co.jp/rms/sd/directory/vc?s=19&tz=100371&v=2&f=0&p={$page}&o=35&oid=000&k=0&a=0";
		$xpath = getXpath($cate_url);
		
		$section = array();

		for ($i = 1; ($i <= 30)&&($i+($page-1)*30 <= $shop_num); $i++) {

			$index = $i*2;
			// 開店日　/html/body/table[7]/tbody/tr[1]/td[2]/table[2]/tbody/tr/td[2]/table[4]/tbody/tr[{$index}]/td[2]/font
			$start_date = getInnerHtml($xpath->query("//body/table[7]/tr[1]/td[2]/table[2]/tr[1]/td[2]/table[4]//tr[{$index}]/td[2]/font[1]")->item(0));

			// 店舗名
			$shop_name = getInnerHtml($xpath->query("//body/table[7]/tr[1]/td[2]/table[2]/tr[1]/td[2]/table[4]//tr[{$index}]/td[1]/font[1]/a[1]/b[1]")->item(0));

			$shop_url = $xpath->query("//body/table[7]/tr[1]/td[2]/table[2]/tr[1]/td[2]/table[4]//tr[{$index}]/td[1]/font[1]/a[1]")->item(0);
			$shop_url = $shop_url->getAttribute("href");

			// 感想
			$review = getInnerHtml($xpath->query("//body/table[7]/tr[1]/td[2]/table[2]/tr[1]/td[2]/table[4]//tr[{$index}]/td[1]/a[1]/font[1]")->item(0));
			$review = preg_replace('/[^\d]*/', '', $review);

			// 会社情報取得
			$index = $index + 1;
			$detail_url = $xpath->query("//body/table[7]/tr[1]/td[2]/table[2]/tr[1]/td[2]/table[4]//tr[{$index}]//table[1]//tr[1]/td[2]/font[1]/a[2]")->item(0);
			$detail_url = $detail_url->getAttribute("href");

			// ジャンル,店舗名,店舗リンク,評価数,開店日,運営会社,郵便番号,住所,電話番号,FAX番号,代表者,店舗責任者,セキュリティ責任者,メールアドレス
			$line = array_merge(array($cate_name, $shop_name, $shop_url, $review, $start_date),
				getCompanyInfo(str_replace('http://', 'https://', $detail_url)));

			// echo ($page-1)*30+$i . ":" . join(',', $line) . "<br/>";

			$section[] = $line;

		}
		flush();
	// 	// ob_flush();
		outPutCsv($section, $file_name);
	}

}

// --------------------------
//          処理開始
// --------------------------
$xpath = getXpath("https://www.rakuten.co.jp/shop/");
$cates = $xpath->query('//*[@id="onScript"]/ul/li');
foreach ($cates as $cate) {
	echo "{$cate->nodeValue},{$cate->getElementsByTagName('a')->item(0)->getAttribute('href')}";

	$cate_url = str_replace('http://', 'https://', $cate->getElementsByTagName('a')->item(0)->getAttribute('href'));
	getCateDetail($cate_url, $cate->nodeValue);
}

// getCompanyInfo("https://www.rakuten.co.jp/terracotta/info.html");
// getCompanyInfo("https://www.rakuten.co.jp/teddyshop/info.html");
?>