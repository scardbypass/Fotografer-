<?php
$config=require __DIR__."/config.php";require __DIR__."/lib/events.php";
$event=preg_replace("/[^a-zA-Z0-9_-]/","",$_GET["event"]??"");$file=basename($_GET["file"]??"");$events=load_events($config);$meta=$events[$event]??null;
if(!$meta||($meta["visibility"]??"private")!=="public"){http_response_code(403);exit("Forbidden");}
$path=$config["storage"]."/".$event."/".$file;if(!$event||!$file||!is_file($path)){http_response_code(404);exit("Not found");}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);header("Content-Type: ".$mime);header("Cache-Control: public,max-age=86400");readfile($path);
