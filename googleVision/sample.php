<?php
	// APIキー
	$api_key = "";

	// リファラー (許可するリファラーを設定した場合)
	$referer = "https://cdn.softbank.jp/mobile/set/data/info/personal/news/shop/20170531a/img/p/fig_main.jpg";

	// 画像へのパス
	// $image_path = "https://cdn.softbank.jp/mobile/set/data/info/personal/news/shop/20170531a/img/p/fig_main.jpg";
	$image_path = "http://localhost:8888/tools/googleVision/14614_BLA161048_L_FLP_01_0000_max_600x600.jpg";

	// リクエスト用のJSONを作成
	$json = json_encode( array(
		"requests" => array(
			array(
				"image" => array(
					"content" => base64_encode( file_get_contents( $image_path ) ) ,
				),
				"features" => array(
					array(
						"type" => "TEXT_DETECTION" ,
						"maxResults" => 3 ,
					)
				),
			),
		),
	));

	$before = ceil(microtime(true)*1000);

	// リクエストを実行
	$curl = curl_init() ;
	curl_setopt( $curl, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=" . $api_key ) ;
	curl_setopt( $curl, CURLOPT_HEADER, true ) ; 
	curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "POST" ) ;
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( "Content-Type: application/json" ) ) ;
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false ) ;
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ) ;
	// if( isset($referer) && !empty($referer) ) curl_setopt( $curl, CURLOPT_REFERER, $referer ) ;
	curl_setopt( $curl, CURLOPT_TIMEOUT, 15 ) ;
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $json ) ;
	$res1 = curl_exec( $curl ) ;
	$res2 = curl_getinfo( $curl ) ;
	curl_close( $curl ) ;

	// 取得したデータ
	$json = substr( $res1, $res2["header_size"] ) ;				// 取得したJSON
	$header = substr( $res1, 0, $res2["header_size"] ) ;		// レスポンスヘッダー

	// 出力
	echo "<h2>JSON</h2>" ;
	echo $json ;

	echo "<h2>ヘッダー</h2>" ;
	echo $header ;

	$after = ceil(microtime(true)*1000);
	$process_time = $after - $before;
	echo "<h2>処理時間</h2>";
	echo "beofre:" . $before . "<br/>";
	echo "after:" . $after . "<br/>";
	echo $process_time;

