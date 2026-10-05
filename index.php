<?php
$config=require __DIR__."/config.php";
$event=preg_replace("/[^a-zA-Z0-9_-]/","",$_GET["event"]??"demo");
$dir=$config["storage"]."/".$event; $photos=[];
if(is_dir($dir)){foreach(glob($dir."/*.{jpg,jpeg,png,JPG,JPEG,PNG}",GLOB_BRACE)?:[] as $p)$photos[]=basename($p); rsort($photos);}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="refresh" content="5"><title>Galeri <?=htmlspecialchars($event)?></title><link rel="stylesheet" href="assets/app.css"></head><body><header><div><b>FOTOGRAFER</b><small>Instant Photo Gallery</small></div><span><?=count($photos)?> foto</span></header><main><h1><?=htmlspecialchars($event)?></h1><p>Foto terbaru akan muncul otomatis.</p><div class="grid"><?php foreach($photos as $f):?><a href="photo.php?event=<?=urlencode($event)?>&file=<?=urlencode($f)?>"><img loading="lazy" src="photo.php?event=<?=urlencode($event)?>&file=<?=urlencode($f)?>"></a><?php endforeach?></div></main></body></html>
