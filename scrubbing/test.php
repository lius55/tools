<?php

// phpQueryの読み込み
require_once("phpQuery-onefile.php");

$doc = phpQuery::newDocumentFile("https://www.amazon.com/gp/search/other/ref=lp_679255011_sa_p_89?rh=n%3A7141123011%2Cn%3A7147441011%2Cn%3A679255011&bbn=679255011&pickerToList=lbr_brands_browse-bin&ie=UTF8&qid=1493181938");

echo $doc;

?>