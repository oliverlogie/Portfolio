<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2018-02-26 11:15:36 +0100 (Mo, 26 Feb 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemQS extends ContentItem
{
  protected $_configPrefix = 'qs';
  protected $_contentPrefix = 'qs';
  protected $_columnPrefix = 'Q';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 3,
  );
  protected $_templateSuffix = 'QS';

  /**
   * Determines if content has changed and thus spidering is necessary.
   *
   * @return bool
   *        True if content was changed, false otherwise.
   */
  protected static function hasContentChanged()
  {
    // The main content has changed.
    if (parent::hasContentChanged()) {
      return true;
    }

    // A statement has changed.
    if (   isset($_POST['process_qs_statement'])
        || isset($_GET['deleteStatementID'])
    ) {
      return true;
    }

    // Nothing has changed.
    return false;
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
    if ($this->_subelements[0]->isProcessed()) {
      $this->_subelements[0]->edit_content();
    }
    else {
      parent::edit_content();
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $qs_subelements_content = $this->_subelements[0]->get_content();
    $qs_statement_items = $qs_subelements_content['content'];
    if ($qs_subelements_content['message']) {
      $this->setMessage($qs_subelements_content['message']);
    }

    $qs_hidden_fields = '<input type="hidden" name="site" value="'.$this->site_id.'" />'
                      . '<input type="hidden" name="page" value="'.$this->page_id.'" />'
                      . '<input type="hidden" name="action" value="content" />'
                      . '<input type="hidden" name="action2" value="" />'
                      . '<input type="hidden" name="statement" class="jq_statement" value="0" />'
                      . '<input type="hidden" name="scrollToAnchor" class="jq_scrollToAnchor" value="" />';

    $qs_scroll_to_anchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';

    if (!$qs_scroll_to_anchor && $this->_subelements[0]->hasStatementChanged()) {
      $qs_scroll_to_anchor = 'a_statements';
    }

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_vars($tplName, array(
      'qs_statements'       => $qs_statement_items,
      'qs_hidden_fields'    => $qs_hidden_fields,
      'qs_scroll_to_anchor' => $qs_scroll_to_anchor,
      'qs_main_content_changed' => parent::hasContentChanged(),
     ));

    return parent::get_content(array_merge($params, array(
      'settings' => array('tpl' => $tplName),
    )));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function preview(){

    $this->tpl->set_tpl_dir('../templates');

    $post = new Input(Input::SOURCE_POST);

    // texts
    $qs_title1 = parseOutput($post->readString('qs_title1', Input::FILTER_CONTENT_TITLE), 2);
    $qs_title2 = parseOutput($post->readString('qs_title2', Input::FILTER_CONTENT_TITLE), 2);
    $qs_title3 = parseOutput($post->readString('qs_title3', Input::FILTER_CONTENT_TITLE), 2);
    $qs_text1 = parseOutput($post->readString('qs_text1', Input::FILTER_CONTENT_TEXT), 1);
    $qs_text2 = parseOutput($post->readString('qs_text2', Input::FILTER_CONTENT_TEXT), 1);
    $qs_text3 = parseOutput($post->readString('qs_text3', Input::FILTER_CONTENT_TEXT), 1);

    // images
    $qs_image_titles = $post->readImageTitles('qs_image_title');
    $qs_image_titles = $this->explode_content_image_titles('c_qs', $qs_image_titles);
    $qs_images = $this->_createPreviewImages(array(
      'QImage1' => 'qs_image1',
      'QImage2' => 'qs_image2',
      'QImage3' => 'qs_image3',
    ));
    $qs_image_src1 = $qs_images['qs_image1'];
    $qs_image_src2 = $qs_images['qs_image2'];
    $qs_image_src3 = $qs_images['qs_image3'];
    $qs_image_large1 = $this->_hasLargeImage($qs_image_src1);
    $qs_image_large2 = $this->_hasLargeImage($qs_image_src2);
    $qs_image_large3 = $this->_hasLargeImage($qs_image_src3);

    $tplName = $this->_getStandardTemplateName();
    $tplPath = $this->_getTemplatePath();
    $this->tpl->load_tpl($tplName, $tplPath);
    $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('qs')
    ));
    $this->tpl->parse_if($tplName, 'zoom1', $qs_image_large1, array('c_qs_zoom1_link' => '#'));
    $this->tpl->parse_if($tplName, 'zoom2', $qs_image_large2, array('c_qs_zoom2_link' => '#'));
    $this->tpl->parse_if($tplName, 'zoom3', $qs_image_large3, array('c_qs_zoom3_link' => '#'));
    $this->tpl->parse_if($tplName, 'image1', $qs_image_src1, array('c_qs_image_src1' => $qs_image_src1));
    $this->tpl->parse_if($tplName, 'image2', $qs_image_src2, array('c_qs_image_src2' => $qs_image_src2));
    $this->tpl->parse_if($tplName, 'image3', $qs_image_src3, array('c_qs_image_src3' => $qs_image_src3));

    $this->tpl->parse_if($tplName, 'zoom', $qs_image_large1, array(
      'c_qs_zoom_link' => '#',
    ));
    $this->tpl->parse_loop($tplName, $this->previewGetStatements(), 'statement_items');
    $this->tpl->parse_vars($tplName, array_merge($qs_image_titles, array(
      'c_qs_title1' => $qs_title1,
      'c_qs_title2' => $qs_title2,
      'c_qs_title3' => $qs_title3,
      'c_qs_text1' => $qs_text1,
      'c_qs_text2' => $qs_text2,
      'c_qs_text3' => $qs_text3,
      'c_qs_image_src1' => $qs_image_src1,
      'c_qs_image_src2' => $qs_image_src2,
      'c_qs_image_src3' => $qs_image_src3,
      'c_surl' => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $qs_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
    $this->tpl->set_tpl_dir('./templates');
    return $qs_content;
  }

  /**
   * Reads all statements from the database and returns an array that can be used to parse the statement-loop inside the QS template.
   *
   * @return array
   *        contains, for each statement, an associative array with one element ("c_qs_statement")
   */
  private function previewGetStatements() {
    $statements = array();

    $position = 1;
    $sql = 'SELECT QSID, QSTitle, QSText, QSImage, QSImageTitles '
         . "FROM {$this->table_prefix}contentitem_qs_statement "
         . "WHERE FK_CIID = $this->page_id "
         . "AND QSDisabled = 0 "
         . 'AND ( '
         . "  COALESCE(QSTitle, '') != '' "
         . "  OR COALESCE(QSImage, '') != '' "
         . "  OR COALESCE(QSText, '') != '' "
         . ') '
         . 'ORDER BY QSPosition ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)){
      $largeImage = $this->_hasLargeImage($row['QSImage']);
      $imageTitles = $this->explode_content_image_titles('c_qs_statement', $row['QSImageTitles']);

      $tplPath = 'content_types/ContentItem' . $this->_templateSuffix. '_Statement.tpl';
      $this->tpl->load_tpl('content_site_qs_statement', $tplPath);
      $this->tpl->parse_if('content_site_qs_statement', 'zoom', $largeImage, array(
        'c_qs_statement_zoom_link' => '#'
      ));
      $this->tpl->parse_if('content_site_qs_statement', 'statement_image', $row['QSImage'], array(
        'c_qs_statement_image_src' => '../' . $row['QSImage'],
      ));
      $statements[] = array(
        'c_qs_statement' => $this->tpl->parsereturn('content_site_qs_statement', array_merge($imageTitles, array(
          'c_qs_statement_title' => parseOutput($row['QSTitle']),
          'c_qs_statement_text' => parseOutput($row['QSText'], 1),
          'c_qs_statement_id' => $row['QSID'],
          'c_qs_statement_position' => $position++,
      ))),
      );
    }
    $this->db->free_result($result);

    return $statements;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID, CIID, CIIdentifier, CTitle, QTitle1,QTitle2,QTitle3, QText1, QText2, QText3, QImageTitles FROM {$this->table_prefix}contentitem_qs ciqs LEFT JOIN {$this->table_prefix}contentitem ci ON ciqs.FK_CIID = ci.CIID ORDER BY ciqs.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)) {
      $class_content[$row['CIID']]['path'] = $row['CIIdentifier'];
      $class_content[$row['CIID']]['path_title'] = $row['CTitle'];
      $class_content[$row['CIID']]['type'] = $row['FK_CTID'];
      $class_content[$row['CIID']]['c_title1'] = $row['QTitle1'];
      $class_content[$row['CIID']]['c_title2'] = $row['QTitle2'];
      $class_content[$row['CIID']]['c_title3'] = $row['QTitle3'];
      $class_content[$row['CIID']]['c_text1'] = $row['QText1'];
      $class_content[$row['CIID']]['c_text2'] = $row['QText2'];
      $class_content[$row['CIID']]['c_text3'] = $row['QText3'];
      $qs_image_titles = $this->explode_content_image_titles('qs', $row['QImageTitles']);
      $class_content[$row['CIID']]['c_image_title1'] = $qs_image_titles['qs_image1_title'];
      $class_content[$row['CIID']]['c_image_title2'] = $qs_image_titles['qs_image2_title'];
      $class_content[$row['CIID']]['c_image_title3'] = $qs_image_titles['qs_image3_title'];
      $class_content[$row['CIID']]['c_sub'] = array();
      $result_sub = $this->db->query("SELECT QSTitle, QSText, QSImageTitles FROM {$this->table_prefix}contentitem_qs_statement WHERE FK_CIID = {$row['CIID']} ORDER BY QSPosition ASC");
      while ($row_sub = $this->db->fetch_row($result_sub)) {
        $qs_image_titles_sub = $this->explode_content_image_titles('qs',$row_sub['QSImageTitles']);
        $class_content[$row['CIID']]['c_sub'][] = array(
          'cs_title' => $row_sub['QSTitle'],
          'cs_text' => $row_sub['QSText'],
          'c_image_title1' => $qs_image_titles_sub['qs_image1_title'],
        );
      }
      $this->db->free_result($result_sub);
    }
    $this->db->free_result($result);

    return $class_content;
  }

  /**
   * Return all image titles.
   *
   * @param bool $subcontent [optional] [default : true]
   *        If subcontent is false, image titles from statements will not be
   *        retrieved.
   *
   * @return array
   *         An array containing all image titles stored for this content
   *         item (and subcontent)
   */
  public function getImageTitles($subcontent = true)
  {
    // Get the image titles for the QS contentitem itself.
    $titles = parent::getImageTitles();

    // Ensure that this part is not executed for ContentItemQS_Statements or other
    // subclasses in case the $subcontent parameter is true.
    if ($subcontent === true && $this instanceof ContentItemQS) {
      $tmpTitles = $this->_subelements[0]->getImageTitles(false);
      $titles = array_merge($titles, $tmpTitles);
    }

    return $titles;
  }

  public function getTexts($subcontent = true)
  {
    $texts = parent::getTexts();

    if ($subcontent)
    {
      $sql = "SELECT QSText "
           . "FROM {$this->table_prefix}contentitem_qs_statement "
           . "WHERE FK_CIID = $this->page_id "
           . "AND (COALESCE(QSText, '') != '') ";
      $texts = array_merge($texts, $this->db->GetCol($sql));
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

    $sql = 'SELECT QSTitle '
         . "FROM {$this->table_prefix}contentitem_qs_statement "
         . "WHERE FK_CIID = $this->page_id "
         . "AND (COALESCE(QSTitle, '') != '') ";
    $titles = array_merge($titles, $this->db->GetCol($sql));

    return $titles;
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[] = new ContentItemQS_Statements(
        $this->site_id, $this->page_id, $this->tpl, $this->db,
        $this->table_prefix, '', '', $this->_user, $this->session,
        $this->_navigation, $this);
  }

  public function getBrokenTextLinks($text = null)
  {
    if ($text)
    {
      return parent::getBrokenTextLinks($text);
    }

    $broken = parent::getBrokenTextLinks();

    $sql = 'SELECT QSID, QSText, QSPosition '
         . "FROM {$this->table_prefix}contentitem_qs_statement "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    while ($stmt = $this->db->fetch_row($result))
    {
      $bls = parent::getBrokenTextLinks($stmt['QSText']);
      // foreach broken link found modify the page link and add to broken links array
      foreach ($bls as $bl)
      {
        $bl['link'] .= "&amp;statement={$stmt['QSID']}&amp;scrollToAnchor=a_area{$stmt['QSPosition']}";
        $broken[] = $bl;
      }
    }
    $this->db->free_result($result);
    return $broken;
  }
}