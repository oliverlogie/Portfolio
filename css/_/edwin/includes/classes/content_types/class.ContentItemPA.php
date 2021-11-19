<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-10-12 14:15:08 +0200 (Do, 12 Okt 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ContentItemPA extends ContentItem
  {
    protected $_configPrefix = 'pa';
    protected $_contentPrefix = 'pa';
    protected $_columnPrefix = 'P';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'PA';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function edit_content()
    {
      // Handle default content elements.
      parent::edit_content();

      $post = new Input(Input::SOURCE_POST);

      $attribute = $post->readArrayIntToInt('pa_attribute');
      $attribute = implode(',', $attribute);

      // Update the database.
      $sql = "UPDATE {$this->table_prefix}contentitem_pa "
           . "SET FK_AVID = '{$this->db->escape($attribute)}' "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
    }

    public function get_content($params = array())
    {
      global $_LANG, $_LANG2;

      $data = $this->_getData();

      $pa_attribute_arr = explode (",",$data["FK_AVID"]);

      // load attributes for current site
      $pa_attributes = array();
      $result = $this->db->query("SELECT AID,ATitle,AVID,AVTitle,AVImage,AImages from ".$this->table_prefix."module_attribute LEFT JOIN ".$this->table_prefix."module_attribute_global ON AID=FK_AID WHERE FK_SID=".$this->site_id." AND FK_CTID = 17 ORDER BY APosition ASC,AVPosition ASC");
      while ($row = $this->db->fetch_row($result)){
        $pa_attributes[$row["AID"]]["ATitle"] = $row["ATitle"];
        $pa_attributes[$row["AID"]][$row["AVID"]]["AVTitle"] = $row["AVTitle"];
        $tmp_img = '';
        if ($row["AVImage"]) {
          $tmp_img = explode(".",$row["AVImage"]);
          $tmp_img = $tmp_img[0]."-th.".$tmp_img[1];
        }
        $pa_attributes[$row["AID"]][$row["AVID"]]["AVImage"] = $tmp_img;
        $pa_attributes[$row["AID"]][$row["AVID"]]["AImages"] = $row['AImages'];
      }
      $this->db->free_result($result);

      // parse attribute types and attributes for output
      $tmp_attribute_values = array();
      $attribute_items = array();
      foreach ($pa_attributes as $aid => $avalues){
        $tmp_attribute_items = array();
        $tmp_attribute_values = array();

        foreach ($avalues as $avid => $avvalues){
          if (is_numeric($avid)){
            $tmp_attribute_checkbox = '<input type="checkbox" name="pa_attribute[]" value="'.$avid.'" class="pa_attribute_checkbox"';
            if (in_array($avid,$pa_attribute_arr)) $tmp_attribute_checkbox .= ' checked="checked"';
            $tmp_attribute_checkbox .= ' />';
            $tmp_attribute_values[] = array(
              'c_pa_attribute_image' => $avvalues['AImages'] ? sprintf($_LANG['c_pa_attribute_image_html'], "../" . $avvalues["AVImage"]) : '',
              'pa_attribute_title'   => $avvalues["AVTitle"],
              'pa_attribute_choose'  => $tmp_attribute_checkbox);
          }
        }

        $tplPath = 'content_types/ContentItem' . $this->_templateSuffix . '_part.tpl';
        $this->tpl->load_tpl('content_site_pa_part', $tplPath);
        $this->tpl->parse_loop('content_site_pa_part', $tmp_attribute_values, 'attribute_values');
        $attribute_items[] = array( 'pa_attribute_type_title' => $avalues["ATitle"],
                                    'pa_attribute_type_id' => $aid,
                                    'pa_attribute_values' => $this->tpl->parsereturn('content_site_pa_part', array ( )));
      }

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'attribute_items', $attribute_items);
      $this->tpl->parse_loop($tplName, $attribute_items, 'attribute_items');

      return parent::get_content(array_merge($params, array(
        'settings' => array( 'tpl' => $tplName )
      )));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){
      global $_LANG;

      $this->tpl->set_tpl_dir("../templates");
      $post = new Input(Input::SOURCE_POST);

      $pa_image_titles = $post->readImageTitles('pa_image_title');
      $pa_image_titles = $this->explode_content_image_titles("c_pa",$pa_image_titles);

      $pa_images = $this->_createPreviewImages(array(
        'PImage1' => 'pa_image1',
        'PImage2' => 'pa_image2',
        'PImage3' => 'pa_image3',
      ));
      $pa_image_src1 = $pa_images['pa_image1'];
      $pa_image_src2 = $pa_images['pa_image2'];
      $pa_image_src3 = $pa_images['pa_image3'];
      $pa_image1_large = $this->_hasLargeImage($pa_image_src1);
      $pa_image2_large = $this->_hasLargeImage($pa_image_src2);
      $pa_image3_large = $this->_hasLargeImage($pa_image_src3);

      $pa_attribute = "";
      if (isset($_POST["pa_attribute"])) $pa_attribute = implode(",",$_POST["pa_attribute"]);

      // load attributes for preview
      $pa_attributes = array();
      $result = $this->db->query("SELECT AID,ATitle,AVID,AVTitle,AVImage from ".$this->table_prefix."module_attribute LEFT JOIN ".$this->table_prefix."module_attribute_global ON AID=FK_AID WHERE AVID IN (".$pa_attribute.") ORDER BY APosition ASC,AVPosition ASC");
      while ($row = $this->db->fetch_row($result)){
        $pa_attributes[$row["AID"]]["ATitle"] = $row["ATitle"];
        $pa_attributes[$row["AID"]][$row["AVID"]]["AVTitle"] = $row["AVTitle"];
        $tmp_img = '';
        if ($row["AVImage"]) {
          $tmp_img = explode(".",$row["AVImage"]);
          $tmp_img = $tmp_img[0]."-th.".$tmp_img[1];
        }
        $pa_attributes[$row["AID"]][$row["AVID"]]["AVImage"] = $tmp_img;
      }
      $this->db->free_result($result);

      $url = $this->_navigation->getCurrentSite()->getUrl();
      // parse attribute types and attributes for output
      $tmp_attribute_values = array();
      $attribute_items = array();
      foreach ($pa_attributes as $aid => $avalues){
        $tmp_attribute_items = array();
        $tmp_attribute_values = array();

        foreach ($avalues as $avid => $avvalues){
         if (is_numeric($avid))
           $tmp_attribute_values[] = array( 'pa_attribute_image_src' => $url.$avvalues["AVImage"],
                                            'pa_attribute_title' => $avvalues["AVTitle"],
                                            'pa_attribute_link' => $url.$this->page_path.".attribute.".$avid,
                                            'pa_attribute_link_label' => $_LANG["pa_attribute_link_label"] );
        }

        $tplPath = 'content_types/ContentItem' . $this->_templateSuffix . '_part.tpl';
        $this->tpl->load_tpl('content_site_pa_part', $tplPath);
        $this->tpl->parse_loop('content_site_pa_part', $tmp_attribute_values, 'attribute_values');
        $attribute_items[] = array( 'pa_attribute_type_title' => $avalues["ATitle"],
                                    'pa_attribute_type_id' => $aid,
                                    'pa_attribute_type_box_label' => sprintf($_LANG["c_pa_attribute_type_label"],$avalues["ATitle"]),
                                    'pa_attribute_values' => $this->tpl->parsereturn('content_site_pa_part', array ( )));
      }

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('pa')
      ));
      $this->tpl->parse_if($tplName, 'zoom1', $pa_image1_large, array(
        'c_pa_zoom1_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom2', $pa_image2_large, array(
        'c_pa_zoom2_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom3', $pa_image3_large, array(
        'c_pa_zoom3_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'image1', $pa_image_src1, array( 'c_pa_image_src1' => $pa_image_src1 ));
      $this->tpl->parse_if($tplName, 'image2', $pa_image_src2, array( 'c_pa_image_src2' => $pa_image_src2 ));
      $this->tpl->parse_if($tplName, 'image3', $pa_image_src3, array( 'c_pa_image_src3' => $pa_image_src3 ));
      $this->tpl->parse_loop($tplName, $attribute_items, 'attribute_items');
      $this->tpl->parse_vars($tplName, array_merge( $pa_image_titles, array (
        'c_pa_title1' => parseOutput($post->readString('pa_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_pa_title2' => parseOutput($post->readString('pa_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_pa_title3' => parseOutput($post->readString('pa_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_pa_text1' => parseOutput($post->readString('pa_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_pa_text2' => parseOutput($post->readString('pa_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_pa_text3' => parseOutput($post->readString('pa_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_pa_image_src1' => $pa_image_src1,
        'c_pa_image_src2' => $pa_image_src2,
        'c_pa_image_src3' => $pa_image_src3,
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $pa_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $pa_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,PTitle1,PTitle2,PTitle3,PText1,PText2,PText3,PImageTitles FROM ".$this->table_prefix."contentitem_pa cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["PTitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["PTitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["PTitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["PText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["PText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["PText3"];
        $pa_image_titles = $this->explode_content_image_titles("pa",$row["PImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $pa_image_titles["pa_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $pa_image_titles["pa_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $pa_image_titles["pa_image3_title"];
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
           . '        FK_AVID '
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }
  }

