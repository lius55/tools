<?php
header('Content-type: text/html; charset=utf-8');
header('Content-Type: application/json');

$response->responseCd = "00";

// 画像保存
if (isset($_FILES['img'])) {
	$imageFile = $_FILES['img']['tmp_name'];
	$ext = end(explode('.', $_FILES['img']['name']));
	$imageFileName = 'test.' . $ext;
	move_uploaded_file($imageFile, './' . $imageFileName);
	$response->responseCd = "01" . $imageFileName . "|" . $_FILES['img']['name'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>