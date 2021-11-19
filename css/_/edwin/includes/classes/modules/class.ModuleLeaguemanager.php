<?php

  /**
   * Leaguemanager Module Class
   *
   * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ModuleLeaguemanager extends Module
  {
    public static $subClasses = array(
        'live' => 'ModuleLeaguemanagerLive',
        'team' => 'ModuleLeaguemanagerTeam',
    );

    protected $_prefix = 'lm';
    private $teams = array();
    private $leagues = array();
    private $seasons = array();

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_innercontent(){

      // read teams
      $result = $this->db->query("SELECT TID,TName,TShortName,TImage1,TLocation from ".$this->table_prefix."module_leaguemanager_team WHERE FK_SID=".$this->site_id." AND TDeleted=0 ORDER BY TID ASC");
      while ($row = $this->db->fetch_row($result)){
        $this->teams[$row["TID"]] = array ( 'team_name' => parseOutput($row["TName"]),
                                            'team_shortname' => parseOutput($row["TShortName"]),
                                            'team_image1' => parseOutput($row["TImage1"]),
                                            'team_location' => parseOutput($row["TLocation"]) );
      }
      $this->db->free_result($result);

      // read leagues
      $result = $this->db->query("SELECT LID,LName,LShortName from ".$this->table_prefix."module_leaguemanager_league ORDER BY LID ASC");
      while ($row = $this->db->fetch_row($result)){
        $this->leagues[$row["LID"]] = array ( 'league_name' => parseOutput($row["LName"]),
                                              'league_shortname' => parseOutput($row["LShortName"]) );
      }
      $this->db->free_result($result);

      // read seasons
      $result = $this->db->query("SELECT DISTINCT(YID),YName from ".$this->table_prefix."module_leaguemanager_game LEFT JOIN ".$this->table_prefix."module_leaguemanager_year ON YID=FK_YID ORDER BY YID DESC");
      while ($row = $this->db->fetch_row($result)){
        $this->seasons[$row["YID"]] = array ( 'season_name' => parseOutput($row["YName"]) );
      }
      $this->db->free_result($result);

      if (isset($_POST["process"]) && $this->action[0]=="new") $this->create_content();
      if ((isset($_POST["process"]) && $this->action[0]=="edit") || isset($_POST["process_ticker_new"]) || isset($_POST["process_ticker_edit"])) $this->edit_content();
      if (isset($_GET["dtid"])) $this->delete_ticker_line((int)$_GET["dtid"]);
      if (isset($_GET["did"])) $this->delete_content((int)$_GET["did"]);

      if (empty($this->action[0])) {
        return $this->list_content();
      } else {
        return $this->get_content();
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Create Content                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function create_content(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $lm_date = $post->readString('lm_date', Input::FILTER_PLAIN);
      $tmp = explode(":",$post->readString('lm_time', Input::FILTER_PLAIN));
      $lm_time_hour = (isset($tmp[0]) ? $tmp[0] : 0);
      $lm_time_minute = (isset($tmp[1]) ? $tmp[1] : 0);
      $lm_timestamp = strtotime($lm_date." ".($lm_time_hour ? $lm_time_hour : '00').":".($lm_time_minute ? $lm_time_minute : '00'));

      $result = 0;
      if (mb_strlen(trim($_POST['lm_date'])) == 0) {
        $this->setMessage(Message::createFailure($_LANG['lm_message_insufficient_input']));
      }
      else if (!checkdate(date('m', $lm_timestamp), date('d', $lm_timestamp), date('Y', $lm_timestamp)) || $lm_date == '1970-01-01' || !$lm_date || $lm_time_hour > 24 || $lm_time_minute < 0 || $lm_time_minute > 60) {
        $this->setMessage(Message::createFailure($_LANG['lm_message_invalid_date']));
      }
      else if ($lm_timestamp < time()) {
        $this->setMessage(Message::createFailure($_LANG['lm_message_past_date']));
      }
      else{

        $lm_status = 1;
        $lm_datetime = date("Y-m-d",$lm_timestamp);

        $lm_year = 1;
        $result = $this->db->query("SELECT YID from ".$this->table_prefix."module_leaguemanager_year WHERE YStartDate<='".$lm_datetime."' AND YEndDate>='".$lm_datetime."'");
        $row = $this->db->fetch_row($result);
        if ($row)
          $lm_year = $row["YID"];
        $this->db->free_result($result);

        $lm_datetime .= " ".$lm_time_hour.":".$lm_time_minute;

        $image1 = "";
        if (isset($_FILES['lm_image1']))
          $image1 = $this->_storeImage($_FILES['lm_image1'], null, 'lm', 1);
        $image2 = "";
        if (isset($_FILES['lm_image2']))
          $image2 = $this->_storeImage($_FILES['lm_image2'], null, 'lm', 2);
        $image3 = "";
        if (isset($_FILES['lm_image3']))
          $image3 = $this->_storeImage($_FILES['lm_image3'], null, 'lm', 3);

        $teamHome = $post->readInt('lm_teamhome');
        $teamGuest = $post->readInt('lm_teamguest');
        $text1 = $post->readString('lm_text1', Input::FILTER_CONTENT_TEXT);
        $text2 = $post->readString('lm_text2', Input::FILTER_CONTENT_TEXT);
        $text3 = $post->readString('lm_text3', Input::FILTER_CONTENT_TEXT);
        $league = $post->readInt('lm_league');

        $sql = "INSERT INTO {$this->table_prefix}module_leaguemanager_game "
             . '(GDateTime, GTeamHome, GTeamGuest, GText1, GText2, GText3, '
             . ' GImage1, GImage2, GImage3, GStatus, FK_LID, FK_YID, FK_SID) '
             . "VALUES ('$lm_datetime', $teamHome, $teamGuest, '{$this->db->escape($text1)}', "
             . "        '{$this->db->escape($text2)}', '{$this->db->escape($text3)}', "
             . "        '$image1', '$image2', '$image3', $lm_status, $league, $lm_year, $this->site_id) ";
        $result = $this->db->query($sql);
        //$this->item_id = $this->db->insert_id();

        $_POST["lm_date"] = "";
        $_POST["lm_time"] = "";
        $_POST["lm_teamhome"] = "";
        $_POST["lm_teamhome_score"] = "";
        $_POST["lm_teamguest"] = "";
        $_POST["lm_teamguest_score"] = "";
        $this->item_id = 0;
      }

      if ($result) {
        $this->setMessage(Message::createSuccess($_LANG['lm_message_newitem_success']));
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function edit_content(){
      global $_LANG;

      if (isset($_POST["lm_report"]) && $_POST["lm_report"] == "<br />") $_POST["lm_report"] = "";
      $post = new Input(Input::SOURCE_POST);

      $lm_date = $post->readString('lm_date', Input::FILTER_PLAIN);
      $lm_league = $post->readInt('lm_league');
      $lm_teamhome = $post->readInt('lm_teamhome');
      $lm_teamguest = $post->readInt('lm_teamguest');
      $tmp = explode(":",$post->readString('lm_time', Input::FILTER_PLAIN));
      $lm_time_hour = (isset($tmp[0]) ? $tmp[0] : 0);
      $lm_time_minute = (isset($tmp[1]) ? $tmp[1] : 0);
      $lm_timestamp = strtotime($lm_date." ".($lm_time_hour ? $lm_time_hour : '00').":".($lm_time_minute ? $lm_time_minute : '00'));

      $result = $this->db->query("SELECT GDateTime,FK_LID,GTeamHome,GTeamGuest from ".$this->table_prefix."module_leaguemanager_game WHERE GID=".$this->item_id);
      $row = $this->db->fetch_row($result);
      $lm_datetime_db = $row["GDateTime"];
      $lm_league_db = $row["FK_LID"];
      $lm_teamhome_db = $row["GTeamHome"];
      $lm_teamguest_db = $row["GTeamGuest"];
      $this->db->free_result($result);

      $past_date = 0;
      $lm_datetime = date("Y-m-d",$lm_timestamp);
      $lm_datetime .= " ".$lm_time_hour.":".$lm_time_minute;
      if (strtotime($lm_datetime_db) < time()) { // past dates cannot be changed
        $lm_datetime = $lm_datetime_db;
        $lm_league = $lm_league_db;
        $lm_teamhome = $lm_teamhome_db;
        $lm_teamguest = $lm_teamguest_db;
        // bypass disabled date fields for date check
        $past_date = 1;
      }

      if (isset($_POST["process_ticker_new"]))
        $this->new_ticker_line();
      else if (isset($_POST["process_ticker_edit"]))
        $this->edit_ticker_line();
      else if (!$past_date && mb_strlen(trim($_POST['lm_date'])) == 0) {
        $this->setMessage(Message::createFailure($_LANG['lm_message_insufficient_input']));
      }
      else if (!$past_date && (!checkdate(date('m', $lm_timestamp), date('d', $lm_timestamp), date('Y', $lm_timestamp)) || $lm_date == '1970-01-01' || !$lm_date || $lm_time_hour > 24 || $lm_time_minute < 0 || $lm_time_minute > 60)) {
        $this->setMessage(Message::createFailure($_LANG['lm_message_invalid_date']));
      }
      else if (!$past_date && ($lm_timestamp < time())) {
        $this->setMessage(Message::createFailure($_LANG['lm_message_past_date']));
      }
      else{

        $lm_status = 1;
        if (strtotime($lm_datetime_db)+(intval(ConfigHelper::get('lm_active_game_max_duration'))*60) < time() || $_POST["process"] == $_LANG["lm_button_finish_submit_label"])
          $lm_status = 2;

        $sql = 'SELECT GImage1, GImage2, GImage3 '
             . "FROM {$this->table_prefix}module_leaguemanager_game "
             . "WHERE GID = $this->item_id ";
        $existingImages = $this->db->GetRow($sql);
        $image1 = $existingImages['GImage1'];
        if (isset($_FILES['lm_image1']) && $uploadedImage = $this->_storeImage($_FILES['lm_image1'], $image1, 'lm', 1)) {
          $image1 = $uploadedImage;
        }
        $image2 = $existingImages['GImage2'];
        if (isset($_FILES['lm_image2']) && $uploadedImage = $this->_storeImage($_FILES['lm_image2'], $image2, 'lm', 2)) {
          $image2 = $uploadedImage;
        }
        $image3 = $existingImages['GImage3'];
        if (isset($_FILES['lm_image3']) && $uploadedImage = $this->_storeImage($_FILES['lm_image3'], $image3, 'lm', 3)) {
          $image3 = $uploadedImage;
        }

        $teamHomeScore = abs($post->readInt('lm_teamhome_score'));
        $teamHomeScorePart1 = $post->readInt('lm_teamhome_score_part1');
        $teamHomeScorePart2 = $post->readInt('lm_teamhome_score_part2');
        $teamHomeScorePart3 = $post->readInt('lm_teamhome_score_part3');
        $teamHomeScorePart4 = $post->readInt('lm_teamhome_score_part4');
        $teamHomeScorePart5 = $post->readInt('lm_teamhome_score_part5');
        $teamHomeLineup = $post->readString('lm_teamhome_lineup', Input::FILTER_PLAIN);
        $teamGuestScore = abs($post->readInt('lm_teamguest_score'));
        $teamGuestScorePart1 = $post->readInt('lm_teamguest_score_part1');
        $teamGuestScorePart2 = $post->readInt('lm_teamguest_score_part2');
        $teamGuestScorePart3 = $post->readInt('lm_teamguest_score_part3');
        $teamGuestScorePart4 = $post->readInt('lm_teamguest_score_part4');
        $teamGuestScorePart5 = $post->readInt('lm_teamguest_score_part5');
        $teamGuestLineup = $post->readString('lm_teamguest_lineup', Input::FILTER_PLAIN);
        $report = $post->readString('lm_report', Input::FILTER_CONTENT_TEXT);
        $text1 = $post->readString('lm_text1', Input::FILTER_CONTENT_TEXT);
        $text2 = $post->readString('lm_text2', Input::FILTER_CONTENT_TEXT);
        $text3 = $post->readString('lm_text3', Input::FILTER_CONTENT_TEXT);

        $sql = "UPDATE {$this->table_prefix}module_leaguemanager_game "
             . "SET GDateTime = '{$this->db->escape($lm_datetime)}', "
             . "    GTeamHome = $lm_teamhome, "
             . "    GTeamHomeScore = $teamHomeScore, "
             . "    GTeamHomeScorePart1 = $teamHomeScorePart1, "
             . "    GTeamHomeScorePart2 = $teamHomeScorePart2, "
             . "    GTeamHomeScorePart3 = $teamHomeScorePart3, "
             . "    GTeamHomeScorePart4 = $teamHomeScorePart4, "
             . "    GTeamHomeScorePart5 = $teamHomeScorePart5, "
             . "    GTeamHomeLineup = '{$this->db->escape($teamHomeLineup)}', "
             . "    GTeamGuest = $lm_teamguest, "
             . "    GTeamGuestScore = $teamGuestScore, "
             . "    GTeamGuestScorePart1 = $teamGuestScorePart1, "
             . "    GTeamGuestScorePart2 = $teamGuestScorePart2, "
             . "    GTeamGuestScorePart3 = $teamGuestScorePart3, "
             . "    GTeamGuestScorePart4 = $teamGuestScorePart4, "
             . "    GTeamGuestScorePart5 = $teamGuestScorePart5, "
             . "    GTeamGuestLineup = '{$this->db->escape($teamGuestLineup)}', "
             . "    GReport = '{$this->db->escape($report)}', "
             . "    GText1 = '{$this->db->escape($text1)}', "
             . "    GText2 = '{$this->db->escape($text2)}', "
             . "    GText3 = '{$this->db->escape($text3)}', "
             . "    FK_LID = $lm_league, "
             . "    GStatus = $lm_status, "
             . "    GImage1 = '$image1', "
             . "    GImage2 = '$image2', "
             . "    GImage3 = '$image3' "
             . "WHERE GID = $this->item_id ";
        $result = $this->db->query($sql);

        if (!$this->_getMessage() && $result && !isset($_POST["process_ticker_new"]) && !isset($_POST["process_ticker_edit"])) {
          $this->session->save("lm_edit_success",1);
          header("Location: index.php?action=mod_leaguemanager");
          exit();
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // New Ticker Line                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function new_ticker_line(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      if (mb_strlen(trim($_POST["lm_ticker_minute"])) || mb_strlen(trim($_POST["lm_ticker_text"]))){
        $minute = $post->readString('lm_ticker_minute', Input::FILTER_PLAIN);
        $image = $post->readInt('lm_ticker_image');
        $text = $post->readString('lm_ticker_text', Input::FILTER_PLAIN);

        $sql = "INSERT INTO {$this->table_prefix}module_leaguemanager_game_ticker "
             . '(TMinute, TImage, TText, FK_GID) '
             . "VALUES('{$this->db->escape($minute)}', $image, '{$this->db->escape($text)}', $this->item_id)";
        $result = $this->db->query($sql);
        if ($result) {
          $this->setMessage(Message::createSuccess($_LANG['lm_message_newtickeritem_success']));
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Ticker Line                                                                      //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function edit_ticker_line(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $lm_ticker_id = $this->session->read("lm_ticker_id");
      if ($lm_ticker_id){
        if (mb_strlen(trim($_POST["lm_ticker_minute_edit"])) || mb_strlen(trim($_POST["lm_ticker_text_edit"]))){
          $minute = $post->readString('lm_ticker_minute_edit', Input::FILTER_PLAIN);
          $image = $post->readInt('lm_ticker_image_edit');
          $text = $post->readString('lm_ticker_text_edit', Input::FILTER_PLAIN);

          $sql = "UPDATE {$this->table_prefix}module_leaguemanager_game_ticker "
               . "SET TMinute = '{$this->db->escape($minute)}', "
               . "    TImage = $image, "
               . "    TText = '{$this->db->escape($text)}' "
               . "WHERE TID = $lm_ticker_id ";
          $result = $this->db->query($sql);
          if ($result){
            $this->setMessage(Message::createSuccess($_LANG['lm_message_edittickeritem_success']));
            $this->session->reset("lm_ticker_id");
          }
        }
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Ticker Line                                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function delete_ticker_line($did){
      global $_LANG;

      $result = $this->db->query("DELETE FROM ".$this->table_prefix."module_leaguemanager_game_ticker WHERE TID=".$did);

      $lm_ticker_id = $this->session->read("lm_ticker_id");
      if ($lm_ticker_id == $did) $this->session->reset("lm_ticker_id");

      $this->setMessage(Message::createSuccess($_LANG['lm_message_deletetickeritem_success']));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_content(){
      global $_LANG;

      $lm_gamedata_visibility = "display:none;";
      $lm_gamedata_icon = "lo_oc1.gif";
      $lm_tickerdata_visibility = "visibility:hidden;display:none;";
      $lm_tickerdata_icon = "lo_oc2.gif";

      if ($this->item_id){ // edit game -> load data
        $result = $this->db->query("SELECT GID,GDateTime,GTeamHome,GTeamHomeScore,GTeamHomeScorePart1,GTeamHomeScorePart2,GTeamHomeScorePart3,GTeamHomeScorePart4,GTeamHomeScorePart5,GTeamHomeLineup,GTeamGuest,GTeamGuestScore,GTeamGuestScore,GTeamGuestScorePart1,GTeamGuestScorePart2,GTeamGuestScorePart3,GTeamGuestScorePart4,GTeamGuestScorePart5,GTeamGuestLineup,GStatus,GReport,GText1,GText2,GText3,GImage1,GImage2,GImage3,FK_LID from ".$this->table_prefix."module_leaguemanager_game WHERE GDeleted=0 AND FK_SID=".$this->site_id." AND GID=".$this->item_id);
        $row = $this->db->fetch_row($result);

        $lm_date = date("d.m.Y",strtotime($row["GDateTime"]));
        $lm_time = date("H:i",strtotime($row["GDateTime"]));
        $lm_datetime_db = date("H:i",strtotime($row["GDateTime"]));
        $lm_teamhome = $row["GTeamHome"];
        $lm_teamhome_lineup = $row["GTeamHomeLineup"];
        $lm_status = $row["GStatus"];
        $lm_teamhome_score = ($lm_status==1 && time() <= strtotime($row["GDateTime"]) ? "" : intval($row["GTeamHomeScore"]));
        $lm_teamguest = $row["GTeamGuest"];
        $lm_teamguest_lineup = $row["GTeamGuestLineup"];
        $lm_teamguest_score = ($lm_status==1 && time() <= strtotime($row["GDateTime"]) ? "" : intval($row["GTeamGuestScore"]));
        $lm_league = $row["FK_LID"];
        $lm_report = $row["GReport"];
        $lm_text1 = $row["GText1"];
        $lm_text2 = $row["GText2"];
        $lm_text3 = $row["GText3"];
        $lm_image_src1 = $this->get_large_image("lm",$row["GImage1"]);
        $lm_image_src2 = $this->get_large_image("lm",$row["GImage2"]);
        $lm_image_src3 = $this->get_large_image("lm",$row["GImage3"]);
        $lm_teamhome_score_part1 = (isset($row["GTeamHomeScorePart1"]) ? intval($row["GTeamHomeScorePart1"]) : "");
        $lm_teamhome_score_part2 = (isset($row["GTeamHomeScorePart2"]) ? intval($row["GTeamHomeScorePart2"]) : "");
        $lm_teamhome_score_part3 = (isset($row["GTeamHomeScorePart3"]) ? intval($row["GTeamHomeScorePart3"]) : "");
        $lm_teamhome_score_part4 = (isset($row["GTeamHomeScorePart4"]) ? intval($row["GTeamHomeScorePart4"]) : "");
        $lm_teamhome_score_part5 = (isset($row["GTeamHomeScorePart5"]) ? intval($row["GTeamHomeScorePart5"]) : "");
        $lm_teamguest_score_part1 = (isset($row["GTeamGuestScorePart1"]) ? intval($row["GTeamGuestScorePart1"]) : "");
        $lm_teamguest_score_part2 = (isset($row["GTeamGuestScorePart2"]) ? intval($row["GTeamGuestScorePart2"]) : "");
        $lm_teamguest_score_part3 = (isset($row["GTeamGuestScorePart3"]) ? intval($row["GTeamGuestScorePart3"]) : "");
        $lm_teamguest_score_part4 = (isset($row["GTeamGuestScorePart4"]) ? intval($row["GTeamGuestScorePart4"]) : "");
        $lm_teamguest_score_part5 = (isset($row["GTeamGuestScorePart5"]) ? intval($row["GTeamGuestScorePart5"]) : "");

        if ($lm_status == 1 && time() < strtotime($row["GDateTime"])){
          $lm_function = "edit_before_game";
          $lm_gamedata_visibility = "visibility:visible;display:block;";
          $lm_gamedata_icon = "lo_oc2.gif";
        }
        else if ($lm_status == 1 && time() >= strtotime($row["GDateTime"])){
          $lm_function = "edit_after_game";
          $lm_gamedata_visibility = "visibility:visible;display:block;";
          $lm_gamedata_icon = "lo_oc2.gif";
        }
        else if ($lm_status == 2) $lm_function = "edit_finished_game";
        $lm_form_action = "edit";

        $this->db->free_result($result);
      }
      else{ // new game
        $lm_date = "";
        $lm_time = "";
        $lm_teamhome = "";
        $lm_teamhome_lineup = "";
        $lm_teamhome_score = "";
        $lm_teamhome_score_part1 = "";
        $lm_teamhome_score_part2 = "";
        $lm_teamhome_score_part3 = "";
        $lm_teamhome_score_part4 = "";
        $lm_teamhome_score_part5 = "";
        $lm_teamguest = "";
        $lm_teamguest_lineup = "";
        $lm_teamguest_score = "";
        $lm_teamguest_score_part1 = "";
        $lm_teamguest_score_part2 = "";
        $lm_teamguest_score_part3 = "";
        $lm_teamguest_score_part4 = "";
        $lm_teamguest_score_part5 = "";
        $lm_status = 0;
        $lm_league = 0;
        $lm_text1 = "";
        $lm_text2 = "";
        $lm_text3 = "";
        $lm_image_src1 = "";
        $lm_image_src2 = "";
        $lm_image_src3 = "";
        $lm_report = "";
        $lm_function = "new";
        $lm_gamedata_visibility = "display:block;";
        $lm_gamedata_icon = "lo_oc2.gif";
        $lm_form_action = "new";

        if (isset($_POST["lm_date"])) $lm_date = strip_tags($_POST["lm_date"]);
        if (isset($_POST["lm_time"])) $lm_time = strip_tags($_POST["lm_time"]);
        if (isset($_POST["lm_teamhome"])) $lm_teamhome = intval(strip_tags($_POST["lm_teamhome"]));
        if (isset($_POST["lm_teamhome_score"])) $lm_teamhome_score = strip_tags($_POST["lm_teamhome_score"]);
        if (isset($_POST["lm_teamguest"])) $lm_teamguest = intval(strip_tags($_POST["lm_teamguest"]));
        if (isset($_POST["lm_teamguest_score"])) $lm_teamguest_score = strip_tags($_POST["lm_teamguest_score"]);
        if (isset($_POST["lm_text1"])) $lm_text1 = strip_tags($_POST["lm_text1"]);
        if (isset($_POST["lm_text2"])) $lm_text2 = strip_tags($_POST["lm_text2"]);
        if (isset($_POST["lm_text3"])) $lm_text3 = strip_tags($_POST["lm_text3"]);
        if (isset($_POST["lm_league"])) $lm_teamguest = intval(strip_tags($_POST["lm_league"]));
      }

      $lm_edit_disabled = ((strtotime($lm_date." ".$lm_time) < time() && $lm_function != "new") ? ' disabled="disabled"' : "");

      // create home team dropdown
      $lm_teamhome_static = "";
      $tmp_teamhome = '<select name="lm_teamhome" class="lm_th"'.$lm_edit_disabled.'>';
      foreach ($this->teams as $tid => $tvalue){
        $tmp_teamhome .= '<option value="'.$tid.'"';
        if ($lm_teamhome == $tid){
          $tmp_teamhome .= ' selected="selected"';
          $lm_teamhome_static = $tvalue["team_name"];
        }
        $tmp_teamhome .= '>'.$tvalue["team_name"].'</option>';
      }
      $lm_teamhome = $tmp_teamhome."</select>";

      // create guest team dropdown
      $lm_teamguest_static = "";
      $tmp_teamguest = '<select name="lm_teamguest" class="lm_tg"'.$lm_edit_disabled.'>';
      foreach ($this->teams as $tid => $tvalue){
        $tmp_teamguest .= '<option value="'.$tid.'"';
        if ($lm_teamguest == $tid){
          $tmp_teamguest .= ' selected="selected"';
          $lm_teamguest_static = $tvalue["team_name"];
        }
        $tmp_teamguest .= '>'.$tvalue["team_name"].'</option>';
      }
      $lm_teamguest = $tmp_teamguest."</select>";

      // create league dropdown
      $tmp_league = '<select name="lm_league" class="lm_tg"'.$lm_edit_disabled.'>';
      foreach ($this->leagues as $lid => $lvalue){
        $tmp_league .= '<option value="'.$lid.'"';
        if ($lm_league == $lid) $tmp_league .= ' selected="selected"';
        $tmp_league .= '>'.$lvalue["league_name"].'</option>';
      }
      $lm_league = $tmp_league."</select>";

      $lm_action = "index.php";
      $lm_hidden_fields = '<input type="hidden" name="action" value="mod_leaguemanager" /><input type="hidden" name="action2" value="main;'.$lm_form_action.'" /><input type="hidden" name="page" value="'.$this->item_id.'" />';
      if ($lm_function == "edit_after_game")
        $lm_buttons = '<input type="submit" class="btn_process button_lm" name="process" value="'.$_LANG["lm_button_finish_submit_label"].'" />';
      else
        $lm_buttons = '<input type="submit" class="btn_process button_lm" name="process" value="' . $this->_langVar('button_submit_label') . '" />';
      $lm_buttons_ticker_new = '<input type="submit" class="button_lm_tn" name="process_ticker_new" value="'.$_LANG["lm_button_new_label"].'" />';
      $lm_buttons_ticker_edit = '<input type="submit" class="button_lm_te" name="process_ticker_edit" value="'.$_LANG["lm_button_edit_label"].'" />';

      // load ticker part
      $lm_ticker_id = 0;
      if (isset($_GET["ticker_id"])) $this->session->save("lm_ticker_id",intval($_GET["ticker_id"]));
      $lm_ticker_id = $this->session->read("lm_ticker_id");

      if (isset($lm_ticker_id) || ($this->_getMessage() && ($this->_getMessage()->getText() == $_LANG["lm_message_newtickeritem_success"] || $this->_getMessage()->getText() == $_LANG["lm_message_edittickeritem_success"] || $this->_getMessage()->getText() == $_LANG["lm_message_deletetickeritem_success"]))) {
        $lm_tickerdata_visibility = "visibility:visible;display:block;";
        $lm_tickerdata_icon = "lo_oc2.gif";
      }

      $icons = ConfigHelper::get('lm_icons');
      $lm_ticker_lines = array();
      $lm_ticker_class = "";
      $lm_ticker_minute_edit = "";
      $lm_ticker_image_edit = "";
      $lm_ticker_text_edit = "";
      $result = $this->db->query("SELECT TID,TMinute,TImage,TText from ".$this->table_prefix."module_leaguemanager_game_ticker WHERE FK_GID=".$this->item_id." ORDER BY TID DESC");
      while ($row = $this->db->fetch_row($result)){
        $lm_ticker_class = "";
        if ($row["TID"] == $lm_ticker_id){
          $lm_ticker_class = "lm_edit_row";
          $lm_ticker_minute_edit = $row["TMinute"];
          $lm_ticker_image_edit = intval($row["TImage"]);
          $lm_ticker_text_edit = $row["TText"];
        }
        $lm_ticker_lines[] = array ( 'lm_ticker_minute' => parseOutput($row["TMinute"]),
                                     'lm_ticker_image_src' => "../".$icons[intval($row["TImage"])],
                                     'lm_ticker_image_label' => $_LANG["lm_icons"][$row["TImage"]],
                                     'lm_ticker_text' => parseOutput($row["TText"]),
                                     'lm_ticker_class' => $lm_ticker_class,
                                     'lm_delete_link' => "index.php?action=mod_leaguemanager&amp;action2=main;edit&amp;page=".$this->item_id."&amp;dtid=".$row["TID"],
                                     'lm_delete_label' => $_LANG["lm_delete_ticker_label"],
                                     'lm_content_link' => "index.php?action=mod_leaguemanager&amp;action2=main;edit&amp;page=".$this->item_id."&amp;ticker_id=".$row["TID"],
                                     'lm_content_label' => $_LANG["lm_content_ticker_label"]  );
      }
      $this->db->free_result($result);

      // create icon selectors
      $tmp_ticker_image = '<select name="lm_ticker_image_edit" class="lm_it">';
      foreach ($icons as $iid => $isrc){
        $tmp_ticker_image .= '<option value="'.$iid.'"';
        if ($lm_ticker_image_edit == $iid) $tmp_ticker_image .= ' selected="selected"';
        $tmp_ticker_image .= '>'.$_LANG["lm_icons"][$iid].'</option>';
      }
      $lm_ticker_image_edit = $tmp_ticker_image."</select>";

      $tmp_ticker_image = '<select name="lm_ticker_image" class="lm_it">';
      foreach ($icons as $iid => $isrc){
        $tmp_ticker_image .= '<option value="'.$iid.'"';
        $tmp_ticker_image .= '>'.$_LANG["lm_icons"][$iid].'</option>';
      }
      $lm_ticker_image = $tmp_ticker_image."</select>";

      $lm_jump_to_anchor = "#";
      if ($this->_getMessage() && $this->_getMessage()->getText() == $_LANG["lm_message_edititem_success"]) {
        $lm_jump_to_anchor = "#afteredit";
      }
      else if ($lm_ticker_id) $lm_jump_to_anchor = "#edit";

      $mode = ConfigHelper::get('lm_mode');
      $skin = ConfigHelper::get('be_skin');
      $this->tpl->load_tpl('content_game_ticker', 'modules/ModuleLeaguemanager_tickerpart-'.$mode.'.tpl');
      $this->tpl->parse_loop('content_game_ticker', $lm_ticker_lines, 'ticker_lines');
      $this->tpl->parse_if('content_game_ticker', 'edit_line', $lm_ticker_id, array( 'lm_ticker_minute_edit' => $lm_ticker_minute_edit,
                                                                                     'lm_ticker_image_edit' => $lm_ticker_image_edit,
                                                                                     'lm_ticker_text_edit' => $lm_ticker_text_edit,
                                                                                     'lm_ticker_minute_label' => $_LANG["lm_ticker_minute_label"],
                                                                                     'lm_ticker_image_label' => $_LANG["lm_ticker_image_label"],
                                                                                     'lm_ticker_text_label' => $_LANG["lm_ticker_text_label"],
                                                                                     'lm_ticker_data_edit_label' => $_LANG["lm_ticker_data_edit_label"],
                                                                                     'lm_buttons_edit' => $lm_buttons_ticker_edit ));
      $lm_ticker = $this->tpl->parsereturn('content_game_ticker', array ( 'lm_ticker_minute_label' => $_LANG["lm_ticker_minute_label"],
                                                                          'lm_ticker_image_label' => $_LANG["lm_ticker_image_label"],
                                                                          'lm_ticker_text_label' => $_LANG["lm_ticker_text_label"],
                                                                          'lm_ticker_image' => $lm_ticker_image,
                                                                          'lm_ticker_data_new_label' => $_LANG["lm_ticker_data_new_label"],
                                                                          'lm_ticker_data_existing_label' => $_LANG["lm_ticker_data_existing_label"],
                                                                          'lm_deleteitem_question_label' => $_LANG["lm_deletetickeritem_question_label"],
                                                                          'lm_jump_to_anchor' => $lm_jump_to_anchor,
                                                                          'lm_buttons_new' => $lm_buttons_ticker_new,
                                                                          'lm_showhide_newticker_label' => $_LANG["lm_showhide_newticker_label"] ));

      $this->tpl->load_tpl('content_game', 'modules/ModuleLeaguemanager-'.$mode.'.tpl');
      $this->tpl->parse_if('content_game', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('lm'));
      $this->tpl->parse_if('content_game', 'gamedata_change_available', ($lm_function == "edit_before_game" || $lm_function == "new"), array( 'lm_teamhome' => $lm_teamhome,
                                                                                                                                              'lm_teamguest' => $lm_teamguest,
                                                                                                                                              'lm_teamhome_label' => $_LANG["lm_teamhome_label"],
                                                                                                                                              'lm_teamguest_label' => $_LANG["lm_teamguest_label"] ));
      $this->tpl->parse_if('content_game', 'report_available', ($lm_status==2), array( 'lm_report' => $lm_report,
                                                                                       'lm_report_label' => $_LANG["lm_report_label"],
                                                                                       'lm_lineup_label' => $_LANG["lm_lineup_label"],
                                                                                       'lm_teamhome_lineup' => $lm_teamhome_lineup,
                                                                                       'lm_teamguest_lineup' => $lm_teamguest_lineup ));
      $this->tpl->parse_if('content_game', 'result_available', ($lm_status==2 || $lm_function == "edit_after_game"), array( 'lm_teamhome_score' => $lm_teamhome_score,
                                                                                       'lm_teamhome_score_part1' => $lm_teamhome_score_part1,
                                                                                       'lm_teamhome_score_part2' => $lm_teamhome_score_part2,
                                                                                       'lm_teamhome_score_part3' => $lm_teamhome_score_part3,
                                                                                       'lm_teamhome_score_part4' => $lm_teamhome_score_part4,
                                                                                       'lm_teamhome_score_part5' => $lm_teamhome_score_part5,
                                                                                       'lm_teamguest_score' => $lm_teamguest_score,
                                                                                       'lm_teamguest_score_part1' => $lm_teamguest_score_part1,
                                                                                       'lm_teamguest_score_part2' => $lm_teamguest_score_part2,
                                                                                       'lm_teamguest_score_part3' => $lm_teamguest_score_part3,
                                                                                       'lm_teamguest_score_part4' => $lm_teamguest_score_part4,
                                                                                       'lm_teamguest_score_part5' => $lm_teamguest_score_part5,
                                                                                       'lm_score_label' => $_LANG["lm_score_label"],
                                                                                       'lm_score_part1_label' => $_LANG["lm_score_part1_label"],
                                                                                       'lm_score_part2_label' => $_LANG["lm_score_part2_label"],
                                                                                       'lm_score_part3_label' => $_LANG["lm_score_part3_label"],
                                                                                       'lm_score_part4_label' => $_LANG["lm_score_part4_label"],
                                                                                       'lm_score_part5_label' => $_LANG["lm_score_part5_label"],
                                                                                       'lm_score_part1_label' => $_LANG["lm_score_part1_label"],
                                                                                       'lm_score_part2_label' => $_LANG["lm_score_part2_label"],
                                                                                       'lm_score_part3_label' => $_LANG["lm_score_part3_label"],
                                                                                       'lm_score_part4_label' => $_LANG["lm_score_part4_label"],
                                                                                       'lm_score_part5_label' => $_LANG["lm_score_part5_label"] ));
      $this->tpl->parse_if('content_game', 'ticker_available', ($lm_status==2 || $lm_function == "edit_after_game"), array( 'lm_ticker' => $lm_ticker,
                                                                                                                            'lm_tickerdata_visibility' => $lm_tickerdata_visibility,
                                                                                                                            'lm_tickerdata_icon' => "pix/".$skin."/".$lm_tickerdata_icon,
                                                                                                                            'lm_ticker_label' => $_LANG["lm_ticker_label"] ));
      $lm_content = $this->tpl->parsereturn('content_game', array ( 'lm_date' => $lm_date,
                                                                    'lm_time' => $lm_time,
                                                                    'lm_text1' => $lm_text1,
                                                                    'lm_text2' => $lm_text2,
                                                                    'lm_text3' => $lm_text3,
                                                                    'lm_image1_src' => $lm_image_src1,
                                                                    'lm_image2_src' => $lm_image_src2,
                                                                    'lm_image3_src' => $lm_image_src3,
                                                                    'lm_edit_disabled' => $lm_edit_disabled,
                                                                    'lm_teamhome_static' => $lm_teamhome_static,
                                                                    'lm_teamguest_static' => $lm_teamguest_static,
                                                                    'lm_league' => $lm_league,
                                                                    'lm_date_label' => $_LANG["lm_date_label"],
                                                                    'lm_teamhome_label' => $_LANG["lm_teamhome_label"],
                                                                    'lm_teamguest_label' => $_LANG["lm_teamguest_label"],
                                                                    'lm_league_label' => $_LANG["lm_league_label"],
                                                                    'lm_status_label' => $_LANG["lm_status_label"],
                                                                    'lm_text1_label' => $_LANG["lm_text1_label"],
                                                                    'lm_text2_label' => $_LANG["lm_text2_label"],
                                                                    'lm_text3_label' => $_LANG["lm_text3_label"],
                                                                    'lm_image1_label' => $_LANG["lm_image1_label"],
                                                                    'lm_image2_label' => $_LANG["lm_image2_label"],
                                                                    'lm_image3_label' => $_LANG["lm_image3_label"],
                                                                    'lm_function_label' => $_LANG["lm_function_".$lm_function."_label"],
                                                                    'lm_action' => $lm_action,
                                                                    'lm_hidden_fields' => $lm_hidden_fields,
                                                                    'lm_function_label2' => $_LANG["lm_function_".$lm_function."_label2"],
                                                                    'lm_data_label' => $_LANG["lm_data_label"],
                                                                    'lm_calendar_month_names' => $_LANG["global_calendar_month_names"],
                                                                    'lm_calendar_week_names' => $_LANG["global_calendar_week_names"],
                                                                    'lm_gamedata_visibility' => $lm_gamedata_visibility,
                                                                    'lm_gamedata_icon' => "pix/".$skin."/".$lm_gamedata_icon,
                                                                    'lm_area_showhide_label' => $_LANG["lm_area_showhide_label"],
                                                                    'lm_required_resolution_label1' => $this->_getImageSizeInfo('lm', 1),
                                                                    'lm_required_resolution_label2' => $this->_getImageSizeInfo('lm', 2),
                                                                    'lm_required_resolution_label3' => $this->_getImageSizeInfo('lm', 3),
                                                                    'lm_image_alt_label' => $_LANG["m_image_alt_label"],
                                                                    'lm_module_action_boxes' => $this->_getContentActionBoxes($lm_buttons),
      ));

      return array(
          'content'      => $lm_content,
          'content_left' => $this->_getContentLeft(true),
      );
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Delete Content                                                                        //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function delete_content($did){
      global $_LANG;

      $result = $this->db->query("UPDATE ".$this->table_prefix."module_leaguemanager_game SET GDeleted=1 WHERE GID=".$did);
      $result = $this->db->query("UPDATE ".$this->table_prefix."module_leaguemanager_game_ticker SET TDeleted=1 WHERE FK_GID=".$did);

      $this->setMessage(Message::createSuccess($_LANG['lm_message_deleteitem_success']));

    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Contents in a List                                                               //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function list_content(){
      global $_LANG;

      // display message after editing
      if ($this->session->read("lm_edit_success")){
        $this->setMessage(Message::createSuccess($_LANG['lm_message_edititem_success']));
        $this->session->reset("lm_edit_success");
      }

      // select current or chosen season
      $now = date("Y-m-d");
      $result = $this->db->query("SELECT YID from ".$this->table_prefix."module_leaguemanager_year WHERE YStartDate<='".$now."' AND YEndDate>='".$now."'");
      $row = $this->db->fetch_row($result);
      $lm_current_season = $row["YID"];
      $this->db->free_result($result);
      $lm_season = 0;
      if (isset($_GET["yid"])) $lm_season = intval($_GET["yid"]);
      if (!$lm_season) $lm_season = (int)$lm_current_season;

      // create season navigation
      $lm_season_navigation = array();
      foreach ($this->seasons as $sid => $svalue){
        $lm_season_navigation[] = array ( 'lm_season_name' => $svalue["season_name"],
                                          'lm_season_class' => ($sid == $lm_season ? "lm_as" : "lm_os"),
                                          'lm_season_link' => "index.php?action=mod_leaguemanager&amp;action2=main&amp;site=".$this->site_id."&amp;yid=".$sid );
      }

      $activeGameStart = (int)ConfigHelper::get('lm_active_game_start');
      $maxDuration = (int)ConfigHelper::get('lm_active_game_max_duration');
      // read games
      $game_items = array ();
      $game_items["past"] = array ();
      $game_items["now"] = array ();
      $game_items["future"] = array ();
      $game_items["current"] = array ();
      $result = $this->db->query("SELECT GID,GDateTime,GTeamHome,GTeamHomeScore,GTeamHomeScorePart1,GTeamHomeScorePart2,GTeamHomeScorePart3,GTeamHomeScorePart4,GTeamHomeScorePart5,GTeamGuest,GTeamGuestScore,GTeamGuestScorePart1,GTeamGuestScorePart2,GTeamGuestScorePart3,GTeamGuestScorePart4,GTeamGuestScorePart5,GStatus,FK_LID,FK_YID from ".$this->table_prefix."module_leaguemanager_game WHERE GDeleted=0 AND FK_SID=".$this->site_id." AND FK_YID=".$lm_season." ORDER BY GDateTime DESC");
      while ($row = $this->db->fetch_row($result)){
        $lm_unfinished = 0;
        $lm_status = "";
        if (intval($row["GStatus"]) == 2){
          $lm_status = $_LANG["lm_status_finished"];
          $group = "past";
        }
        else if (intval($row["GStatus"]) == 1){
          // game running
          if (time() >= strtotime($row["GDateTime"])-($activeGameStart*60) && time() <= strtotime($row["GDateTime"])+($maxDuration*60)){
            $lm_status = $_LANG["lm_status_running"];
            $group = "now";
          }
          else{
            $lm_status = $_LANG["lm_status_coming"];
            if (time() > strtotime($row["GDateTime"])+($maxDuration*60)){
              $group = "past";
              $lm_status = $_LANG["lm_status_unfinished"];
              $lm_unfinished = 1;
            }
            else $group = "future";
          }
        }

        $game_items[$group][] = array( 'lm_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'lm'),strtotime($row["GDateTime"])),
                                       'lm_teamhome' => $this->teams[intval($row["GTeamHome"])]["team_name"],
                                       'lm_teamhomescore' => (intval($row["GStatus"]) == 1 ? "-" : intval($row["GTeamHomeScore"])),
                                       'lm_teamhomescore_part1' => (isset($row["GTeamHomeScorePart1"]) ? intval($row["GTeamHomeScorePart1"]) : "-"),
                                       'lm_teamhomescore_part2' => (isset($row["GTeamHomeScorePart2"]) ? intval($row["GTeamHomeScorePart2"]) : "-"),
                                       'lm_teamhomescore_part3' => (isset($row["GTeamHomeScorePart3"]) ? intval($row["GTeamHomeScorePart3"]) : "-"),
                                       'lm_teamhomescore_part4' => (isset($row["GTeamHomeScorePart4"]) ? intval($row["GTeamHomeScorePart4"]) : "-"),
                                       'lm_teamhomescore_part5' => (isset($row["GTeamHomeScorePart5"]) ? intval($row["GTeamHomeScorePart5"]) : "-"),
                                       'lm_teamguest' => $this->teams[intval($row["GTeamGuest"])]["team_name"],
                                       'lm_teamguestcore' => (intval($row["GStatus"]) == 1 ? "-" : intval($row["GTeamGuestScore"])),
                                       'lm_teamguestscore_part1' => (isset($row["GTeamGuestScorePart1"]) ? intval($row["GTeamGuestScorePart1"]) : "-"),
                                       'lm_teamguestscore_part2' => (isset($row["GTeamGuestScorePart2"]) ? intval($row["GTeamGuestScorePart2"]) : "-"),
                                       'lm_teamguestscore_part3' => (isset($row["GTeamGuestScorePart3"]) ? intval($row["GTeamGuestScorePart3"]) : "-"),
                                       'lm_teamguestscore_part4' => (isset($row["GTeamGuestScorePart4"]) ? intval($row["GTeamGuestScorePart4"]) : "-"),
                                       'lm_teamguestscore_part5' => (isset($row["GTeamGuestScorePart5"]) ? intval($row["GTeamGuestScorePart5"]) : "-"),
                                       'lm_league' => $this->leagues[intval($row["FK_LID"])]["league_shortname"],
                                       'lm_delete_link' => "index.php?action=mod_leaguemanager&amp;did=".$row["GID"],
                                       'lm_delete_label' => $_LANG["lm_delete_label"],
                                       'lm_content_link' => ($lm_status == $_LANG["lm_status_running"] ? "index.php?action=mod_leaguemanager&amp;action2=live" : "index.php?action=mod_leaguemanager&amp;action2=main;edit&amp;page=".$row["GID"]),
                                       'lm_content_label' => $_LANG["lm_content_label"],
                                       'lm_class' => ($lm_unfinished ? "lm_ug" : ($group == "now" ? "lm_cg" : "lm_g")),
                                       'lm_status' => $lm_status );
      }
      $this->db->free_result($result);

      $cnt_past = count($game_items["past"]);
      $cnt_now = count($game_items["now"]);
      $cnt_future = count($game_items["future"]);
      $game_items["future"] = array_reverse($game_items["future"]);
      if ($lm_current_season == $lm_season){
        foreach ($game_items["future"] as $id => $value){
          if ($id < 2 || ($id <= 2 && !$cnt_now) || ($id < 5-($cnt_past+$cnt_now) && $cnt_past < 3)){
            $game_items["current"][] = $value;
            unset($game_items["future"][$id]);
          }
        }
      }
      $game_items["future"] = array_reverse($game_items["future"]);
      $game_items["current"] = array_reverse($game_items["current"]);
      if ($lm_current_season == $lm_season){
        if ($cnt_now) $game_items["current"][] = $game_items["now"][0];
        else if (count($game_items["current"])) $game_items["current"][count($game_items["current"])-1]['lm_class'] = "lm_ncg";
        foreach ($game_items["past"] as $id => $value){
          if ($id < 2 || ($id < 5-($cnt_future+$cnt_now) && $cnt_future < 3)){
            $game_items["current"][] = $value;
            unset($game_items["past"][$id]);
          }
        }
      }
      if (!$game_items) {
        $this->setMessage(Message::createFailure($_LANG['lm_message_no_games']));
      }

      $mode = ConfigHelper::get('lm_mode');
      $this->tpl->load_tpl('content_gamelist', 'modules/'.($lm_current_season == $lm_season ? "ModuleLeaguemanager_listCurrent" : "ModuleLeaguemanager_list").'-'.$mode.'.tpl');
      $this->tpl->parse_if('content_gamelist', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('lm'));
      $this->tpl->parse_loop('content_gamelist', $game_items["past"], 'game_items_past');
      $this->tpl->parse_loop('content_gamelist', $game_items["current"], 'game_items');
      $this->tpl->parse_loop('content_gamelist', $game_items["future"], 'game_items_future');
      $this->tpl->parse_loop('content_gamelist', $lm_season_navigation, 'season_navigation');
      $lm_content = $this->tpl->parsereturn('content_gamelist', array ( 'lm_deleteitem_question_label' => $_LANG["lm_deleteitem_question_label"],
                                                                        'lm_list_label' => $_LANG["lm_function_list_label"],
                                                                        'lm_list_label2' => $_LANG["lm_function_list_label2"],
                                                                        'lm_list_showall_label' => $_LANG["lm_list_showall_label"],
                                                                        'lm_list_date_label' => $_LANG["lm_list_date_label"],
                                                                        'lm_list_league_label' => $_LANG["lm_list_league_label"],
                                                                        'lm_list_teams_label' => $_LANG["lm_list_teams_label"],
                                                                        'lm_list_result_label' => $_LANG["lm_list_result_label"],
                                                                        'lm_list_status_label' => $_LANG["lm_list_status_label"], ));

      return array(
          'content'      => $lm_content,
          'content_left' => $this->_getContentLeft(),
      );
    }
  }