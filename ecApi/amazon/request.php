<?php
require_once 'config.php'; 

$searchIndex = $_REQUEST["searchIndex"];
$keywords = $_REQUEST["keywords"];

ItemSearch("All", "アナと雪の女王");
 
//Set up the operation in the request
function ItemSearch($SearchIndex, $Keywords){
 
    $baseurl = "http://webservices.amazon.co.jp/onca/xml";
 
    // リクエストのパラメータ作成
    $params = array();
    $params["Service"]          = "AWSECommerceService";
    $params["AWSAccessKeyId"]   = Access_Key_ID;
    // $params["Version"]          = "2013-08-01";
    $params["Operation"]        = "ItemSearch";
    $params["SearchIndex"]      = $SearchIndex;
    $params["Keywords"]         = $Keywords;
    $params["AssociateTag"]     = Associate_tag;
    $params["ResponseGroup"]    = "Images,ItemAttributes,Offers";
     
    /* 0.元となるリクエスト */
    $base_request = "";
    foreach ($params as $k => $v) {
        $base_request .= "&" . $k . "=" . $v;
    }
    $base_request = $baseurl . "?" . substr($base_request, 1);
    // echo "【0.元となるリクエスト】<br>" . $base_request . "<br>";
     
    /* 1.タイムスタンプを付ける */
    $params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
    $base_request .= "&Timestamp=" . $params['Timestamp'];
    // echo "【1.タイムスタンプを付ける】<br>" . $base_request . "<br>";
 
    /* 2.「RFC 3986」形式でエンコーディング */
    $base_request = "";
    foreach ($params as $k => $v) {
        $base_request .= '&' . $k . '=' . rawurlencode($v);
        $params[$k] = rawurlencode($v);
    }
    $base_request = $baseurl . "?" . substr($base_request, 1);
    // echo "【2.「RFC 3986」形式でエンコーディング】<br>" . $base_request . "<br>";
     
    /* 3.「&」とか消して改行 */
    $base_request = preg_replace("/.*\?/", "", $base_request);
    $base_request = str_replace("&", "\n", $base_request);
    // echo "【3.「&」とか消して改行】<br>" . $base_request . "<br>";
     
    /* 4.パラメーター名で昇順ソート */
    ksort($params);
     
    $base_request = "";
    foreach ($params as $k => $v) {
        $base_request .= "&" . $k . "=" . $v;
    }
    $base_request = substr($base_request, 1);
    $base_request = str_replace("&", "\n", $base_request);
    // echo "【4.パラメーター名で昇順ソート】<br>" . $base_request . "<br>";
     
    /* 5.もう一度「&」でつなぐ */
    $base_request = str_replace("\n", "&", $base_request);
    // echo "【5.もう一度「&」でつなぐ】<br>" . $base_request . "<br>";
     
    /* 6.3行を頭に追加 */
    $parsed_url = parse_url($baseurl);
    $base_request = "GET\n" . $parsed_url['host'] . "\n" . $parsed_url['path'] . "\n" . $base_request;
     
    //$base_request = "GET\nwebservices.amazon.com\n/onca/xml\n" . $base_request;
    // echo "【6.3行を頭に追加】<br>" . $base_request . "<br>";
     
    /* 7.よく分からんエンコーディング */
    $signature = base64_encode(hash_hmac('sha256', $base_request, Secret_Access_Key, true));
    $signature = rawurlencode($signature);
    // echo "【7.よく分からんエンコーディング】<br>" . $signature . "<br>";
     
    /* 8.「Signature」として最後に追加 */
    $base_request = "";
    foreach ($params as $k => $v) {
        $base_request .= "&" . $k . "=" . $v;
    }
    $base_request = $baseurl . "?" . substr($base_request, 1) . "&Signature=" . $signature;
    // echo "【8.「Signature」として最後に追加】<br>" . $base_request . "<br>";
     
    echo "<a href=\"" . $base_request . "\" target=\"_blank\">結果</a>";
}

?>