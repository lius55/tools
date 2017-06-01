<?php
$url = "https://www.amazon.com/gp/search/other/ref=lp_679255011_sa_p_89?rh=n%3A7141123011%2Cn%3A7147441011%2Cn%3A679255011&bbn=679255011&pickerToList=lbr_brands_browse-bin&ie=UTF8&qid=1493181938";

// ------------------------
//          CURL
// ------------------------
// $html = file_get_contents($url);
// var_dump($html);

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

$html =  curl_exec($ch);

// header('Content-type: text/plain; charset=utf-8');

echo $html;
curl_close($ch); //終了