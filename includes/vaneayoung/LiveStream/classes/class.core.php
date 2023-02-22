<?php

/*

Plugin Live Stream
email: movileanuion@gmail.com 
Copyright 2022 by Vanea Young 

*/

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "config.php";
require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "ssvgs.php";
if (!class_exists("Smarty")) {
    require_once dirname(__DIR__, 1) .
        DIRECTORY_SEPARATOR .
        "libraries/smarty-3.1.34/Smarty.class.php";
}

include dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "api.php";

class VY_LIVESTREAM_CORE
{
    public $db_path;
    public $db;
    public $USER;
    public $cronjob;
    public $settings;
    public $view_as_json;
    public $svg;
    public $upload_path_covers;
    public $upload_path_blobs;

    // --------------------------- Connect to DATABASE ---------------------------------
    private function db_conn($encoding = "utf8")
    {
 
        try {
            $this->db = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASSWORD,
                DB_NAME
            );

            if ($this->db->connect_errno > 0) {
                die(
                    "Unable to connect to database [" .
                        $this->db->connect_error .
                        "]"
                );
            } else {
                $this->db->set_charset("utf8mb4");
            }

            //register_shutdown_function([$this, 'autoclean']);

            return $this->db;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    } // END db_conn()

    // ------------------------------ RUN QUERIES --------------------------
    // for select
    public function query_select($query)
    {
        $result_array = [];
        $database = $this->db_conn();
        ($result = $database->query($query)) or die($database->error);
        if (!$result) {
            die("No disponible data to show. [ error: empty ]");
        }

        while ($row = $result->fetch_assoc()) {
            $result_array[] = $row;
        }

        return $result_array;
    } // END run_query()

    // for insert
    public function query_insert($query)
    {
        $database = $this->db_conn("utf8mb4");
        ($query = $database->query($query)) or die($database->error);
        $insert_id = @mysqli_insert_id($database);
        if (!$insert_id) {
            die("An error occurred to insert data into database.");
        }

        return $insert_id;
    } // END run_query_insert()

    // for update
    public function query_update($query)
    {
        $database = $this->db_conn("utf8mb4");
        ($query = $database->query($query)) or die($database->error);
        if (!$query) {
            die("An error occurred to update data.");
        }

        return true;
    } // END run_query_update()

    // for delete
    public function query_delete($query)
    {
        $database = $this->db_conn();
        ($query = $database->query($query)) or die($database->error);
        if (!$query) {
            die("An error occurred to delete data from database.");
        }

        return true;
    } // END query_delete()

    public function __construct()
    {
        global $wo,$user,$__svgI;

       // echo '<pre>';print_r($user);die();
 

        $this->db_conn();
        $this->USER = [];
        $this->cronjob = [];


        $this->USER = $user->_data;


        $this->USER["id"] = $this->USER["user_id"];
        $this->USER["fullname"] = empty($this->USER["fist_name"])
            ? $this->USER["user_name"]
            : $this->USER["user_firstname"] . " " . $this->USER["user_lastname"];
        $this->USER["profile_photo"] = $this->USER["user_picture"];
        $this->view_as_json =
            isset($_GET["view_as"]) || isset($_POST["view_as"]) ? true : false;
        $this->template = new Smarty();

        $this->theme_dir = getcwd() . "/vy-livestream/layout";
        $this->svg = $__svgI;
        $this->upload_path_covers =
            $wo["vy-livestream"]["record"]["record_path"] . "/%s/covers/";
        $this->upload_path_blobs =
            $wo["vy-livestream"]["record"]["record_path"] . "/%s/streams/";
        $this->recording = $wo["vy-livestream"]["record"]["recording"];
        $this->settings = $wo["vy-livestream"]["record"];

      

        // create user's upload dir
        # crete cover dir
        if (
            !file_exists(
                $_SERVER["DOCUMENT_ROOT"] .
                    DIRECTORY_SEPARATOR .
                    sprintf($this->upload_path_covers, $this->USER["id"])
            )
        ) {
            mkdir(
                $_SERVER["DOCUMENT_ROOT"] .
                    DIRECTORY_SEPARATOR .
                    sprintf($this->upload_path_covers, $this->USER["id"]),
                0777,
                true
            );
        }

        # crete stream dir
        if (
            !file_exists(
                $_SERVER["DOCUMENT_ROOT"] .
                    DIRECTORY_SEPARATOR .
                    sprintf($this->upload_path_blobs, $this->USER["id"])
            )
        ) {
            mkdir(
                $_SERVER["DOCUMENT_ROOT"] .
                    DIRECTORY_SEPARATOR .
                    sprintf($this->upload_path_blobs, $this->USER["id"]),
                0777,
                true
            );
        }

        // require language file
        $global_language = "en";

        switch ($wo["user"]["language"]) {
            case "english":
                $global_language = "en";
                break;
            case "arabic":
                $global_language = "ab";
                break;
            case "german":
                $global_language = "de";
                break;
            case "spanish":
                $global_language = "es";
                break;
            case "french":
                $global_language = "fr";
                break;
            case "italian":
                $global_language = "it";
                break;
            case "dutch":
                $global_language = "nl";
                break;
            case "portuguese":
                $global_language = "pg";
                break;
            case "russian":
                $global_language = "ru";
                break;
            case "turkish":
                $global_language = "tr";
                break;
        }

        $vy_lv_language = include dirname(__DIR__, 1) .
            DIRECTORY_SEPARATOR .
            "lang/{$global_language}.php";

        // get site language
        foreach ($vy_lv_language as $key => $value) {
            $this->lang[$key] = $value;
        }

        // insert settings in db
        $lock_file = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "conf.lock";
        if (!file_exists($lock_file)) {
            $conf = $this->jencode($wo["vy-livestream"]["record"]);

            // truncate the table
            $this->db->query("TRUNCATE TABLE " . VY_LV_TBL["CONF"]);

            // add settings
            if (
                $this->db->query(
                    "INSERT INTO " .
                        VY_LV_TBL["CONF"] .
                        " set `settings`='{$conf}'"
                )
            ) {
                $fp = fopen($lock_file, "wb");
                fwrite($fp, "Silence is golden");
                fclose($fp);
            }
        }
    } // END __construct()
    public function im_live()
    {
        return new LIVE_STREAM();
    }
    // escape input
    public function test_input($data, $no_escape = false)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);

        return $no_escape ? $data : $this->db->real_escape_string($data);
    }
    // escape for sql
    public function sql_escape($str)
    {
        return $this->db->real_escape_string($str);
    }
    public function getPage($content, $page = false)
    {
        if ($this->view_as_json) {
            return $this->jencode([
                "page" => $page ? $page : "",
                "content" => $content,
            ]);
        } else {
            return $content;
        }
    }
    public function isSecure()
    {
        return (isset($_SERVER["HTTPS"]) &&
            !empty($_SERVER["HTTPS"]) &&
            $_SERVER["HTTPS"] !== "off") ||
            $_SERVER["SERVER_PORT"] == 443;
    }
    public function getAvatar($avatar = "")
    {
        $m_avatar = Wo_GetMedia($avatar);
        if (!empty($m_avatar)) {
            return $m_avatar;
        } else {
            return "/" . $avatar;
        }
    }
    public function isarray($var)
    {
        return is_array($var) or $var instanceof Traversable;
    }
    public function post_vars($var, $no_test_input = false)
    {
        return isset($_POST[$var])
            ? ($no_test_input
                ? $_POST[$var]
                : $this->test_input($_POST[$var]))
            : false;
    }
    public function isLogged()
    {
        global $user;
        return !$user->_logged_in ? false : true;
    }
    public function lv_get_avatar($avatar = "")
    {   global $system;


        if(empty($avatar)){

            return get_picture(NULL,NULL);
        } else {
            return $system['system_uploads'] . '/' . $avatar;  
        }

 
    }
    public function jencode($d){
  /*  if (is_array($d) || is_object($d))
        foreach ($d as &$v) $v = $this->jencode($v);
    else
        return utf8_encode($d);
*/
    return json_encode($d,JSON_UNESCAPED_UNICODE);

    }

} // end class
