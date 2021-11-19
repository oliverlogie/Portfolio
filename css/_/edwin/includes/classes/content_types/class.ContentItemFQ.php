<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2018-02-22 16:13:41 +0100 (Do, 22 Feb 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ContentItemFQ extends ContentItem
{
  protected $_configPrefix = 'fq';
  protected $_contentPrefix = 'fq';
  protected $_columnPrefix = 'FQ';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 3,
  );
  protected $_templateSuffix = 'FQ';

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
    if (   isset($_POST['process_fq_question'])
        || isset($_GET['deleteQuestionID'])
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

    $fq_questions_content = $this->_subelements[0]->get_content();
    $fq_question_items = $fq_questions_content['content'];
    if ($fq_questions_content['message']) {
      $this->setMessage($fq_questions_content['message']);
    }

    $fq_hidden_fields = '<input type="hidden" name="site" value="'.$this->site_id.'" />'
                      . '<input type="hidden" name="page" value="'.$this->page_id.'" />'
                      . '<input type="hidden" name="action" value="content" />'
                      . '<input type="hidden" name="action2" value="" />'
                      . '<input type="hidden" name="question" class="jq_question" value="0" />'
                      . '<input type="hidden" name="scrollToAnchor" class="jq_scrollToAnchor" value="" />';
    $fq_scroll_to_anchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';

    if (!$fq_scroll_to_anchor && $this->_subelements[0]->hasQuestionChanged()) {
      $fq_scroll_to_anchor = 'a_questions';
    }

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_vars($tplName, array(
      'fq_questions' => $fq_question_items,
      'fq_hidden_fields' => $fq_hidden_fields,
      'fq_scroll_to_anchor' => $fq_scroll_to_anchor,
      'fq_main_content_changed' => parent::hasContentChanged(),
    ));

    return parent::get_content(array_merge($params, array(
      'settings' => array( 'tpl' => $tplName )
    )));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function preview()
  {
    $this->tpl->set_tpl_dir("../templates");
    $post = new Input(Input::SOURCE_POST);

    // texts
    $fq_title1 = parseOutput($post->readString('fq_title1', Input::FILTER_CONTENT_TITLE), 2);
    $fq_title2 = parseOutput($post->readString('fq_title2', Input::FILTER_CONTENT_TITLE), 2);
    $fq_title3 = parseOutput($post->readString('fq_title3', Input::FILTER_CONTENT_TITLE), 2);
    $fq_text1 = parseOutput($post->readString('fq_text1', Input::FILTER_CONTENT_TEXT), 1);
    $fq_text2 = parseOutput($post->readString('fq_text2', Input::FILTER_CONTENT_TEXT), 1);
    $fq_text3 = parseOutput($post->readString('fq_text3', Input::FILTER_CONTENT_TEXT), 1);

    // images
    $fq_image_titles = $post->readImageTitles('fq_image_title');
    $fq_image_titles = $this->explode_content_image_titles('c_fq', $fq_image_titles);
    $fq_images = $this->_createPreviewImages(array(
      'FQImage1' => 'fq_image1',
      'FQImage2' => 'fq_image2',
      'FQImage3' => 'fq_image3',
    ));
    $fq_image_src1 = $fq_images['fq_image1'];
    $fq_image_src2 = $fq_images['fq_image2'];
    $fq_image_src3 = $fq_images['fq_image3'];
    $fq_image_large1 = $this->_hasLargeImage($fq_image_src1);
    $fq_image_large2 = $this->_hasLargeImage($fq_image_src2);
    $fq_image_large3 = $this->_hasLargeImage($fq_image_src3);

    $questions = $this->previewGetQuestions();
    $questionItems = $questions['questions'];
    $questionAnchors = $questions['anchors'];

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('fq')
    ));
    $this->tpl->parse_if($tplName, 'zoom1', $fq_image_large1, array('c_fq_zoom1_link' => '#'));
    $this->tpl->parse_if($tplName, 'zoom2', $fq_image_large2, array('c_fq_zoom2_link' => '#'));
    $this->tpl->parse_if($tplName, 'zoom3', $fq_image_large3, array('c_fq_zoom3_link' => '#'));
    $this->tpl->parse_if($tplName, 'image1', $fq_image_src1, array('c_fq_image_src1' => $fq_image_src1));
    $this->tpl->parse_if($tplName, 'image2', $fq_image_src2, array('c_fq_image_src2' => $fq_image_src2));
    $this->tpl->parse_if($tplName, 'image3', $fq_image_src3, array('c_fq_image_src3' => $fq_image_src3));

    $this->tpl->parse_if($tplName, 'zoom', $fq_image_large1, array(
      'c_fq_zoom_link' => '#',
    ));
    $this->tpl->parse_loop($tplName, $questionItems, 'question_items');
    $this->tpl->parse_loop($tplName, $questionAnchors, 'question_anchors');
    $this->tpl->parse_vars($tplName, array_merge($fq_image_titles, array(
      'c_fq_title1' => $fq_title1,
      'c_fq_title2' => $fq_title2,
      'c_fq_title3' => $fq_title3,
      'c_fq_text1' => $fq_text1,
      'c_fq_text2' => $fq_text2,
      'c_fq_text3' => $fq_text3,
      'c_fq_image_src1' => $fq_image_src1,
      'c_fq_image_src2' => $fq_image_src2,
      'c_fq_image_src3' => $fq_image_src3,
      'c_surl' => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $fq_content = $this->tpl->parsereturn('content_site_fq', $this->_getFrontentLang());
    $this->tpl->set_tpl_dir('./templates');
    return $fq_content;
  }

  /**
   * Reads all questions from the database and returns an array that can be used
   * to parse the question-loop inside the FQ template as well as an array that
   * can be used to parse the question-anchor-loop inside the FQ template
   *
   * @return array
   *         'questions' - contains, for each question, an associative array
   *                       with one element ("c_fq_question")
   *         'anchors'   - contains, for each question an array with item title
   *                       and id.
   */
  private function previewGetQuestions()
  {
    $questions = array('questions' => array(), 'anchors' => array());
    $tplPath = 'content_types/ContentItem' . $this->_templateSuffix . '_Question.tpl';

    $position = 1;
    $sql = 'SELECT FQQID, FQQTitle, FQQText, FQQImage, FQQImageTitles '
         . "FROM {$this->table_prefix}contentitem_fq_question "
         . "WHERE FK_CIID = $this->page_id "
         . "AND FQQDisabled = 0 "
         . 'AND ( '
         . "  COALESCE(FQQTitle, '') != '' "
         . "  OR COALESCE(FQQImage, '') != '' "
         . "  OR COALESCE(FQQText, '') != '' "
         . ') '
         . 'ORDER BY FQQPosition ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)){
      $largeImage = $this->_hasLargeImage($row['FQQImage']);
      $imageTitles = $this->explode_content_image_titles('c_fq_question', $row['FQQImageTitles']);

      $this->tpl->load_tpl('content_site_fq_question', 'content_types/ContentItemFQ_Question.tpl');
      $this->tpl->parse_if('content_site_fq_question', 'zoom', $largeImage, array(
        'c_fq_question_zoom_link' => '#',
      ));
      $this->tpl->parse_if('content_site_fq_question', 'statement_image', $row['FQQImage'], array(
        'c_fq_question_image_src' => '../' . $row['FQQImage'],
      ));

      $questions['questions'][] = array(
        'c_fq_question' => $this->tpl->parsereturn('content_site_fq_question', array_merge($imageTitles, array(
          'c_fq_question_title' => parseOutput($row['FQQTitle']),
          'c_fq_question_text' => parseOutput($row['FQQText'], 1),
          'c_fq_question_id' => $row['FQQID'],
          'c_fq_question_position' => $position,
        ))),
      );

      $questions['anchors'][] = array(
        'c_fq_question_title' => parseOutput($row['FQQTitle']),
        'c_fq_question_id' => $row['FQQID'],
        'c_fq_question_position' => $position,
      );

      $position++;
    }
    $this->db->free_result($result);

    return $questions;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID, CIID, CIIdentifier, CTitle, FQTitle1, FQTitle2, FQTitle3, FQText1, FQText2, FQText3, FQImageTitles FROM {$this->table_prefix}contentitem_fq cifq LEFT JOIN {$this->table_prefix}contentitem ci ON cifq.FK_CIID = ci.CIID ORDER BY cifq.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)) {
      $class_content[$row['CIID']]['path'] = $row['CIIdentifier'];
      $class_content[$row['CIID']]['path_title'] = $row['CTitle'];
      $class_content[$row['CIID']]['type'] = $row['FK_CTID'];
      $class_content[$row['CIID']]['c_title1'] = $row['FQTitle1'];
      $class_content[$row['CIID']]['c_title2'] = $row['FQTitle2'];
      $class_content[$row['CIID']]['c_title3'] = $row['FQTitle3'];
      $class_content[$row['CIID']]['c_text1'] = $row['FQText1'];
      $class_content[$row['CIID']]['c_text2'] = $row['FQText2'];
      $class_content[$row['CIID']]['c_text3'] = $row['FQText3'];
      $fq_image_titles = $this->explode_content_image_titles('fq', $row['FQImageTitles']);
      $class_content[$row['CIID']]['c_image_title1'] = $fq_image_titles['fq_image1_title'];
      $class_content[$row['CIID']]['c_image_title2'] = $fq_image_titles['fq_image2_title'];
      $class_content[$row['CIID']]['c_image_title3'] = $fq_image_titles['fq_image3_title'];
      $class_content[$row['CIID']]['c_sub'] = array();
      $result_sub = $this->db->query("SELECT FQQTitle, FQQText, FQQImageTitles FROM {$this->table_prefix}contentitem_fq_question WHERE FK_CIID = {$row['CIID']} ORDER BY FQQPosition ASC");
      while ($row_sub = $this->db->fetch_row($result_sub)) {
        $fq_image_titles_sub = $this->explode_content_image_titles('fq_question', $row_sub['FQQImageTitles']);
        $class_content[$row['CIID']]['c_sub'][] = array(
          'cs_title' => $row_sub['FQQTitle'],
          'cs_text' => $row_sub['FQQText'],
          'c_image_title1' => $fq_image_titles_sub['fq_question_image1_title'],
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

    // Ensure that this part is not executed for ContentItemFQ_Questions or other
    // subclasses in case the $subcontent parameter is true.
    if ($subcontent === true && $this instanceof ContentItemFQ) {
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
      $sql = "SELECT FQQText "
           . "FROM {$this->table_prefix}contentitem_fq_question "
           . "WHERE FK_CIID = $this->page_id "
           . "AND (COALESCE(FQQText, '') != '') ";
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

    $sql = 'SELECT FQQTitle '
         . "FROM {$this->table_prefix}contentitem_fq_question "
         . "WHERE FK_CIID = $this->page_id "
         . "AND (COALESCE(FQQTitle, '') != '') ";
    $titles = array_merge($titles, $this->db->GetCol($sql));

    return $titles;
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[] = new ContentItemFQ_Questions(
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

    $sql = 'SELECT FQQID, FQQText, FQQPosition '
         . "FROM {$this->table_prefix}contentitem_fq_question "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    while ($stmt = $this->db->fetch_row($result))
    {
      $bls = parent::getBrokenTextLinks($stmt['FQQText']);
      // foreach broken link found modify the page link and add to broken links array
      foreach ($bls as $bl)
      {
        $bl['link'] .= "&amp;statement={$stmt['FQQID']}&amp;scrollToAnchor=a_area{$stmt['FQQPosition']}";
        $broken[] = $bl;
      }
    }
    $this->db->free_result($result);
    return $broken;
  }
}