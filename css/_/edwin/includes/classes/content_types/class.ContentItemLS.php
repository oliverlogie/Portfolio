<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-10-10 10:48:41 +0200 (Di, 10 Okt 2017) $
   * $LastChangedBy: jua $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ContentItemLS extends ContentItem
  {
    protected $_configPrefix = 'ls';
    protected $_contentPrefix = 'ls';
    protected $_columnPrefix = 'S';
    protected $_contentElements = array(
      'Title' => 2,
      'Text' => 2,
      'Image' => 2,
    );
    protected $_templateSuffix = 'LS';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function edit_content()
    {
      // Handle default content elements.
      parent::edit_content();

      $post = new Input(Input::SOURCE_POST);

      $league = $post->readInt('ls_league');
      $year = $post->readInt('ls_year');

      // Update the database.
      $sql = "UPDATE {$this->table_prefix}contentitem_ls "
           . "SET FK_LID = $league, "
           . "    FK_YID = $year "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
    }

    public function get_content($params = array())
    {
      global $_LANG, $_LANG2;

      $row = $this->_getData();

      $ls_year = $row["FK_YID"];
      $ls_league = $row["FK_LID"];

      // load leagues
      $tmp_league = '<select name="ls_league" class="ls_league form-control">';
      $result = $this->db->query("SELECT LID,LName,LShortName from ".$this->table_prefix."module_leaguemanager_league ORDER BY LID ASC");
      while ($row = $this->db->fetch_row($result)){
        $tmp_league .= '<option value="'.$row["LID"].'"';
        if ($ls_league == $row["LID"]) $tmp_league .= ' selected="selected"';
        $tmp_league .= '>'.parseOutput($row["LName"]).'</option>';
      }
      $ls_league = $tmp_league."</select>";
      $this->db->free_result($result);

      // load years
      $tmp_year = '<select name="ls_year" class="ls_year form-control">';
      $result = $this->db->query("SELECT YID,YName,YStartDate,YEndDate from ".$this->table_prefix."module_leaguemanager_year ORDER BY YID ASC");
      while ($row = $this->db->fetch_row($result)){
        $tmp_year .= '<option value="'.$row["YID"].'"';
        if ($ls_year == $row["YID"] || (!$ls_year && date("Y-m-d") >= $row["YStartDate"] && date("Y-m-d") <= $row["YEndDate"])) $tmp_year .= ' selected="selected"';
        $tmp_year .= '>'.parseOutput($row["YName"]).'</option>';
      }
      $ls_year = $tmp_year."</select>";
      $this->db->free_result($result);

      $this->tpl->load_tpl('content_site_ls', $this->_getTemplatePath());
      $this->tpl->parse_vars('content_site_ls', array (
        'ls_league' => $ls_league,
        'ls_league_label' => $_LANG["ls_league_label"],
        'ls_year' => $ls_year,
        'ls_year_label' => $_LANG["ls_year_label"]
      ));

      $settings = array(
        'no_preview' => true,
        'tpl' => 'content_site_ls',
      );

      return parent::get_content(array_merge($params, array(
        'settings' => $settings,
      )));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){}

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,STitle1,STitle2,SText1,SText2,SImageTitles FROM ".$this->table_prefix."contentitem_ls cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["STitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["STitle2"];
        $class_content[$row["CIID"]]["c_title3"] = "";
        $class_content[$row["CIID"]]["c_text1"] = $row["SText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["SText2"];
        $class_content[$row["CIID"]]["c_text3"] = "";
        $ls_image_titles = $this->explode_content_image_titles("ls",$row["SImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $ls_image_titles["ls_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $ls_image_titles["ls_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = "";
        $class_content[$row["CIID"]]["c_sub"] = array();
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
           . '        FK_LID, FK_YID '
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }
  }

