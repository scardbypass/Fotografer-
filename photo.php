<?php
$config=require __DIR__."/config.php";
$event=preg_replace("/[^a-zA-Z0-9_-]/","",$_GET["event"]??""); $file=basename($_GET["file"]??"");
$path=$config["storage"]."/".$event."/".$file;
if(!$event||!$file||!is_file($path)){http_response_code(404);exit("Not found");}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path); header("Content-Type: ".$mime); header("Cache-Control: public,max-age=86400"); readfile($path);
