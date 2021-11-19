<?php

  /**
   * Bugtracking Form
   *
   * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  class ModuleBugtracking extends Module{

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function get_content($main_action){
      global $_LANG, $_LANG2;

      $bt_module_name = (mb_substr($main_action,0,4) == "mod_" ? mb_substr($main_action,4) : "");
      $bt_path_id = (mb_substr($main_action,0,4) != "mod_" ? $this->item_id : 0);
      $bt_path_link = "index.php?action=".$main_action."&amp;site=".$this->site_id."&amp;page=".$this->item_id."&amp;action2=".($this->action ? implode(";",$this->action) : "");

      $page_info = array();
      $page_info[0] = $bt_module_name;
      $page_info[1] = $bt_path_id;
      $page_info[2] = $bt_path_link;
      $page_info = urlencode(serialize($page_info));

      // site content
      $this->tpl->load_tpl('content', 'modules/ModuleBugtracking.tpl');

      $content = $this->tpl->parsereturn('content', array_merge($_LANG2['bt'], array(
        'bt_sender_name_label' => $_LANG["bt_sender_name_label"],
        'bt_text_label' => $_LANG["bt_text_label"],
        'bt_page_info' => $page_info,
        'bt_button_send_label' => $_LANG["bt_button_send_label"],
        'bt_close_label' => $_LANG["bt_close_label"],
        'bt_action' => "index.php?action=mod_response_bugtracking&amp;site=$this->site_id&amp;request=sendbug" )));

      return $content;

    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Send Response                                                                         //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function sendResponse($request)
    {
      global $_LANG, $_LANG2;;

      parent::sendResponse($request);

      $get = new Input(Input::SOURCE_GET);

      $bt_message = "";
      $returncode = 0;

      $page_info = unserialize(urldecode($_GET["bt_page_info"]));
      $bt_module_name = $page_info[0];
      $bt_path_id = $page_info[1];
      $bt_path_link = edwin_url() . $page_info[2];

      $bt_sender_name = $get->readString('bt_sender_name');
      $bt_text = $get->readString('bt_text');

      // check input
      if (!$bt_sender_name || !$bt_text) {
        $bt_message = $_LANG["bt_message_incomplete_input"];
      }

      if (!$bt_message)
      {
        // retrieve contentitem infotmation from database
        $sql = ' SELECT CTID, CTClass, FK_SID '
             . " FROM {$this->table_prefix}contentitem ci, "
             . "      {$this->table_prefix}contenttype ct, "
             . "      {$this->table_prefix}site s "
             . ' WHERE ct.CTID = ci.FK_CTID '
             . " AND ci.CIID = {$bt_path_id} "
             . ' AND ci.FK_SID = s.SID ';
        $result = $this->db->query($sql);
        $row = $this->db->fetch_row($result);
        $bt_contenttype = isset($_LANG["global_{$row['CTClass']}_intlabel"]) ? $_LANG["global_{$row['CTClass']}_intlabel"] : '';
        $bt_contenttype_class = $row["CTClass"];
        $bt_site_id = (int)$row["FK_SID"] ? (int)$row["FK_SID"] : $this->site_id;
        $this->db->free_result($result);

        // prepare mail
        $bt_sitetitle = ContentBase::getLanguageSiteLabel($this->_navigation->getSiteByID($bt_site_id));
        $mail_subject = sprintf($_LANG["bt_mail_subject_form"],$bt_sitetitle);

        // load mail template
        $this->tpl->load_tpl('mail_main', 'mail/bt_main.tpl');
        $mail_text = $this->tpl->parsereturn('mail_main', array_merge($_LANG2['bt'], array( 'bt_sender_name' => parseMailOutput($bt_sender_name, false),
          'bt_site_title' => $bt_sitetitle,
          'bt_user_name' => $this->_user->getNick(),
          'bt_user_email' => $this->_user->getEmail(),
          'bt_text' => parseMailOutput($bt_text, false),
          'bt_path_link' => parseMailOutput($bt_path_link, false),
          'bt_module' => parseMailOutput((isset($_LANG["mod_".$bt_module_name."_title"]) ? $_LANG["mod_".$bt_module_name."_title"] : ""),0),
          'bt_contenttype' => parseMailOutput((isset($_LANG["global_".$bt_contenttype_class."_intlabel"]) ? $_LANG["global_".$bt_contenttype_class."_intlabel"] : $bt_contenttype),0),
          'bt_contenttype_custom' => parseMailOutput((isset($_LANG["global_".$bt_contenttype_class."_text"]) ? $_LANG["global_".$bt_contenttype_class."_text"] : ""),0) )));

        try {
          Container::make('CmsBugtracking')->track($mail_text, $mail_subject);
          $bt_message = $_LANG["bt_message_success"];
          $returncode = 1;
        }
        catch(Exception $e) {
          $bt_message = $_LANG["bt_message_mail_failure"];
          $returncode = 0;
        }
      }

      return $returncode.";".$bt_message;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show/Hide Part for main template                                                      //
    ///////////////////////////////////////////////////////////////////////////////////////////
    static function main_template_part ($tpl){
      global $_LANG;

      $tpl->load_tpl('main_bugtracking', 'main_bugtracking_part.tpl');
      return $tpl->parsereturn('main_bugtracking', array( 'bt_open_label' => $_LANG["bt_open_label"] ));
    }
  }

