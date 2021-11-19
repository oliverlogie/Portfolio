<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2018-02-22 16:13:41 +0100 (Do, 22 Feb 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ContentItemQP extends ContentItem
{
  protected $_configPrefix = 'qp';
  protected $_contentPrefix = 'qp';
  protected $_columnPrefix = 'QP';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 10,
  );
  protected $_templateSuffix = 'QP';

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
    if (   isset($_POST['process_qp_statement'])
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

    $statementsContent = $this->_subelements[0]->get_content();
    $statementItems = $statementsContent['content'];
    if ($statementsContent['message']) {
      $this->setMessage($statementsContent['message']);
    }

    $hiddenFields = '<input type="hidden" name="site" value="'.$this->site_id.'" />'
                      . '<input type="hidden" name="page" value="'.$this->page_id.'" />'
                      . '<input type="hidden" name="action" value="content" />'
                      . '<input type="hidden" name="action2" value="" />'
                      . '<input type="hidden" name="statement" class="jq_statement" value="0" />'
                      . '<input type="hidden" name="scrollToAnchor" class="jq_scrollToAnchor" value="" />';

    $scrollToAnchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';

    if (!$scrollToAnchor && $this->_subelements[0]->hasStatementChanged()) {
      $scrollToAnchor = 'a_statements';
    }

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_vars($tplName, array(
      'qp_statements'       => $statementItems,
      'qp_hidden_fields'    => $hiddenFields,
      'qp_scroll_to_anchor' => $scrollToAnchor,
      'qp_main_content_changed' => parent::hasContentChanged(),
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
    $title1 = parseOutput($post->readString('qp_title1', Input::FILTER_CONTENT_TITLE), 2);
    $title2 = parseOutput($post->readString('qp_title2', Input::FILTER_CONTENT_TITLE), 2);
    $title3 = parseOutput($post->readString('qp_title3', Input::FILTER_CONTENT_TITLE), 2);
    $text1 = parseOutput($post->readString('qp_text1', Input::FILTER_CONTENT_TEXT), 1);
    $text2 = parseOutput($post->readString('qp_text2', Input::FILTER_CONTENT_TEXT), 1);
    $text3 = parseOutput($post->readString('qp_text3', Input::FILTER_CONTENT_TEXT), 1);

    // images
    $imageTitles = $post->readImageTitles('qp_image_title');
    $imageTitles = $this->explode_content_image_titles('c_qp', $imageTitles);
    $images = $this->_createPreviewImages(array(
      'QPImage1'  => 'qp_image1',
      'QPImage2'  => 'qp_image2',
      'QPImage3'  => 'qp_image3',
      'QPImage4'  => 'qp_image4',
      'QPImage5'  => 'qp_image5',
      'QPImage6'  => 'qp_image6',
      'QPImage7'  => 'qp_image7',
      'QPImage8'  => 'qp_image8',
      'QPImage9'  => 'qp_image9',
      'QPImage10' => 'qp_image10',
    ));
    $imageSrc1 = $images['qp_image1'];
    $imageSrc2 = $images['qp_image2'];
    $imageSrc3 = $images['qp_image3'];
    $imageSrc4 = $images['qp_image4'];
    $imageSrc5 = $images['qp_image5'];
    $imageSrc6 = $images['qp_image6'];
    $imageSrc7 = $images['qp_image7'];
    $imageSrc8 = $images['qp_image8'];
    $imageSrc9 = $images['qp_image9'];
    $imageSrc10 = $images['qp_image10'];
    $imageLarge1 = $this->_hasLargeImage($imageSrc1);
    $imageLarge2 = $this->_hasLargeImage($imageSrc2);
    $imageLarge3 = $this->_hasLargeImage($imageSrc3);
    $imageLarge4 = $this->_hasLargeImage($imageSrc4);
    $imageLarge5 = $this->_hasLargeImage($imageSrc5);
    $imageLarge6 = $this->_hasLargeImage($imageSrc6);
    $imageLarge7 = $this->_hasLargeImage($imageSrc7);
    $imageLarge8 = $this->_hasLargeImage($imageSrc8);
    $imageLarge9 = $this->_hasLargeImage($imageSrc9);
    $imageLarge10 = $this->_hasLargeImage($imageSrc10);

    $statements = $this->previewGetStatements();
    $statementItems = $statements['statements'];
    $statementAnchors = $statements['anchors'];

    $this->tpl->load_tpl('content_site_qp', $this->_getTemplatePath());
    $this->tpl->parse_if('content_site_qp', 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('qp')
    ));
    $this->tpl->parse_if('content_site_qp', 'zoom1', $imageLarge1, array('c_qp_zoom1_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom2', $imageLarge2, array('c_qp_zoom2_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom3', $imageLarge3, array('c_qp_zoom3_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom4', $imageLarge1, array('c_qp_zoom4_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom5', $imageLarge2, array('c_qp_zoom5_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom6', $imageLarge3, array('c_qp_zoom6_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom7', $imageLarge1, array('c_qp_zoom7_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom8', $imageLarge2, array('c_qp_zoom8_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom9', $imageLarge3, array('c_qp_zoom9_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'zoom10', $imageLarge1, array('c_qp_zoom10_link' => '#'));
    $this->tpl->parse_if('content_site_qp', 'image1', $imageSrc1, array('c_qp_image_src1' => $imageSrc1));
    $this->tpl->parse_if('content_site_qp', 'image2', $imageSrc2, array('c_qp_image_src2' => $imageSrc2));
    $this->tpl->parse_if('content_site_qp', 'image3', $imageSrc3, array('c_qp_image_src3' => $imageSrc3));
    $this->tpl->parse_if('content_site_qp', 'image4', $imageSrc4, array('c_qp_image_src4' => $imageSrc4));
    $this->tpl->parse_if('content_site_qp', 'image5', $imageSrc5, array('c_qp_image_src5' => $imageSrc5));
    $this->tpl->parse_if('content_site_qp', 'image6', $imageSrc6, array('c_qp_image_src6' => $imageSrc6));
    $this->tpl->parse_if('content_site_qp', 'image7', $imageSrc7, array('c_qp_image_src7' => $imageSrc7));
    $this->tpl->parse_if('content_site_qp', 'image8', $imageSrc8, array('c_qp_image_src8' => $imageSrc8));
    $this->tpl->parse_if('content_site_qp', 'image9', $imageSrc9, array('c_qp_image_src9' => $imageSrc9));
    $this->tpl->parse_if('content_site_qp', 'image10', $imageSrc10, array('c_qp_image_src10' => $imageSrc10));
    $this->tpl->parse_loop('content_site_qp', $statementItems, 'statement_items');
    $this->tpl->parse_loop('content_site_qp', $statementAnchors, 'statement_anchors');
    foreach ($statementAnchors as $anchor)
    {
      $this->tpl->parse_if('content_site_qp', 'statement_zoom1', $anchor['c_qp_statement_large_image_src1'], array(
        'c_qp_statement_zoom_link1' => $anchor['c_qp_statement_large_image_src1'],
      ));
      $this->tpl->parse_if('content_site_qp', 'statement_image1', $anchor['c_qp_statement_image1'], array(
        'c_qp_statement_image_src1' => '../' .  $anchor['c_qp_statement_image1'],
      ));
    }
    $this->tpl->parse_vars('content_site_qp', array_merge($imageTitles, array(
      'c_qp_title1' => $title1,
      'c_qp_title2' => $title2,
      'c_qp_title3' => $title3,
      'c_qp_text1' => $text1,
      'c_qp_text2' => $text2,
      'c_qp_text3' => $text3,
      'c_qp_image_src1' => $imageSrc1,
      'c_qp_image_src2' => $imageSrc2,
      'c_qp_image_src3' => $imageSrc3,
      'c_qp_image_src4' => $imageSrc4,
      'c_qp_image_src5' => $imageSrc5,
      'c_qp_image_src6' => $imageSrc6,
      'c_qp_image_src7' => $imageSrc7,
      'c_qp_image_src8' => $imageSrc8,
      'c_qp_image_src9' => $imageSrc9,
      'c_qp_image_src10' => $imageSrc10,
      'c_surl' => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $qp_content = $this->tpl->parsereturn('content_site_qp', $this->_getFrontentLang());
    $this->tpl->set_tpl_dir('./templates');

    return $qp_content;
  }

  /**
   * Reads all statements from the database and returns an array that can be used to parse the statement-loop inside the QP template.
   *
   * @return array
   *        contains, for each statement, an associative array with one element ("c_qp_statement")
   */
  private function previewGetStatements()
  {
    $statements = array('statements' => array(), 'anchors' => array());
    $tplPath = 'content_types/ContentItem' . $this->_templateSuffix . '_Statement.tpl';

    $position = 1;
    $sql = 'SELECT QPSID, QPSTitle1, QPSTitle2, QPSTitle3, QPSTitle4, '
         . '        QPSText1, QPSText2, QPSText3, QPSText4, '
         . '        QPSImage1, QPSImage2, QPSImage3, QPSImage4, '
         . '        QPSImage5, QPSImage6, QPSImage7, QPSImage8, '
         . '        QPSImage9, QPSImage10, QPSImage11, '
         . '        QPSImageTitles '
         . "FROM {$this->table_prefix}contentitem_qp_statement "
         . "WHERE FK_CIID = $this->page_id "
         . "AND QPSDisabled = 0 "
         . 'AND ( ' // we need title1, image1 or text1 for the navigation
         . "  COALESCE(QPSTitle1, '') != '' "
         . "  OR COALESCE(QPSImage1, '') != '' "
         . "  OR COALESCE(QPSText1, '') != '' "
         . ') '
         . 'AND ( ' // and we need some content
         . "  COALESCE(QPSTitle2, QPSTitle3, QPSTitle4, '') != '' "
         . "  OR COALESCE(QPSImage2, QPSImage3, QPSImage4, '') != '' "
         . "  OR COALESCE(QPSText2, QPSText3, QPSText4, '') != '' "
         . ') '
         . 'ORDER BY QPSPosition ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result))
    {
      $largeImage1 = $this->_hasLargeImage($row['QPSImage1']);
      $largeImage2 = $this->_hasLargeImage($row['QPSImage2']);
      $largeImage3 = $this->_hasLargeImage($row['QPSImage3']);
      $largeImage4 = $this->_hasLargeImage($row['QPSImage4']);
      $largeImage5 = $this->_hasLargeImage($row['QPSImage5']);
      $largeImage6 = $this->_hasLargeImage($row['QPSImage6']);
      $largeImage7 = $this->_hasLargeImage($row['QPSImage7']);
      $largeImage8 = $this->_hasLargeImage($row['QPSImage8']);
      $largeImage9 = $this->_hasLargeImage($row['QPSImage9']);
      $largeImage10 = $this->_hasLargeImage($row['QPSImage10']);
      $largeImage11 = $this->_hasLargeImage($row['QPSImage11']);
      $imageTitles = $this->explode_content_image_titles('c_qp_statement', $row['QPSImageTitles']);

      $this->tpl->load_tpl('content_site_qp_statement', $tplPath);
      $this->tpl->parse_if('content_site_qp_statement', 'zoom1', $largeImage1, array(
        'c_qp_statement_zoom_link1' => '#',
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image1', $row['QPSImage1'], array(
        'c_qp_statement_image_src1' => '../' .  $row['QPSImage1'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'zoom2', $largeImage2, array(
        'c_qp_statement_zoom_link2' => '#',
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image2', $row['QPSImage2'], array(
        'c_qp_statement_image_src2' => '../' .  $row['QPSImage2'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'zoom3', $largeImage3, array(
        'c_qp_statement_zoom_link3' => '#',
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image3', $row['QPSImage3'], array(
        'c_qp_statement_image_src3' => '../' .  $row['QPSImage3'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'zoom4', $largeImage4, array(
        'c_qp_statement_zoom_link4' => '#',
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image4', $row['QPSImage4'], array(
        'c_qp_statement_image_src4' => '../' .  $row['QPSImage4'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image5', $row['QPSImage5'], array(
        'c_qp_statement_image_src5' => '../' .  $row['QPSImage5'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image6', $row['QPSImage6'], array(
        'c_qp_statement_image_src6' => '../' .  $row['QPSImage6'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image7', $row['QPSImage7'], array(
        'c_qp_statement_image_src7' => '../' .  $row['QPSImage7'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image8', $row['QPSImage8'], array(
        'c_qp_statement_image_src8' => '../' .  $row['QPSImage8'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image9', $row['QPSImage9'], array(
        'c_qp_statement_image_src9' => '../' .  $row['QPSImage9'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image10', $row['QPSImage10'], array(
        'c_qp_statement_image_src10' => '../' .  $row['QPSImage10'],
      ));
      $this->tpl->parse_if('content_site_qp_statement', 'statement_image11', $row['QPSImage11'], array(
        'c_qp_statement_image_src11' => '../' .  $row['QPSImage11'],
      ));

      $statements['statements'][] = array(
        'c_qp_statement' => $this->tpl->parsereturn('content_site_qp_statement', array_merge($imageTitles, array(
          'c_qp_statement_title1' => parseOutput($row['QPSTitle1'], 2),
          'c_qp_statement_title2' => parseOutput($row['QPSTitle2'], 2),
          'c_qp_statement_title3' => parseOutput($row['QPSTitle3'], 2),
          'c_qp_statement_title4' => parseOutput($row['QPSTitle4'], 2),
          'c_qp_statement_text1' => parseOutput($row['QPSText1'], 1),
          'c_qp_statement_text2' => parseOutput($row['QPSText2'], 1),
          'c_qp_statement_text3' => parseOutput($row['QPSText3'], 1),
          'c_qp_statement_text4' => parseOutput($row['QPSText4'], 1),
          'c_qp_statement_id' => $row['QPSID'],
          'c_qp_statement_position' => $position,
        ))),
      );

      $statements['anchors'][] = array_merge($imageTitles, array(
        'c_qp_statement_title1' => parseOutput($row['QPSTitle1'], 2),
        'c_qp_statement_text1' => parseOutput($row['QPSText1'], 1),
        'c_qp_statement_image1' => $row['QPSImage1'],
        'c_qp_statement_large_image_src1' => $largeImage1,
        'c_qp_statement_id' => $row['QPSID'],
        'c_qp_statement_position' => $position,
      ));

      $position++;
    }
    $this->db->free_result($result);

    return $statements;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $classContent = array();
    $sql = " SELECT FK_CTID, CIID, CIIdentifier, CTitle, "
         . "        {$this->_columnPrefix}Title1, {$this->_columnPrefix}Title2, {$this->_columnPrefix}Title3, "
         . "        {$this->_columnPrefix}Text1, {$this->_columnPrefix}Text2, {$this->_columnPrefix}Text3, "
         . "        {$this->_columnPrefix}ImageTitles "
         . " FROM {$this->table_prefix}contentitem_qp ciqp "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . '   ON ciqp.FK_CIID = ci.CIID '
         . ' ORDER BY ciqp.FK_CIID ASC ';
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result))
    {
      $classContent[$row['CIID']]['path'] = $row['CIIdentifier'];
      $classContent[$row['CIID']]['path_title'] = $row['CTitle'];
      $classContent[$row['CIID']]['type'] = $row['FK_CTID'];
      $classContent[$row['CIID']]['c_title1'] = $row[$this->_columnPrefix.'Title1'];
      $classContent[$row['CIID']]['c_title2'] = $row[$this->_columnPrefix.'Title2'];
      $classContent[$row['CIID']]['c_title3'] = $row[$this->_columnPrefix.'Title3'];
      $classContent[$row['CIID']]['c_text1'] = $row[$this->_columnPrefix.'Text1'];
      $classContent[$row['CIID']]['c_text2'] = $row[$this->_columnPrefix.'Text2'];
      $classContent[$row['CIID']]['c_text3'] = $row[$this->_columnPrefix.'Text3'];
      $imageTitles = $this->explode_content_image_titles('qp', $row[$this->_columnPrefix.'ImageTitles']);
      $classContent[$row['CIID']]['c_image_title1'] = $imageTitles['qp_image1_title'];
      $classContent[$row['CIID']]['c_image_title2'] = $imageTitles['qp_image2_title'];
      $classContent[$row['CIID']]['c_image_title3'] = $imageTitles['qp_image3_title'];
      $classContent[$row['CIID']]['c_sub'] = array();

      $sql = " SELECT QPSTitle1, QPSTitle2, QPSTitle3, QPSTitle4, "
           . "        QPSText1, QPSText2, QPSText3, QPSText4, "
           . "        QPSImageTitles "
           . " FROM {$this->table_prefix}contentitem_qp_statement "
           . " WHERE FK_CIID = {$row['CIID']} "
           . ' ORDER BY QPSPosition ASC ';
      $resultSub = $this->db->query($sql);
      while ($rowSub = $this->db->fetch_row($resultSub))
      {
        $imageTitlesSub = $this->explode_content_image_titles('qp',$rowSub['QPSImageTitles']);
        $classContent[$row['CIID']]['c_sub'][] = array(
          'cs_title1' => $rowSub['QPSTitle1'],
          'cs_title2' => $rowSub['QPSTitle2'],
          'cs_title3' => $rowSub['QPSTitle3'],
          'cs_title4' => $rowSub['QPSTitle4'],
          'cs_text1' => $rowSub['QPSText1'],
          'cs_text2' => $rowSub['QPSText2'],
          'cs_text3' => $rowSub['QPSText3'],
          'cs_text4' => $rowSub['QPSText4'],
          'c_image_title1' => $imageTitlesSub['qp_image1_title'],
          'c_image_title2' => $imageTitlesSub['qp_image2_title'],
          'c_image_title3' => $imageTitlesSub['qp_image3_title'],
          'c_image_title4' => $imageTitlesSub['qp_image4_title'],
        );
      }
      $this->db->free_result($resultSub);
    }
    $this->db->free_result($result);

    return $classContent;
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
    // Get the image titles for the QP contentitem itself.
    $titles = parent::getImageTitles();

    // Ensure that this part is not executed for ContentItemQP_Statements or other
    // subclasses in case the $subcontent parameter is true.
    if ($subcontent === true && $this instanceof ContentItemQP) {
      $tmpTitles = $this->_subelements[0]->getImageTitles(false);
      $titles = array_merge($titles, $tmpTitles);
    }

    return $titles;
  }

  /**
   * Returns all title elements within this content item (or subcontent)
   * @see ContentItem::getTexts()
   *
   * @param boolean $subcontent
   *        If true, also subcontent text will be returned.
   * @return array
   *         An array containing all texts stored for this content item (or subcontent)
   */
  public function getTexts($subcontent = true)
  {
    $texts = parent::getTexts();

    if ($subcontent) {
      $sql = "SELECT QPSText1, QPSText2, QPSText3, QPSText4 "
           . "FROM {$this->table_prefix}contentitem_qp_statement "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);

      $subtexts = array();
      while ($row = $this->db->fetch_row($result)) {
        $str = '';
        for ($i = 1; $i < 5; $i++) {
          $str .= $row["QPSText$i"];
        }
        if ($str) {
          $subtexts[] = $str;
        }
      }
      $this->db->free_result($result);
      $texts = array_merge($texts, $subtexts);
    }
    return $texts;
  }

  /**
   * Returns all title elements within this content item (or subcontent)
   * @see ContentItem::getTitles()
   *
   * @return array
   *          an array containing all titles stored for this content item (or subcontent)
   */
  protected function getTitles()
  {
    $titles = parent::getTitles();

    $sql = 'SELECT QPSTitle1, QPSTitle2, QPSTitle3, QPSTitle4 '
         . "FROM {$this->table_prefix}contentitem_qp_statement "
         . "WHERE FK_CIID = $this->page_id "
         . "AND (COALESCE(QPSTitle1, QPSTitle2, QPSTitle3, QPSTitle4, '') != '') ";
    $titles = array_merge($titles, $this->db->GetCol($sql));

    return $titles;
  }

  /**
   * Returns all broken links found inside the contentitems text fields
   * @see ContentItem::getBrokenTextLinks()
   */
  public function getBrokenTextLinks($text = null)
  {
    if ($text)
    {
      return parent::getBrokenTextLinks($text);
    }

    $broken = parent::getBrokenTextLinks();

    $sql = 'SELECT QPSID, QPSText1, QPSText2, QPSText3, QPSText4, QPSPosition '
         . "FROM {$this->table_prefix}contentitem_qp_statement "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    while ($stmt = $this->db->fetch_row($result))
    {
      $bls = parent::getBrokenTextLinks($stmt['QPSText1'].' '.$stmt['QPSText2'].' '.$stmt['QPSText3'].' '.$stmt['QPSText4']);
      // foreach broken link found modify the page link and add to broken links array
      foreach ($bls as $bl)
      {
        $bl['link'] .= "&amp;statement={$stmt['QPSID']}&amp;scrollToAnchor=a_area{$stmt['QPSPosition']}";
        $broken[] = $bl;
      }
    }
    $this->db->free_result($result);

    return $broken;
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[0] = new ContentItemQP_Statements($this->site_id,
        $this->page_id, $this->tpl, $this->db, $this->table_prefix, '', '',
        $this->_user, $this->session, $this->_navigation, $this);
  }
}
