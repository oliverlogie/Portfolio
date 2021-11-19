<?php

  /**
   * Leaguemanager LiveTicker Module Class
   *
   * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ModuleLeaguemanagerLive extends Module {

    private $teams = array();

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_innercontent(){
      $result = $this->db->query("SELECT TID,TName,TShortName,TImage1,TLocation from ".$this->table_prefix."module_leaguemanager_team WHERE FK_SID=".$this->site_id." ORDER BY TID ASC");
      while ($row = $this->db->fetch_row($result)){
        $this->teams[$row["TID"]] = array ( 'team_name' => parseOutput($row["TName"]),
                                            'team_shortname' => parseOutput($row["TShortName"]),
                                            'team_image1' => "../".parseOutput($row["TImage1"]),
                                            'team_location' => parseOutput($row["TLocation"]) );
      }
      $this->db->free_result($result);

      if (isset($_POST["process_ticker"]) && $this->action[0]=="edit") $this->edit_ticker_line();
      else if ((isset($_POST["process"]) || isset($_POST["ll_finish"]) )&& $this->action[0]=="edit") $this->edit_content();
      if (isset($_GET["did"])) $this->delete_content((int)$_GET["did"]);

      return $this->get_content();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Ticker Line                                                                      //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function edit_ticker_line(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $ll_ticker_id = $this->session->read("ll_ticker_id");
      if ($ll_ticker_id){
        if (mb_strlen(trim($_POST["ll_ticker_minute_edit"])) || mb_strlen(trim($_POST["ll_ticker_text_edit"]))){
          $minute = $post->readString('ll_ticker_minute_edit', Input::FILTER_PLAIN);
          $image = $post->readInt('ll_ticker_image_edit');
          $text = $post->readString('ll_ticker_text_edit', Input::FILTER_PLAIN);

          $sql = "UPDATE {$this->table_prefix}module_leaguemanager_game_ticker "
               . "SET TMinute = '{$this->db->escape($minute)}', "
               . "    TImage = $image, "
               . "    TText = '{$this->db->escape($text)}' "
               . "WHERE TID = $ll_ticker_id ";
          $result = $this->db->query($sql);
          if ($result){
            $this->setMessage(Message::createSuccess($_LANG['ll_message_edititem_success']));
            $this->session->reset("ll_ticker_id");
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

      $ll_status = 1;
      if (isset($_POST["ll_finish"]))
        if ($_POST["ll_finish"])
          $ll_status = 2;

      if (mb_strlen(trim($_POST['ll_teamhome_score'])) == 0 || mb_strlen(trim($_POST['ll_teamguest_score'])) == 0) {
        $this->setMessage(Message::createFailure($_LANG['ll_message_insufficient_input']));
      }
      else{
        $sql = 'SELECT GImage1, GImage2, GImage3 '
             . "FROM {$this->table_prefix}module_leaguemanager_game "
             . "WHERE GID = $this->item_id ";
        $existingImages = $this->db->GetRow($sql);
        $image1 = $existingImages['GImage1'];
        if ($uploadedImage = $this->_storeImage($_FILES['ll_image1'], $image1, 'lm', 1)) {
          $image1 = $uploadedImage;
        }
        $image2 = $existingImages['GImage2'];
        if ($uploadedImage = $this->_storeImage($_FILES['ll_image2'], $image2, 'lm', 2)) {
          $image2 = $uploadedImage;
        }
        $image3 = $existingImages['GImage3'];
        if ($uploadedImage = $this->_storeImage($_FILES['ll_image3'], $image3, 'lm', 3)) {
          $image3 = $uploadedImage;
        }

        $teamHomeScore = abs($post->readInt('ll_teamhome_score'));
        $teamHomeScorePart1 = abs($post->readInt('ll_teamhome_score_part1'));
        $teamHomeScorePart2 = abs($post->readInt('ll_teamhome_score_part2'));
        $teamHomeScorePart3 = abs($post->readInt('ll_teamhome_score_part3'));
        $teamHomeScorePart4 = abs($post->readInt('ll_teamhome_score_part4'));
        $teamHomeScorePart5 = abs($post->readInt('ll_teamhome_score_part5'));
        $teamHomeLineup = $post->readString('ll_teamhome_lineup', Input::FILTER_PLAIN);
        $teamGuestScore = abs($post->readInt('ll_teamguest_score'));
        $teamGuestScorePart1 = abs($post->readInt('ll_teamguest_score_part1'));
        $teamGuestScorePart2 = abs($post->readInt('ll_teamguest_score_part2'));
        $teamGuestScorePart3 = abs($post->readInt('ll_teamguest_score_part3'));
        $teamGuestScorePart4 = abs($post->readInt('ll_teamguest_score_part4'));
        $teamGuestScorePart5 = abs($post->readInt('ll_teamguest_score_part5'));
        $teamGuestLineup = $post->readString('ll_teamguest_lineup', Input::FILTER_PLAIN);
        $text1 = $post->readString('ll_text1', Input::FILTER_CONTENT_TEXT);
        $text2 = $post->readString('ll_text2', Input::FILTER_CONTENT_TEXT);
        $text3 = $post->readString('ll_text3', Input::FILTER_CONTENT_TEXT);

        $sql = "UPDATE {$this->table_prefix}module_leaguemanager_game "
             . "SET GTeamHomeScore = $teamHomeScore, "
             . "    GTeamHomeScorePart1 = $teamHomeScorePart1, "
             . "    GTeamHomeScorePart2 = $teamHomeScorePart2, "
             . "    GTeamHomeScorePart3 = $teamHomeScorePart3, "
             . "    GTeamHomeScorePart4 = $teamHomeScorePart4, "
             . "    GTeamHomeScorePart5 = $teamHomeScorePart5, "
             . "    GTeamHomeLineup = '{$this->db->escape($teamHomeLineup)}', "
             . "    GTeamGuestScore = $teamGuestScore, "
             . "    GTeamGuestScorePart1 = $teamGuestScorePart1, "
             . "    GTeamGuestScorePart2 = $teamGuestScorePart2, "
             . "    GTeamGuestScorePart3 = $teamGuestScorePart3, "
             . "    GTeamGuestScorePart4 = $teamGuestScorePart4, "
             . "    GTeamGuestScorePart5 = $teamGuestScorePart5, "
             . "    GTeamGuestLineup = '{$this->db->escape($teamGuestLineup)}', "
             . "    GStatus = $ll_status, "
             . "    GText1 = '{$this->db->escape($text1)}', "
             . "    GText2 = '{$this->db->escape($text2)}', "
             . "    GText3 = '{$this->db->escape($text3)}', "
             . "    GImage1 = '$image1', "
             . "    GImage2 = '$image2', "
             . "    GImage3 = '$image3' "
             . "WHERE GID = $this->item_id ";
        $result = $this->db->query($sql);

        if (mb_strlen(trim($_POST["ll_ticker_minute"])) || mb_strlen(trim($_POST["ll_ticker_text"]))){
          $minute = $post->readString('ll_ticker_minute', Input::FILTER_PLAIN);
          $image = $post->readInt('ll_ticker_image');
          $text = $post->readString('ll_ticker_text', Input::FILTER_PLAIN);
          $sql = "INSERT INTO {$this->table_prefix}module_leaguemanager_game_ticker "
               . '(TMinute, TImage, TText, FK_GID) '
               . "VALUES('{$this->db->escape($minute)}', $image, '{$this->db->escape($text)}', $this->item_id) ";
          $result = $this->db->query($sql);
          if ($result) $this->setMessage(Message::createSuccess($_LANG['ll_message_newitem_success']));
        }

        if ($result) {
          $this->setMessage(Message::createSuccess($_LANG['ll_message_edit_success']));
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_content(){
      global $_LANG;

      $maxDuration = ConfigHelper::get('lm_active_game_max_duration');
      $icons = ConfigHelper::get('lm_icons');
      $mode = ConfigHelper::get('lm_mode');
      // read active game
      $this->item_id = 0;
      $result = $this->db->query("SELECT GID,GDateTime,GTeamHome,GTeamHomeScore,GTeamHomeScorePart1,GTeamHomeScorePart2,GTeamHomeScorePart3,GTeamHomeScorePart4,GTeamHomeScorePart5,GTeamHomeLineup,GTeamGuest,GTeamGuestScore,GTeamGuestScorePart1,GTeamGuestScorePart2,GTeamGuestScorePart3,GTeamGuestScorePart4,GTeamGuestScorePart5,GTeamGuestLineup,GText1,GText2,GText3,GImage1,GImage2,GImage3,GStatus from ".$this->table_prefix."module_leaguemanager_game WHERE GDeleted=0 AND FK_SID=".$this->site_id." ORDER BY GDateTime DESC");
      while ($row = $this->db->fetch_row($result)){
        if (intval($row["GStatus"]) == 1){
          if (time() >= strtotime($row["GDateTime"])-1800 && time() <= strtotime($row["GDateTime"])+($maxDuration*60)){
            $this->item_id = $row["GID"];
            $ll_date = date($this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'll'),strtotime($row["GDateTime"]));
            $ll_time = date("H:i",strtotime($row["GDateTime"]));
            $ll_teamhome = $row["GTeamHome"];
            $ll_status = $row["GStatus"];
            $ll_teamhome_score = intval($row["GTeamHomeScore"]);
            $ll_teamhome_score_part1 = (isset($row["GTeamHomeScorePart1"]) ? intval($row["GTeamHomeScorePart1"]) : "");
            $ll_teamhome_score_part2 = (isset($row["GTeamHomeScorePart2"]) ? intval($row["GTeamHomeScorePart2"]) : "");
            $ll_teamhome_score_part3 = (isset($row["GTeamHomeScorePart3"]) ? intval($row["GTeamHomeScorePart3"]) : "");
            $ll_teamhome_score_part4 = (isset($row["GTeamHomeScorePart4"]) ? intval($row["GTeamHomeScorePart4"]) : "");
            $ll_teamhome_score_part5 = (isset($row["GTeamHomeScorePart5"]) ? intval($row["GTeamHomeScorePart5"]) : "");
            $ll_teamhome_lineup = $row["GTeamHomeLineup"];
            $ll_teamguest = $row["GTeamGuest"];
            $ll_teamguest_score = intval($row["GTeamGuestScore"]);
            $ll_teamguest_score_part1 = (isset($row["GTeamGuestScorePart1"]) ? intval($row["GTeamGuestScorePart1"]) : "");
            $ll_teamguest_score_part2 = (isset($row["GTeamGuestScorePart2"]) ? intval($row["GTeamGuestScorePart2"]) : "");
            $ll_teamguest_score_part3 = (isset($row["GTeamGuestScorePart3"]) ? intval($row["GTeamGuestScorePart3"]) : "");
            $ll_teamguest_score_part4 = (isset($row["GTeamGuestScorePart4"]) ? intval($row["GTeamGuestScorePart4"]) : "");
            $ll_teamguest_score_part5 = (isset($row["GTeamGuestScorePart5"]) ? intval($row["GTeamGuestScorePart5"]) : "");
            $ll_teamguest_lineup = $row["GTeamGuestLineup"];
            $ll_text1 = $row["GText1"];
            $ll_text2 = $row["GText2"];
            $ll_text3 = $row["GText3"];
            $ll_image_src1 = $this->get_large_image("lm",$row["GImage1"]);
            $ll_image_src2 = $this->get_large_image("lm",$row["GImage2"]);
            $ll_image_src3 = $this->get_large_image("lm",$row["GImage3"]);
          }
        }
      }
      $this->db->free_result($result);
      // now active game -> redirect to game list
      if (!$this->item_id) header("Location: index.php?action=mod_leaguemanager&action2=main");

      // ticker line management
      $ll_ticker_id = 0;
      if (isset($_GET["ticker_id"])) $this->session->save("ll_ticker_id",intval($_GET["ticker_id"]));
      $ll_ticker_id = $this->session->read("ll_ticker_id");

      $ll_ticker_lines = array();
      $result = $this->db->query("SELECT TID,TMinute,TImage,TText from ".$this->table_prefix."module_leaguemanager_game_ticker WHERE FK_GID=".$this->item_id." ORDER BY TID DESC");
      while ($row = $this->db->fetch_row($result)){
        $ll_ticker_class = "";
        if ($row["TID"] == $ll_ticker_id){
          $ll_ticker_class = "ll_edit_row";
          $ll_ticker_minute_edit = $row["TMinute"];
          $ll_ticker_image_edit = intval($row["TImage"]);
          $ll_ticker_text_edit = $row["TText"];
        }
        $ll_ticker_lines[] = array ( 'll_ticker_minute' => parseOutput($row["TMinute"]),
                                     'll_ticker_image_src' => "../".$icons[intval($row["TImage"])],
                                     'll_ticker_image_label' => $_LANG["lm_icons"][$row["TImage"]],
                                     'll_ticker_text' => parseOutput($row["TText"]),
                                     'll_ticker_class' => $ll_ticker_class,
                                     'll_delete_link' => "index.php?action=mod_leaguemanager&amp;action2=live&amp;did=".$row["TID"],
                                     'll_delete_label' => $_LANG["ll_delete_label"],
                                     'll_content_link' => "index.php?action=mod_leaguemanager&amp;action2=live&amp;ticker_id=".$row["TID"],
                                     'll_content_label' => $_LANG["ll_content_label"]  );
      }
      $this->db->free_result($result);

      // create icon selectors
      $tmp_ticker_image = '<select name="ll_ticker_image_edit" class="ll_it">';
      foreach ($icons as $iid => $isrc){
        $tmp_ticker_image .= '<option value="'.$iid.'"';
        if ($ll_ticker_image_edit == $iid) $tmp_ticker_image .= ' selected="selected"';
        $tmp_ticker_image .= '>'.$_LANG["lm_icons"][$iid].'</option>';
      }
      $ll_ticker_image_edit = $tmp_ticker_image."</select>";

      $tmp_ticker_image = '<select name="ll_ticker_image" class="ll_it">';
      foreach ($icons as $iid => $isrc){
        $tmp_ticker_image .= '<option value="'.$iid.'"';
        if ($ll_ticker_image == $iid) $tmp_ticker_image .= ' selected="selected"';
        $tmp_ticker_image .= '>'.$_LANG["lm_icons"][$iid].'</option>';
      }
      $ll_ticker_image = $tmp_ticker_image."</select>";

      $ll_jump_to_anchor = "#";
      if ($this->_getMessage() && $this->_getMessage()->getText() == $_LANG["ll_message_edititem_success"]) {
        $ll_jump_to_anchor = "#afteredit";
      }
      else if ($ll_ticker_id) $ll_jump_to_anchor = "#edit";
      else if ($this->_getMessage() && $this->_getMessage()->getText() == $_LANG["ll_message_edit_success"]) {
        $ll_jump_to_anchor = "#playdata";
      }

      $ll_action = "index.php";
      $ll_hidden_fields = '<input type="hidden" name="action" value="mod_leaguemanager" /><input type="hidden" name="action2" value="live;edit" /><input type="hidden" name="page" value="'.$this->item_id.'" /><input type="hidden" name="ticker_id" value="'.$ticker_id.'" /><input type="hidden" name="ll_finish" value="0" />';
      $ll_buttons = '<input type="submit" class="btn_process button_ll" name="process" value="'.$_LANG["ll_button_submit_label"].'" />';
      $ll_buttons_new = '<input type="submit" class="btn_process button_ll_tn" name="process" value="'.$_LANG["ll_button_new_label"].'" />';
      $ll_buttons_edit = '<input type="submit" class="button_ll_te" name="process_ticker" value="'.$_LANG["ll_button_edit_label"].'" />';
      $ll_buttons_finish = '<input type="button" class="button_ll_finished" name="process_finish" value="'.$_LANG["ll_button_finish_label"].'" onclick="finish_game();" />';
      $this->tpl->load_tpl('content_live', 'modules/ModuleLeaguemanagerLive-'.$mode.'.tpl');
      $this->tpl->parse_if('content_live', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ll'));
      $this->tpl->parse_if('content_live', 'edit_line', $ll_ticker_id, array( 'll_ticker_minute_edit' => $ll_ticker_minute_edit,
                                                                  'll_ticker_image_edit' => $ll_ticker_image_edit,
                                                                  'll_ticker_text_edit' => $ll_ticker_text_edit,
                                                                  'll_ticker_minute_label' => $_LANG["ll_ticker_minute_label"],
                                                                  'll_ticker_image_label' => $_LANG["ll_ticker_image_label"],
                                                                  'll_ticker_text_label' => $_LANG["ll_ticker_text_label"],
                                                                  'll_ticker_data_edit_label' => $_LANG["ll_ticker_data_edit_label"] ));
      $this->tpl->parse_loop('content_live', $ll_ticker_lines, 'ticker_lines');
      $ll_content = $this->tpl->parsereturn('content_live', array ( 'll_teamhome' => $this->teams[$ll_teamhome]["team_name"],
                                                                    'll_teamguest' => $this->teams[$ll_teamguest]["team_name"],
                                                                    'll_teamhome_image1' => $this->teams[$ll_teamhome]["team_image1"],
                                                                    'll_teamguest_image1' => $this->teams[$ll_teamguest]["team_image1"],
                                                                    'll_teamhome_score' => $ll_teamhome_score,
                                                                    'll_teamhome_score_part1' => $ll_teamhome_score_part1,
                                                                    'll_teamhome_score_part2' => $ll_teamhome_score_part2,
                                                                    'll_teamhome_score_part3' => $ll_teamhome_score_part3,
                                                                    'll_teamhome_score_part4' => $ll_teamhome_score_part4,
                                                                    'll_teamhome_score_part5' => $ll_teamhome_score_part5,
                                                                    'll_teamhome_lineup' => $ll_teamhome_lineup,
                                                                    'll_teamguest_score' => $ll_teamguest_score,
                                                                    'll_teamguest_score_part1' => $ll_teamguest_score_part1,
                                                                    'll_teamguest_score_part2' => $ll_teamguest_score_part2,
                                                                    'll_teamguest_score_part3' => $ll_teamguest_score_part3,
                                                                    'll_teamguest_score_part4' => $ll_teamguest_score_part4,
                                                                    'll_teamguest_score_part5' => $ll_teamguest_score_part5,
                                                                    'll_teamguest_lineup' => $ll_teamguest_lineup,
                                                                    'll_date' => $ll_date,
                                                                    'll_text1' => $ll_text1,
                                                                    'll_text2' => $ll_text2,
                                                                    'll_text3' => $ll_text3,
                                                                    'll_image1_src' => $ll_image_src1,
                                                                    'll_image2_src' => $ll_image_src2,
                                                                    'll_image3_src' => $ll_image_src3,
                                                                    'll_date_label' => $_LANG["ll_date_label"],
                                                                    'll_score_part1_label' => $_LANG["ll_score_part1_label"],
                                                                    'll_score_part2_label' => $_LANG["ll_score_part2_label"],
                                                                    'll_score_part3_label' => $_LANG["ll_score_part3_label"],
                                                                    'll_score_part4_label' => $_LANG["ll_score_part4_label"],
                                                                    'll_score_part5_label' => $_LANG["ll_score_part5_label"],
                                                                    'll_text1_label' => $_LANG["ll_text1_label"],
                                                                    'll_text2_label' => $_LANG["ll_text2_label"],
                                                                    'll_text3_label' => $_LANG["ll_text3_label"],
                                                                    'll_image1_label' => $_LANG["ll_image1_label"],
                                                                    'll_image2_label' => $_LANG["ll_image2_label"],
                                                                    'll_image3_label' => $_LANG["ll_image3_label"],
                                                                    'll_lineup_label' => $_LANG["ll_lineup_label"],
                                                                    'll_teamhome_label' => $_LANG["ll_teamhome_label"],
                                                                    'll_teamguest_label' => $_LANG["ll_teamguest_label"],
                                                                    'll_ticker_minute_label' => $_LANG["ll_ticker_minute_label"],
                                                                    'll_ticker_image_label' => $_LANG["ll_ticker_image_label"],
                                                                    'll_ticker_text_label' => $_LANG["ll_ticker_text_label"],
                                                                    'll_ticker_minute' => $ll_ticker_minute,
                                                                    'll_ticker_image' => $ll_ticker_image,
                                                                    'll_ticker_text' => $ll_ticker_text,
                                                                    'll_function_label' => $_LANG["ll_function_label"],
                                                                    'll_function_label2' => $_LANG["ll_function_label2"],
                                                                    'll_action' => $ll_action,
                                                                    'll_hidden_fields' => $ll_hidden_fields,
                                                                    'll_buttons' => $ll_buttons,
                                                                    'll_buttons_new' => $ll_buttons_new,
                                                                    'll_buttons_edit' => $ll_buttons_edit,
                                                                    'll_buttons_finish' => $ll_buttons_finish,
                                                                    'll_jump_to_anchor' => $ll_jump_to_anchor,
                                                                    'll_data_label' => $_LANG["ll_data_label"],
                                                                    'll_game_label' => $_LANG["ll_game_label"],
                                                                    'll_ticker_data_new_label' => $_LANG["ll_ticker_data_new_label"],
                                                                    'll_ticker_data_existing_label' => $_LANG["ll_ticker_data_existing_label"],
                                                                    'll_actions_label' => $_LANG["ll_actions_label"],
                                                                    'll_deleteitem_question_label' => $_LANG["ll_deleteitem_question_label"],
                                                                    'll_finish_question_label' => $_LANG["ll_finish_question_label"],
                                                                    'll_area_showhide_label' => $_LANG["ll_area_showhide_label"],
                                                                    'll_required_resolution_label1' => $this->_getImageSizeInfo('lm', 1),
                                                                    'll_required_resolution_label2' => $this->_getImageSizeInfo('lm', 2),
                                                                    'll_required_resolution_label3' => $this->_getImageSizeInfo('lm', 3),
                                                                    'll_image_alt_label' => $_LANG["m_image_alt_label"] ));

      return array(
          'content' => $ll_content,
      );
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Content                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function delete_content($did){
      global $_LANG;

      $result = $this->db->query("DELETE FROM ".$this->table_prefix."module_leaguemanager_game_ticker WHERE TID=".$did);

      $ll_ticker_id = $this->session->read("ll_ticker_id");
      if ($ll_ticker_id == $did) $this->session->reset("ll_ticker_id");

      $this->setMessage(Message::createSuccess($_LANG['ll_message_deleteitem_success']));
    }

  }


