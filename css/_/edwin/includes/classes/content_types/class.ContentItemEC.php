<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2016-11-03 13:40:58 +0100 (Do, 03 Nov 2016) $
   * $LastChangedBy: jua $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  class ContentItemEC extends ContentItem
  {
    protected $_configPrefix = 'ec';
    protected $_contentPrefix = 'ec';
    protected $_columnPrefix = 'EC';
    protected $_contentElements = array(
      'Title' => 1,
      'Text' => 3,
      'Image' => 1,
    );
    protected $_contentImageTitles = false;
    protected $_templateSuffix = 'EC';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function edit_content()
    {
      // Handle default content elements.
      parent::edit_content();

      $post = new Input(Input::SOURCE_POST);

      $recipient = $post->readString('ec_recipient', Input::FILTER_PLAIN);
      $settingAbcLinks = ($post->exists('ec_setting_abc_links')) ? 1 : 0;
      $settingLocationAddress = $post->readInt('ec_setting_location_address', 1);

      // Update the database.
      $sql = "UPDATE {$this->table_prefix}contentitem_ec "
           . "SET ECRecipient = '{$this->db->escape($recipient)}', "
           . "    ECSettingABCLinks = '{$this->db->escape($settingAbcLinks)}', "
           . "    ECSettingLocationAddress = '{$this->db->escape($settingLocationAddress)}', "
           . "    FK_ETID = '{$this->db->escape($post->readInt('ec_type_options'))}', "
           . "    FK_EDID = '{$this->db->escape($post->readInt('ec_department_options'))}' "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
    }

    public function get_content($params = array())
    {
      global $_LANG, $_LANG2;

      $row = $this->_getData();

      $ec_recipient = $row['ECRecipient'];
      $settingAbcLinks = $row['ECSettingABCLinks'];
      $settingLocationAddress = $row['ECSettingLocationAddress'];
      $settingLocationAddressOptions = array(1 => 'hidden', 2 => 'once', 3 => 'multiple');
      foreach ($settingLocationAddressOptions as $key => $val) {
        $checked = ($key == $settingLocationAddress) ? 'checked="checked"' : '';
        $settingLocationAddressChecked["ec_setting_location_address_{$val}_checked"] = $checked;
      }

      $attribute = new Attribute($this->db, $this->table_prefix);
      $departments = $attribute->readAttributesByAGlobalIdentifier(AttributeGlobal::ID_EMPLOYEE_DEPARTMENT);
      $locations = $attribute->readAttributesByAGlobalIdentifier(AttributeGlobal::ID_EMPLOYEE_LOCATION);

      $departmentsArray[0] = $_LANG['ec_choose_department'];
      foreach ($departments as $department) {
        $departmentsArray[$department->id] = parseOutput($department->title);
      }
      $locationsArray[0] = $_LANG['ec_choose_location'];
      foreach ($locations as $location) {
        $locationsArray[$location->id] = parseOutput($location->title);
      }

      $this->tpl->load_tpl('content_site_ec', 'content_types/ContentItemEC.tpl');
      $this->tpl->parse_vars('content_site_ec', array_merge($settingLocationAddressChecked, array (
        'ec_department_options'        => AbstractForm::selectOptions($departmentsArray, $row['FK_EDID']),
        'ec_location_options'          => AbstractForm::selectOptions($locationsArray, $row['FK_ETID']),
        'ec_recipient'                 => $ec_recipient,
        'ec_setting_abc_links_checked' => $settingAbcLinks ? 'checked="checked"' : '',
      )));

      return parent::get_content(array_merge($params, array(
        'row'      => $row,
        'settings' => array( 'tpl' => 'content_site_ec' ),
      )));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){
      $post = new Input(Input::SOURCE_POST);

      $image_titles = array();
      $ec_images = $this->_createPreviewImages(array(
        'ECImage' => 'ec_image',
      ));
      $image_src1 = $ec_images['ec_image'];
      $image_src_large1 = $this->_hasLargeImage($image_src1);

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('ec')
      ));
      $this->tpl->parse_if($tplName, 'zoom', $image_src_large1, array(
        'c_ec_zoom_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'image', $image_src1, array( 'c_ec_image_src' => $image_src1 ));
      $this->tpl->parse_vars($tplName, array_merge( $image_titles, array (
        'c_ec_title' => parseOutput($post->readString('ec_title', Input::FILTER_CONTENT_TITLE),2),
        'c_ec_title1' => parseOutput($post->readString('ec_title', Input::FILTER_CONTENT_TITLE)),
        'c_ec_text1' => parseOutput($post->readString('ec_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_ec_text2' => parseOutput($post->readString('ec_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_ec_text3' => parseOutput($post->readString('ec_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_ec_image_src' => $image_src1,
        'c_ec_filter_title' => '',
        'c_ec_library_items' => '',
        'c_ec_search_text' => '',
        'c_ec_list' => '',
        'c_ec_btn_reset_url' => '#',
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $content;
    }
    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content(){

      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,ECTitle,ECText1,ECText2,ECText3 FROM ".$this->table_prefix."contentitem_ec cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["ECTitle"];
        $class_content[$row["CIID"]]["c_title2"] = "";
        $class_content[$row["CIID"]]["c_title3"] = "";
        $class_content[$row["CIID"]]["c_text1"] = $row["ECText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["ECText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["ECText3"];
        $class_content[$row["CIID"]]["c_image_title1"] = "";
        $class_content[$row["CIID"]]["c_image_title2"] = "";
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
           . '        ECRecipient, ECSettingABCLinks, ECSettingLocationAddress, FK_ETID, FK_EDID '
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }
  }

