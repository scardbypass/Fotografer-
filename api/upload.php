<?php
header("Content-Type: application/json");
$config=require dirname(__DIR__)."/config.php";require dirname(__DIR__)."/lib/events.php";
$auth=$_SERVER["HTTP_AUTHORIZATION"]??"";
if(!preg_match("/^Bearer\\s+(.+)$/",$auth,$m)){http_response_code(401);echo json_encode(["ok"=>false,"error"=>"token required"]);exit;}
$events=load_events($config);$event=find_event_by_token($events,$m[1]);
if(!$event){http_response_code(401);echo json_encode(["ok"=>false,"error"=>"invalid token"]);exit;}
if(empty($_FILES["photo"]["tmp_name"])){http_response_code(422);echo json_encode(["ok"=>false,"error"=>"photo required"]);exit;}
$tmp=$_FILES["photo"]["tmp_name"];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
if(!in_array($mime,["image/jpeg","image/png"],true)){http_response_code(415);echo json_encode(["ok"=>false,"error"=>"invalid image"]);exit;}
$dir=$config["storage"]."/".$event["id"];if(!is_dir($dir))mkdir($dir,0755,true);
$ext=$mime==="image/png"?"png":"jpg";$name=date("Ymd_His")."_".bin2hex(random_bytes(5)).".".$ext;
if(!move_uploaded_file($tmp,$dir."/".$name)){http_response_code(500);echo json_encode(["ok"=>false,"error"=>"save failed"]);exit;}
$id=$event["id"];$events[$id]["last_upload"]=date(DATE_ATOM);$events[$id]["upload_count"]=($events[$id]["upload_count"]??0)+1;save_events($config,$events);
echo json_encode(["ok"=>true,"event"=>$id,"file"=>$name]);
