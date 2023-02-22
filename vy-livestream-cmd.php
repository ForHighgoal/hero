<?php

/*
# Live Stream Plugin.
# Copyright 2022 by Vanea Young
*/
 

// fetch bootloader
require('bootloader.php');

// api 
if(isset($_POST['source']) && $_POST['source'] == 'api'){
	
 return API_SendNotification();
 exit();
 
}
 

// user access
if ($user->_logged_in || !$system['system_public']) {
  user_access();
}
 

try {
  
	// build engine
	$core = new VY_LIVESTREAM_CORE;
  
    $live = $core->im_live();
 
 
	// get param
	$cmd = isset($_GET['cmd']) ? $core->test_input($_GET['cmd']) : ( isset($_POST['cmd']) ? $core->test_input($_POST['cmd']) : '');
	$view_as_json = isset($_GET['view_as']) ? $core->test_input($_GET['view_as']) : ( isset($_POST['view_as']) ? $core->test_input($_POST['view_as']) : '');
	$id = isset($_GET['id']) ? $core->test_input($_GET['id']) : ( isset($_POST['id']) ? $core->test_input($_POST['id']) : '');
	
	switch ($cmd){
	case 'watchstream':
	header("location: /");
	exit();
	break;
	case 'get-content':
	$live->getContent();
	break;
	case 'popup':
	$live->getPopup();
	break;
	case 'get-prelive-st':
	echo $live->getPreLiveSt();
	break;
	case 'golive':
	$live->goLive();
	break;
	case 'stoplive':
	$live->stopLive();
	break;
	case 'join_live': 
	$live->joinLive();
	break;
	case 'addcomment':
	echo $live->AddComment();
	break;
	case 'showdashboard':
	echo $live->showdashboard();
	break;
	case 'get-viewers':
	echo $live->getViewers();
	break;
	case 'get-available-for-moder':
	echo $live->availableViewersForModerator();
	break;
	case 'remove-moderators':
	echo $live->removeModerators();
	break;
	case 'get-userdetails':
	echo json_encode($live->lv_getUserDetails($id));
	break;
	case 'generate-stream-key':
	echo $live->generateUniqueStreamKey();
	break;
	case 'record':
	$live->recording();
	break;
	case 'rename-obs-file':
	$live->renameObsFile();
	break;
	case 'delete-crashed':
	$live->delete_crashed();
	break;
	case 'delete-broadcast':
	$live->deleteShortVideos();
	break;
	case 'remove-files':
	$live->removeFiles();
	break;
	case 'mob_popup':
	$live->mob_popup();
	break;
	case 'get-turn-credentials':
	echo $live->getTurnCredentials();
	break;
	case 'generateCover':
	$live->generateCover();
	break;
	case 'get_rtmp_hls_path':
	echo $live->getRtmpHLS_Path();
	break;
	default:
        // page header
        page_header(__($system['system_title']) . ' - ' . __("Live Video"));
   		$live->constructPage();
		page_footer("vy_live");

	break;
	
	
	}
	
	} catch (Exception $e) {
	print $e->getMessage();
}
