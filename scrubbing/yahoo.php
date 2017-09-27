<?php
/**
 * 楽天店舗スクレイピングプログラム
 * 参考サイト：http://php-archive.net/php/dom-scraping/
 */

// エラーのみ表示する
error_reporting(E_ERROR);
// タイムアウトさせない
set_time_limit(0);

/**
 * HTMLとして取り出す
 * @param node
 * @return htmlをstringで返却
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
 * @param url
 * @return dom
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
		getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[1]/div[1]/table[1]/tr[1]/td[1]')->item(0));

	// 問い合わせ窓口
	$inquiry = getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[3]/div[1]/table[1]/tr[1]/td[1]')->item(0));

	// 郵便番号
	$post_cd = getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[3]/div[1]/table[1]/tr[2]/td[1]')->item(0));
	$post_cd = str_replace("〒", '', $post_cd);
	$post_cd = str_replace("-", '', $post_cd);

	// 住所
	$address = getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[3]/div[1]/table[1]/tr[3]/td[1]')->item(0));

	// 電話番号
	$tel = getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[3]/div[1]/table[1]/tr[4]/td[1]')->item(0));

	$temp_str = getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[3]/div[1]/table[1]/tr[5]/th[1]')->item(0));

	// FAX 、email
	if (strpos($temp_str, 'ファックス番号') === false) {
		$fax = '';
		$email = getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[3]/div[1]/table[1]/tr[5]/td[1]')->item(0));
	} else {
		$fax = getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[3]/div[1]/table[1]/tr[5]/td[1]')->item(0));
		$email = getInnerHtml($xpath->query('//*[@id="CentInfoPage1"]/div[3]/div[1]/table[1]/tr[6]/td[1]')->item(0));
	}

	if (strlen($company_name) < 1) {
		// 会社名
		$company_name = 
			getInnerHtml($xpath->query('//*[@id="contents"]/div/dl[1]/dt[1]')->item(0));
		$company_name = str_replace("ストア名：", "", $company_name);

		$comp_info = getInnerHtml($xpath->query('//*[@id="contents"]/div[1]/dl[1]/dd[6]')->item(0));

		// 事業責任者
		$result_ary = array();
		preg_match('/事業責任者: [^<]+/', $comp_info, $result_ary);
		$inquiry = str_replace('事業責任者:', '', $result_ary[0]);

		// 郵便番号
		preg_match('/郵便番号: [^<]+/', $comp_info, $result_ary);
		$post_cd = str_replace('郵便番号:', '', $result_ary[0]);

		// 都道府県
		preg_match('/都道府県: [^<]+/', $comp_info, $result_ary);
		$address = str_replace('都道府県: ', '', $result_ary[0]);
		preg_match('/市区町村: [^<]+/', $comp_info, $result_ary);
		$address .= str_replace('市区町村: ', '', $result_ary[0]);
		preg_match('/番地・号: [^<]+/', $comp_info, $result_ary);
		$address .= str_replace('番地・号: ', '', $result_ary[0]);
		preg_match('/ビル名・室番号: [^<]+/', $comp_info, $result_ary);
		$address .= str_replace('ビル名・室番号: ', '', $result_ary[0]);

		// 電話番号
		preg_match('/電話番号: [^<]+/', $comp_info, $result_ary);
		$tel = str_replace('電話番号: ', '', $result_ary[0]);

		// メールアドレス
		preg_match('/メールアドレス: [^<]+/', $comp_info, $result_ary);
		$email = str_replace('メールアドレス: ', '', $result_ary[0]);
	}

	$ret_ary = array($company_name, $inquiry, $post_cd, $address, $tel, $fax, $email); 
	// echo "ret_ary:" . join(',', $ret_ary);
	return $ret_ary;
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
	$num = getInnerHtml($xpath->query('//*[@id="shpMain"]/div[1]/div[1]/div[1]/div[1]/div[1]/div[1]/dl[1]/dd[1]/em[1]')->item(0));

	$shop_num = (int)str_replace(",", "", $num);
	$page_num = ceil($shop_num/50);

	echo "[店舗件数：{$shop_num},ページ数：{$page_num}]<br>";

	// file作成
	$file_name = "yahoo/{$cate_name}.csv";
	touch($file_name);

	for ($page = 1; $page <= $page_num; $page++) {

		$start = ($page-1)*50; 
		$cate_url = $url . "?b={$start}";
		echo $cate_url;
		$xpath = getXpath($cate_url);
		
		$section = array();

		$content = $xpath->query('//*[@id="shpMain"]/div[2]/div[1]/div[1]/div[3]/div[1]/ul[1]/li');

		foreach($content as $node) {

			// 店舗名
			$shop_name = getInnerHtml($node->getElementsByTagName("span")->item(0));

			// url
			$shop_url = $node->getElementsByTagName("a")->item(0)->getAttribute("href");

			// 点数、評価数
			$score = getInnerHtml($node->getElementsByTagName("a")->item(1)->getElementsByTagName("span")->item(6));
			$score = str_replace('点', '', $score);
			$score_num = getInnerHtml($node->getElementsByTagName("a")->item(1)->getElementsByTagName("span")->item(7));
			$score_num = preg_replace('/[^\d]+/', '', $score_num);

			// 会社情報
			$line = array_merge(array($cate_name,$shop_name, $shop_url, $score, $score_num), getCompanyInfo($shop_url . "info.html"));
			$section[] = $line;
			// echo "line:" . join(',', $line) . "<br>";
		}

		flush();
		outPutCsv($section, $file_name);
		echo "[{$page}ページ目処理終了.]<br>";
	}

}

// --------------------------
//          処理開始
// --------------------------
$processed = array();
// $processed[] = 'ファッション';
// $processed[] = '食品';
// $processed[] = 'アウトドア、釣り、旅行用品';
// $processed[] = 'ダイエット、健康';
// $processed[] = 'コスメ、美容、ヘアケア';
// $processed[] = 'スマホ、タブレット、パソコン';
// $processed[] = 'テレビ、オーディオ、カメラ';
// $processed[] = '家電';
// $processed[] = '家具、インテリア';
// $processed[] = '花、ガーデニング';
// $processed[] = 'キッチン、日用品、文具';
// $processed[] = 'DIY、工具';
// $processed[] = 'ペット用品、生き物';
// $processed[] = '楽器、手芸、コレクション';
// $processed[] = 'ゲーム、おもちゃ';
// $processed[] = 'ベビー、キッズ、マタニティ';
// $processed[] = 'スポーツ';
// $processed[] = '車、バイク、自転車';
// $processed[] = 'CD、音楽ソフト、チケット';
// $processed[] = 'DVD、映像ソフト';
// $processed[] = '本、雑誌、コミック';
$processed[] = 'レンタル、各種サービス';

// 店舗トップページ
$xpath = getXpath("https://shopping.yahoo.co.jp/stores/");
// ジャンル取得
$cates = $xpath->query('//*[@id="shpMain"]/div[1]/div[1]/div[1]/div[1]/div[1]/div[1]/dl[1]/dd[1]/ul/li');

foreach ($cates as $cate) {

	$cate_url = $cate->getElementsByTagName('a')->item(0)->getAttribute('href');
	echo "{$cate->nodeValue},{$cate_url}<br>";
	
	if (preg_match("/{$cate->nodeValue}+/", join(",", $processed)) == 1) { 
		echo "スクレイピング完了のためスキップ致します。<br>";
		continue; 
	}

	getCateDetail($cate_url, $cate->nodeValue);
}

// getCompanyInfo("https://store.shopping.yahoo.co.jp/aaa01dia/info.html");
// getCompanyInfo("https://store.shopping.yahoo.co.jp/arch38/info.html");

// getCateDetail("https://shopping.yahoo.co.jp/category/13457/stores/", "test");
?>