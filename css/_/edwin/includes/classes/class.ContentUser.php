<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2020-02-14 13:40:11 +0100 (Fr, 14 Feb 2020) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  class ContentUser {
    protected $user = "";
    protected $db = 0;
    protected $table_prefix = "";
    protected $tpl = 0;
    /**
     * Contains the status message of the page.
     * @var Message
     */
    protected $message = null;

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Constructor                                                                           //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function __construct($tpl,$db,$table_prefix,$user) {
      $this->db = $db;
      $this->table_prefix = $table_prefix;
      $this->tpl = $tpl;
      $this->user = $user;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Change Password for current User                                                      //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_change_pw(){
      global $_LANG;

      // Read Data ////////////////////////////////////////////////////////////////////////////////
      if (isset($_POST["cp_old_password"])) $cp_old_password = $_POST["cp_old_password"];
      else $cp_old_password = "";
      if (isset($_POST["cp_password"])) $cp_password = $_POST["cp_password"];
      else $cp_password = "";
      if (isset($_POST["cp_password2"])) $cp_password2 = $_POST["cp_password2"];
      else $cp_password2 = "";
      /////////////////////////////////////////////////////////////////////////////////////////////

      if (isset($_POST["process"])) {
        $configLength = ConfigHelper::get('m_password_length');
        $configQualilty = ConfigHelper::get('m_password_quality');
        $configTypes = ConfigHelper::get('m_password_types');
        $pwHelper = new Password();
        $pwQuality = $pwHelper->setPassword($cp_password)->getCalculatedQuality();

        if (!mb_strlen(trim($cp_old_password)) || !mb_strlen(trim($cp_password)) || !mb_strlen(trim($cp_password2))) {
          $this->setMessage(Message::createFailure($_LANG['cp_message_no_password']));
        }
        else {
          $uid = $this->db->GetOne("SELECT UID FROM {$this->table_prefix}user where UID = {$this->user->getID()} and UPW = '" . md5($cp_old_password) . "'");
          if ($uid === null) {
            $this->setMessage(Message::createFailure($_LANG['cp_message_invalid_old_password']));
          }
          else if (mb_strlen($cp_password) < intval($configLength)) {
            $this->setMessage(Message::createFailure(sprintf($_LANG['cp_message_invalid_too_short'], (int)$configLength)));
          }
          else if ($cp_password != $cp_password2) {
            $this->setMessage(Message::createFailure($_LANG['cp_message_invalid_password2']));
          }
          else if ($pwQuality < (int)$configQualilty){
            $characterTypes = '';
            $quality = (int)$configQualilty;
            for ($i = 0; $i < $quality; $i++) {
              $characterTypes .= $_LANG['cp_password_character_type'][$configTypes[$i]];

              if ($i + 2 < $quality) {
                $characterTypes .= $_LANG['cp_message_invalid_too_weak_spacer'];
              } else if ($i + 1 < $quality) {
                $characterTypes .= $_LANG['cp_message_invalid_too_weak_lastspacer'];
              }
            }

            $this->setMessage(Message::createFailure(sprintf($_LANG['cp_message_invalid_too_weak'], $characterTypes)));
          }
          else{
            $result = $this->db->query("UPDATE ".$this->table_prefix."user SET UPW='".md5($cp_password)."' where UID=".$this->user->getID());

            if ($result) {
              $this->setMessage(Message::createSuccess($_LANG['cp_message_successful']));
            }
          }
        }
      }

      $cp_action = "index.php";
      $cp_hidden_fields = '<input type="hidden" name="action" value="change_pw" />';
      $cp_content_top = $cp_content_left = "";

      $this->tpl->load_tpl('change_password', 'content_change_password.tpl');
      $this->tpl->parse_if('change_password', 'message', $this->getMessage(), $this->getMessageTemplateArray('cp'));
      $cp_content = $this->tpl->parsereturn('change_password', array( 'cp_action' => $cp_action,
                                                                      'cp_hidden_fields' => $cp_hidden_fields,
                                                                      'cp_function_label' => $_LANG["cp_function_label"],
                                                                      'cp_old_password_label' => $_LANG["cp_old_password_label"],
                                                                      'cp_password_label' => $_LANG["cp_password_label"],
                                                                      'cp_password2_label' => $_LANG["cp_password2_label"],
                                                                      'cp_button_send_label' => $_LANG["cp_button_send_label"],
                                                                      'cp_new_password_label' => $_LANG["cp_new_password_label"],
                                                                      'cp_function_label2' => $_LANG["cp_function_label2"], ));

      return array( 'content' => $cp_content, "content_left" => $cp_content_left, "content_top" => $cp_content_top, 'content_contenttype' => "ContentUser" );
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Request new Password a User                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function request_pw(){
      global $_LANG;

      // Read Data ////////////////////////////////////////////////////////////////////////////////
      if (isset($_POST["rq_email"])) $rq_email = $_POST["rq_email"];
      else $rq_email = "";
      /////////////////////////////////////////////////////////////////////////////////////////////

      if (isset($_POST["process"]) && mb_strlen(trim($rq_email))) {
        $result = $this->db->query("SELECT UID,UNick,UEmail,UDeleted from ".$this->table_prefix."user where UEmail='".$this->db->escape($rq_email)."'");
        if ($row = $this->db->fetch_row($result)) {
          // check if deleted. deleted users are not allowed to use the request pw function
          if (!$row['UDeleted']) {
            // prepare mail
            $mail_user_address = $row["UEmail"];
            $mail_subject = sprintf($_LANG["rq_mail_subject"],$_LANG["global_main_name"]);
            $pwHelper = new Password();
            $pwHelper->setLength(ConfigHelper::get('m_password_length'))
                     ->setQuality(ConfigHelper::get('m_password_quality'))
                     ->setTypes(ConfigHelper::get('m_password_types'))
                     ->create();
            $mail_user_password = $pwHelper->getPassword();
            // load mail template
            $this->tpl->load_tpl('request_mail', 'mail/rq_main.tpl');
            $mail_text = $this->tpl->parsereturn('request_mail', array( 'rq_username' => $row["UNick"],
                                                                        'rq_password' => $mail_user_password,
                                                                        'rq_main_name' => $_LANG["global_main_name"] ));
            // echo $mail_text;

            // send mail
            $mail_sender_label = ConfigHelper::get('m_mail_sender_label', null, 0);
            $mailer = new \Core\Mailers\BaseMailer($this->tpl, $mail_sender_label);
            $mailer->setFormat(\Core\Mailers\BaseMailer::FORMAT_TEXT);
            $mailer->sendTo($mail_user_address, $mail_subject, $mail_text);

            $result2 = $this->db->query("UPDATE ".$this->table_prefix."user SET UPW='".md5($mail_user_password)."' where UID=".$row["UID"]);

            $this->setMessage(Message::createSuccess($_LANG['rq_message_successful']));
          }
          else {
            $this->setMessage(Message::createFailure($_LANG['rq_message_invalid_user']));
          }
        }
        else {
          $this->setMessage(Message::createFailure($_LANG['rq_message_invalid_mail']));
        }
        $this->db->free_result($result);
      }
      else if (!mb_strlen(trim($rq_email)) && isset($_POST['process'])) {
        $this->setMessage(Message::createFailure($_LANG['rq_message_no_mail']));
      }

      $rq_action = "index.php";
      $rq_hidden_fields = '<input type="hidden" name="action" value="request_pw" />';
      $rq_content_top = $rq_content_left = "";

      $this->tpl->load_tpl('request_password', 'content_request_password.tpl');
      $this->tpl->parse_if('request_password', 'message', $this->getMessage(), $this->getMessageTemplateArray('rq'));
      $this->tpl->parse_if('request_password', 'm_backend_live_mode', ConfigHelper::get('m_backend_live_mode'));
      $this->tpl->parse_if('request_password', 'm_backend_dev_mode', !ConfigHelper::get('m_backend_live_mode'));
      $rq_content = $this->tpl->parsereturn('request_password', array(
        'rq_main_name'                => $_LANG["global_main_name"],
        'rq_function_name'            => $_LANG["rq_function_name"],
        'rq_hidden_fields'            => $rq_hidden_fields,
        'rq_action'                   => $rq_action,
        'rq_email_label'              => $_LANG["rq_email_label"],
        'rq_backlink_label'           => $_LANG["rq_backlink_label"],
        'rq_button_send_label'        => $_LANG["rq_button_send_label"],
        'rq_info_label'               => $_LANG["rq_info_label"],
        'main_cache_resource_version' => ConfigHelper::get('m_cache_resource_version'),
        'main_theme'                  => ConfigHelper::get('m_backend_theme'),));

      return array( 'content' => $rq_content, "content_left" => $rq_content_left, "content_top" => $rq_content_top, 'content_contenttype' => "ContentUser" );
    }

    /**
     * Sets the message to the given message, if it's not already set.
     *
     * @param Message $message
     *        The message that should be set.
     * @throws InvalidArgumentException
     *        The given message object is null.
     */
    protected function setMessage(Message $message) {
      if (!$message) {
        throw new InvalidArgumentException('The given message must not be null.');
      }

      if (!$this->message) {
        $this->message = $message;
      }
    }

    /**
     * Gets the status message of the page.
     *
     * @return Message
     *        The status message of the page or null if there is no message.
     */
    protected function getMessage() {
      return $this->message;
    }

    /**
     * Gets an array that can be used with the Template class to parse the IF for the message.
     *
     * @param string $prefix
     *        The prefix for the template variables (usually the two-character-prefix for the ContentItem/Module).
     * @return array
     *        An array that contains the message text and the type of the message or an empty array if there is no message.
     */
    protected function getMessageTemplateArray($prefix) {
      $templateArray = array(0);

      if ($this->message) {
        $templateArray = $this->message->getTemplateArray($prefix);
      }

      return $templateArray;
    }
  }

