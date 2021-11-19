<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2017-11-10 15:48:18 +0100 (Fr, 10 Nov 2017) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */
class ContentItemRL extends ContentItem
{
  protected $_configPrefix = 'rl';
  protected $_contentPrefix = 'rl';
  protected $_columnPrefix = 'RL';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 3,
  );
  protected $_templateSuffix = 'RL';

  /**
   * Edit content of RL.
   *
   * @see ContentItem::edit_content()
   */
  public function edit_content()
  {
    // Handle default content elements.
    parent::edit_content();

    $post = new Input(Input::SOURCE_POST);
    $catId = $post->readInt('rl_category');
    $typeId = $post->readInt('rl_tpl_type');

    $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " SET FK_RCID = '{$this->db->escape($catId)}', "
         . "     RLTplType = '{$this->db->escape($typeId)}' "
         . " WHERE FK_CIID = {$this->page_id} ";
    $this->db->query($sql);
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    // Read all reseller categories
    $sql = ' SELECT RCID, RCName '
         . " FROM {$this->table_prefix}module_reseller_category ";
    $categories = $this->db->GetAssoc($sql);
    // Read content item data
    $row = $this->_getData();
    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_vars($tplName, array (
      'rl_tpl_type_options' => $this->_getTplOptions($_LANG['rl_tpl_types'], $row['RLTplType']),
      'rl_category_options' => $this->_getTplOptions($categories, $row['FK_RCID']),
    ));

    return parent::get_content(array_merge($params, array(
      'row'      => $row,
      'settings' => array( 'tpl' => $tplName ),
    )));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function preview()
  {
    $post = new Input(Input::SOURCE_POST);

    $rl_image_titles = $post->readImageTitles('rl_image_title');
    $rl_image_titles = $this->explode_content_image_titles('c_rl', $rl_image_titles);
    $type = $post->readInt('rl_tpl_type');
    $resellerData = $this->_getResellerData($post->readInt('rl_category'));
    $frontendLang = $this->_getFrontentLang();

    $rl_images = $this->_createPreviewImages(array(
      'RLImage1' => 'rl_image1',
      'RLImage2' => 'rl_image2',
      'RLImage3' => 'rl_image3',
    ));
    $rl_image_src1 = $rl_images['rl_image1'];
    $rl_image_src2 = $rl_images['rl_image2'];
    $rl_image_src3 = $rl_images['rl_image3'];
    $rl_image_src_large1 = $this->_hasLargeImage($rl_image_src1);
    $rl_image_src_large2 = $this->_hasLargeImage($rl_image_src2);
    $rl_image_src_large3 = $this->_hasLargeImage($rl_image_src3);
    $tplName = $this->_getStandardTemplateName();
    $this->tpl->set_tpl_dir("../templates");
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('rl')
    ));
    $this->tpl->parse_if($tplName, 'zoom1', $rl_image_src_large1, array(
      'c_rl_zoom1_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'zoom2', $rl_image_src_large2, array(
      'c_rl_zoom2_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'zoom3', $rl_image_src_large3, array(
      'c_rl_zoom3_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'image1', $rl_image_src1, array( 'c_rl_image_src1' => $rl_image_src1 ));
    $this->tpl->parse_if($tplName, 'image2', $rl_image_src2, array( 'c_rl_image_src2' => $rl_image_src2 ));
    $this->tpl->parse_if($tplName, 'image3', $rl_image_src3, array( 'c_rl_image_src3' => $rl_image_src3 ));
    $this->tpl->parse_vars($tplName, array_merge( $rl_image_titles, array (
      'c_rl_title1' => parseOutput($post->readString('rl_title1', Input::FILTER_CONTENT_TITLE),2),
      'c_rl_title2' => parseOutput($post->readString('rl_title2', Input::FILTER_CONTENT_TITLE),2),
      'c_rl_title3' => parseOutput($post->readString('rl_title3', Input::FILTER_CONTENT_TITLE),2),
      'c_rl_text1' => nl2br(parseOutput($post->readString('rl_text1', Input::FILTER_CONTENT_TEXT), 1)),
      'c_rl_text2' => nl2br(parseOutput($post->readString('rl_text2', Input::FILTER_CONTENT_TEXT), 1)),
      'c_rl_text3' => nl2br(parseOutput($post->readString('rl_text3', Input::FILTER_CONTENT_TEXT), 1)),
      'c_rl_image_src1' => $rl_image_src1,
      'c_rl_image_src2' => $rl_image_src2,
      'c_rl_image_src3' => $rl_image_src3,
      'c_rl_reseller_part' => $this->_getContentPart($type, $resellerData, $frontendLang),
      'c_surl' => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $rl_content = $this->tpl->parsereturn($tplName, $frontendLang);
    $this->tpl->set_tpl_dir("./templates");
    return $rl_content;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,RLTitle1,RLTitle2,RLTitle3,RLText1,RLText2,RLText3,RLImageTitles FROM ".$this->table_prefix."contentitem_rl cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)){
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["RLTitle1"];
      $class_content[$row["CIID"]]["c_title2"] = $row["RLTitle2"];
      $class_content[$row["CIID"]]["c_title3"] = $row["RLTitle3"];
      $class_content[$row["CIID"]]["c_text1"] = $row["RLText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["RLText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["RLText3"];
      $rl_image_titles = $this->explode_content_image_titles("rl",$row["RLImageTitles"]);
      $class_content[$row["CIID"]]["c_image_title1"] = $rl_image_titles["rl_image1_title"];
      $class_content[$row["CIID"]]["c_image_title2"] = $rl_image_titles["rl_image2_title"];
      $class_content[$row["CIID"]]["c_image_title3"] = $rl_image_titles["rl_image3_title"];
      $class_content[$row["CIID"]]["c_sub"] = array();
    }
    $this->db->free_result($result);

    return $class_content;
  }

  /**
   * Get content item's data.
   *
   * @see ContentItem::_getData()
   */
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
         . '        RLTplType, FK_RCID '
         .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
         .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
         . " FROM {$this->table_prefix}contentitem ci "
         . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
         . '      ON CIID = ci_sub.FK_CIID '
         . " WHERE CIID = $this->page_id ";
    return $this->db->GetRow($sql);
  }

  /**
   * Get content of "part" template.
   *
   * @param array $reseller
   *        Reseller data.
   * @param array $row
   *        Contentitem's data.
   * @return multitype:
   */
  private function _getContentPart($tplType, $reseller, $frontendLang)
  {
    $tplName = $this->_getStandardTemplateName().'_part';
    $tplPath = 'content_types/ContentItem' . $this->_templateSuffix . '_part'.$tplType.'.tpl';
    $this->tpl->load_tpl($tplName, $tplPath);
    $this->tpl->parse_if($tplName, 'rl_reseller', $reseller);
    $this->tpl->parse_loop($tplName, $reseller, 'rl_reseller_data');

    foreach ($reseller as $value)
    {
      $websites = array();
      if ($value['c_rl_reseller_websites'])
      {
        foreach (explode(" ", $value['c_rl_reseller_websites']) as $website) {
          $websites[] = array('c_rl_reseller_web' => $website);
        }
      }
      $emails = array();
      if ($value['c_rl_reseller_emails'])
      {
        foreach (explode(" ", $value['c_rl_reseller_emails']) as $email)
        {
          $email = sprintf($frontendLang['c_rl_email_link'], $email, $email);
          $emails[] = array('c_rl_reseller_email' => parseOutput($email, 1));
        }
      }
      $this->tpl->parse_loop($tplName, $websites, "c_rl_reseller_websites_{$value['c_rl_reseller_position']}");
      $this->tpl->parse_loop($tplName, $emails, "c_rl_reseller_emails_{$value['c_rl_reseller_position']}");
    }

    return $this->tpl->parsereturn($tplName, $frontendLang);
  }

  /**
   * Return reseller data of given category id.
   *
   * @param int $catId
   *        The category id.
   * @return array
   *         Contains reseller data
   */
  private function _getResellerData($catId)
  {
    global $_LANG;

    $reseller = array();
    $sql = " SELECT RName, RAddress, RPostalCode, RCity, RCountry, RCallNumber, RFax, REmail, RWeb, RNotes "
         . " FROM {$this->table_prefix}module_reseller "
         . " INNER JOIN {$this->table_prefix}module_reseller_assignation "
         . "    ON RID = FK_RID "
         . " INNER JOIN {$this->table_prefix}module_reseller_category_assignation rca "
         . "    ON RID = rca.FK_RID "
         . " WHERE rca.FK_RCID = {$catId}";
    $result = $this->db->query($sql);
    $position = 1;
    while ($row = $this->db->fetch_row($result))
    {
      $reseller [] = array(
        'c_rl_reseller_position' => $position ++,
        'c_rl_reseller_name' => parseOutput($row['RName']),
        'c_rl_reseller_address' => parseOutput($row['RAddress']),
        'c_rl_reseller_postal_code' => parseOutput($row['RPostalCode']),
        'c_rl_reseller_city' => parseOutput($row['RCity']),
        'c_rl_reseller_country' => parseOutput($row['RCountry']),
        'c_rl_reseller_call_number' => parseOutput($row['RCallNumber']),
        'c_rl_reseller_fax' => parseOutput($row['RFax']),
        'c_rl_reseller_emails' => $row['REmail'],
        'c_rl_reseller_websites' => parseOutput($row['RWeb']),
        'c_rl_reseller_notes' => parseOutput($row['RNotes'])
      );
    }

    return $reseller;
  }

  /**
   * Creates select options with search result template types.
   *
   * @param int $selectedId
   *        The option id to select.
   * @return string
   *         HTML options.
   */
  private function _getTplOptions($options, $selectedId)
  {
    global $_LANG;

    $typeOptions = '';
    foreach ($options as $id => $name)
    {
      $selected = '';
      if ($selectedId == $id) {
        $selected = 'selected="selected"';
      }
      $typeOptions .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
    }

    return $typeOptions;
  }
}