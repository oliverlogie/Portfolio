<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2018-12-14 10:26:08 +0100 (Fr, 14 Dez 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemCB extends ContentItem
{
  protected $_configPrefix = 'cb';
  protected $_contentPrefix = 'cb';
  protected $_columnPrefix = 'CB';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 3,
    'Image' => 1,
  );
  protected $_templateSuffix = 'CB';

  /**
   * Determines the path of the image that should be used as the source for an auto-generated image.
   *
   * @param integer $ciid
   *        the ID of the content item
   * @return string
   *        the path to the image (guaranteed to exist) or false
   */
  protected function getAutoImageSourcePath($ciid) {
    // determine the box image of the linked content item (because that's the only image we can determine
    // from the table "contentabstract", we don't know the structure of the tables "contentitem_xx")
    $sourcePath = $this->db->GetOne("SELECT CImage FROM {$this->table_prefix}contentabstract WHERE FK_CIID = $ciid");

    // if there exists a box image we try to determine the related normal image
    if ($sourcePath) {
      extract(pathinfo("../$sourcePath"));
      if (!isset($filename)) { // compatibility with PHP < 5.2.0
        $filename = getFilenameFromBasename($basename);
      }
      // cut the '-b' from the end of the filename
      if (mb_substr($filename, -2) == '-b') {
        $filename = mb_substr($filename, 0, -2);
        $sourcePath = "$dirname/$filename.$extension";
      }
    }

    if (!is_file($sourcePath)) {
      $sourcePath = false;
    }

    return $sourcePath;
  }

  /**
   * Creates a temporary image file that is automatically generated from the main image of a content item and can be passed to _storeImage().
   *
   * @param integer $ciid
   *        the ID of the content item from which to take the image
   * @param string $prefix
   *        the Prefix for the image size configuration (i.e. "cb_box" or "cb_box_biglink")
   * @return string
   *        the path to the temporary image file which should be passed to _storeImage() or null, if no image was found
   */
  protected function getAutoImage($ciid, $prefix) {
    global $_LANG;

    // Determine the path of the content item image.
    $sourcePath = $this->getAutoImageSourcePath($ciid);
    if (!$sourcePath) {
      $this->setMessage(Message::createFailure($_LANG['cb_message_box_biglink_autoimage_notpossible']));
      return null;
    }

    // Load the image and simply crop it to the correct size.
    $image = CmsImageFactory::create($sourcePath);
    $normalWidth = $this->_configHelper->readImageConfiguration($prefix, 'image_width', 0);
    $normalHeight = $this->_configHelper->readImageConfiguration($prefix, 'image_height', 0);

    //Special handling for mutable image sizes (minimum and maximum values defined)
    if (is_array($normalWidth) || is_array($normalHeight))
    {
      $imageSize = ContentBase::_readMutableSize($image, $normalWidth, $normalHeight);
      $normalWidth = $imageSize[0];
      $normalHeight = $imageSize[1];
      $selectionWidth = $imageSize[2];
      $selectionHeight = $imageSize[3];
    }
    else
    {
      // The resize() method throws an exception if the size of the selection is
      // larger than the image, so we limit the width and height.
      $selectionWidth = min($normalWidth, $image->getWidth());
      $selectionHeight = min($normalHeight, $image->getHeight());
    }
    $image->resize((int)$normalWidth, (int)$normalHeight, (int)$selectionWidth, (int)$selectionHeight);

    // Determine a destination file path and save the image.
    $destinationPath = tempnam(sys_get_temp_dir(), 'edw');
    switch($image->getType()) {
      case IMAGETYPE_GIF:
        $image->writeGif($destinationPath);
        break;
      case IMAGETYPE_JPEG:
        $image->writeJpeg($destinationPath);
        break;
      case IMAGETYPE_PNG:
        $image->writePng($destinationPath);
        break;
      default:
        $this->setMessage(Message::createFailure($_LANG['global_message_upload_type_error']));
        return null;
    }

    return $destinationPath;
  }

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

    // A box has changed.
    if (   isset($_POST['process_cb_box'])
        || isset($_GET['deleteBoxID'])
    ) {
      return true;
    }

    // A big link has changed.
    if (   isset($_POST['process_cb_box_biglink'])
        || isset($_GET['deleteBigLinkID'])
    ) {
      return true;
    }

    // A small link has changed.
    if (   isset($_POST['process_cb_box_smalllink_create'])
        || isset($_POST['process_cb_box_smalllink_edit'])
        || isset($_GET['deleteSmallLinkID'])
    ) {
      return true;
    }

    // Nothing has changed.
    return false;
  }

  public function getImageTitles($subcontent = true)
  {
    // Get the image titles for the CB contentitem itself.
    $titles = parent::getImageTitles();

    // Ensure that this part is not executed for ContentItemCB_Boxes or other
    // subclasses in case the $subcontent parameter is true.
    if ($subcontent === true) {
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
      $sql = 'SELECT CBBText AS Text '
           . "FROM {$this->table_prefix}contentitem_cb_box "
           . "WHERE FK_CIID = $this->page_id "
           . "AND (COALESCE(CBBText, '') != '') "
           . 'UNION ALL '
           . 'SELECT BLText AS Text '
           . "FROM {$this->table_prefix}contentitem_cb_box_biglink "
           . "JOIN {$this->table_prefix}contentitem_cb_box ON FK_CBBID = CBBID "
           . "WHERE FK_CIID = $this->page_id "
           . "AND (COALESCE(BLText, '') != '') "
           . 'UNION ALL '
           . "SELECT '' AS Text "
           . "FROM {$this->table_prefix}contentitem_cb_box_smalllink "
           . "JOIN {$this->table_prefix}contentitem_cb_box ON FK_CBBID = CBBID "
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

    $sql = 'SELECT CBBTitle AS Title '
         . "FROM {$this->table_prefix}contentitem_cb_box "
         . "WHERE FK_CIID = $this->page_id "
         . "AND (COALESCE(CBBTitle, '') != '') "
         . 'UNION ALL '
         . 'SELECT BLTitle AS Title '
         . "FROM {$this->table_prefix}contentitem_cb_box_biglink "
         . "JOIN {$this->table_prefix}contentitem_cb_box ON FK_CBBID = CBBID "
         . "WHERE FK_CIID = $this->page_id "
         . "AND (COALESCE(BLTitle, '') != '' ) "
         . 'UNION ALL '
         . "SELECT SLTitle AS Title "
         . "FROM {$this->table_prefix}contentitem_cb_box_smalllink "
         . "JOIN {$this->table_prefix}contentitem_cb_box ON FK_CBBID = CBBID "
         . "WHERE FK_CIID = $this->page_id "
         . "AND COALESCE(SLTitle, '') != '' ";
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

    $sql = 'SELECT CBBID, CBBText, CBBPosition, CBBLink '
         . "FROM {$this->table_prefix}contentitem_cb_box "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    $boxIds = array();
    while ($box = $this->db->fetch_row($result))
    {
      $boxIds[] = $box['CBBID'];
      $bls = parent::getBrokenTextLinks($box['CBBText']);
      // foreach broken link found modify the page link and add to broken links array
      foreach ($bls as $bl)
      {
        $bl['link'] .= "&amp;box={$box['CBBID']}&amp;scrollToAnchor=a_box{$box['CBBPosition']}";
        $broken[] = $bl;
      }
      if ($box['CBBLink'])
      {
        $sql = "SELECT CIID "
             . "FROM {$this->table_prefix}contentitem "
             . "WHERE CIID = {$box['CBBLink']} ";
        if (!$this->db->GetOne($sql))
        {
          $broken[] = array(
            'title' => $_LANG['cb_box_link_broken_label'],
            'type'  => 'internal',
            'link'  => "index.php?action=content&amp;site=".$this->site_id
                      ."&amp;page=".$this->page_id."&amp;box=".$box['CBBID']
                      ."&amp;scrollToAnchor=a_box{$box['CBBPosition']}",
          );
        }
      }
    }
    $this->db->free_result($result);

    // if no boxes were found
    if (!$boxIds)
    {
      return $broken;
    }
    $boxIds = implode (', ', $boxIds);
    $sql = 'SELECT BLID, BLText, FK_CBBID, BLLink, CBBPosition '
         . "FROM {$this->table_prefix}contentitem_cb_box_biglink "
         . "JOIN {$this->table_prefix}contentitem_cb_box ON CBBID = FK_CBBID "
         . "WHERE FK_CBBID IN ({$boxIds}) ";
    $result = $this->db->query($sql);
    while ($boxLink = $this->db->fetch_row($result))
    {
      $bls = parent::getBrokenTextLinks($boxLink['BLText']);
      foreach ($bls as $bl)
      {
        $bl['link'] .= "&amp;box={$boxLink['FK_CBBID']}&amp;bigLink={$boxLink['BLID']}"
                      ."&amp;scrollToAnchor=a_box{$boxLink['CBBPosition']}_biglinks";
        $broken[] = $bl;
      }
      if ($boxLink['BLLink'])
      {
        // check internal link fields of biglink boxes
        $sql = "SELECT CIID "
             . "FROM {$this->table_prefix}contentitem "
             . "WHERE CIID = {$boxLink['BLLink']} ";
        if (!$this->db->GetOne($sql))
        {
          $broken[] = array(
            'title' => $_LANG['cb_box_biglink_broken_label'],
            'type'  => 'internal',
            'link'  => "index.php?action=content&amp;site=".$this->site_id."&amp;page="
                       .$this->page_id."&amp;box=".$boxLink['FK_CBBID']
                       ."&amp;bigLink=".$boxLink['BLID']
                       ."&amp;scrollToAnchor=a_box{$boxLink['CBBPosition']}_biglinks",
          );
        }
      }
    }
    $this->db->free_result($result);

    // check internal link fields of smalllink boxes
    $sql = 'SELECT SLID, FK_CBBID, SLLink, CBBPosition '
         . "FROM {$this->table_prefix}contentitem_cb_box_smalllink "
         . "JOIN {$this->table_prefix}contentitem_cb_box ON CBBID = FK_CBBID "
         . "WHERE FK_CBBID IN ({$boxIds}) "
         . "AND SLLink != 0 ";
    $result = $this->db->query($sql);
    while ($boxLink = $this->db->fetch_row($result))
    {
      $sql = "SELECT CIID "
           . "FROM {$this->table_prefix}contentitem "
           . "WHERE CIID = {$boxLink['SLLink']} ";
      if (!$this->db->GetOne($sql))
      {
        $broken[] = array(
          'title' => $_LANG['cb_box_smalllink_broken_label'],
          'type'  => 'internal',
          'link'  => "index.php?action=content&amp;site=".$this->site_id."&amp;page="
                     .$this->page_id."&amp;box=".$boxLink['FK_CBBID']
                     ."&amp;smalllink=".$boxLink['SLID']
                     ."&amp;scrollToAnchor=a_box{$boxLink['CBBPosition']}_smalllinks",
        );
      }
    }
    $this->db->free_result($result);

    return $broken;
  }

  protected function _hasBrokenInternalLink()
  {
    // check all subcontent boxes for broken links in their internal link field
    // check boxes themself for broken links
    $sql = "SELECT CBBLink "
         . "FROM {$this->table_prefix}contentitem_cb_box "
         . "WHERE CBBLink != 0 "
         . "AND FK_CIID = $this->page_id "
         . "AND CBBLink NOT IN (SELECT CIID FROM {$this->table_prefix}contentitem )";
    if ($this->db->GetOne($sql))
    {
      return true;
    }

    // check biglink boxes for broken links
    $sql = "SELECT BLLink "
         . "FROM {$this->table_prefix}contentitem_cb_box_biglink "
         . "WHERE BLLink != 'NULL' "
         . "AND FK_CBBID IN ( "
         . "      SELECT CBBID "
         . "      FROM {$this->table_prefix}contentitem_cb_box "
         . "      WHERE FK_CIID = $this->page_id "
         . ") "
         . "AND BLLink NOT IN (SELECT CIID FROM {$this->table_prefix}contentitem )";
    if ($this->db->GetOne($sql))
    {
      return true;
    }

    // check smalllink boxes for broken links
    $sql = "SELECT SLLink "
         . "FROM {$this->table_prefix}contentitem_cb_box_smalllink "
         . "WHERE SLLink != 'NULL' "
         . "AND FK_CBBID IN ( "
         . "      SELECT CBBID "
         . "      FROM {$this->table_prefix}contentitem_cb_box "
         . "      WHERE FK_CIID = $this->page_id "
         . ") "
         . "AND SLLink NOT IN (SELECT CIID FROM {$this->table_prefix}contentitem )";
    if ($this->db->GetOne($sql))
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

    $cb_boxes_content = $this->_subelements[0]->get_content();
    $cb_boxes_items = $cb_boxes_content["content"];
    if ($cb_boxes_content["message"]) $this->setMessage($cb_boxes_content["message"]);
    $invalidLinks = $cb_boxes_content['invalidLinks'];
    if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['cb_message_invalid_links'], $invalidLinks)));
    }

    $cb_hidden_fields = '<input type="hidden" name="site" value="'.$this->site_id.'" />'
                      . '<input type="hidden" name="page" value="'.$this->page_id.'" />'
                      . '<input type="hidden" name="action" value="content" />'
                      . '<input type="hidden" name="action2" value="" />'
                      . '<input type="hidden" name="box" class="jq_box" value="0" />'
                      . '<input type="hidden" name="bigLink" class="jq_bigLink" value="0" />'
                      . '<input type="hidden" name="scrollToAnchor" class="jq_scrollToAnchor" value="" />';
    $cb_scroll_to_anchor = isset($_REQUEST["scrollToAnchor"]) ? $_REQUEST["scrollToAnchor"] : "";

    $this->tpl->load_tpl("content_site_cb", $this->_getTemplatePath());
    $cb_content = $this->tpl->parsereturn("content_site_cb", array(
      "cb_boxes" => $cb_boxes_items,
      "cb_hidden_fields" => $cb_hidden_fields,
      "cb_autocomplete_contentitem_url" => "index.php?action=response&site=$this->site_id&page=$this->page_id&request=ContentItemAutoComplete&excludeContentItems=$this->page_id",
      "cb_scroll_to_anchor" => $cb_scroll_to_anchor,
      "cb_main_content_changed" => parent::hasContentChanged(),
    ));

    return parent::get_content(array_merge($params, array(
      'settings' => array( 'tpl' => 'content_site_cb' ),
    )));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function preview()
  {
    $post = new Input(Input::SOURCE_POST);

    // texts
    $cb_title = parseOutput($post->readString('cb_title', Input::FILTER_CONTENT_TITLE), 2);
    $cb_text1 = parseOutput($post->readString('cb_text1', Input::FILTER_CONTENT_TEXT), 1);
    $cb_text2 = parseOutput($post->readString('cb_text2', Input::FILTER_CONTENT_TEXT), 1);
    $cb_text3 = parseOutput($post->readString('cb_text3', Input::FILTER_CONTENT_TEXT), 1);

    // images
    $cb_image_titles = $post->readImageTitles('cb_image_title');
    $cb_image_titles = $this->explode_content_image_titles("c_cb", $cb_image_titles);
    $cb_images = $this->_createPreviewImages(array(
      'CBImage' => 'cb_image',
    ));
    $cb_image_src = $cb_images['cb_image'];
    $cb_image_large = $this->_hasLargeImage($cb_image_src);

    $this->tpl->set_tpl_dir('../templates');
    $this->tpl->load_tpl('content_site_cb', $this->_getTemplatePath());
    $this->tpl->parse_if('content_site_cb', 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('cb')
    ));
    $this->tpl->parse_if('content_site_cb', 'zoom', $cb_image_large, array(
      'c_cb_zoom_link' => '#',
    ));
    $this->tpl->parse_loop("content_site_cb", $this->previewGetBoxes(), "box_items");
    $this->tpl->parse_vars("content_site_cb", array_merge($cb_image_titles, array(
      "c_cb_title" => $cb_title,
      "c_cb_text1" => $cb_text1,
      "c_cb_text2" => $cb_text2,
      "c_cb_text3" => $cb_text3,
      "c_cb_image_src" => $cb_image_src,
      "c_surl" => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $cb_content = $this->tpl->parsereturn("content_site_cb", $this->_getFrontentLang());
    $this->tpl->set_tpl_dir("./templates");
    return $cb_content;
  }

  /**
   * Reads all boxes from the database and returns an array that can be used to parse the box-loop inside the CB template.
   *
   * @return array
   *        contains, for each box, an associative array with one element ("c_cb_box")
   */
  private function previewGetBoxes() {
    $boxes = array();
    $tplPath =  'content_types/ContentItem' . $this->_templateSuffix . '_Box.tpl';
    $position = 1;
    $sql = 'SELECT CBBID, CBBTitle, CBBText, CBBImage, CBBImageTitles, '
         . '       CBBPosition, CIID '
         . "FROM {$this->table_prefix}contentitem_cb_box cicbb "
         . "JOIN {$this->table_prefix}contentitem ON CBBLink = CIID "
         . "WHERE cicbb.FK_CIID = $this->page_id "
         . "AND CBBDisabled = 0 "
         . 'AND ( '
         . "  COALESCE(CBBTitle, '') != '' "
         . "  OR COALESCE(CBBImage, '') != '' "
         . "  OR COALESCE(CBBText, '') != '' "
         . ') '
         . 'ORDER BY CBBPosition ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $linkedPage = $this->_navigation->getPageByID((int)$row['CIID']);
      $largeImage = $this->_hasLargeImage($row["CBBImage"]);
      $imageTitles = $this->explode_content_image_titles("c_cb_box", $row["CBBImageTitles"]);

      $this->tpl->load_tpl("content_site_cb_box", $tplPath);
      $this->tpl->parse_if('content_site_cb_box', 'zoom', $largeImage, array(
        'c_cb_box_zoom_link' => '#',
      ));
      $this->tpl->parse_if("content_site_cb_box", "box_image", $row["CBBImage"], array('c_cb_box_image_src' => "../".$row["CBBImage"]));
      $this->tpl->parse_loop("content_site_cb_box", $this->previewGetBigLinks($row["CBBID"]), "box_biglink_items");
      $this->tpl->parse_loop("content_site_cb_box", $this->previewGetSmallLinks($row["CBBID"]), "box_smalllink_items");
      $boxes[] = array(
        "c_cb_box" => $this->tpl->parsereturn("content_site_cb_box", array_merge($imageTitles, array(
          "c_cb_box_title" => parseOutput($row["CBBTitle"]),
          "c_cb_box_text" => parseOutput($row["CBBText"], 1),
          "c_cb_box_link" => '#',
          "c_cb_box_link_title" => (!$linkedPage || !$linkedPage->isVisible()) ? '#' : $linkedPage->getUrl(),
          "c_cb_box_id" => $row["CBBID"],
          "c_cb_box_position" => $position++,
          'c_cb_box_real_position' => $row['CBBPosition'],
      ))),
      );
    }
    $this->db->free_result($result);

    return $boxes;
  }

  /**
   * Reads all big links inside a box from the database and returns an array that can be used to parse the biglink-loop inside the CB-Box template.
   *
   * @param integer $cbbid
   *        the ID of the CB-Box for which to get the big links
   * @return array
   *        contains, for each big link, an associative array with one element ("c_cb_box_biglink")
   */
  private function previewGetBigLinks($cbbid) {
    $biglinks = array();
    $tplPath =  'content_types/ContentItem' . $this->_templateSuffix . '_Box_Biglink.tpl';
    $position = 1;
    $result = $this->db->query(<<<SQL
SELECT BLID, BLTitle, BLText, BLImage, BLImageTitles, BLPosition, CIID
FROM {$this->table_prefix}contentitem_cb_box_biglink
JOIN {$this->table_prefix}contentitem ON BLLink = CIID
WHERE FK_CBBID = $cbbid
AND (COALESCE(BLTitle, '') != '' OR COALESCE(BLImage, '') != '' OR COALESCE(BLText, '') != '')
ORDER BY BLPosition
SQL
    );


    while ($row = $this->db->fetch_row($result)) {
      $linkedPage = $this->_navigation->getPageByID((int)$row['CIID']);
      $largeImage = $this->_hasLargeImage($row["BLImage"]);
      $imageTitles = $this->explode_content_image_titles("c_cb_box_biglink", $row["BLImageTitles"]);

      $this->tpl->load_tpl("content_site_cb_box_biglink", $tplPath);
      $this->tpl->parse_if('content_site_cb_box_biglink', 'zoom', $largeImage, array(
        'c_cb_box_biglink_zoom_link' => '#',
      ));
      $this->tpl->parse_if("content_site_cb_box_biglink", "box_biglink_image", $row["BLImage"], array('c_cb_box_biglink_image_src' => "../".$row["BLImage"]));
      $biglinks[] = array(
        "c_cb_box_biglink" => $this->tpl->parsereturn("content_site_cb_box_biglink", array_merge($imageTitles, array(
          "c_cb_box_biglink_title" => parseOutput($row["BLTitle"]),
          "c_cb_box_biglink_text" => nl2br(parseOutput($row["BLText"], 1)),
          "c_cb_box_biglink_link" => "#",
          "c_cb_box_biglink_link_title" => (!$linkedPage || !$linkedPage->isVisible()) ? '#' : $linkedPage->getUrl(),
          "c_cb_box_biglink_id" => $row["BLID"],
          "c_cb_box_biglink_position" => $position++,
          'c_cb_box_biglink_real_position' => $row['BLPosition'],
      ))),
      );
    }
    $this->db->free_result($result);

    return $biglinks;
  }

  /**
   * Reads all small links inside a box from the database and returns an array that can be used to parse the smalllink-loop inside the CB-Box template.
   *
   * @param integer $cbbid
   *        the ID of the CB-Box for which to get the small links
   * @return array
   *        contains, for each small link, an associative array with one element ("c_cb_box_smalllink")
   */
  private function previewGetSmallLinks($cbbid) {
    $smalllinks = array();
    $tplPath =  'content_types/ContentItem' . $this->_templateSuffix . '_Box_SmallLink.tpl';
    $position = 1;
    $result = $this->db->query(<<<SQL
SELECT SLID, SLTitle, SLPosition, CIID
FROM {$this->table_prefix}contentitem_cb_box_smalllink
JOIN {$this->table_prefix}contentitem ON SLLink = CIID
WHERE FK_CBBID = $cbbid
AND COALESCE(SLTitle, '') != ''
ORDER BY SLPosition
SQL
    );

    while ($row = $this->db->fetch_row($result)) {
      $linkedPage = $this->_navigation->getPageByID((int)$row['CIID']);
      $this->tpl->load_tpl("content_site_cb_box_smalllink", $tplPath);
      $smalllinks[] = array(
        "c_cb_box_smalllink" => $this->tpl->parsereturn("content_site_cb_box_smalllink", array(
          "c_cb_box_smalllink_title" => parseOutput($row["SLTitle"]),
          "c_cb_box_smalllink_link" => "#",
          "c_cb_box_smalllink_link_title" => (!$linkedPage || !$linkedPage->isVisible()) ? '#' : $linkedPage->getUrl(),
          "c_cb_box_smalllink_id" => $row["SLID"],
          "c_cb_box_smalllink_position" => $position++,
          'c_cb_box_smalllink_real_position' => $row['SLPosition'],
      )),
      );
    }
    $this->db->free_result($result);

    return $smalllinks;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content(){

    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,CBTitle,CBText1,CBText2,CBText3,CBImageTitles FROM ".$this->table_prefix."contentitem_cb cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)) {
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["CBTitle"];
      $class_content[$row["CIID"]]["c_title2"] = "";
      $class_content[$row["CIID"]]["c_title3"] = "";
      $class_content[$row["CIID"]]["c_text1"] = $row["CBText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["CBText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["CBText3"];
      $cb_image_titles = $this->explode_content_image_titles("cb", $row["CBImageTitles"]);
      $class_content[$row["CIID"]]["c_image_title1"] = $cb_image_titles["cb_image1_title"];
      $class_content[$row["CIID"]]["c_image_title2"] = "";
      $class_content[$row["CIID"]]["c_image_title3"] = "";
      $class_content[$row["CIID"]]["c_sub"] = array();
      $result_sub = $this->db->query("SELECT CBBID, CBBTitle, CBBText, CBBImageTitles FROM ".$this->table_prefix."contentitem_cb_box WHERE FK_CIID = ".$row["CIID"]." ORDER BY CBBPosition ASC");
      while ($row_sub = $this->db->fetch_row($result_sub)) {
        $cb_image_titles_sub = $this->explode_content_image_titles("cb", $row_sub["CBBImageTitles"]);
        $class_content[$row["CIID"]]["c_sub"][] = array(
          "cs_title"       => $row_sub["CBBTitle"],
          "cs_text"        => $row_sub["CBBText"],
          "cs_image_title" => $cb_image_titles_sub["cb_image1_title"],
        );

        $result_sub1 = $this->db->query("SELECT BLTitle, BLText, BLImageTitles FROM ".$this->table_prefix."contentitem_cb_box_biglink WHERE FK_CBBID = ".$row_sub["CBBID"]." ORDER BY BLPosition ASC");
        while ($row_sub1 = $this->db->fetch_row($result_sub1)) {
          $class_content[$row["CIID"]]["c_sub"][] = array(
            "cs_title"       => $row_sub1["BLTitle"],
            "cs_text"        => $row_sub1["BLText"],
            "cs_image_title" => $row_sub1["BLImageTitles"],
          );
        }
        $this->db->free_result($result_sub1);

        $result_sub2 = $this->db->query("SELECT SLTitle FROM ".$this->table_prefix."contentitem_cb_box_smalllink WHERE FK_CBBID = ".$row_sub["CBBID"]." ORDER BY SLPosition ASC");
        while ($row_sub2 = $this->db->fetch_row($result_sub2)) {
          $class_content[$row["CIID"]]["c_sub"][] = array(
            "cs_title"       => $row_sub2["SLTitle"],
            "cs_text"        => "",
            "cs_image_title" => "",
          );
        }
        $this->db->free_result($result_sub2);

      }
      $this->db->free_result($result_sub);
    }
    $this->db->free_result($result);

    return $class_content;
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[] = new ContentItemCB_Boxes($this->site_id,
        $this->page_id, $this->tpl, $this->db, $this->table_prefix, '', '',
        $this->_user, $this->session, $this->_navigation, $this);
  }
}
