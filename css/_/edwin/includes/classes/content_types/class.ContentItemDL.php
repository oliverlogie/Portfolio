<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2018-02-26 11:11:55 +0100 (Mo, 26 Feb 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemDL extends ContentItem
{
  protected $_configPrefix = 'dl';
  protected $_contentPrefix = 'dl';
  protected $_columnPrefix = 'DL';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 2,
    'Image' => 1,
  );
  protected $_templateSuffix = 'DL';

  /**
   * Determines if content has changed and thus spidering is necessary.
   *
   * @return bool
   *        True if content was changed, false otherwise.
   */
  protected static function hasContentChanged()
  {
    // The main content has changed.
    if (parent::hasContentChanged() ) {
      return true;
    }

    // An area has changed.
    if (   isset($_POST['process_dl_area'])
        || isset($_GET['deleteAreaID'])
    ) {
      return true;
    }

    // Nothing has changed.
    return false;
  }

  public function getTexts($subcontent = true)
  {
    $texts = parent::getTexts();
    // Determine the texts of all areas.
    if ($subcontent)
    {
      $sql = 'SELECT DAText '
           . "FROM {$this->table_prefix}contentitem_dl_area "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result)) {
        $texts[] = $row['DAText'];
      }
      $this->db->free_result($result);
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

    // Determine the titles of all areas.
    $sql = 'SELECT DATitle '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)) {
      $titles[] = $row['DATitle'];
    }
    $this->db->free_result($result);

    return $titles;

  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[] = new ContentItemDL_Areas($this->site_id,
        $this->page_id, $this->tpl, $this->db, $this->table_prefix, $this->_user,
        $this->session, $this->_navigation, $this);
  }

  public function getBrokenTextLinks($text = null)
  {
    if ($text)
    {
      return parent::getBrokenTextLinks($text);
    }

    $broken = parent::getBrokenTextLinks();

    $sql = 'SELECT DAID, DAText '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    while ($area = $this->db->fetch_row($result))
    {
      $bls = parent::getBrokenTextLinks($area['CBBText']);
      // foreach broken link found modify the page link and add to broken links array
      foreach ($bls as $bl)
      {
        $bl['link'] .= "&amp;area={$area['CBBID']}";
        $broken[] = $bl;
      }
    }
    $this->db->free_result($result);
    return $broken;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    $this->_subelements->delete_content();
    return parent::delete_content();
  }

  public function edit_content()
  {
    if ($this->_subelements->isProcessed()) {
      $this->_subelements->edit_content();
    }
    else {
      parent::edit_content();
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $areasContent = $this->_subelements[0]->get_content();
    $areasItems = $areasContent['content'];
    if ($areasContent['message']) {
      $this->setMessage($areasContent['message']);
    }
    $invalidLinks = $areasContent['invalidLinks'];
    if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['dl_message_invalid_links'], $invalidLinks)));
    }

    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="page" value="' . $this->page_id . '" />'
                  . '<input type="hidden" name="action" value="content" />'
                  . '<input type="hidden" name="action2" value="" />'
                  . '<input type="hidden" name="area" class="jq_area" value="0" />'
                  . '<input type="hidden" name="scrollToAnchor" class="jq_scrollToAnchor" value="" />';
    $scrollToAnchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';

    if (!$scrollToAnchor && $this->_subelements[0]->hasAreaChanged()) {
      $scrollToAnchor = 'a_areas';
    }

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_vars($tplName, array(
      'dl_areas' => $areasItems,
      'dl_hidden_fields' => $hiddenFields,
      'dl_autocomplete_download_url' => "index.php?action=response&site={$this->site_id}&page={$this->page_id}&request=DownloadAutoComplete&scope=global&downloadTypes=centralfile",
      'dl_scroll_to_anchor' => $scrollToAnchor,
      'dl_main_content_changed' => parent::hasContentChanged(),
    ));

    return parent::get_content(array_merge($params, array(
      'settings' => array( 'tpl' => $tplName ),
    )));
  }

  /**
   * Return all image titles.
   *
   * @param bool $subcontent [optional] [default : true]
   *        If subcontent is false, image titles from areas will not be
   *        retrieved.
   *
   * @return array
   *         An array containing all image titles stored for this content
   *         item (and subcontent)
   */
  public function getImageTitles($subcontent = true)
  {
    // Get the image titles for the DL contentitem itself.
    $titles = parent::getImageTitles();

    // Ensure that this part is not executed for ContentItemDL_Areas or other
    // subclasses in case the $subcontent parameter is true.
    if ($subcontent === true && $this instanceof ContentItemDL) {
      $tmpTitles = $this->_subelements[0]->getImageTitles(false);
      $titles = array_merge($titles, $tmpTitles);
    }

    return $titles;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function preview()
  {
    $post = new Input(Input::SOURCE_POST);

    // texts
    $title = parseOutput($post->readString('dl_title', Input::FILTER_CONTENT_TITLE), 2);
    $text1 = parseOutput($post->readString('dl_text1', Input::FILTER_CONTENT_TEXT), 1);
    $text2 = parseOutput($post->readString('dl_text2', Input::FILTER_CONTENT_TEXT), 1);

    // images
    $images = $this->_createPreviewImages(array(
      'DLImage' => 'dl_image',
    ));
    $imageTitles = $post->readImageTitles('dl_image_title');
    $imageTitles = $this->explode_content_image_titles('c_dl', $imageTitles);
    $imageSource = $images['dl_image'];
    $imageLarge = $this->_hasLargeImage($imageSource);

    $this->tpl->set_tpl_dir("../templates");
    $this->tpl->load_tpl('content_site_dl', $this->_getTemplatePath());
    $this->tpl->parse_if('content_site_dl', 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('dl')
    ));
    $this->tpl->parse_if('content_site_dl', 'zoom', $imageLarge, array(
      'c_dl_zoom_link' => '#',
    ));
    $this->tpl->parse_loop('content_site_dl', $this->_previewGetAreas(), 'area_items');
    $this->tpl->parse_vars('content_site_dl', array_merge($imageTitles, array(
      'c_dl_title' => $title,
      'c_dl_text1' => $text1,
      'c_dl_text2' => $text2,
      'c_dl_image_src' => $imageSource,
      'c_surl' => '../',
      'm_print_part' => $this->get_print_part(),
    )));
    $content = $this->tpl->parsereturn('content_site_dl', $this->_getFrontentLang());
    $this->tpl->set_tpl_dir('./templates');
    return $content;
  }

  /**
   * Reads all areas from the database and returns an array that can be used to parse the area-loop inside the DL template.
   *
   * @return array
   *        Contains, for each area, an associative array with one element ('c_dl_area')
   */
  private function _previewGetAreas()
  {
    $areas = array();
    $tplPath = 'content_types/ContentItem' . $this->_templateSuffix . '_Area.tpl';

    $position = 1;
    $sql = 'SELECT DAID, DATitle, DAText, DAImage, DAImageTitles, DAPosition '
         . "FROM {$this->table_prefix}contentitem_dl_area "
         . "WHERE FK_CIID = $this->page_id "
         . "AND DADisabled = 0 "
         . "AND COALESCE(DATitle, '') != '' "
         . 'ORDER BY DAPosition ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $largeImage = $this->_hasLargeImage($row['DAImage']);
      $imageTitles = $this->explode_content_image_titles('c_dl_area', $row['DAImageTitles']);

      $this->tpl->load_tpl('content_site_dl_area', $tplPath);
      $this->tpl->parse_if('content_site_dl_area', 'zoom', $largeImage, array(
        'c_dl_area_zoom_link' => '#',
      ));
      $this->tpl->parse_if('content_site_dl_area', 'area_image', $row['DAImage'], array(
        'c_dl_area_image_src' => '../' . $row['DAImage'],
      ));
      $this->tpl->parse_loop('content_site_dl_area', $this->_previewGetFiles($row['DAID']), 'area_file_items');
      $areas[] = array(
        'c_dl_area' => $this->tpl->parsereturn('content_site_dl_area', array_merge($imageTitles, array(
          'c_dl_area_id' => $row['DAID'],
          'c_dl_area_position' => $position++,
          'c_dl_area_real_position' => $row['DAPosition'],
          'c_dl_area_title' => parseOutput($row['DATitle']),
          'c_dl_area_text' => parseOutput($row['DAText'], 1),
      ))),
      );
    }
    $this->db->free_result($result);

    return $areas;
  }

  /**
   * Reads all files inside an area from the database and returns an array that can be used to parse the file-loop inside the DL-Area template.
   *
   * @param integer $daid
   *        the ID of the DL-Area for which to get the files
   * @return array
   *        contains, for each file, an associative array
   */
  private function _previewGetFiles($daid)
  {
    global $_LANG;

    $files = array();
    $configNewDays = ConfigHelper::get('dl_area_file_new_days');
    $configUpdatedDays = ConfigHelper::get('dl_area_file_updated_days');
    $configThreshold = ConfigHelper::get('dl_area_file_size_display_threshold');
    $rootPath = root_url();
    $position = 1;
    $sql = 'SELECT DFID, DFTitle, DFPosition, CFTitle, '
         . '       COALESCE(DFFile, CFFile) AS File, '
         . '       COALESCE(CFCreated, DFCreated) AS Created, '
         . '       COALESCE(CFModified, DFModified) AS Modified '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "LEFT JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
         . "WHERE FK_DAID = $daid "
         . 'AND ( '
         . '  DFFile IS NOT NULL OR '
         . '  CFFile IS NOT NULL '
         . ') '
         . 'ORDER BY DFPosition ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      // title
      $title = coalesce($row['DFTitle'], $row['CFTitle']);
      $titleOnly = $title;

      // status
      $status = 'normal';
      $created = strtotime($row['Created']);
      $ageDays = (time() - $created) / 86400;
      $modified = null;
      if ($ageDays <= $configNewDays) {
        $status = 'new';
      } else if ($row['Modified']) {
        $modified = strtotime($row['Modified']);
        $ageDays = (time() - $modified) / 86400;
        if ($ageDays <= $configUpdatedDays) {
          $status = 'updated';
        }
      }

      // extension
      $pathInfo = pathinfo('../' . $row['File']);
      $extension = mb_strtolower($pathInfo['extension']);

      // size
      $sizeOnly = '';
      $size = filesize('../' . $row['File']);
      if ($size >= parsePhpIniSize($configThreshold)) {
        $title .= ' (' . formatFileSize($size) . ')';
        $sizeOnly = formatFileSize($size);
      }

      $files[] = array(
        'c_dl_area_file_id' => $row['DFID'],
        'c_dl_area_file_position' => $position++,
        'c_dl_area_file_real_position' => $row['DFPosition'],
        'c_dl_area_file_title' => parseOutput($title),
        'c_dl_area_file_link' => $rootPath . $row['File'],
        'c_dl_area_file_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), $this->getConfigPrefix()), coalesce($modified, $created)),
        'c_dl_area_file_status' => $status,
        'c_dl_area_file_status_label' => $_LANG["dl_area_file_status_{$status}_label"],
        'c_dl_area_file_extension' => $extension,
        'c_dl_area_file_title_only' => parseOutput($titleOnly),
        'c_dl_area_file_size' => $sizeOnly,
      );
    }
    $this->db->free_result($result);

    return $files;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,DLTitle,DLText1,DLText2,DLImageTitles FROM ".$this->table_prefix."contentitem_dl cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)){
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["DLTitle"];
      $class_content[$row["CIID"]]["c_title2"] = "";
      $class_content[$row["CIID"]]["c_title3"] = "";
      $class_content[$row["CIID"]]["c_text1"] = $row["DLText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["DLText2"];
      $class_content[$row["CIID"]]["c_text3"] = "";
      $dl_image_titles = $this->explode_content_image_titles("dl",$row["DLImageTitles"]);
      $class_content[$row["CIID"]]["c_image_title1"] = $dl_image_titles["dl_image1_title"];
      $class_content[$row["CIID"]]["c_image_title2"] = "";
      $class_content[$row["CIID"]]["c_image_title3"] = "";
      $class_content[$row["CIID"]]["c_sub"] = array();
      $result_sub = $this->db->query("SELECT DATitle,DAText FROM ".$this->table_prefix."contentitem_dl_area WHERE FK_CIID=".$row["CIID"]." ORDER BY DAID ASC");
      while ($row_sub = $this->db->fetch_row($result_sub)){
        $class_content[$row["CIID"]]["c_sub"][] = array(
          "cs_title" => $row_sub["DATitle"],
          "cs_text" => $row_sub["DAText"],
        );
      }
      $this->db->free_result($result_sub);

    }
    $this->db->free_result($result);

    return $class_content;
  }
}

