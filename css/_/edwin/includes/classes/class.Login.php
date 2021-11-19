<?php

  /**
   * Login Class
   *
   * $LastChangedDate: 2019-08-19 08:00:15 +0200 (Mo, 19 Aug 2019) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class Login {
    private $db = 0;
    private $table_prefix = "";
    private $tpl = 0;
    private $user = 0;
    private $sid = "";
    private $session;

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Constructor                                                                           //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function __construct($tpl,$db,$table_prefix,$session) {
      $this->db = $db;
      $this->table_prefix = $table_prefix;
      $this->tpl = $tpl;

      if (!$session) $this->session = Container::make('Session');
      else $this->session = $session;

      $this->sid = $this->session->getId();

    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Check Login                                                                           //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function check() {
      global $_POST,$_LANG;

      $action = "";
      if (isset($_GET["action"])) $action = addslashes(strip_tags($_GET["action"]));
      else if (isset($_POST["action"])) $action = addslashes(strip_tags($_POST["action"]));

      $this->user = new User($this->sid, (int)(($this->session->read("m_cid") ? $this->session->read("m_cid") : 0)^ConfigHelper::get('key')));
      if (!$this->user->isValid()){ // not logged in

        // for ajax requests
        if (   !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
        ) {
          ed_http_code(\Core\Http\ResponseCode::UNAUTHORIZED, true);
        }

        if ($action == "request_pw"){
          $c_contenuser = new ContentUser($this->tpl,$this->db,$this->table_prefix,0);
          $temp = $c_contenuser->request_pw();
          echo $temp["content"];
        }
        else if (isset($_POST["process"]))
          $this->process();
        else
          $this->show();
      }
      else if ($action)
        if ($action == "logout")
          $this->process_logout();

      return $this->user;
    }

    protected function show(Message $lg_message = null)
    {
       global $_LANG, $_LANG2;

       if (isset($_POST["lg_ref"])) $lg_referer = addslashes(strip_tags($_POST["lg_ref"]));
       else $lg_referer = mb_stristr(str_replace("&","&amp;",$_SERVER["REQUEST_URI"]),"index.php");

       if ($lg_referer == "index.php?action=logout") $lg_referer = "index.php";

       $lg_hidden_fields = "<input type=\"hidden\" name=\"do\" value=\"login\" /><input type=\"hidden\" name=\"lg_ref\" value=\"".$lg_referer."\" />";
       $lg_action = "index.php";
       $lg_button = "<input type=\"submit\" name=\"process\" class=\"btn btn-success\" value=\"".$_LANG["lg_button_send_label"]."\" tabindex=\"3\"/>";
       $lg_request_password_link = "index.php?action=request_pw";

       $this->tpl->load_tpl('login', 'login.tpl');
       $this->tpl->parse_if('login', 'message', $lg_message, $lg_message ? $lg_message->getTemplateArray('lg') : array());
       $this->tpl->parse_if('login', 'm_backend_live_mode', ConfigHelper::get('m_backend_live_mode'));
       $this->tpl->parse_if('login', 'm_backend_dev_mode', !ConfigHelper::get('m_backend_live_mode'));
       $this->tpl->parseprint('login', array_merge(array(
         'lg_main_name'                => $_LANG["global_main_name"],
         'lg_hidden_fields'            => $lg_hidden_fields,
         'lg_request_password_link'    => $lg_request_password_link,
         'lg_action'                   => $lg_action,
         'lg_button'                   => $lg_button,
         'main_cache_resource_version' => ConfigHelper::get('m_cache_resource_version'),
         'main_theme'                  => ConfigHelper::get('m_backend_theme'),
       ), $_LANG2['global']));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Process Login Form                                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function process()
    {
      global $_POST,$_GET,$_LANG;

      $post = new Input(Input::SOURCE_POST);

      // Read Data ////////////////////////////////////////////////////////////////////////////
      $lg_user = $post->readString('lg_user', Input::FILTER_PLAIN);
      $lg_pw = $post->readString('lg_pw', Input::FILTER_NONE);
      $lg_ref = $post->readString('lg_ref', Input::FILTER_NONE);
      /////////////////////////////////////////////////////////////////////////////////////////////

      $lg_message = null;

      $now = date("Y-m-d H:i:s");
      $passwordMD5 = md5($lg_pw);
      $sql = " SELECT UID, UBlocked, UBlockedMessage "
           . " FROM {$this->table_prefix}user "
           . " WHERE UDeleted = 0 "
           . " AND UNick = '{$this->db->escape($lg_user)}' "
           . " AND UPW = '$passwordMD5' ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);
      $this->db->free_result($result);
      $uid = (int)$row['UID'];
      $blocked = (bool)$row['UBlocked'];
      if ($blocked) {
        $lg_message = $row['UBlockedMessage'] ? parseOutput($row['UBlockedMessage'],0) :
                      $_LANG['lg_message_blocked_user'];
        $lg_message = Message::createFailure($lg_message);
      }
      else if ($uid) {
        $this->session->resetSession();
        $this->sid = $this->session->getId();
        $this->db->query("UPDATE ".$this->table_prefix."user set USID='".$this->sid."',ULastLogin='$now',UCountLogins=UCountLogins+1 WHERE UID=".$uid);
        $this->user->setUser($this->sid, $uid);
        $this->session->save("m_cid",((string)$this->user->getID())^ConfigHelper::get('key'));
        $this->clean_user_uploads($uid);
        $this->_deleteExpiredComments();
        $this->_deleteExpiredClientUploads();

        // validate the $lg_ref parameter to avoid setting dangerous headers
        $url = edwin_url() . $lg_ref;
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
          $url = edwin_url();
        }
        header("Location: $url");
        exit;
      }
      else {
        $lg_message = Message::createFailure($_LANG['lg_message_login_failed']);
      }

      $this->show($lg_message);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Process Logout                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function process_logout() {
      global $_LANG;

      $result = $this->db->query("UPDATE ".$this->table_prefix."user set USID='' WHERE UID='".$this->user->getID()."'");
      $this->user->reset();

      if ($result) {
        $lg_message = Message::createSuccess($_LANG['lg_message_logout_done']);
      }
      else {
        $lg_message = Message::createFailure($_LANG['lg_message_logout_failed']);
      }

      $this->show($lg_message);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete temorary user uploads                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    protected function clean_user_uploads($uid) {
      global $_LANG;

      // delete temorary files from current user
      $result = $this->db->query("SELECT UUFile from ".$this->table_prefix."user_uploads WHERE UUType=1 AND FK_UID=".$uid);
      while ($row = $this->db->fetch_row($result)) {
        unlinkIfExists($row["UUFile"]);
        $result1 = $this->db->query("DELETE FROM ".$this->table_prefix."user_uploads WHERE UUFile='".$row["UUFile"]."'");
      }
      $this->db->free_result($result);

      // delete temorary files from other users, if they are older than 2 days
      $result = $this->db->query("SELECT UUFile from ".$this->table_prefix."user_uploads WHERE UUType=1 AND UUTime<=".(time()+172800));
      while ($row = $this->db->fetch_row($result)) {
        unlinkIfExists($row["UUFile"]);
        $result1 = $this->db->query("DELETE FROM ".$this->table_prefix."user_uploads WHERE UUFile='".$row["UUFile"]."'");
      }
      $this->db->free_result($result);
    }

    /**
     * Delete (deleted) comments, created one year ago.
     *
     * Comments aren't deleted from the database if a user deletes them.
     * This method removes all deleted comments older than one year permanently
     * from the database.
     */
    protected function _deleteExpiredComments()
    {
      $yearAgo = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')-1);
      $date = date('Y-m-d H:i:s', $yearAgo);

      $sql = " DELETE FROM {$this->table_prefix}comments "
           . ' WHERE CDeleted = 1 '
           . " AND CCreateDateTime < '{$date}' ";
      $result = $this->db->query($sql);
      $this->db->free_result($result);
    }

    /**
     * Delete expired client uploads.
     *
     * Clients may upload files, when using a form. All files older
     * than $_CONFIG['m_client_uploads_expiration'] have to be deleted from database
     * and filesystem.
     */
    private function _deleteExpiredClientUploads()
    {
      // Do not delete any uploads if expiration time is invalid (non-integer
      // value) or set to 0.
      $expiration = (int)ConfigHelper::get('m_client_uploads_expiration');
      if ($expiration == 0) {
        return;
      }

      $timestamp = time() - $expiration;
      $expiredDateTime = date('Y-m-d H:i:s', $timestamp);

      $sql = ' SELECT CUID, CUFile '
           . " FROM {$this->table_prefix}client_uploads "
           . " WHERE CUCreateDateTime < '$expiredDateTime'"
           . " AND CUViewed = 1 ";
      $uploads = $this->db->GetAssoc($sql);

      // Remove expired files from filesystem.
      foreach ($uploads as $id => $file)
      {
        unlinkIfExists('../' . $file);
        $deleted[] = $id; // Store ids of deleted files.
      }

      // Remove all deleted files from database.
      if (isset($deleted) && !empty($deleted))
      {
        $sql = " DELETE FROM {$this->table_prefix}client_uploads "
             . " WHERE CUID IN ( " . implode(',', $deleted) . ")";
        $this->db->query($sql);
      }
    }
  }

