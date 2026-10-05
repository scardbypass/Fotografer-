<?php
header("Content-Type: application/json");
$config = require dirname(__DIR__) . "/config.php";
$auth = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
if (!hash_equals("Bearer ".$config["upload_token"], $auth)) { http_response_code(401); echo json_encode(["ok"=>false,"error"=>"unauthorized"]); exit; }
$event = preg_replace("/[^a-zA-Z0-9_-]/", "", $_POST["event"] ?? "");
if (!$event || empty($_FILES["photo"]["tmp_name"])) { http_response_code(422); echo json_encode(["ok"=>false,"error"=>"event/photo required"]); exit; }
$finfo = new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($_FILES["photo"]["tmp_name"]);
if (!in_array($mime,["image/jpeg","image/png"])) { http_response_code(415); echo json_encode(["ok"=>false,"error"=>"invalid image"]); exit; }
$dir=$config["storage"]."/".$event; if(!is_dir($dir)) mkdir($dir,0755,true);
$name=date("Ymd_His")."_".bin2hex(random_bytes(4)).".jpg"; $dest=$dir."/".$name;
if(!move_uploaded_file($_FILES["photo"]["tmp_name"],$dest)){http_response_code(500);echo json_encode(["ok"=>false]);exit;}
echo json_encode(["ok"=>true,"event"=>$event,"file"=>$name,"url"=>$config["base_url"]."/photo.php?event=".rawurlencode($event)."&file=".rawurlencode($name)]);
