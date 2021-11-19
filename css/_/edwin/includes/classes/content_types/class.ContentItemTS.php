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
class ContentItemTS extends ContentItem
{
  protected $_configPrefix = 'ts';
  protected $_contentPrefix = 'ts';
  protected $_columnPrefix = 'T';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 3,
  );
  protected $_templateSuffix = 'TS';

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

    // A block has changed.
    if (   isset($_POST['process_ts_block'])
        || isset($_GET['deleteBlockID'])
    ) {
      return true;
    }

    // Nothing has changed.
    return false;
  }

  /**
   * Return all image titles.
   *
   * @param bool $subcontent [optional] [default : true]
   *        If subcontent is false, image titles from blocks will not be
   *        retrieved.
   *
   * @return array
   *         An array containing all image titles stored for this content
   *         item (and subcontent)
   */
  public function getImageTitles($subcontent = true)
  {
    // Get the image titles for the TS contentitem itself.
    $titles = parent::getImageTitles();

    // Ensure that this part is not executed for ContentItemTS_Blocks or other
    // subclasses in case the $subcontent parameter is true.
    if ($subcontent === true && $this instanceof ContentItemTS) {
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
      $sql = 'SELECT TBText '
           . "FROM {$this->table_prefix}contentitem_ts_block "
           . "WHERE FK_CIID = $this->page_id ";
      $texts = array_merge($texts ,$this->db->GetCol($sql));
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

    $sql = 'SELECT TBTitle '
         . "FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE FK_CIID = $this->page_id ";
    $titles = array_merge($titles ,$this->db->GetCol($sql));

    return $titles;
  }

  public function getBrokenTextLinks($text = null)
  {
    global $_LANG;

    if ($text)
    {
      return parent::getBrokenTextLinks($text);
    }

    $broken = parent::getBrokenTextLinks();

    $sql = "SELECT TBID, TBText, TBPosition "
         . "FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    while ($block = $this->db->fetch_row($result))
    {
      $bls = parent::getBrokenTextLinks($block['TBText']);
      // foreach broken link found modify the page link and add to broken links array
      foreach ($bls as $bl)
      {
        $bl['link'] .= "&amp;block={$block['TBID']}&amp;scrollToAnchor=a_block{$block['TBPosition']}";
        $broken[] = $bl;
      }
    }
    $this->db->free_result($result);
    // also check linkboxes inside block
    $sql = "SELECT TLID, TLTitle, TLPosition, TLLink, TBPosition, TBID "
         . "FROM {$this->table_prefix}contentitem_ts_block_link "
         . "JOIN {$this->table_prefix}contentitem_ts_block ON TBID = FK_TBID "
         . "WHERE FK_TBID IN ( "
         . "      SELECT TBID "
         . "      FROM {$this->table_prefix}contentitem_ts_block "
         . "      WHERE FK_CIID = {$this->page_id} "
         . ") "
         . "ORDER BY TLPosition ASC ";
    $result = $this->db->query($sql);
    while ($tLink = $this->db->fetch_row($result))
    {
      $sql = "SELECT CIID "
           . "FROM {$this->table_prefix}contentitem "
           . "WHERE CIID = {$tLink['TLLink']} ";
      if (!$this->db->GetOne($sql))
      {
        $broken[] = array(
          'title' => $_LANG['ts_block_link_broken_label'],
          'type'  => 'internal',
          'link'  => "index.php?action=content&amp;site=".$this->site_id
                    ."&amp;page=".$this->page_id."&amp;block=".$tLink['TBID']
                    ."&amp;scrollToAnchor=a_block{$tLink['TBPosition']}_links",
        );
      }
    }
    return $broken;
  }

  protected function _hasBrokenInternalLink()
  {
    // check if broken link in contentitem_ts_block_link exists
    // get links where contentitem (target) was deleted
    $sql = "SELECT TLID "
         . "FROM {$this->table_prefix}contentitem_ts_block_link "
         . "WHERE TLLink NOT IN ("
         . "    SELECT CIID FROM {$this->table_prefix}contentitem "
         . ") "
         . "AND FK_TBID IN ( "
         . "      SELECT TBID "
         . "      FROM {$this->table_prefix}contentitem_ts_block "
         . "      WHERE FK_CIID = {$this->page_id} "
         . ") ";
    if ($this->db->GetCol($sql))
    {
      return true;
    }

    // no broken links inside internal link field of boxes -> check texts
    return parent::_hasBrokenInternalLink();
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

    $blocksContent= $this->_subelements[0]->get_content();
    $blocksItems = $blocksContent['content'];
    if ($blocksContent['message']) {
      $this->setMessage($blocksContent['message']);
    }
    $invalidLinks = $blocksContent['invalidLinks'];
    if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['ts_message_invalid_links'], $invalidLinks)));
    }

    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="page" value="' . $this->page_id . '" />'
                  . '<input type="hidden" name="action" value="content" />'
                  . '<input type="hidden" name="action2" value="" />'
                  . '<input type="hidden" name="block" class="jq_block" value="0" />'
                  . '<input type="hidden" name="scrollToAnchor" class="jq_scrollToAnchor" value="" />';

    $scrollToAnchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';

    if (!$scrollToAnchor && $this->_subelements[0]->hasBlockChanged()) {
      $scrollToAnchor = 'a_blocks';
    }

    $this->tpl->load_tpl('content_site_ts', $this->_getTemplatePath());
    $content = $this->tpl->parsereturn('content_site_ts', array(
      'ts_blocks' => $blocksItems,
      'ts_hidden_fields' => $hiddenFields,
      'ts_autocomplete_contentitem_url' => "index.php?action=response&site=$this->site_id&page=$this->page_id&request=ContentItemAutoComplete&excludeContentItems=$this->page_id",
      'ts_scroll_to_anchor' => $scrollToAnchor,
      'ts_main_content_changed' => parent::hasContentChanged(),
    ));

    return parent::get_content(array_merge($params, array(
      'settings' => array( 'tpl' => 'content_site_ts' ),
    )));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function preview()
  {
    $cp = $this->_contentPrefix;
    $tp = 'c_' . $cp;
    $this->tpl->set_tpl_dir("../templates");
    $post = new Input(Input::SOURCE_POST);
    $className = 'ContentItem' . $this->_templateSuffix;

    // Texts
    $title1 = $post->readString($cp . '_title1', Input::FILTER_CONTENT_TITLE);
    $title2 = $post->readString($cp . '_title2', Input::FILTER_CONTENT_TITLE);
    $title3 = $post->readString($cp . '_title3', Input::FILTER_CONTENT_TITLE);
    $text1 = $post->readString($cp . '_text1', Input::FILTER_CONTENT_TEXT);
    $text2 = $post->readString($cp . '_text2', Input::FILTER_CONTENT_TEXT);
    $text3 = $post->readString($cp . '_text3', Input::FILTER_CONTENT_TEXT);

    // Images
    $images = $this->_createPreviewImages(array(
      'TImage1' => $cp . '_image1',
      'TImage2' => $cp . '_image2',
      'TImage3' => $cp . '_image3',
    ));
    $imageSource1 = $images[$cp . '_image1'];
    $imageSource2 = $images[$cp . '_image2'];
    $imageSource3 = $images[$cp . '_image3'];
    $imageLarge1 = $this->_hasLargeImage($imageSource1);
    $imageLarge2 = $this->_hasLargeImage($imageSource2);
    $imageLarge3 = $this->_hasLargeImage($imageSource3);
    $imageTitles = $post->readImageTitles($cp . '_image_title');
    $imageTitles = $this->explode_content_image_titles($tp, $imageTitles);

    $this->tpl->load_tpl('content_site_ts', 'content_types/ContentItemTS.tpl');
    $this->tpl->parse_if('content_site_ts', 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('ts')
    ));
    $this->tpl->parse_if('content_site_ts', 'zoom1', $imageLarge1, array(
      $tp . '_zoom1_link' => '#',
    ));
    $this->tpl->parse_if('content_site_ts', 'zoom2', $imageLarge2, array(
      $tp . '_zoom2_link' => '#',
    ));
    $this->tpl->parse_if('content_site_ts', 'zoom3', $imageLarge3, array(
      $tp . '_zoom3_link' => '#',
    ));
    $this->tpl->parse_if('content_site_ts', 'image1', $imageSource1);
    $this->tpl->parse_if('content_site_ts', 'image2', $imageSource2);
    $this->tpl->parse_if('content_site_ts', 'image3', $imageSource3);
    $this->tpl->parse_loop('content_site_ts', $this->_previewGetBlocks(), 'block_items');

    $this->tpl->parse_vars('content_site_ts', array_merge($imageTitles, array(
      $tp . '_title1' => parseOutput($title1, 2),
      $tp . '_title2' => parseOutput($title2, 2),
      $tp . '_title3' => parseOutput($title3, 2),
      $tp . '_text1' => parseOutput($text1, 1),
      $tp . '_text2' => parseOutput($text2, 1),
      $tp . '_text3' => parseOutput($text3, 1),
      $tp . '_image_src1' => $imageSource1,
      $tp . '_image_src2' => $imageSource2,
      $tp . '_image_src3' => $imageSource3,
      'c_surl' => '../',
      'm_print_part' => $this->get_print_part(),
    )));

    $content = $this->tpl->parsereturn('content_site_ts', $this->_getFrontentLang());
    $this->tpl->set_tpl_dir('./templates');

    return $content;
  }

  /**
   * Reads all blocks from the database and returns an array that can be used to parse the block-loop inside the TS template.
   *
   * @return array
   *        Contains, for each block, an associative array with one element ("c_ts_block").
   */
  private function _previewGetBlocks()
  {
    $blocks = array();
    $tp = 'c_' . $this->_contentPrefix  . '_block';
    $tplPath = 'content_types/ContentItem' . $this->_templateSuffix . '_Block.tpl';

    $position = 1;
    $sql = 'SELECT TBID, TBTitle, TBText, TBImage, TBImageTitles, TBPosition '
         . "FROM {$this->table_prefix}contentitem_ts_block "
         . "WHERE FK_CIID = $this->page_id "
         . "AND COALESCE(TBTitle, '') != '' "
         . "AND TBDisabled = 0 "
         . 'ORDER BY TBPosition ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $largeImage = $this->_hasLargeImage($row['TBImage']);
      $imageTitles = $this->explode_content_image_titles($tp, $row['TBImageTitles']);

      $this->tpl->load_tpl('content_site_ts_block', $tplPath);
      $this->tpl->parse_if('content_site_ts_block', 'zoom', $largeImage, array(
        $tp . '_zoom_link' => '#',
      ));
      $this->tpl->parse_if('content_site_ts_block', 'block_image', $row['TBImage'], array(
        $tp . '_image_src' => '../' . $row['TBImage'],
      ));
      $this->tpl->parse_loop('content_site_ts_block', $this->_previewGetLinks($row['TBID']), 'block_link_items');
      $blocks[] = array(
        $tp => $this->tpl->parsereturn('content_site_ts_block', array_merge($imageTitles, array(
          $tp . '_id' => $row['TBID'],
          $tp . '_position' => $position++,
          $tp . '_real_position' => $row['TBPosition'],
          $tp . '_title' => parseOutput($row['TBTitle']),
          $tp . '_text' => parseOutput($row['TBText'], 1),
      ))),
      );
    }
    $this->db->free_result($result);

    return $blocks;
  }

  /**
   * Reads all links inside a block from the database and returns an array that can be used to parse the link-loop inside the TS-Block template.
   *
   * @param integer $tbid
   *        The ID of the TS-Block for which to get the links.
   * @return array
   *        Contains, for each link, an associative array with link data.
   */
  private function _previewGetLinks($tbid)
  {
    $links = array();
    $position = 1;
    $sql = 'SELECT TLID, TLTitle, TLPosition, CIID '
         . "FROM {$this->table_prefix}contentitem_ts_block_link "
         . "JOIN {$this->table_prefix}contentitem "
         . '     ON TLLink = CIID '
         . "WHERE FK_TBID = $tbid "
         . 'ORDER BY TLPosition ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      // The SQL query does not filter non-visible sites, we have to do it here.
      $linkedPage = $this->_navigation->getPageByID((int)$row['CIID']);
      if (!$linkedPage || !$linkedPage->isVisible()) {
        continue;
      }

      $pagePath = $linkedPage->getUrl();

      $links[] = array(
        'c_ts_block_link_id' => $row['TLID'],
        'c_ts_block_link_position' => $position++,
        'c_ts_block_link_real_position' => $row['TLPosition'],
        'c_ts_block_link_title' => parseOutput($row['TLTitle']),
        'c_ts_block_link_link' => $pagePath,
      );
    }
    $this->db->free_result($result);

    return $links;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,TTitle1,TTitle2,TTitle3,TText1,TText2,TText3,TImageTitles FROM ".$this->table_prefix."contentitem_ts cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)){
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["TTitle1"];
      $class_content[$row["CIID"]]["c_title2"] = $row["TTitle2"];
      $class_content[$row["CIID"]]["c_title3"] = $row["TTitle3"];
      $class_content[$row["CIID"]]["c_text1"] = $row["TText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["TText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["TText3"];
      $ts_image_titles = $this->explode_content_image_titles("ts",$row["TImageTitles"]);
      $class_content[$row["CIID"]]["c_image_title1"] = $ts_image_titles["ts_image1_title"];
      $class_content[$row["CIID"]]["c_image_title2"] = $ts_image_titles["ts_image2_title"];
      $class_content[$row["CIID"]]["c_image_title3"] = $ts_image_titles["ts_image3_title"];
      $class_content[$row["CIID"]]["c_sub"] = array();
      $result_sub = $this->db->query("SELECT TBID, TBTitle, TBText, TBImageTitles FROM ".$this->table_prefix."contentitem_ts_block WHERE FK_CIID = ".$row["CIID"]." ORDER BY  TBPosition ASC");
      while ($row_sub = $this->db->fetch_row($result_sub)) {
        $ts_image_titles_sub = $this->explode_content_image_titles("ts", $row_sub["TBImageTitles"]);
        $class_content[$row["CIID"]]["c_sub"][] = array(
          "cs_title"       => $row_sub["TBTitle"],
          "cs_text"        => $row_sub["TBText"],
          "cs_image_title" => $row_sub["TBImageTitles"],
        );

        $result_sub1 = $this->db->query("SELECT TLTitle FROM ".$this->table_prefix."contentitem_ts_block_link WHERE FK_TBID = ".$row_sub["TBID"]." ORDER BY TLPosition ASC");
        while ($row_sub1 = $this->db->fetch_row($result_sub1)) {
          $class_content[$row["CIID"]]["c_sub"][] = array(
            "cs_title"       => $row_sub1["TLTitle"],
            "cs_text"        => "",
            "cs_image_title" => "",
          );
        }
        $this->db->free_result($result_sub1);
      }
      $this->db->free_result($result_sub);
    }
    $this->db->free_result($result);

    return $class_content;
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[] = new ContentItemTS_Blocks($this->site_id,
        $this->page_id, $this->tpl, $this->db, $this->table_prefix,
        $this->_user, $this->session, $this->_navigation, $this);
  }
}