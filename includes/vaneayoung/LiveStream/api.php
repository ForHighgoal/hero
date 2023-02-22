<?php 

 
function API_SendNotification(){
	global $db;
	
	
    $notif_text = '';
	$type = 'post';
	$time = time();
	$notif = isset($_POST['notification']) ? $_POST['notification'] : 0;
	$recipient_id = isset($_POST['id']) ? $_POST['id'] : 0;
	$post_id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;
	$notifier_id = API_GetAnyNotifierId($recipient_id);
	$url = '/post/'.$post_id;
	 
	if(is_numeric($post_id) && is_numeric($recipient_id) && $post_id > 0  && $recipient_id > 0){
 
		switch($notif){
			
			case 'processing_stream':
	 
							
				$notif_text = 'Your live stream its generating now by our system. You will be notified when it is ready.';
				$db->query("insert into " .VY_LV_TBL["NOTIF"] ." set `time`=NOW(),`to_user_id`='{$recipient_id}',`from_user_id`='0',`node_type`='{$type}',`node_url`='{$url}',`message`='{$notif_text}'");
 
				// make the post invisible untill the stream its processed
				$db->query("update " .VY_LV_TBL["POSTS"] ." set `is_hidden`='1' where `post_id`='{$post_id}'");

			break;
			
			case 'stream_processed':
				$notif_text = 'Your live stream its ready! Now you can see it on your timeline.';
				$db->query("insert into ".VY_LV_TBL["NOTIF"]." set `time`=NOW(),`to_user_id`='{$recipient_id}',`from_user_id`='0',`node_type`='{$type}',`url`='{$url}',`message`='{$notif_text}'");
				
				// set the post available
				$db->query("update " .VY_LV_TBL["POSTS"] ." set `is_hidden`='0' where `post_id`='{$post_id}'");
			
			break;
			
			
			
		}
	}
	return true;
	
}
 
function API_GetAnyNotifierId($id = 0){
	global $db;
	
	$id = $id > 0 ? $id : 2;
    $q = $db->query("select `user_id` from " .VY_LV_TBL["USERS"] ." where `user_id`<>'{$id}' limit 1");
    $r = $q->fetch_array(MYSQLI_ASSOC);
 
	if(isset($r['user_id']))
		$id = $r['user_id'];
 
	
	return $id;
	
}