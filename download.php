<?php
declare(strict_types=1);
$config=require __DIR__.'/lib/bootstrap.php'; require __DIR__.'/lib/events.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
$eventId=preg_replace('/[^a-zA-Z0-9_-]/','',(string)($_POST['event']??$_GET['event']??''));
$events=load_events($config);$event=$events[$eventId]??null;
if(!$event){http_response_code(404);exit('Event tidak ditemukan');}
if(($event['visibility']??'private')!=='public'&&!($_SESSION['gallery_access'][$eventId]??false)){http_response_code(403);exit('Akses ditolak');}
$dir=storage_path($config).'/'.$eventId;
$requested=$_POST['files']??[];
if(!is_array($requested))$requested=[];
$files=[];
foreach($requested as $name){$name=basename((string)$name);$path=$dir.'/'.$name;if(is_file($path)){ $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);if(in_array($mime,['image/jpeg','image/png'],true))$files[$name]=$path;}}
if(!$files){http_response_code(400);exit('Pilih minimal satu foto.');}
if(count($files)===1){$name=array_key_first($files);$path=$files[$name];header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.str_replace('"','',$name).'"');header('Content-Length: '.filesize($path));readfile($path);exit;}
if(!class_exists('ZipArchive')){http_response_code(500);exit('Ekstensi ZIP belum aktif di hosting.');}
$tmp=tempnam(sys_get_temp_dir(),'fotozip_');$zip=new ZipArchive();if($zip->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){http_response_code(500);exit('Gagal membuat ZIP.');}
foreach($files as $name=>$path)$zip->addFile($path,$name);$zip->close();
$safe=preg_replace('/[^a-zA-Z0-9_-]+/','-',(string)($event['name']??$eventId));header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$safe.'-foto.zip"');header('Content-Length: '.filesize($tmp));readfile($tmp);@unlink($tmp);
