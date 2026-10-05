<?php
function load_events(array $c):array{$f=$c["storage"]."/events.json";if(!is_file($f))return[];$v=json_decode(file_get_contents($f),true);return is_array($v)?$v:[];}
function save_events(array $c,array $e):void{if(!is_dir($c["storage"]))mkdir($c["storage"],0755,true);file_put_contents($c["storage"]."/events.json",json_encode($e,JSON_PRETTY_PRINT),LOCK_EX);}
function token_hash(string $t):string{return hash("sha256",$t);}
function new_upload_token():string{return "ft_".bin2hex(random_bytes(24));}
function find_event_by_token(array $events,string $token):?array{$h=token_hash($token);foreach($events as $e){if(($e["active"]??false)&&hash_equals($e["token_hash"]??"",$h))return $e;}return null;}
