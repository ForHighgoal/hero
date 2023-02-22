<?php 

/*

Live stream plugin.

email: movileanuion@gmail.com 

Copyright 2022 by Vanea Young 

*/

$c = glob(dirname(__FILE__). DIRECTORY_SEPARATOR ."ini". DIRECTORY_SEPARATOR ."*.ini");
$CONFIG = parse_ini_file($c[0]); 

 
date_default_timezone_set($CONFIG['timezone']);

 
// website url
$wo['vy-livestream']['site_url'] = $wo['site_url'];
$wo['vy-livestream']['u_scheme'] = $_SERVER['SERVER_PORT'] != 443 ? 'http://' :  'https://';

// version
$wo['vy-livestream']['version'] = file_get_contents($wo['vy-livestream']['u_scheme'].'liveplugin.lyoncat.com/version', false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
$wo['vy-livestream']['license_key'] = $CONFIG['st__PURCHASE_KEY'];
$wo['vy-livestream']['blank'] = $CONFIG['st__DEFAULT_BLANK'];
$wo['vy-livestream']['original_theme_name'] = $CONFIG['st__ORIGINAL_THEME_NAME'];

// ports
$wo['vy-livestream']['port']['media_server'] = $CONFIG['st__MEDIA_SERVER'];
$wo['vy-livestream']['port']['media_server_tls'] = $CONFIG['st__MEDIA_SERVER_TLS'];
$wo['vy-livestream']['port']['rtmp_http'] = $CONFIG['st__RTMP_HTTP'];
$wo['vy-livestream']['port']['rtmp_https'] = $CONFIG['st__RTMP_HTTPS'];
$wo['vy-livestream']['port']['rtmp'] = $CONFIG['st__RTMP_PORT'];
$wo['vy-livestream']['port']['rtmp_tls'] = $CONFIG['st__RTMP_PORT_TLS'];


$wo['vy-livestream']['nodejs_server']['port'] = $CONFIG['st__SERVER_PORT']; 
$wo['vy-livestream']['nodejs_server']['url'] = $CONFIG['st__APP_SERVER_URL'];
$wo['vy-livestream']['nodejs_server']['full_url'] = "{$CONFIG['st__APP_SERVER_URL']}:{$CONFIG['st__SERVER_PORT']}";
$wo['vy-livestream']['nodejs_server']['RTMP_URL'] = "{$wo['vy-livestream']['u_scheme']}{$CONFIG['st__APP_SERVER_URL']}:{$CONFIG['st__RTMP_HTTPS']}"; 



// sql tables
define('VY_LV_TBL', array(	'COMMENTS' => $CONFIG['tbl_comments'],
							'USERS' => $CONFIG['tbl_users'],
							'BROADCASTS'=> $CONFIG['tbl_vy_lv_broadcasts'],
							'CONF'=> $CONFIG['tbl_vy_lv_config'],
							'NOTIF'=> $CONFIG['tbl_notif'],
							'PAGES' => $CONFIG['tbl_pages'],
							'GROUPS' => $CONFIG['tbl_groups'],
							'FOLLOWERS' => $CONFIG['tbl_followers'],
							'POSTS_LIVE' => $CONFIG['tbl_posts_live'],
							'POSTS'=> $CONFIG['tbl_posts']));
 
// reactions
define('VY_LV_REACTIONS',array(			array("id" => 1, "class" => "__like", "name" => "like"),
										array("id" => 2, "class" => "__heart", "name" => "heart"),
										array("id" => 3, "class" => "__haha", "name" => "haha"),
										array("id" => 4, "class" => "__wow", "name" => "wow"),
										array("id" => 5, "class" => "__cry", "name" => "cry"),
										array("id" => 6, "class" => "__angry", "name" => "angry"),
										));

$wo['vy-livestream']['plugin_assets'] = $CONFIG['st__PLUGIN_ASSETS'];
$wo['vy-livestream']['host'] = $wo['vy-livestream']['site_url'];

$wo['vy-livestream']['sounds'] = array( "success" => $wo['vy-livestream']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__success'],
										"countdown2" => $wo['vy-livestream']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__countdown2'],
										"countdown" => $wo['vy-livestream']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__countdown'],
										"click" => $wo['vy-livestream']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__clickuibut'], 
										"openpopup" => $wo['vy-livestream']['plugin_assets']. DIRECTORY_SEPARATOR . $CONFIG['st__openpopup']);
 

// Record streams
$wo['vy-livestream']['record'] = array("recording" => $CONFIG['st__recording'], // record bool true|false
									   "record_type" => $CONFIG['st__record_type'], // allowed format > [.mp4 or .webm], record video format webm or mp4 [this is only for local device streaming]
									   "mp4_high_quality" => $CONFIG['st__mp4_high_quality'], // this only works if record_type option is set to .mp4 value,
																	// if you enable this to true the video size will increase x4, 
																	// for example a 1 minute video size will be almost 100MB
									   "record_path" => $CONFIG['st__STORAGE_DIR'],
									   "obs_enabled" => $CONFIG['st__rtmp_enabled'],
									   "away_desktop" => $CONFIG['st__away_desktop'],
									   "reconnecting" => $CONFIG['st__reconnecting_notif'],
									   "audioBitsPerSecond" => $CONFIG['st__audioBitsPerSecond'],
									   "videoBitsPerSecond" => $CONFIG['st__videoBitsPerSecond'],
									   "fr_miliseconds" => $CONFIG['st__fr_miliseconds'],// the number of milliseconds to record into each Blob
									   "p_path" => getcwd() . DIRECTORY_SEPARATOR,
									   "stream_secret" => $CONFIG['st__stream_secret'], // stream secret, any word
									   "stream_key_prefix" => $CONFIG['st__stream_prefix'], // stream key prefix
									   "ffmpeg_path"=> $CONFIG['st__ffmpeg_path'], // define ffmpeg location [ $ whereis ffmpeg ]
									   "app_name"=> $CONFIG['st__app_name'], // define app name 
									   "hls"=>$CONFIG['st__hls'], // hls bool true/false
									   "hlsFlags"=>$CONFIG['st__hlsFlags'], // hls options
									   "dash"=> $CONFIG['st__dash'], // dash bool true/false
									   "dashFlags"=>$CONFIG['st__dashFlags'], // dash options
									   "mp4Flags" => $CONFIG['st__mp4Flags'] // mp4 options 
									   );
									   
$wo['vy-livestream']['record']['media_root'] = $wo['vy-livestream']['record']['p_path'].$wo['vy-livestream']['record']['record_path'] . DIRECTORY_SEPARATOR;
$wo['vy-livestream']['record']['host'] = $_SERVER["SERVER_NAME"];
$wo['vy-livestream']['record']['tables'] = call_user_func(function() {
													 $tbls = array();
													 foreach(VY_LV_TBL as $key => $value ){  $tbls['VY_LV_TBL_'.$key] = $value; }
													 return $tbls;
												  });

 			   
									   