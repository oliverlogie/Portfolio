<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2018-04-27 10:39:49 +0200 (Fr, 27 Apr 2018) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ContentItemSP extends ContentItem
  {
    protected $_configPrefix = 'sp';
    protected $_contentPrefix = 'sp';
    protected $_columnPrefix = 'P';
    protected $_contentElements = array(
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'SP';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function edit_content()
    {
      // Handle default content elements.
      parent::edit_content();

      $post = new Input(Input::SOURCE_POST);

      // Read titles and texts.
      $name = $post->readString('sp_name', Input::FILTER_CONTENT_TITLE);
      $shortDescription = $post->readString('sp_shortdescription', Input::FILTER_CONTENT_TITLE);
      $nick = $post->readString('sp_nick', Input::FILTER_CONTENT_TITLE);
      $height = $post->readString('sp_height', Input::FILTER_CONTENT_TITLE);
      $country = $post->readString('sp_country', Input::FILTER_CONTENT_TITLE);
      $number = $post->readInt('sp_number');
      $position = $post->readInt('sp_position');
      $hobbies = $post->readString('sp_hobbies', Input::FILTER_CONTENT_TEXT);
      $familyStatus = $post->readString('sp_familystatus', Input::FILTER_CONTENT_TITLE);
      $history = $post->readString('sp_history', Input::FILTER_CONTENT_TEXT);
      $team = $post->readInt('sp_team', 1);
      $birthday = $post->readDate('sp_birthday');

      // Update the database.
      $sql = "UPDATE {$this->table_prefix}contentitem_sp "
           . "SET PName = '$name', "
           . "    PShortDescription = '{$this->db->escape($shortDescription)}', "
           . "    PNick = '{$this->db->escape($nick)}', "
           . "    PBirthday = " . ($birthday ? sprintf("'%s'", $birthday) : 'NULL') . ", "
           . "    PHeight = '{$this->db->escape($height)}', "
           . "    PCountry = '{$this->db->escape($country)}', "
           . "    PNumber = $number, "
           . "    PPosition = '$position', "
           . "    PHobbies = '{$this->db->escape($hobbies)}', "
           . "    PFamilyStatus = '{$this->db->escape($familyStatus)}', "
           . "    PHistory = '{$this->db->escape($history)}', "
           . "    FK_TID = $team "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
    }

    public function getTexts($subcontent = true)
    {
      $texts = parent::getTexts();

      if ($subcontent)
      {
        $sql = 'SELECT PShortDescription, PHobbies, PHistory, PNick '
             . "FROM {$this->table_prefix}contentitem_sp "
             . "WHERE FK_CIID = $this->page_id ";
        $additionalTexts = $this->db->GetRow($sql);
        $texts = array_merge($texts, $additionalTexts);
      }
      return $texts;
    }

    /**
     * Returns all title elements within this content item (or subcontent)
     * @return array
     *          an array containing all titles stored for this content item (or subcontent)
     */
    protected function getTitles()
    {
      $titles = parent::getTitles();

      $sql = 'SELECT PName '
           . "FROM {$this->table_prefix}contentitem_sp "
           . "WHERE FK_CIID = $this->page_id ";
      $additionalTitles = $this->db->GetRow($sql);
      $titles = array_merge($titles, $additionalTitles);

      return $titles;
    }

    public function get_content($params = array())
    {
      global $_LANG, $_LANG2;

      $row = $this->_getData();

      $sp_name = $row["PName"];
      $sp_shortdescription = $row["PShortDescription"];
      $sp_nick = $row["PNick"];
      $sp_birthday = $row["PBirthday"];
      $sp_height = $row["PHeight"];
      $sp_country = $row["PCountry"];
      $sp_number = $row["PNumber"];
      $sp_position = intval($row["PPosition"]);
      $sp_hobbies = $row["PHobbies"];
      $sp_familystatus = $row["PFamilyStatus"];
      $sp_history = $row["PHistory"];
      $sp_team = intval($row["FK_TID"]);

      // read teams
      $sql = 'SELECT TID, TName '
           . "FROM {$this->table_prefix}module_leaguemanager_team "
           . "WHERE FK_SID = $this->site_id "
           . 'AND TDeleted = 0 '
           . 'ORDER BY TID ASC ';
      $sp_teams = $this->db->GetAssoc($sql);

      // create team dropdown
      $tmp_team = '<select name="sp_team" class="sp_ts">';
      foreach ($sp_teams as $tid => $tname) {
        $tmp_team .= '<option value="'.$tid.'"';
        if ($sp_team == $tid) $tmp_team .= ' selected="selected"';
        $tmp_team .= '>' . parseOutput($tname) . '</option>';
      }
      $sp_team = $tmp_team."</select>";

      // create position dropdown
      $tmp_position = '<select name="sp_position" class="sp_ps">';
      foreach ($_LANG["sp_position"] as $pid => $pvalue){
        $tmp_position .= '<option value="'.$pid.'"';
        if ($sp_position == $pid) $tmp_position .= ' selected="selected"';
        $tmp_position .= '>'.$pvalue.'</option>';
      }
      $sp_position = $tmp_position."</select>";

      if ($sp_birthday == "1970-01-01" || $sp_birthday == "0000-00-00" || !$sp_birthday) $sp_birthday = "";
      else{
        $sp_birthday = date($this->_configHelper->getDateFormat($this->_user->getLanguage(), $this->getConfigPrefix()),strtotime($sp_birthday));
      }

      $this->tpl->load_tpl('content_site_sp', $this->_getTemplatePath());
      $this->tpl->parse_vars('content_site_sp', array (
          'sp_name' => $sp_name,
          'sp_shortdescription' => $sp_shortdescription,
          'sp_nick' => $sp_nick,
          'sp_birthday' => $sp_birthday,
          'sp_height' => $sp_height,
          'sp_country' => $sp_country,
          'sp_number' => $sp_number,
          'sp_position' => $sp_position,
          'sp_hobbies' => $sp_hobbies,
          'sp_familystatus' => $sp_familystatus,
          'sp_history' => $sp_history,
          'sp_team' => $sp_team,
          'sp_player_data_label' => $_LANG["sp_player_data_label"],
          'sp_player_images_label' => $_LANG["sp_player_images_label"],
          'sp_showhide_label' => $_LANG["sp_showhide_label"],
          'sp_name_label' => $_LANG["sp_name_label"],
          'sp_shortdescription_label' => $_LANG["sp_shortdescription_label"],
          'sp_nick_label' => $_LANG["sp_nick_label"],
          'sp_birthday_label' => $_LANG["sp_birthday_label"],
          'sp_height_label' => $_LANG["sp_height_label"],
          'sp_country_label' => $_LANG["sp_country_label"],
          'sp_number_label' => $_LANG["sp_number_label"],
          'sp_position_label' => $_LANG["sp_position_label"],
          'sp_hobbies_label' => $_LANG["sp_hobbies_label"],
          'sp_familystatus_label' => $_LANG["sp_familystatus_label"],
          'sp_history_label' => $_LANG["sp_history_label"],
          'sp_history_label2' => $_LANG["sp_history_label2"],
          'sp_team_label' => $_LANG["sp_team_label"],
      ));

      return parent::get_content(array_merge($params, array(
        'row'      => $row,
        'settings' => array( 'tpl' => 'content_site_sp' ),
      )));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){
      global $_LANG, $_LANG2;

      $post = new Input(Input::SOURCE_POST);

      $sp_image_titles = $post->readImageTitles('sp_image_title');
      $sp_image_titles = $this->explode_content_image_titles("c_sp",$sp_image_titles);

      $sp_images = $this->_createPreviewImages(array(
        'PImage1' => 'sp_image1',
        'PImage2' => 'sp_image2',
        'PImage3' => 'sp_image3',
      ));
      $sp_image_src1 = $sp_images['sp_image1'];
      $sp_image_src2 = $sp_images['sp_image2'];
      $sp_image_src3 = $sp_images['sp_image3'];
      $sp_image_src_large1 = $this->_hasLargeImage($sp_image_src1);
      $sp_image_src_large2 = $this->_hasLargeImage($sp_image_src2);
      $sp_image_src_large3 = $this->_hasLargeImage($sp_image_src3);

      $this->tpl->set_tpl_dir("../templates");

      $history_items = array();
      $tmp_row = explode("<br />",$post->readString('sp_history', Input::FILTER_PLAIN));
      foreach ($tmp_row as $value){
        $tmp_cols = explode(", ",$value);
        if (isset($tmp_cols[2]))
          $history_items[] = array ( 'sp_history_from' => $tmp_cols[0],
                                     'sp_history_to' => $tmp_cols[1],
                                     'sp_history_text' => $tmp_cols[2] );
      }

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('sp')
    ));
      $this->tpl->parse_if($tplName, 'zoom1', $sp_image_src_large1, array(
        'c_sp_zoom1_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom2', $sp_image_src_large2, array(
        'c_sp_zoom2_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom3', $sp_image_src_large3, array(
        'c_sp_zoom3_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'image1', $sp_image_src1, array( 'c_sp_image_src1' => $sp_image_src1 ));
      $this->tpl->parse_if($tplName, 'image2', $sp_image_src2, array( 'c_sp_image_src2' => $sp_image_src2 ));
      $this->tpl->parse_if($tplName, 'image3', $sp_image_src3, array( 'c_sp_image_src3' => $sp_image_src3 ));
      $this->tpl->parse_loop($tplName, $history_items, 'history_items');
      $this->tpl->parse_vars($tplName, array_merge( $sp_image_titles, array (
        'c_sp_name' => parseOutput($post->readString('sp_name', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_text1' => parseOutput($post->readString('sp_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_sp_text2' => parseOutput($post->readString('sp_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_sp_text3' => parseOutput($post->readString('sp_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_sp_image_src1' => $sp_image_src1,
        'c_sp_image_src2' => $sp_image_src2,
        'c_sp_image_src3' => $sp_image_src3,
        'c_sp_shortdescription' => parseOutput($post->readString('sp_nick', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_nick' => parseOutput($post->readString('sp_nick', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_birthday' => parseOutput($post->readString('sp_birthday', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_height' => parseOutput($post->readString('sp_height', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_country' => parseOutput($post->readString('sp_country', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_number' => parseOutput($post->readString('sp_number', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_position' => $_LANG["sp_position"][$post->readInt('sp_position')],
        'c_sp_position_src' => (is_file("../img/ci_plpos".$post->readInt('sp_position').".png") ? "../img/ci_plpos".$post->readInt('sp_position').".png" : "../img/ci_plpos".$post->readInt('sp_position').".jpg"),
        'c_sp_hobbies' => parseOutput($post->readString('sp_hobbies', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_familystatus' => parseOutput($post->readString('sp_familystatus', Input::FILTER_CONTENT_TITLE),2),
        'c_sp_name_label' => $_LANG["sp_name_label"],
        'c_sp_text1_label' => $_LANG["c_sp_text1_label"],
        'c_sp_text2_label' => $_LANG["c_sp_text2_label"],
        'c_sp_text3_label' => $_LANG["c_sp_text3_label"],
        'c_sp_shortdescription_label' => $_LANG["sp_shortdescription_label"],
        'c_sp_nick_label' => $_LANG["sp_nick_label"],
        'c_sp_birthday_label' => $_LANG["sp_birthday_label"],
        'c_sp_height_label' => $_LANG["sp_height_label"],
        'c_sp_country_label' => $_LANG["sp_country_label"],
        'c_sp_number_label' => $_LANG["sp_number_label"],
        'c_sp_position_label' => $_LANG["sp_position_label"],
        'c_sp_hobbies_label' => $_LANG["sp_hobbies_label"],
        'c_sp_familystatus_label' => $_LANG["sp_familystatus_label"],
        'c_sp_history_label' => $_LANG["sp_history_label"],
        'c_sc_profile_label' => $_LANG["sp_profile_label"],
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $sp_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $sp_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,PText1,PText2,PText3,PImageTitles,PName,PNick,PBirthday,PHeight,PCountry,PNumber,PPosition,PHobbies,PFamilyStatus,PHistory,FK_TID FROM ".$this->table_prefix."contentitem_sp cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["PName"];
        $class_content[$row["CIID"]]["c_title2"] = "";
        $class_content[$row["CIID"]]["c_title3"] = "";
        $class_content[$row["CIID"]]["c_text1"] = $row["PText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["PText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["PText3"];
        $sp_image_titles = $this->explode_content_image_titles("sp",$row["PImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $sp_image_titles["sp_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $sp_image_titles["sp_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $sp_image_titles["sp_image3_title"];
        $class_content[$row["CIID"]]["c_sub"] = array();
        $class_content[$row["CIID"]]["c_sub"][0]["sp_name"] = $row["PName"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_nick"] = $row["PNick"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_birthday"] = $row["PBirthday"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_height"] = $row["PHeight"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_country"] = $row["PCountry"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_number"] = $row["PNumber"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_position"] = $row["PPosition"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_hobbies"] = $row["PHobbies"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_familystatus"] = $row["PFamilyStatus"];
        $class_content[$row["CIID"]]["c_sub"][0]["sp_history"] = $row["PHistory"];
      }
      $this->db->free_result($result);

      return $class_content;
    }

    protected function _getData()
    {
      // Create database entries.
      $this->_checkDataBase();

      foreach ($this->_contentElements as $type => $count) {
        for ($i = 1; $i <= $count; $i++) {
          $sqlArgs[] = $this->_getContentElementColumnName($type, $count, $i);
        }
      }

      $sql = ' SELECT ' . implode(', ', $this->_dataFields) . ', '
           . '        PShortDescription, PNick, PBirthday, PHeight, PCountry, PName, '
           . '        PNumber, PPosition, PHobbies, PFamilyStatus, PHistory, FK_TID '
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }
  }
