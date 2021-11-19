<?php

  /**
   * Leaguemanager Team Module Class
   *
   * $LastChangedDate: 2014-05-23 09:56:43 +0200 (Fr, 23 Mai 2014) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  class ModuleLeaguemanagerTeam extends Module
  {
    protected $_prefix = 'lt';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_innercontent(){

      if (isset($_POST["process"]) && $this->action[0]=="new") $this->create_content();
      if (isset($_POST["process"]) && $this->action[0]=="edit") $this->edit_content();
      if (isset($_GET["did"])) $this->delete_content($_GET["did"]);

      if (!$this->action[0])
        return $this->list_content();
      else
        return $this->get_content();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Create Content                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function create_content(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      if (mb_strlen(trim($_POST['lt_name'])) == 0 || mb_strlen(trim($_POST['lt_shortname'])) == 0) {
        $this->setMessage(Message::createFailure($_LANG['lt_message_insufficient_input']));
      }
      else{
        $name = $post->readString('lt_name', Input::FILTER_PLAIN);
        $shortName = $post->readString('lt_shortname', Input::FILTER_PLAIN);
        $location = $post->readString('lt_location', Input::FILTER_PLAIN);

        $sql = "INSERT INTO {$this->table_prefix}module_leaguemanager_team "
             . '(TName, TShortName, TLocation, FK_SID) '
             . "VALUES ('{$this->db->escape($name)}', '{$this->db->escape($shortName)}', "
             . "        '{$this->db->escape($location)}', $this->site_id) ";
        $result = $this->db->query($sql);
        $this->item_id = $this->db->insert_id();

        $lt_image = $this->_storeImage($_FILES['lt_image'], null, 'lt', 0);
        if ($lt_image) {
          $result = $this->db->query("UPDATE {$this->table_prefix}module_leaguemanager_team SET TImage1 = '$lt_image' WHERE TID = $this->item_id");
        }

        if ($result) {
          $message = $this->_getMessage() ?: Message::createSuccess($_LANG['lt_message_newitem_success']);
          if ($this->_redirectAfterProcessingRequested('list')) {
            $this->_redirect($this->_getBackLinkUrl(), $message);
          }
          else {
            $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)), $message);
          }
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function edit_content(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      if (mb_strlen(trim($_POST['lt_name'])) == 0 || mb_strlen(trim($_POST['lt_shortname'])) == 0) {
        $this->setMessage(Message::createFailure($_LANG['lt_message_insufficient_input']));
      }
      else{
        $sql = 'SELECT TImage1 '
             . "FROM {$this->table_prefix}module_leaguemanager_team "
             . "WHERE TID = $this->item_id ";
        $existingImage = $this->db->GetOne($sql);
        $image1 = $existingImage;
        if ($uploadedImage = $this->_storeImage($_FILES['lt_image'], $image1, 'lt', 0)) {
          $image1 = $uploadedImage;
        }

        $name = $post->readString('lt_name', Input::FILTER_PLAIN);
        $shortName = $post->readString('lt_shortname', Input::FILTER_PLAIN);
        $location = $post->readString('lt_location', Input::FILTER_PLAIN);

        $sql = "UPDATE {$this->table_prefix}module_leaguemanager_team "
             . "SET TName = '{$this->db->escape($name)}', "
             . "    TShortName = '{$this->db->escape($shortName)}', "
             . "    TLocation = '{$this->db->escape($location)}', "
             . "    TImage1 = '$image1', "
             . "    FK_SID = $this->site_id "
             . "WHERE TID = $this->item_id ";
        $result = $this->db->query($sql);

        if (!$this->_getMessage() && $result) {
          if ($this->_redirectAfterProcessingRequested('list')) {
            $this->_redirect($this->_getBackLinkUrl(),
                Message::createSuccess($_LANG['lt_message_edititem_success']));
          }
          else {
            $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
                Message::createSuccess($_LANG['lt_message_edititem_success']));
          }
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_content(){
      global $_LANG;

      if ($this->item_id){ // edit team -> load data
        $result = $this->db->query("SELECT TID,TName,TShortName,TImage1,TLocation from ".$this->table_prefix."module_leaguemanager_team WHERE TDeleted=0 AND TID=".$this->item_id);
        $row = $this->db->fetch_row($result);

        $lt_name = $row["TName"];
        $lt_shortname = $row["TShortName"];
        $lt_email = $row["TImage1"];
        $lt_location = $row["TLocation"];
        $lt_image_src = $this->get_large_image("lt",$row["TImage1"]);

        $this->db->free_result($result);
        $lt_function = "edit";
      }
      else{ // new team
        $lt_name = "";
        $lt_shortname = "";
        $lt_email = "";
        $lt_location = "";
        $lt_image_src = $this->get_large_image("lt","");
        $lt_function = "new";

        if (isset($_POST["lt_name"])) $lt_name = strip_tags($_POST["lt_name"]);
        if (isset($_POST["lt_shortname"])) $lt_shortname = strip_tags($_POST["lt_shortname"]);
        if (isset($_POST["lt_location"])) $lt_location = strip_tags($_POST["lt_location"]);
      }

      $lt_action = "index.php";
      $lt_hidden_fields = '<input type="hidden" name="action" value="mod_leaguemanager" /><input type="hidden" name="action2" value="team;'.$lt_function.'" /><input type="hidden" name="page" value="'.$this->item_id.'" />';

      $mode = ConfigHelper::get('lm_mode');
      $this->tpl->load_tpl('content_team', 'modules/ModuleLeaguemanagerTeam-'.$mode.'.tpl');
      $this->tpl->parse_if('content_team', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('lt'));
      $lt_content = $this->tpl->parsereturn('content_team', array ( 'lt_name' => $lt_name,
                                                                    'lt_shortname' => $lt_shortname,
                                                                    'lt_location' => $lt_location,
                                                                    'lt_image_src' => $lt_image_src,
                                                                    'lt_name_label' => $_LANG["lt_name_label"],
                                                                    'lt_shortname_label' => $_LANG["lt_shortname_label"],
                                                                    'lt_location_label' => $_LANG["lt_location_label"],
                                                                    'lt_image_label' => $_LANG["lt_image_label"],
                                                                    'lt_function_label' => $_LANG["lt_function_".$lt_function."_label"],
                                                                    'lt_action' => $lt_action,
                                                                    'lt_hidden_fields' => $lt_hidden_fields,
                                                                    'lt_function_label2' => $_LANG["lt_function_".$lt_function."_label2"],
                                                                    'lt_data_label' => $_LANG["lt_data_label"],
                                                                    'lt_required_resolution_label' => $this->_getImageSizeInfo('lt', 1),
                                                                    'lt_image_alt_label' => $_LANG["m_image_alt_label"],
                                                                    'lt_module_action_boxes' => $this->_getContentActionBoxes() ));

      return array(
          'content'      => $lt_content,
          'content_left' => $this->_getContentLeft(true),
      );

    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Content                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function delete_content($did){
      global $_LANG;

      /*$result = $this->db->query("SELECT TImage1 from ".$this->table_prefix."module_leaguemanager_team WHERE TID=".$did);
      $row = $this->db->fetch_row($result);
      $lt_image = $row["TImage1"];
      $this->db->free_result($result);
      self::_deleteImageFiles($lt_image);*/

      $result = $this->db->query("UPDATE ".$this->table_prefix."module_leaguemanager_team SET TDeleted=1 WHERE TID=".$did);

      $this->setMessage(Message::createSuccess($_LANG['lt_message_deleteitem_success']));

    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Contents in a List                                                               //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function list_content()
    {
      global $_LANG;

      // read teams
      $team_items = array ();
      $result = $this->db->query("SELECT TID,TName,TShortName,TImage1,TLocation from ".$this->table_prefix."module_leaguemanager_team WHERE TDeleted=0 AND FK_SID=".$this->site_id." ORDER BY TID ASC");
      while ($row = $this->db->fetch_row($result)){
        $team_items[] = array ( 'lt_team_id' => intval($row["TID"]),
                                'lt_team_name' => parseOutput($row["TName"]),
                                'lt_team_shortname' => parseOutput($row["TShortName"]),
                                'lt_team_image_src' => "../".$row["TImage1"],
                                'lt_team_location' => parseOutput($row["TLocation"]),
                                'lt_delete_link' => "index.php?action=mod_leaguemanager&action2=team&amp;did=".$row["TID"],
                                'lt_delete_label' => $_LANG["lt_delete_label"],
                                'lt_content_link' => "index.php?action=mod_leaguemanager&amp;action2=team;edit&amp;page=".$row["TID"],
                                'lt_content_label' => $_LANG["lt_content_label"] );
      }
      $this->db->free_result($result);

      if (!$team_items) {
        $this->setMessage(Message::createFailure($_LANG["lt_message_no_teams"]));
      }

      $mode = ConfigHelper::get('lm_mode');
      $this->tpl->load_tpl('content_teamlist', 'modules/ModuleLeaguemanagerTeam_list-'.$mode.'.tpl');
      $this->tpl->parse_if('content_teamlist', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('lt'));
      $this->tpl->parse_loop('content_teamlist', $team_items, 'team_items');
      $lt_content = $this->tpl->parsereturn('content_teamlist', array ( 'lt_deleteitem_question_label' => $_LANG["lt_deleteitem_question_label"],
                                                                        'lt_function_label' => $_LANG["lt_function_list_label"],
                                                                        'lt_function_label2' => $_LANG["lt_function_list_label2"],
                                                                        'lt_list_image_label' => $_LANG["lt_list_image_label"],
                                                                        'lt_list_team_label' => $_LANG["lt_list_team_label"],
                                                                        'lt_list_location_label' => $_LANG["lt_list_location_label"], ));

      return array(
          'content'      => $lt_content,
          'content_left' => $this->_getContentLeft(),
      );
    }
  }

