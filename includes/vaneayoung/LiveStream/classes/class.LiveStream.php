<?php

/*

Plugin Live Stream
email: movileanuion@gmail.com 
Copyright 2022 by Vanea Young 

*/

require_once "class.core.php";

class LIVE_STREAM extends VY_LIVESTREAM_CORE
{
    public $userid = 0;
    public $now;
    public $id;
    public $action;
    public $categ;
    public $page_id;
    public $group_id;

    public function __construct()
    {
        global $wo;

        //the old building from parent class
        parent::__construct();
        $this->userid = $this->USER["id"];
        $this->now = time();

        $this->id = isset($_POST["id"])
            ? $this->test_input($_POST["id"])
            : (isset($_GET["id"])
                ? $this->test_input($_GET["id"])
                : 0);
        $this->page_id = isset($_POST["page_id"])
            ? $this->test_input($_POST["page_id"])
            : (isset($_GET["page_id"])
                ? $this->test_input($_GET["page_id"])
                : 0);
        $this->group_id = isset($_POST["group_id"])
            ? $this->test_input($_POST["group_id"])
            : (isset($_GET["group_id"])
                ? $this->test_input($_GET["group_id"])
                : 0);
        $this->action = isset($_POST["action"])
            ? $this->test_input($_POST["action"])
            : (isset($_GET["action"])
                ? $this->test_input($_GET["action"])
                : "");
        $this->categ = isset($_POST["categ"])
            ? $this->test_input($_POST["categ"])
            : (isset($_GET["categ"])
                ? $this->test_input($_GET["categ"])
                : "");
    }
    public function deleteShortVideos()
    {
        $resp = 0;
        $post_id = $this->id;
        $broadcast_id = $this->post_vars("broadcast_id");
        $filename = $this->post_vars("filename");
        $file_type = $this->post_vars("file_type");
        $cover = $post_id . ".png";

        $q = $this->db->query(
            "select `user_id` from " .
                VY_LV_TBL["BROADCASTS"] .
                " where `id`='{$broadcast_id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        if (isset($r["user_id"]) && $r["user_id"] == $this->userid) {
            // delete post
            $delete_post = $this->deletePost($post_id);
            // delete broadcast
            $delete_broadcast = $this->deleteBroadCast($broadcast_id);
            // delete comments
            $delete_comments = $this->deleteComments($post_id);

            // delete files
            //sleep(20); // wait 20 seconds
            $this->deleteFiles($filename, $cover);

            $resp = 1;
        }

        echo $resp;
    }
    public function delete_crashed(){

        $resp = 0;
 
        $q = $this->db->query(
            "select `id`,`stream_name` from " .
                VY_LV_TBL["BROADCASTS"] .
                " where `post_id`='{$this->id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

            // delete post
            $delete_post = $this->deletePost($this->id);
            // delete broadcast
            $delete_broadcast = $this->deleteBroadCast($r['id']);
            // delete comments
            $delete_comments = $this->deleteComments($this->id);

            // delete files
            $this->removeFiles($r['stream_name']);
 
            $resp = 1;

        echo $resp; 
           }
    public function removeFiles($filename = '')
    {
        $post_id = $this->id;
        $filename = !empty($filename) ? $filename : $this->post_vars("filename");
        $cover = $post_id . ".png";

        $this->deleteFiles($filename, $cover);
        return true;
    }
    public function deleteFiles($stream_filename, $cover)
    {
        $stream =
            sprintf($this->upload_path_blobs, $this->USER["id"]) .
            $stream_filename;
        $cover = sprintf($this->upload_path_covers, $this->USER["id"]) . $cover;

        // delete stream
        if (!empty($stream_filename) && file_exists($stream . ".webm")) {
            unlink($stream . ".webm");
        } elseif (!empty($stream_filename) && file_exists($stream . ".mp4")) {
            unlink($stream . ".mp4");
        }

        // delete cover
        if (file_exists($cover)) {
            unlink($cover);
        }

        return true;
    }
    public function deleteBroadCast($id = 0)
    {
        if (
            $this->query_delete(
                "delete from " . VY_LV_TBL["BROADCASTS"] . " where `id`='{$id}'"
            )
        ) {
            return true;
        } else {
            return false;
        }
    }
    public function deletePost($id = 0)
    {
        $this->db->query("delete from ".VY_LV_TBL["POSTS"]." where `post_id`='{$id}'");
        $this->db->query("delete from ".VY_LV_TBL["POSTS_LIVE"]." where `post_id`='{$id}'");
    }
    public function deleteComments($post_id = 0)
    {
        if (
            $this->query_delete(
                "delete from " .
                    VY_LV_TBL["COMMENTS"] .
                    " where `node_id`='{$post_id}'"
            )
        ) {
            return true;
        } else {
            return false;
        }
    }
    public function generateUniqueStreamKey()
    {
        $r = $this->lang["error_generating_stream_key"];
        $user_id = $this->USER["id"];
        $timestamp = ((time() / 1000) | 0) + 10;
        $key = md5($timestamp) . "--" . md5($this->USER["id"]);

        if (
            $this->query_update(
                "update " .
                    VY_LV_TBL["USERS"] .
                    " set `vy-live-streamkey`='{$key}' where `user_id`='{$user_id}'"
            )
        ) {
            $r = $key;
        }

        return $r;
    }

    public function getUserSstreamKey()
    {
        $user_id = $this->USER["id"];
        $q = $this->db->query(
            "select `vy-live-streamkey` from " .
                VY_LV_TBL["USERS"] .
                " where `user_id`='{$user_id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        return $r["vy-live-streamkey"];
    }
    public function constructPage()
    {
        global $smarty,$__svgI;

        if (!$this->checking()) {
            header("location: /livestream");
            exit();
        }

        $this->template->assign([
            "this" => $this,
            "i" => $this->userid,
        ]);
        $content = $this->template->display($this->theme_dir . "/index.html");



 
        echo $this->getPage($content);
    }
    public function getViewers()
    {
        global $wo;
        $users = isset($_POST["users"])
            ? json_decode($_POST["users"], true)
            : [];

        $this->template->assign([
            "this" => $this,
            "wo" => $wo,
            "live_id" => $this->id,
            "i" => $this->userid,
            "users" => $users,
        ]);
        $content = $this->template->fetch($this->theme_dir . "/viewers.html");

        echo $this->getPage($content);
    }
    public function availableViewersForModerator()
    {
        global $wo;
        $users = isset($_POST["users"])
            ? json_decode($_POST["users"], true)
            : [];

        $this->template->assign([
            "this" => $this,
            "wo" => $wo,
            "live_id" => $this->id,
            "i" => $this->userid,
            "users" => $users,
        ]);
        $content = $this->template->fetch(
            $this->theme_dir . "/add-moderators.html"
        );

        echo $this->getPage($content);
    }
    public function removeModerators()
    {
        global $wo;
        $users = isset($_POST["users"])
            ? json_decode($_POST["users"], true)
            : [];

        $this->template->assign([
            "this" => $this,
            "wo" => $wo,
            "live_id" => $this->id,
            "i" => $this->userid,
            "users" => $users,
        ]);
        $content = $this->template->fetch(
            $this->theme_dir . "/remove-moderators.html"
        );

        echo $this->getPage($content);
    }

    public function getPopup()
    {
        $value = $this->post_vars("value");

        $p = "/popups/not-found.html";
        $title = "404 Error";
        $arr = [];
        switch ($this->categ) {
            case "select-privacy":
                $title = $this->lang["post_privacy"];
                $p = "/popups/select-privacy.html";
                $arr = $this->getPrivacyOpts();
                break;
            case "live-settings":
                $title = $this->lang["live_settings"];
                $p = "/popups/live-settings.html";
                break;

            case "":
            default:
                $p = $p;
        }

        $this->template->assign([
            "this" => $this,
            "arr" => $arr,
            "value" => $value,
            "title" => $title,
            "i" => $this->userid,
        ]);
        $content = $this->template->fetch($this->theme_dir . $p);

        echo $this->getPage($content);
    }
    public function checking()
    {
        if (
            !isset($this->USER["id"]) ||
            !$this->USER["id"] ||
            $this->USER["id"] <= 0
        ) {
            header("location: /");
            exit();
        }


        return true;
    }

    public function goLive()
    {
        $descr = $this->post_vars("descr");
        $title = $this->post_vars("title");
        $privacy = $this->post_vars("privacy");
        $obs = $this->post_vars("obs");
        $obs_stream_name = $this->post_vars("stream_name");
        $post_to_timeline = $this->post_vars("post_to_timeline");
        $live_recording = $post_to_timeline == 'yes' ? 1 : 0;
        $now = time();
        $date = date("n/Y");
        $is_anonim = $in_group = $in_page = $group_approved = 0;
        $user_type = "user";
        switch ($privacy) {
            case "1":
                $privacy = "public";
                break;

            case "2":
                $privacy = "friends";
                break;

            case "3":
                $privacy = "me";
                break;

            case "4":
                $privacy = "public";
                $is_anonim = 1;
                break;
        }

        if (trim($title) && strlen($title) > 0) {
            $descr = "<strong>" . $title . "</strong><br/>" . $descr;
        }

        if($this->group_id) {
            $in_group = 1;
            $user_type = "user";
            $group_approved = 1;
        }

        if($this->page_id) {
            $in_page = 1;
            $user_type = "page";
        }

        // create live post
        $insert = $this->query_insert(
            "insert into " .
                VY_LV_TBL["POSTS"] .
                " set `is_anonymous`='{$is_anonim}',`post_type`='live',`group_approved`='{$group_approved}',`user_type`='{$user_type}',`in_group`='{$in_group}',`group_id`='{$this->group_id}',`privacy`='{$privacy}',`time`=NOW(),`user_id`='{$this->userid}',`text`='{$descr}'"
        );

        if ($insert) {

            // insert post to posts_live table 
            $insert_live = $this->query_insert(
                "insert into " .
                    VY_LV_TBL["POSTS_LIVE"] .
                    " set `post_id`='{$insert}',`live_ended`='0',`live_recorded`='{$live_recording}'"
            );
            $this->query_update(
                "update " .
                    VY_LV_TBL["POSTS"] .
                    " set `vy-live`='yes' where `post_id`='{$insert}'"
            );

            // add live to broadcasts table
            $add_broadcast = $this->query_insert(
                "insert into " .
                    VY_LV_TBL["BROADCASTS"] .
                    " set `obs`='{$obs}',`islivenow`='yes',`stream_name`='{$obs_stream_name}',`post_id`='{$insert}',`added`='{$now}',`user_id`='{$this->userid}'"
            );

            // send notification
            //Wo_notifyUsersLive($insert);
        }

        echo $this->jencode([
            "post_id" => $insert,
            "broadcast_id" => $add_broadcast,
            "filename" => md5($insert),
        ]);
    }
    public function generateCover(){
        
        
            $cover = $_POST["cover"];
            // generate cover
            $cover = str_replace("data:image/png;base64,", "", $cover);
            $cover = str_replace(" ", "+", $cover);
            $data = base64_decode($cover);
            $cover_file = $this->id . ".png";
            $success = file_put_contents(
                sprintf($this->upload_path_covers, $this->USER["id"]) .
                    $cover_file,
                $data
            );
            
            // add live to broadcasts table
            $update_broadcast = $this->db->query(
                "update " .VY_LV_TBL["BROADCASTS"] ." set `live-cover`='{$cover_file}' where `post_id`='{$this->id}'");

            $update_broadcast2 = $this->db->query(
                "update " .VY_LV_TBL["POSTS_LIVE"] ." set `video_thumbnail`='{$cover_file}' where `post_id`='{$this->id}'
            ");
 
            if($update_broadcast && $update_broadcast2)
                echo 1;
            else echo 0;
        
        
    }
    public function renameObsFile()
    {
        $path = $this->post_vars("path");
        $filename = $this->post_vars("filename");

        $response = ["success" => 0, "filename" => $filename];

        if (rename($path, str_replace("writing", $filename, $path))) {
            $response["success"] = 1;
        }

        echo $this->jencode($response);
    }
    public function stopLive()
    {
        $post_id = $this->post_vars("post_id");
        $broadcast_id = $this->post_vars("broadcast_id");
        $time = $this->post_vars("time");
        $post_to_timeline = $this->post_vars("post_to_timeline");
        $file_type = $this->post_vars("file_type");
        $filename = $this->post_vars("filename") . "." . $file_type;
        $live_recorded = $post_to_timeline == 'yes' ? 1 : 0;
        $update = $update2 = true;

        if (!$this->recording || $post_to_timeline == "no") {
            // delete post
            $delete_post = $this->deletePost($post_id);
            // delete broadcast
            $delete_broadcast = $this->deleteBroadCast($broadcast_id);
            // delete comments
            $delete_comments = $this->deleteComments($post_id);

            $live_recorded = 0;
        } else {
    
            $update2 = $this->query_update(
                "update " .
                    VY_LV_TBL["BROADCASTS"] .
                    " set `islivenow`='no',`ended`='yes',`stream_name`='{$filename}',`time`='{$time}' where `id`='{$broadcast_id}'"
            );

            $update3 = $this->query_update(
                "update " .VY_LV_TBL["POSTS_LIVE"] ." set `live_ended`='1',`live_recorded`='{$live_recorded}' where `post_id`='{$post_id}'"
            );
            $update4 = $this->query_update(
                "update " .VY_LV_TBL["POSTS"] ." set `is_hidden`='1' where `post_id`='{$post_id}'"
            );
        }

        // re-generate the stream keys for OBS
        $this->generateUniqueStreamKey();

        if ($update && $update2) {
            echo 1;
        } else {
            echo 0;
        }
    }
    public function getRtmpHLS_Path(){
        global $wo;
        return $wo['vy-livestream']['host'] . sprintf($this->upload_path_blobs, $this->id) . "index.m3u8";
    }
    public function getBroadcastData($post_id = 0)
    {
        global $wo;
        $data = ["full_cover_path" => null, "full_file_path" => null];

        if ($post_id > 0) {
            $q = $this->db->query(
                "Select * from " .
                    VY_LV_TBL["BROADCASTS"] .
                    " where `post_id`='{$post_id}' limit 1"
            );
            $data = $q->fetch_array(MYSQLI_ASSOC);

            if (isset($data["user_id"]) && isset($data["stream_name"])) {
                $data["full_file_path"] =
                    sprintf($this->upload_path_blobs, $data["user_id"]) .
                    $data["stream_name"];
            }
                        
            if (isset($data["user_id"]) && isset($data["live-cover"])) {
                
                if(empty($data["live-cover"])){
                $data["full_cover_path"] = $wo['site_url'] . $wo['vy-livestream']['blank'];
                } else {
                
                $data["full_cover_path"] =
                    sprintf($this->upload_path_covers, $data["user_id"]) .
                    $data["live-cover"];
                    
                }
            }
        }

        return $data;
    }
    public function AddComment()
    {
        $post_id = $this->post_vars("post_id");
        $text = $this->post_vars("text");

        if (
            !$this->isLogged() ||
            !is_numeric($post_id) ||
            $post_id <= 0 ||
            !trim($text) ||
            empty($text)
        ) {
            die();
        }

        $query = $this->query_insert(
            "insert into " .
                VY_LV_TBL["COMMENTS"] .
                " set `text`='{$text}',`node_id`='{$post_id}',`time`=NOW(),`user_id`='{$this->userid}'"
        );

        if ($query) {
            return true;
        } else {
            return false;
        }
    }
    public function isLiveExists($id)
    {
        $q = $this->db->query(
            "select COUNT(*) from " . VY_LV_TBL["POSTS"] . " where `post_id`='{$id}' limit 1"
        );
        $r = $q->fetch_row();

        return $r[0];
    }
    public function joinLive()
    {
        $data = ["error" => 0, "error_code" => 0];
        $post_id = $this->post_vars("id");
        $cover_path = $stream_path = '';
        if (!$this->isLiveExists($post_id)) {
            echo $this->jencode(["error" => 1, "error_code" => 404]);
            exit();
        }

        $query = $this->db->query(
            "select * from " . VY_LV_TBL["POSTS"] . " where `post_id`='{$post_id}' limit 1"
        );
        $post_data = $query->fetch_array(MYSQLI_ASSOC);

        // select data from live broadcast table
        $query2 = $this->db->query(
            "select * from " .
                VY_LV_TBL["BROADCASTS"] .
                " where `post_id`='{$post_id}' limit 1"
        );
        $rows = $query2->fetch_array(MYSQLI_ASSOC);

        // get last 15 comments
        $comments = $this->query_select(
            "
    
                select * from (
                select * from " .
                VY_LV_TBL["COMMENTS"] .
                " where `node_id`='{$post_id}' order by comment_id desc limit 15
            ) tmp order by tmp.comment_id asc


    "
        );

        if (isset($rows["user_id"]) && isset($rows["live-cover"])) {
            $cover_path =
                sprintf($this->upload_path_covers, $rows["user_id"]) .
                $rows["live-cover"];
        }

        if (isset($rows["user_id"]) && isset($rows["stream_name"])) {
            $stream_path =
                sprintf($this->upload_path_blobs, $rows["user_id"]) .
                $rows["stream_name"];
        }

        $this->template->assign([
            "this" => $this,
            "live_id" => $post_id,
            "stream_path" => $stream_path,
            "cover_path" => $cover_path,
            "rows" => $rows,
            "post" => $post_data,
            "author" => $this->lv_getUserDetails($rows["user_id"]),
            "comments" => $comments,
            "i" => $this->userid,
            "id" => $post_id,
        ]);
        $content = $this->template->fetch($this->theme_dir . "/live.html");
 
        $data["html"] = $content;
        $data["post"] = $rows;
        $data["post"]["stream_name"] = $rows["stream_name"];

        echo $this->jencode($data);
    }
    public function getNiceDuration($durationInSeconds)
    {
        $duration = "";
        $days = floor($durationInSeconds / 86400);
        $durationInSeconds -= $days * 86400;
        $hours = floor($durationInSeconds / 3600);
        $durationInSeconds -= $hours * 3600;
        $minutes = floor($durationInSeconds / 60);
        $seconds = $durationInSeconds - $minutes * 60;

        if ($days > 0) {
            $duration .= $days . "d";
        }
        if ($hours > 0) {
            $duration .=
                " " .
                $hours .
                ' <span class="vy_lv_small">' .
                $this->lang["hours"] .
                "</span>";
        }
        if ($minutes > 0) {
            $duration .=
                " " .
                $minutes .
                ' <span class="vy_lv_small">' .
                $this->lang["minutes"] .
                "</span>";
        }
        /*
  if($seconds > 0) {
    $duration .= ' ' . $seconds . 's';
  }*/
        return $duration;
    }
    public function lv_getUserDetails($uid)
    {
        $rs = [];

        $q = $this->db->query(
            "Select * from " .
                VY_LV_TBL["USERS"] .
                " where `user_id`='{$uid}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        if (!isset($r["user_id"])) {
            return [];
        }

        $rs["fullname"] = !empty($r["first_name"])
            ? $r["first_name"] . " " . $r["last_name"]
            : $r["user_name"];
        $rs['username'] = $r['user_name'];
        $rs['last_name'] = $r["last_name"];
        $rs['first_name'] = $r['first_name'];
        $rs["id"] = $r["user_id"];
        $rs["avatar"] = $this->lv_get_avatar($r["user_picture"]);
        $rs["name"] = empty($r["first_name"])
            ? $r["username"]
            : $r["first_name"];
        $rs["online_status"] = $r["status"];
        $rs["online"] = $r["lastseen"];
        $rs["follwing_btn"] = $this->getFollowButton($r["user_id"]);
        $rs["following_me"] = $this->IsFollowing($this->USER["id"], $r["user_id"]);

        return $rs;
    }
    public function lv_getUserDetails_js($uid){
        $data = $this->lv_getUserDetails($uid);
        return ["i" => $data['id'],"p"=>$data['avatar'],"last_name"=> empty($data['last_name']) ? $data['username'] : $data['last_name'],"first_name"=>empty($data['first_name']) ? $data['username'] : $data['first_name'],"username"=>$data['username'],"fn" => empty($data['last_name']) && empty($data['first_name']) ? $data['username'] : $data['first_name'].' '.$data['last_name']];
    }
    public static function index_userdetails(){
        global $user;
        return (new LIVE_STREAM)->lv_getUserDetails_js($user->_data['user_id']);
    }
    public function getFollowButton($uid = 0){
      $myid = $this->USER['id'];
      $query = $this->db->query(
            "select * from " . VY_LV_TBL["FOLLOWERS"] . " where `user_id`='{$myid}' && `following_id`='{$uid}' limit 1"
        );
        $f_data = $query->fetch_array(MYSQLI_ASSOC);
        $button = '';
         if($f_data['id'] > 0){

            $button = ' <button type="button" class="btn btn-sm btn-info js_unfollow" data-uid="'.$uid.'">
                  <i class="fa fa-check mr5"></i>'.__("Following").'
                </button>';

         } else {

            $button = '<button type="button" class="btn btn-sm btn-info js_follow" data-uid="'.$uid.'">
                  <i class="fa fa-rss mr5"></i>'.__("Follow").'
                </button>';
         }
 
         return $button;


    }
public function IsFollowing($uid = 0){

      $myid = $this->USER['id'];
      $query = $this->db->query(
            "select * from " . VY_LV_TBL["FOLLOWERS"] . " where `user_id`='{$myid}' && `following_id`='{$uid}' limit 1"
        );
        $f_data = $query->fetch_array(MYSQLI_ASSOC);

         if($f_data['id'] > 0)
            return true;
        else
            return false;




    }
    public static function getSvgIcons()
    {
        global $__svgI;
        return $__svgI;
    }
    public static function getSounds()
    {
        global $wo;
        return $wo["vy-livestream"]["sounds"];
    }
    public function showdashboard()
    {
        $this->template->assign([
            "this" => $this,
            "id" => $this->id,
            "i" => $this->userid,
        ]);
        $content = $this->template->fetch($this->theme_dir . "/dashboard.html");
        return $content;
    }
    public function getPreLiveSt()
    {
        $this->template->assign([
            "this" => $this,
            "id" => $this->id,
            "i" => $this->userid,
        ]);
        $content = $this->template->fetch(
            $this->theme_dir . "/pre-live-settings.html"
        );
        return $content;
    }
    public static function getReactionsBtns()
    {
        return VY_LV_REACTIONS;
    }
    public static function isRecording()
    {
        global $wo;
        return $wo["vy-livestream"]["record"]["recording"] ? 1 : 0;
    }
    public static function awayDesktop()
    {
        global $wo;
        return $wo["vy-livestream"]["record"]["away_desktop"] ? true : false;
    }
    public static function recType()
    {
        global $wo;
        return str_replace(
            ".",
            "",
            $wo["vy-livestream"]["record"]["record_type"]
        );
    }
    public static function recordingBits($x = "video")
    {
        global $wo;
        return $x == "audio"
            ? $wo["vy-livestream"]["record"]["audioBitsPerSecond"]
            : $wo["vy-livestream"]["record"]["videoBitsPerSecond"];
    }
    public static function fr_miliseconds()
    {
        global $wo;
        return $wo["vy-livestream"]["record"]["fr_miliseconds"];
    }
    public static function getPrivacyOpts()
    {
        $core = new VY_LIVESTREAM_CORE();
        return [
            "0" => [
                "id" => 1,
                "title" => $core->lang["everyone"],
                "descr" => $core->lang["everyone_info"],
                "ic" => "__everyone",
            ],
            "1" => [
                "id" => 2,
                "title" => "Friends",
                "descr" => "",
                "ic" => "__p_i_follow",
            ],
            "2" => [
                "id" => 3,
                "title" => $core->lang["only_me"],
                "descr" => "",
                "ic" => "__only_me",
            ],
            "3" => [
                "id" => 4,
                "title" => $core->lang["anonymous"],
                "descr" => "",
                "ic" => "__anonymous",
            ],
        ];
    }
    public function recording()
    {
        global $wo;

        $post_id = $this->post_vars("live_id");

        // make the post invisible till the video file is merged
        $this->query_update(
            "update " .
                VY_LV_TBL["POSTS"] .
                " set `is_hidden`='1' where `post_id`='{$post_id}'"
        );

        // send user notification
        $notification_data = [
            "recipient_id" => $this->USER["id"],
            "notifier_id" => $this->getAnyNotifierId(),
            "type" => "admin_notification",
            "text" => $this->lang["we_process_your_stream"],
            "admin" => 1,
            "url" => "index.php",
        ];
        Wo_RegisterNotification($notification_data);

        // because we've different ffmpeg commands for windows & linux
        // that's why following script is used to fetch target OS
        $OSList = [
            "Windows 3.11" => "Win16",
            "Windows 95" => "(Windows 95)|(Win95)|(Windows_95)",
            "Windows 98" => "(Windows 98)|(Win98)",
            "Windows 2000" => "(Windows NT 5.0)|(Windows 2000)",
            "Windows XP" => "(Windows NT 5.1)|(Windows XP)",
            "Windows Server 2003" => "(Windows NT 5.2)",
            "Windows Vista" => "(Windows NT 6.0)",
            "Windows 7" => "(Windows NT 7.0)",
            "Windows NT 4.0" =>
                "(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)",
            "Windows ME" => "Windows ME",
            "Open BSD" => "OpenBSD",
            "Sun OS" => "SunOS",
            "Linux" => "(Linux)|(X11)",
            "Mac OS" => "(Mac_PowerPC)|(Macintosh)",
            "QNX" => "QNX",
            "BeOS" => "BeOS",
            "OS/2" => "OS/2",
            "Search Bot" =>
                "(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(MSNBot)|(Ask Jeeves/Teoma)|(ia_archiver)",
        ];
        // Loop through the array of user agents and matching operating systems
        foreach ($OSList as $CurrOS => $Match) {
            // Find a match
            if (preg_match("/" . $Match . "/i", $_SERVER["HTTP_USER_AGENT"])) {
                // We found the correct match
                break;
            }
        }

        $dir = sprintf($this->upload_path_blobs, $this->USER["id"]);

        if (!file_exists($dir)) {
            mkdir(
                $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . $dir,
                0777,
                true
            );
        }

        // if it is audio-blob
        if (isset($_FILES["audio-blob"])) {
            $uploadDirectory = $dir . $_POST["filename"] . ".wav";
            if (
                !move_uploaded_file(
                    $_FILES["audio-blob"]["tmp_name"],
                    $uploadDirectory
                )
            ) {
                echo "Problem writing audio file to disk!";
            } else {
                // if it is video-blob
                if (isset($_FILES["video-blob"])) {
                    $uploadDirectory =
                        $dir .
                        $_POST["filename"] .
                        $wo["vy-livestream"]["record"]["recor_type"];
                    if (
                        !move_uploaded_file(
                            $_FILES["video-blob"]["tmp_name"],
                            $uploadDirectory
                        )
                    ) {
                        echo "Problem writing video file to disk!";
                    } else {
                        $audioFile = $dir . $_POST["filename"] . ".wav";
                        $videoFile =
                            $dir .
                            $_POST["filename"] .
                            $wo["vy-livestream"]["record"]["recor_type"];

                        $mergedFile =
                            $dir .
                            $_POST["filename"] .
                            "-merged" .
                            $wo["vy-livestream"]["record"]["recor_type"];

                        // ffmpeg depends on yasm
                        // libvpx depends on libvorbis
                        // libvorbis depends on libogg
                        // make sure that you're using newest ffmpeg version!

                        if (!strrpos($CurrOS, "Windows")) {
                            $cmd =
                                "-i " .
                                $audioFile .
                                " -i " .
                                $videoFile .
                                " -map 0:0 -map 1:0 " .
                                $mergedFile;
                        } else {
                            $cmd =
                                " -i " .
                                $audioFile .
                                " -i " .
                                $videoFile .
                                " -c:v mpeg4 -c:a vorbis -b:v 64k -b:a 12k -strict experimental " .
                                $mergedFile;
                        }

                        exec("ffmpeg " . $cmd . " 2>&1", $out, $ret);
                        if ($ret) {
                            // the record can not be saved remove post,comments and broadcast
                            $this->query_delete(
                                "delete from " .
                                    VY_LV_TBL["POSTS"] .
                                    " where `post_id`='{$post_id}'"
                            );
                            $this->query_delete(
                                "delete from " .
                                    VY_LV_TBL["BROADCASTS"] .
                                    " where `post_id`='{$post_id}'"
                            );
                            $this->query_delete(
                                "delete from " .
                                    VY_LV_TBL["COMMENTS"] .
                                    " where `node_id`='{$post_id}'"  
                            );

                            // send user notification
                            $notification_data = [
                                "recipient_id" => $this->USER["id"],
                                "notifier_id" => $this->getAnyNotifierId(),
                                "type" => "admin_notification",
                                "text" =>
                                    "Your previous live stream was not saved, and the post has been removed permanently.",
                                "admin" => 1,
                                "url" => "index.php",
                            ];
                            Wo_RegisterNotification($notification_data);

                            echo "There was a problem!\n";
                            print_r($cmd . '\n');
                            print_r($out);
                        } else {
                            echo "Ffmpeg successfully merged audi/video files into single WebM container!\n";

                            // make the post ready
                            $this->query_update(
                                "update " .
                                    VY_LV_TBL["POSTS"] .
                                    " set `is_hidden`='0' where `post_id`='{$post_id}'"
                            );

                            // send user notification
                            $notification_data = [
                                "recipient_id" => $this->USER["id"],
                                "notifier_id" => $this->getAnyNotifierId(),
                                "type" => "admin_notification",
                                "text" =>
                                    "Your live stream its ready! Now you can see it on your timeline.",
                                "post_id" => $post_id,
                                "admin" => 1,
                                "url" => "index.php?link1=post&id=" . $post_id,
                            ];
                            Wo_RegisterNotification($notification_data);
                            unlink($audioFile);
                            unlink($videoFile);
                        }
                    }
                }
            }
        }
    }
    public function getContent()
    {
        global $wo;
        $file = $this->post_vars("type");
        $available_files = ["desktop", "mobile"];
 
 
        $this->template->assign([
            "this" => $this,
            "wo" => $wo,
            "i" => $this->userid,
        ]);

        if (!in_array($file, $available_files)) {
            $content = $this->template->fetch($this->theme_dir . "/404.html");
        } else {
            $content = $this->template->fetch(
                $this->theme_dir . "/{$file}-stream-author.html"
            );
        }

        echo $this->getPage($content);
    }
    public function mob_popup()
    {
        global $wo;

        $title = $this->post_vars("title");
        $kind = $this->post_vars("kind");
        $mob_pop_dir = "/popups/mob/";
        $users = isset($_POST["users"])
            ? json_decode($_POST["users"], true)
            : [];

        $popup_content = "404.html";
        switch ($kind) {
            case "mob-pre-live-settings":
                $popup_content = "pre-live-settings.html";
                break;
            case "mob-streaming-settings":
                $popup_content = "mob-streaming-settings.html";
                break;
            case "get-viewers":
                $popup_content = "get-viewers.html";
                break;
            case "get-available-for-moder":
                $popup_content = "add-moderators.html";
                break;
            case "remove-moderators":
                $popup_content = "remove-moderators.html";
                break;
        }

        $this->template->assign([
            "this" => $this,
            "users" => $users,
            "live_id" => $this->id,
            "dir" => $this->theme_dir,
            "wo" => $wo,
            "id" => $this->id,
            "file_content" => $popup_content,
            "i" => $this->userid,
            "title" => $title,
        ]);

        $content = $this->template->fetch(
            $this->theme_dir . $mob_pop_dir . "content.html"
        );
        echo $this->getPage($content);
    }
    public function getPageDetails($page_id = 0)
    {
        $arr = ["name" => "unknown-page", "avatar" => "", "owner" => 0];

        if (!$page_id || !is_numeric($page_id)) {
            return $arr;
        }

        $q = $this->db->query(
            "select `page_admin`,`page_title`,`page_picture` as avatar from " .
                VY_LV_TBL["PAGES"] .
                " where `page_id`='{$page_id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        if (isset($r["page_title"])) {
            $arr["name"] = $r["page_title"];
        }

        if (isset($r["page_admin"])) {
            $arr["owner"] = $r["page_admin"];
        }

        if (isset($r["avatar"])) {
            $arr["avatar"] = $this->lv_get_avatar($r["avatar"]);
        }

        return $arr;
    }
    public function doesGroupExists($group_id = 0){
        
        return count($this->query_select("select id from ".VY_LV_TBL["GROUPS"] ." where `id`='{$group_id}' limit 1"));
        
    }
    public function doesPageExists($page_id = 0){
        
        return count($this->query_select("select page_id from ".VY_LV_TBL["PAGES"] ." where `page_id`='{$page_id}' limit 1"));
        
    }
    public function getGroupDetails($group_id = 0)
    {
        $arr = ["name" => "unknown-page", "avatar" => "", "owner" => 0];

        if (!$group_id || !is_numeric($group_id)) {
            return $arr;
        }

        $q = $this->db->query(
            "select `group_admin`,`group_title`,`group_picture` as `avatar` from " .
                VY_LV_TBL["GROUPS"] .
                " where `group_id`='{$group_id}' limit 1"
        );
        $r = $q->fetch_array(MYSQLI_ASSOC);

        if (isset($r["group_title"])) {
            $arr["name"] = $r["group_title"];
        }

        if (isset($r["group_admin"])) {
            $arr["owner"] = $r["group_admin"];
        }

        if (isset($r["avatar"])) {
            $arr["avatar"] = $this->lv_get_avatar($r["avatar"]);
        }

        return $arr;
    }
    public function getTurnCredentials()
    {
        global $wo;
        $json_path = glob(getcwd() . DIRECTORY_SEPARATOR ."cr_turnserver". DIRECTORY_SEPARATOR ."*.json");
        $json = file_get_contents($json_path[0]); 
 
        return $json;
/*
        $data = [];

        $data["stun"] = $wo["vy-livestream"]["ice_servers"]["stun_url"];
        $data["turn"] = $wo["vy-livestream"]["ice_servers"]["turn_url"];
        $data["turn_username"] = $wo["vy-livestream"]["ice_servers"]["turn_un"];
        $data["turn_password"] = $wo["vy-livestream"]["ice_servers"]["turn_cr"];

        return $this->jencode($data);
        */
    }
    public static function getLLang()
    {
        $core = new VY_LIVESTREAM_CORE();
        return $core->lang;
    }
}
