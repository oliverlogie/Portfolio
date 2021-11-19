<?php

/**
 * Multimedia Library Module Class
 *
 * $LastChangedDate: 2019-11-04 07:30:27 +0100 (Mo, 04 Nov 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleMultimediaLibrary extends Module
{
  public static $subClasses = array(
      'category' => 'ModuleMultimediaLibraryCategory',  // prefix: mc
  );

  /**
   * Specifies the youtube video type constant
   *
   * @var string
   */
  const VIDEO_TYPE_YOUTUBE = 'youtube';

  const SHOW_TAB_VIDEO = 'video';
  const SHOW_TAB_IMAGE = 'image';
  const SHOW_TAB_DOC = 'document';

  /**
   * The database column prefix.
   *
   * @var string
   */
  protected $_dbColumnPrefix = 'M';

  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'ms';

  /**
   * The issuu object.
   *
   * @var Issuu
   */
  private $_issuu = null;

  /**
   * Last inserted issuu document id.
   *
   * @var int
   */
  private $_iDId = 0;

  /**
   * The category id.
   *
   * @var int
   */
  private $_catId = 0;

  /**
   * @var boolean
   */
  private $_video1DataError = false;

  /**
   * @var PositionHelper
   */
  private $_positionHelper = null;

  /**
   * @var int
   */
  private $_listItemWithTimingOpened = 0;

  /**
   * Current item row, do not access directly, use its accessor method instead.
   *
   * @see ModuleMultimediaLibrary::_cachedItemRow()
   * @var array
   */
  private $_cachedItemRow;

  /**
   * @see ModuleMultimediaLibrary::_getPageNavigation()
   * @var PageNavigation
   */
  private $_pageNavigation;

  /**
   * contains the available filter criteria including SQL WHERE clauses
   * the format is "name" => ( "DBColumn1 = '", "' OR DBColumn2 LIKE '%", "%'")
   * the filter expression is inserted between the array elements
   * @var array
   */
  private $_filters =  array(
    "title" => array("MTitle1 LIKE '%", "%'"),
  );

  /**
   * @see ModuleMultimediaLibrary::_getCurrentFilter()
   * @var array
   */
  private $_currentFilter;

  public function show_innercontent()
  {
    global $_LANG;

    $request = new Input(Input::SOURCE_REQUEST);
    $this->_catId = $request->readInt('cat_id');
    $this->_issuu = new Issuu($this->db, $this->table_prefix, $this->site_id);

    // Perform create/update/move/delete of a side box if necessary
    $this->_createMedialibraryBox();
    $this->_updateMedialibraryBox();
    $this->_moveMedialibraryBox();
    $this->_deleteMedialibraryBox();
    $this->_changeMediaLibraryBoxActivation();
    $this->_updateMediaLibraryBoxTiming();

    // Delete issuu document
    if ($request->readString('delete_issuu_document')) {
      $result = $this->_deleteDocument($this->_getDocumentName(
                    $request->readString('delete_issuu_document')));
      if ($result) {
        $this->setMessage(Message::createSuccess($_LANG['ms_message_document_delete_success']));
      }
    }

    // Delete a side box image.
    $this->_deleteMedialibraryBoxImage();

    if (isset($this->action[0]) && $this->action[0]) {
      return $this->_showForm();
    } else {
      return $this->_showList();
    }
  }

  /**
   * Deletes an image.
   * @see Module::delete_content_image()
   */
  protected function delete_content_image($module, $table, $key, $col, $number) {
    global $_LANG;
    $image = $this->db->GetOne("SELECT $col$number FROM {$this->table_prefix}module_$table WHERE $key = $this->item_id");
    if ($image) {
      self::_deleteImageFiles($image);
      $this->db->query("UPDATE {$this->table_prefix}module_$table SET $col$number = '' WHERE $key = $this->item_id");
    }

    $this->setMessage(Message::createSuccess($_LANG['ms_message_delete_image_success']));
  }

  /**
   * Returns delete data (label, question label, link) to delete
   * an image.
   * @see Module::get_delete_image()
   */
  protected function get_delete_image($module,$prefix,$image_number) {
    global $_LANG;

    $delete_link = "index.php?action=mod_".$module."&amp;action2=main;edit&amp;cat_id=".$this->_catId."&amp;site=".$this->site_id."&amp;page=".$this->item_id."&amp;dimg=".$image_number;
    $delete_data = array( $prefix.'_delete_image_label' => (isset($_LANG[$prefix."_delete_image_label"]) ? $_LANG[$prefix."_delete_image_label"] : $_LANG["global_delete_image_label"]),
                          $prefix.'_delete_image_question_label' => (isset($_LANG[$prefix."_delete_image_question_label"]) ? $_LANG[$prefix."_delete_image_question_label"] : $_LANG["global_delete_image_question_label"]),
                          $prefix.'_delete_image'.$image_number.'_link' => $delete_link );

    return $delete_data;
  }

  protected function _getModuleUrlParts()
  {
    return array_merge(parent::_getModuleUrlParts(), array(
        'cat_id' => $this->_catId,
    ));
  }

  protected function _getContentActionBoxes($buttons = null)
  {
    global $_LANG;

    if ($buttons === null) {
      $buttons = $this->_getContentActionBoxButtons();
    }

    $row = $this->_cachedItemRow();
    $dateFrom = isset($row['MShowFromDateTime']) ? DateHandler::getValidDateTime($row['MShowFromDateTime'], 'd.m.Y') : '';
    $dateUntil = isset($row['MShowUntilDateTime']) ? DateHandler::getValidDateTime($row['MShowUntilDateTime'], 'd.m.Y') : '';
    $timeFrom = isset($row['MShowFromDateTime']) ? DateHandler::getValidDateTime($row['MShowFromDateTime'], 'H:i') : '';
    $timeUntil = isset($row['MShowUntilDateTime']) ? DateHandler::getValidDateTime($row['MShowUntilDateTime'], 'H:i') : '';

    $tplName = 'module_' . $this->_prefix . '_action_boxes';
    $this->tpl->load_tpl($tplName, 'modules/ModuleMultimediaLibrary_action_box.tpl');
    $this->tpl->parse_if($tplName, 'timing_form_available', $this->item_id && ConfigHelper::get('ms_timing_activated'), array(
      'ms_id' => $this->item_id,
      'date_from' => $dateFrom,
      'time_from' => $timeFrom,
      'date_until' => $dateUntil,
      'time_until' => $timeUntil,
    ));
    $moduleActionBoxes = $this->tpl->parsereturn($tplName, array(
      'module_actions_buttons' => $buttons,
      'module_actions_label'   => $this->_langVar('actions_label'),
    ));

    return $moduleActionBoxes;
  }

  /**
   * Creates a side box.
   */
  private function _createMedialibraryBox()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new') {
      return;
    }

    $title1 = $post->readString('ms_title1', Input::FILTER_PLAIN);
    $title2 = $post->readString('ms_title2', Input::FILTER_PLAIN);
    $title3 = $post->readString('ms_title3', Input::FILTER_PLAIN);
    $text1 = $post->readString('ms_text1', Input::FILTER_CONTENT_TEXT);
    $text2 = $post->readString('ms_text2', Input::FILTER_CONTENT_TEXT);
    $text3 = $post->readString('ms_text3', Input::FILTER_CONTENT_TEXT);
    $imageTitles = $post->readImageTitles('ms_image_title');
    $randomlyShow = $this->_isShowRandomlyAvailable() ? (int)$post->exists('ms_randomly_show') : 0;
    list($link, $linkID) = $post->readContentItemLink('ms_link');
    $extLink = $post->readString('ms_url', Input::FILTER_PLAIN);

    // validate url protocol
    if ($extLink)
    {
      $valid = false;
      $protocols = $this->_configHelper->getVar('url_protocols', 'ms');

      foreach ($protocols as $protocol) {
        if (mb_substr($extLink, 0, mb_strlen($protocol)) === $protocol) {
          $valid = true;
          break;
        }
      }

      if (!$valid) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['ms_message_invalid_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    $uploadImage1 = isset($_FILES['ms_image1']) && $_FILES['ms_image1']['size'];
    $uploadImage2 = isset($_FILES['ms_image2']) && $_FILES['ms_image2']['size'];
    $uploadImage3 = isset($_FILES['ms_image3']) && $_FILES['ms_image3']['size'];
    $uploadImage4 = isset($_FILES['ms_image4']) && $_FILES['ms_image4']['size'];
    $uploadImage5 = isset($_FILES['ms_image5']) && $_FILES['ms_image5']['size'];
    $uploadImage6 = isset($_FILES['ms_image6']) && $_FILES['ms_image6']['size'];
    $uploadDocument = isset($_FILES['ms_issuu_document_file']) &&
                      $_FILES['ms_issuu_document_file']['size'];

    $videoType1 = $post->readString('ms_video_type1', Input::FILTER_NONE);
    $videoData1 = $post->readString('ms_video_data1', Input::FILTER_NONE);
    $video1 = $this->_parseVideoData($videoType1, $videoData1, 1);
    $videoType2 = $post->readString('ms_video_type2', Input::FILTER_NONE);
    $videoData2 = $post->readString('ms_video_data2', Input::FILTER_NONE);
    $video2 = $this->_parseVideoData($videoType2, $videoData2, 2);
    $videoType3 = $post->readString('ms_video_type3', Input::FILTER_NONE);
    $videoData3 = $post->readString('ms_video_data3', Input::FILTER_NONE);
    $video3 = $this->_parseVideoData($videoType3, $videoData3, 3);

    if (   !$title1 && !$title2 && !$title3
        && !$text1 && !$text2 && !$text3
        && !$uploadImage1 && !$uploadImage2 && !$uploadImage3
        && !$uploadImage4 && !$uploadImage5 && !$uploadImage6
        && empty($video1) && empty($video2) && empty($video3)
        && !$uploadDocument
    ) {
      $this->setMessage(Message::createFailure($_LANG['ms_message_create_failure']));
    }

    if ($this->_getMessage()) {
      return;
    }

    // Video 1
    if ($video1)
    {
      $vData = $this->_getVideoData($videoType1, $video1[0]);
      if (is_array($vData))
      {
        $videoDuration1 = $vData['duration'];
        $videoThumbnail1 = $vData['thumbnail'];
        $videoPublishedDate1 = $vData['published_date'];
      }
      else {
        $videoPublishedDate1 = $videoThumbnail1 = $videoDuration1 = '';
      }
      $video1 = serialize($video1);
    }
    else {
      $videoPublishedDate1 = $videoThumbnail1 = $videoDuration1 = $videoType1 = $video1 = '';
    }

    // Video 2
    if ($video2)
    {
      $vData = $this->_getVideoData($videoType2, $video2[0]);
      if (is_array($vData))
      {
        $videoDuration2 = $vData['duration'];
        $videoThumbnail2 = $vData['thumbnail'];
        $videoPublishedDate2 = $vData['published_date'];
      }
      else {
        $videoPublishedDate2 = $videoThumbnail2 = $videoDuration2 = '';
      }
      $video2 = serialize($video2);
    }
    else {
       $videoPublishedDate2 = $videoThumbnail2 = $videoDuration2 = $videoType2 = $video2 = '';
    }

    // Video 3
    if ($video3)
    {
      $vData = $this->_getVideoData($videoType3, $video3[0]);
      if (is_array($vData))
      {
        $videoDuration3 = $vData['duration'];
        $videoThumbnail3 = $vData['thumbnail'];
        $videoPublishedDate3 = $vData['published_date'];
      }
      else {
        $videoPublishedDate3 = $videoThumbnail3 = $videoDuration3 = '';
      }
      $video3 = serialize($video3);
    }
    else {
       $videoPublishedDate3 = $videoThumbnail3 = $videoDuration3 = $videoType3 = $video3 = '';
    }

    // return, if a message was set during parsing video data
    if ($this->_getMessage()) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary",
                                         'MID', 'MPosition', 'FK_SID', $this->site_id);
    $position = $positionHelper->getHighestPosition() + 1;

    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO {$this->table_prefix}module_medialibrary "
         . '(MTitle1, MTitle2, MTitle3, MText1, MText2, MText3, MRandomlyShow, '
         . ' MVideo1, MVideoType1, MVideoDuration1, MVideoThumbnail1, MVideoPublishedDate1, '
         . ' MVideo2, MVideoType2, MVideoDuration2, MVideoThumbnail2, MVideoPublishedDate2, '
         . ' MVideo3, MVideoType3, MVideoDuration3, MVideoThumbnail3, MVideoPublishedDate3, '
         . ' MImageTitles, MPosition, MUrl, MCreateDateTime, MChangeDateTime, FK_CIID, FK_SID) '
         . "VALUES ('{$this->db->escape($title1)}', '{$this->db->escape($title2)}', "
         . "        '{$this->db->escape($title3)}', '{$this->db->escape($text1)}', "
         . "        '{$this->db->escape($text2)}', '{$this->db->escape($text3)}', "
         . "        '$randomlyShow', "
         . "        '{$this->db->escape($video1)}', '{$this->db->escape($videoType1)}', "
         .          (($videoDuration1) ? "'{$this->db->escape($videoDuration1)}'" : "NULL").", "
         . "        '{$this->db->escape($videoThumbnail1)}', "
         .          (($videoPublishedDate1) ? "'{$this->db->escape($videoPublishedDate1)}'" : "NULL").", "
         . "        '{$this->db->escape($video2)}', '{$this->db->escape($videoType2)}', "
         .          (($videoDuration2) ? "'{$this->db->escape($videoDuration2)}'" : "NULL").", "
         . "        '{$this->db->escape($videoThumbnail2)}', "
         .          (($videoPublishedDate2) ? "'{$this->db->escape($videoPublishedDate2)}'" : "NULL").", "
         . "        '{$this->db->escape($video3)}', '{$this->db->escape($videoType3)}', "
         .          (($videoDuration3) ? "'{$this->db->escape($videoDuration3)}'" : "NULL").", "
         . "        '{$this->db->escape($videoThumbnail3)}', "
         .          (($videoPublishedDate3) ? "'{$this->db->escape($videoPublishedDate3)}'" : "NULL").", "
         . "        '$imageTitles', $position, '{$this->db->escape($extLink)}', "
         . "        '$now', '$now', $linkID, $this->site_id "
         . " ) ";
    $this->db->query($sql);

    // Set the item ID to the inserted side box so that the _storeImage
    // method can assign the correct file names to the image files.
    $this->item_id = $this->db->insert_id();

    $image1 = isset($_FILES['ms_image1']) ? $this->_storeImage($_FILES['ms_image1'], null, 'ms', 1) : '';
    $image2 = isset($_FILES['ms_image2']) ? $this->_storeImage($_FILES['ms_image2'], null, 'ms', 2) : '';
    $image3 = isset($_FILES['ms_image3']) ? $this->_storeImage($_FILES['ms_image3'], null, 'ms', 3) : '';
    $image4 = isset($_FILES['ms_image4']) ? $this->_storeImage($_FILES['ms_image4'], null, 'ms', 4) : '';
    $image5 = isset($_FILES['ms_image5']) ? $this->_storeImage($_FILES['ms_image5'], null, 'ms', 5) : '';
    $image6 = isset($_FILES['ms_image6']) ? $this->_storeImage($_FILES['ms_image6'], null, 'ms', 6) : '';

    // Process attached campaign
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $cgAId = $cgAttached->process(array(
        'm_cg'             => $post->readInt('m_cg'),
        'm_cg_recipient'   => $post->readString('m_cg_recipient', Input::FILTER_PLAIN),
        'm_cg_data_origin' => $post->readString('m_cg_data_origin', Input::FILTER_PLAIN),
    ));

    $sql = "UPDATE {$this->table_prefix}module_medialibrary "
         . "SET MImage1 = '$image1', "
         . "    MImage2 = '$image2', "
         . "    MImage3 = '$image3', "
         . "    MImage4 = '$image4', "
         . "    MImage5 = '$image5', "
         . "    MImage6 = '$image6', "
         . "    FK_CGAID = '$cgAId' "
         . "WHERE MID = $this->item_id ";
    $result = $this->db->query($sql);

    // Category assignment
    $categories = $post->readArrayIntToInt('ms_category');
    $categoryValues = array();
    foreach ($categories as $key => $value) {
      $catPositionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category_assignment",
                                           'MCAID', 'MCAPosition', 'FK_MCID', $value);
      $pos = $catPositionHelper->getHighestPosition() + 1;
      $categoryValues[] = " ('$this->item_id', '".$this->db->escape($value)."', $pos) ";
    }
    if ($categoryValues) {
      $sql = " INSERT INTO {$this->table_prefix}module_medialibrary_category_assignment "
           . ' (FK_MID, FK_MCID, MCAPosition) '
           . " VALUES ".implode(',', $categoryValues)." ";
      $this->db->query($sql);
    }

    // Move box to first position if configuration available.
    if (ConfigHelper::get('insert_box_at_top_position', $this->_prefix, $this->site_id)) {
      // Move box itself.
      $positionHelper->update()->move($this->item_id, 1);

      // Move category assignments to first position.
      foreach ($categories as $key => $value) {
        $sql = " SELECT MCAID "
             . " FROM {$this->table_prefix}module_medialibrary_category_assignment "
             . " WHERE FK_MID = '".$this->db->escape($this->item_id)."'"
             . "   AND FK_MCID = '".$this->db->escape($value)."' ";
        $mcaId = $this->db->GetOne($sql);
        $catPositionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category_assignment",
                                             'MCAID', 'MCAPosition', 'FK_MCID', $value);
        $catPositionHelper->update()->move($mcaId, 1);
      }
    }

    // Issuu document upload
    $this->_handleDocumentUpload();

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($_LANG['ms_message_create_success']));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
          Message::createSuccess($_LANG['ms_message_create_success']));
    }
  }

  /**
   * Deletes a side box if the GET parameter deletemedialibraryID is set.
   */
  private function _deleteMedialibraryBox()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $ID = $get->readInt('deletemedialibraryID');
    if (!$ID) {
      return;
    }

    $documentName = $this->_getDocumentName();
    if ($documentName && !$this->_deleteDocument($documentName)) {
      $this->setMessage(Message::createFailure(
          $_LANG[$this->_prefix . '_message_document_delete_error']));
      return;
    }

    // Delete attached campaigns
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $sql = " SELECT FK_CGAID "
         . " FROM {$this->table_prefix}module_medialibrary "
         . " WHERE MID = $ID ";
    $cgAttached->id = $this->db->GetOne($sql);
    $cgAttached->delete();

    // Delete images.
    $sql = 'SELECT MImage1, MImage2, MImage3, MImage4, MImage5, MImage6 '
         . "FROM {$this->table_prefix}module_medialibrary "
         . "WHERE MID = $ID ";
    $images= $this->db->GetRow($sql);
    self::_deleteImageFiles($images);

    // Delete the side boxes assignments.
    $sql = "DELETE FROM {$this->table_prefix}module_medialibrary_assignment "
         . "WHERE FK_MID = $ID ";
    $result = $this->db->query($sql);

    // Move category assignment to last position before deleting it.
    $sql = " SELECT MCAID, FK_MCID "
         . " FROM {$this->table_prefix}module_medialibrary_category_assignment "
         . " WHERE FK_MID = $ID ";
    $categories = $this->db->GetAssoc($sql);
    foreach ($categories as $mcaId => $categoryId) {
      $catPositionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category_assignment",
                                           'MCAID', 'MCAPosition', 'FK_MCID', $categoryId);
      $catPositionHelper->move($mcaId, $catPositionHelper->getHighestPosition());
    }

    // Delete old category assignments
    $sql = " DELETE FROM {$this->table_prefix}module_medialibrary_category_assignment "
         . " WHERE FK_MID = $ID ";
    $this->db->query($sql);

    // move item to last position before deleting it
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary",
                                         'MID', 'MPosition',
                                         'FK_SID', $this->site_id);
    $positionHelper->move($ID, $positionHelper->getHighestPosition());

    // Delete side box.
    $sql = "DELETE FROM {$this->table_prefix}module_medialibrary "
         . "WHERE MID = $ID ";
    $result = $this->db->query($sql);

    if ($result)
    {
      $this->setMessage(Message::createSuccess($_LANG['ms_message_delete_item_success']));
      return true;
    }

    return false;
  }

  /**
   * Deletes a side box image if the GET parameter dimg is set.
   */
  private function _deleteMedialibraryBoxImage()
  {
    $get = new Input(Input::SOURCE_GET);

    $imageNumber = $get->readInt('dimg');
    if (!$imageNumber) {
      return;
    }

    $this->delete_content_image('medialibrary', 'medialibrary', 'MID', 'MImage', $imageNumber);
  }

  /**
   * Returns a css string that shows or hides an element
   *
   * @param bool $visible
   *        True if the element should be shown, false otherwise
   * @return string
   *         Either "visibility: visible; display: block;" or "visibility: hidden; display: none;"
   */
  private function _getCssVisibility($visible) {
    return $visible ? "visibility: visible; display: block;" : "visibility: hidden; display: none;";
  }

  /**
   * Gets the video duration of given video.
   * To do this a configured video api (videos_api) is required
   * of the given video type.
   *
   * @param string $videoType
   *        The type of the video.
   * @param string $videoId
   *        The video id.
   * @return string|null
   *         The video duration (H:i:s) or null on failure.
   */
  private function _getVideoData($videoType, $videoId)
  {
    // return if there is no type or video id
    if (!$videoType || $videoType == 'none' || !$videoId) {
      return null;
    }

    // return, if there is no api uri
    $types = ConfigHelper::get('ms_video_types');
    if (!isset($types[$videoType]['videos_api'])) {
      return null;
    }

    // read api uri from configuration
    $videosApiUri = $types[$videoType]['videos_api'];
    // read video data into SimpleXML object
    $data = @simplexml_load_file($videosApiUri.'/'.$videoId);
    // return, if SimpleXMLElement object could not be created
    if ($data === false) {
      return null;
    }
    switch($videoType)
    {
      case self::VIDEO_TYPE_YOUTUBE:
        $media = $data->children('http://search.yahoo.com/mrss/');
        $yt = $media->children('http://gdata.youtube.com/schemas/2007');
        $dAttrs = $yt->duration->attributes();
        $thAttrs = $media->group->thumbnail[0]->attributes();
        return array(
          'duration'  => (int) $dAttrs['seconds'],
          'thumbnail' => mb_substr($thAttrs['url'], 0, -5),
          'published_date' => date('Y-m-d H:i:s', ContentBase::strToTime($data->published)),
        );
        break;
      default:
        return null;
    }
  }

  /**
   * Moves a side box if:
   * - no category is selected
   * - the GET parameters moveID and moveTo are set
   * The sidebox position can only be defined globally ( = category independent )
   */
  private function _moveMedialibraryBox()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $moveID = $get->readInt('moveID');
    $moveTo = $get->readInt('moveTo');

    if (!$moveID || !$moveTo) {
      return;
    }

    if ($this->_catId) {
      $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category_assignment",
                                           'FK_MID', 'MCAPosition', 'FK_MCID', $this->_catId);
    }
    else {
      $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary",
                                           'MID', 'MPosition', 'FK_SID', $this->site_id);
    }
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ms_message_move_success']));
    }
  }

  /**
   * Processes the video data according to the video type and returns the VideoID as an array
   *
   * @param string $videoType
   *        The name of the video type (i.e. "youtube")
   * @param string $videoData
   *        The video data that was entered by the user
   * @param int $videoNumber
   * @return array
   *         Vontains the VideoID or an empty array if the data couldn't be processed
   */
  private function _parseVideoData($videoType, $videoData, $videoNumber) {
    global $_LANG;

    $videoID = array();

    if ($videoType && $videoData && $videoType != "none") {
      $types = ConfigHelper::get('ms_video_types');
      $regex = $types[$videoType]["data_regex"];

      preg_match($regex, $videoData, $videoID);

      // remove the first element in $videoID because it contains the text that matched the full pattern,
      // but we only want the texts that matched the sub-patterns
      array_shift($videoID);

      if (!$videoID) {
        if ($videoNumber == 1) {
          $this->_video1DataError = true;
        }
        $this->setMessage(Message::createFailure($_LANG["ms_message_invalid_video_data"]));
      }
    }
    else if ($videoType && !$videoData && $videoType != "none") {
      if ($videoNumber == 1) {
        $this->_video1DataError = true;
      }
      $this->setMessage(Message::createFailure($_LANG["ms_message_no_video_data"]));
    }
    else if ($videoType == "none" && $videoData) {
      if ($videoNumber == 1) {
        $this->_video1DataError = true;
      }
      $this->setMessage(Message::createFailure($_LANG["ms_message_no_video_type"]));
    }

    return $videoID;
  }

  /**
   * Gets hours, minutes and seconds (H:i:s) string of given seconds.
   * @see http://www.laughing-buddha.net/php/lib/sec2hms/
   * Same function exists in frontend class.ContentItemMB.php
   *
   * @param int $sec
   *        The seconds.
   * @param boolean $padHours
   *        Set to true to add a leading zero for less than 10 hours.
   * @return string
   *         Hours, minutes and seconds (hh:mm:ss)
   */
  private function _sec2hms($sec, $padHours = false)
  {
    // start with a blank string
    $hms = '';

    // do the hours first: there are 3600 seconds in an hour, so if we divide
    // the total number of seconds by 3600 and throw away the remainder, we're
    // left with the number of hours in those seconds
    $hours = intval(intval($sec) / 3600);

    // add hours to $hms (with a leading 0 if asked for)
    $hms .= ($padHours) ? str_pad($hours, 2, '0', STR_PAD_LEFT). ":" : $hours. ":";

    // dividing the total seconds by 60 will give us the number of minutes
    // in total, but we're interested in *minutes past the hour* and to get
    // this, we have to divide by 60 again and then use the remainder
    $minutes = intval(($sec / 60) % 60);

    // add minutes to $hms (with a leading 0 if needed)
    $hms .= str_pad($minutes, 2, '0', STR_PAD_LEFT). ":";

    // seconds past the minute are found by dividing the total number of seconds
    // by 60 and using the remainder
    $seconds = intval($sec % 60);

    // add seconds to $hms (with a leading 0 if needed)
    $hms .= str_pad($seconds, 2, '0', STR_PAD_LEFT);

    // done!
    return $hms;
  }

  /**
   * Shows the form for creating or editing side boxes.
   */
  private function _showForm()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);

    $categories = array();
    if ($this->item_id) {
      $sql = " SELECT FK_MCID "
           . " FROM {$this->table_prefix}module_medialibrary_category_assignment "
           . " WHERE FK_MID = $this->item_id ";
      $categories = $this->db->GetCol($sql);
    }

    $mlCategory = new MedialibraryCategory($this->db, $this->table_prefix);
    $mlCategory->siteId = $this->site_id;
    $condition = array(
      'where' => 'FK_SID = '.$this->site_id,
      'order' => 'MCPosition ASC',
    );
    $mlCategories = $mlCategory->readMedialibraryCategories($condition);
    $categoryItems = array();
    foreach ($mlCategories as $category) {
      $categoryItems[] = array(
       'ms_category_checked'  => (in_array($category->id, $categories) || (!$this->item_id && $this->_catId == $category->id)) ? 'checked="checked"' : '',
       'ms_category_id'       => $category->id,
       'ms_category_title'    => parseOutput($category->title),
      );
    }

    $types = ConfigHelper::get('ms_video_types');
    $availableTypes = ConfigHelper::get('ms_video_types_available');
    $videoType1 = $videoType2 = $videoType3 = '';
    $video1 = $video2 = $video3 = array();
    $videoData1 = $videoData2 = $videoData3 = '';
    $videoUrl1 = $videoUrl2 = $videoUrl3 = '';
    $videoDuration1 = $videoDuration2 = $videoDuration3 = null;
    $showTab1 = $showTab2 = $showTab3 = $showTab4 = $showTab5 = $showTab6 = self::SHOW_TAB_IMAGE;

    if ($this->_isActionImageUpload(1)) {
      $showTab1 = self::SHOW_TAB_IMAGE;
    }
    else if ($this->_isActionDocumentUpload()) {
      $showTab1 = self::SHOW_TAB_DOC;
    }
    else if ($this->_video1DataError) {
      $showTab1 = self::SHOW_TAB_VIDEO;
    }

    // we can not display tabs unavailable within configuration, we change
    // the variable to an available tab
    $availableTabs = ConfigHelper::get('ms_available_tabs');
    if (!in_array($showTab1, $availableTabs)) {
      $showTab1 = $availableTabs[0];
    }

    $imageSource1 = $imageSource2 = $imageSource3 = $imageSource4 = $imageSource5 = $imageSource6 = '';
    $imageTitles = $this->explode_content_image_titles('ms', array());

    $row = array();
    // edit medialibrary -> load data
    if ($this->item_id)
    {
      $row = $this->_cachedItemRow();

      $title1 = $row['MTitle1'];
      $title2 = $row['MTitle2'];
      $title3 = $row['MTitle3'];
      $text1 = $row['MText1'];
      $text2 = $row['MText2'];
      $text3 = $row['MText3'];
      $imageSource1 = $row['MImage1'];
      $imageSource2 = $row['MImage2'];
      $imageSource3 = $row['MImage3'];
      $imageSource4 = $row['MImage4'];
      $imageSource5 = $row['MImage5'];
      $imageSource6 = $row['MImage6'];
      $extLink = $row['MUrl'];
      $randomlyShow = $row['MRandomlyShow'] ? ' checked="checked"' : '';
      $cgAttached = $cgAttached->readCampaignAttachedById($row['FK_CGAID']);
      $imageTitles = $this->explode_content_image_titles('ms', $row['MImageTitles']);
      // Video 1
      if ($row["MVideoType1"] && $row["MVideo1"] && isset($types[$row["MVideoType1"]])) {
        $videoType1 = $row["MVideoType1"];
        $video1 = unserialize($row["MVideo1"]);
        $videoData1 = vsprintf($types[$videoType1]["data_format"], $video1);
        $videoUrl1 = vsprintf($types[$videoType1]["url"], $video1);
        $videoDuration1 = $row["MVideoDuration1"];
      }

      // Video 2
      if ($row["MVideoType2"] && $row["MVideo2"] && isset($types[$row["MVideoType2"]])) {
        $videoType2 = $row["MVideoType2"];
        $video2 = unserialize($row["MVideo2"]);
        $videoData2 = vsprintf($types[$videoType2]["data_format"], $video2);
        $videoUrl2 = vsprintf($types[$videoType2]["url"], $video2);
        $videoDuration2 = $row["MVideoDuration2"];
      }

      // Video 3
      if ($row["MVideoType3"] && $row["MVideo3"] && isset($types[$row["MVideoType3"]])) {
        $videoType3 = $row["MVideoType3"];
        $video3 = unserialize($row["MVideo3"]);
        $videoData3 = vsprintf($types[$videoType3]["data_format"], $video3);
        $videoUrl3 = vsprintf($types[$videoType3]["url"], $video3);
        $videoDuration3 = $row["MVideoDuration3"];
      }
      $pageParameter = array(
        'action2' => 'main;edit',
        'cat_id'  => $this->_catId,
      );

      if (($row['IDState'] == Issuu::STATE_PROCESSING || !$row['IDState']) && $row['IDID']) {
        $state = $this->_issuu->determineState($row['IDDocumentId']);
        $this->_issuu->updateState($row['IDID'], $state);
        $row['IDState'] = $state;
      }
      $issuuDocument = array(
        'ms_issuu_document_delete_link' => "index.php?action=mod_medialibrary&amp;action2=main;edit&amp;cat_id={$this->_catId}&amp;page={$this->item_id}&amp;site={$this->site_id}&amp;delete_issuu_document=".$row['IDDocumentId'],
        'ms_issuu_document_name'        => $row['IDName'],
        'ms_issuu_document_state'       => $row['IDState'],
        'ms_issuu_document_title'       => $row['IDTitle'],
        'ms_issuu_document_user'        => $row['IDUsername'],
      );

      $function = 'edit';
    }
    // new medialibrary
    else
    {
      $title1 = $post->readString('ms_title1', Input::FILTER_PLAIN);
      $title2 = $post->readString('ms_title2', Input::FILTER_PLAIN);
      $title3 = $post->readString('ms_title3', Input::FILTER_PLAIN);
      $text1 = $post->readString('ms_text1', Input::FILTER_CONTENT_TEXT);
      $text2 = $post->readString('ms_text2', Input::FILTER_CONTENT_TEXT);
      $text3 = $post->readString('ms_text3', Input::FILTER_CONTENT_TEXT);
      $extLink = $post->readString('ms_url', Input::FILTER_PLAIN);
      $imageTitles['ms_image1_title_plain'] = $post->readString('ms_image_title[1]', Input::FILTER_CONTENT_TEXT);
      $imageTitles['ms_image2_title_plain'] = $post->readString('ms_image_title[2]', Input::FILTER_CONTENT_TEXT);
      $imageTitles['ms_image3_title_plain'] = $post->readString('ms_image_title[3]', Input::FILTER_CONTENT_TEXT);
      $imageTitles['ms_image4_title_plain'] = $post->readString('ms_image_title[4]', Input::FILTER_CONTENT_TEXT);
      $imageTitles['ms_image5_title_plain'] = $post->readString('ms_image_title[5]', Input::FILTER_CONTENT_TEXT);
      $imageTitles['ms_image6_title_plain'] = $post->readString('ms_image_title[6]', Input::FILTER_CONTENT_TEXT);
      $randomlyShow = ConfigHelper::get('ms_random_box_for_mixed_category_randomly_shown_by_default', '', $this->site_id) ? 'checked="checked"' : '';
      $function = 'new';
      $pageParameter = array();
      $videoType1 = $post->readString('ms_video_type1');
      $videoType2 = $post->readString('ms_video_type2');
      $videoType3 = $post->readString('ms_video_type3');
      $cgAttached->parentId = $post->readInt('m_cg');
      $cgAttached->dataOrigin = $post->readString('m_cg_data_origin', Input::FILTER_PLAIN);
      $cgAttached->recipient = $post->readString('m_cg_recipient', Input::FILTER_PLAIN);

      $issuuDocument = array(
        'ms_issuu_document_state' => '',
      );
    }

    // create the $videoTypesJs array which contains labels and descriptions for all available video types
    // also create the $videoTypesX arrays which contain the names and labels for the individual selects
    $videoTypesJs = array();
    $videoTypesJs[] = array(
      'ms_video_type_name' => 'none',
      'ms_video_type_data_label' => $_LANG['ms_video_type_none_data_label'],
      'ms_video_type_data_descr' => $_LANG['ms_video_type_none_data_descr'],
    );
    $videoTypes1 = array();
    $videoTypes1[] = array(
      'ms_video_type_name' => 'none',
      'ms_video_type_selected' => !$videoType1 ? ' selected="selected"' : '',
      'ms_video_type_label' => parseOutput($_LANG['ms_video_type_none_label']),
    );
    $videoTypes2[] = array(
      'ms_video_type_name' => 'none',
      'ms_video_type_selected' => !$videoType2 ? ' selected="selected"' : '',
      'ms_video_type_label' => parseOutput($_LANG['ms_video_type_none_label']),
    );
    $videoTypes3[] = array(
      'ms_video_type_name' => 'none',
      'ms_video_type_selected' => !$videoType3 ? ' selected="selected"' : '',
      'ms_video_type_label' => parseOutput($_LANG['ms_video_type_none_label']),
    );
    foreach ($availableTypes as $videoTypeName)
    {
      $videoTypesJs[] = array(
        'ms_video_type_name' => $videoTypeName,
        'ms_video_type_data_label' => $_LANG['ms_video_type_'.$videoTypeName.'_data_label'],
        'ms_video_type_data_descr' => $_LANG['ms_video_type_'.$videoTypeName.'_data_descr'],
      );
      $videoTypes1[] = array(
        'ms_video_type_name' => $videoTypeName,
        'ms_video_type_selected' => $videoType1 == $videoTypeName ? ' selected="selected"' : '',
        'ms_video_type_label' => parseOutput($_LANG['ms_video_type_'.$videoTypeName.'_label']),
      );
      $videoTypes2[] = array(
        'ms_video_type_name' => $videoTypeName,
        'ms_video_type_selected' => $videoType2 == $videoTypeName ? ' selected="selected"' : '',
        'ms_video_type_label' => parseOutput($_LANG['ms_video_type_'.$videoTypeName.'_label']),
      );
      $videoTypes3[] = array(
        'ms_video_type_name' => $videoTypeName,
        'ms_video_type_selected' => $videoType3 == $videoTypeName ? ' selected="selected"' : '',
        'ms_video_type_label' => parseOutput($_LANG['ms_video_type_'.$videoTypeName.'_label']),
      );
    }

    $action = "index.php?action=mod_medialibrary&amp;action2=main;$function&amp;site=$this->site_id&amp;cat_id=$this->_catId&amp;page=$this->item_id";
    $hiddenFields = '<input type="hidden" name="action" value="mod_medialibrary" />'
                  . '<input type="hidden" name="action2" value="main;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="cat_id" value="' . $this->_catId . '" />';

    $autoCompleteUrl = 'index.php?action=mod_response_medialibrary&site=' . $this->site_id
                     . '&request=ContentItemAutoComplete';

    $this->tpl->load_tpl('content_medialibrary', 'modules/ModuleMultimediaLibrary.tpl');
    $this->tpl->parse_loop('content_medialibrary', $videoTypesJs, 'video_types_js');
    $this->tpl->parse_loop('content_medialibrary', $videoTypes1, 'video_types1');
    $this->tpl->parse_loop('content_medialibrary', $videoTypes2, 'video_types2');
    $this->tpl->parse_loop('content_medialibrary', $videoTypes3, 'video_types3');
    $this->tpl->parse_loop('content_medialibrary', $categoryItems, 'ms_categories');
    $this->tpl->parse_if("content_medialibrary", "delete_video1", $video1);
    $this->tpl->parse_if("content_medialibrary", "delete_video2", $video2);
    $this->tpl->parse_if("content_medialibrary", "delete_video3", $video3);
    $this->tpl->parse_if('content_medialibrary', 'video_duration1', $videoDuration1, array(
      'ms_video_duration1' => $this->_sec2hms($videoDuration1, true),
    ));
    $this->tpl->parse_if('content_medialibrary', 'video_duration2', $videoDuration2, array(
      'ms_video_duration2' => $this->_sec2hms($videoDuration2, true),
    ));
    $this->tpl->parse_if('content_medialibrary', 'video_duration3', $videoDuration3, array(
      'ms_video_duration3' => $this->_sec2hms($videoDuration3, true),
    ));
    $this->tpl->parse_if('content_medialibrary', 'video1', $video1, array(
      "ms_video1" => parseOutput(implode(", ", $video1)),
    ));
    $this->tpl->parse_if('content_medialibrary', 'video2', $video2, array(
      "ms_video2" => parseOutput(implode(", ", $video2)),
    ));
    $this->tpl->parse_if('content_medialibrary', 'video3', $video3, array(
      "ms_video3" => parseOutput(implode(", ", $video3)),
    ));
    $this->tpl->parse_if('content_medialibrary', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ms'));
    $this->tpl->parse_if('content_medialibrary', 'ms_issuu_document_active', $issuuDocument['ms_issuu_document_state'] == Issuu::STATE_ACTIVE);
    $this->tpl->parse_if('content_medialibrary', 'ms_issuu_document_delete', $issuuDocument['ms_issuu_document_state'] == Issuu::STATE_ACTIVE);
    $this->tpl->parse_if('content_medialibrary', 'ms_issuu_document_processing', $issuuDocument['ms_issuu_document_state'] == Issuu::STATE_PROCESSING);
    $this->tpl->parse_if('content_medialibrary', 'ms_issuu_document_failure', $issuuDocument['ms_issuu_document_state'] == Issuu::STATE_FAILURE);
    $this->tpl->parse_if('content_medialibrary', 'ms_randomly_show_available', $this->_isShowRandomlyAvailable());
    $this->tpl->parse_if('content_medialibrary', 'delete_image1', $imageSource1, $this->get_delete_image('medialibrary', 'ms', 1));
    $this->tpl->parse_if('content_medialibrary', 'delete_image2', $imageSource2, $this->get_delete_image('medialibrary', 'ms', 2));
    $this->tpl->parse_if('content_medialibrary', 'delete_image3', $imageSource3, $this->get_delete_image('medialibrary', 'ms', 3));
    $this->tpl->parse_if('content_medialibrary', 'delete_image4', $imageSource4, $this->get_delete_image('medialibrary', 'ms', 4));
    $this->tpl->parse_if('content_medialibrary', 'delete_image5', $imageSource5, $this->get_delete_image('medialibrary', 'ms', 5));
    $this->tpl->parse_if('content_medialibrary', 'delete_image6', $imageSource6, $this->get_delete_image('medialibrary', 'ms', 6));
    $this->tpl->parse_if('content_medialibrary', 'display_behaviour_info_text', $this->item_id);
    $this->tpl->parse_if('content_medialibrary', 'item_is_edited', $this->item_id);
    $this->tpl->parse_if('content_medialibrary', 'item_is_edited', $this->item_id);
    $content = $this->tpl->parsereturn('content_medialibrary', array_merge(
      $issuuDocument,
      $imageTitles,
      $this->_showFormGetDisplayTabsTemplateVars(),
      $this->getInternalLinkHelper($row['FK_CIID'] ?? 0)->getTemplateVars($this->_prefix),
      array(
      'ms_title1' => $title1,
      'ms_title2' => $title2,
      'ms_title3' => $title3,
      'ms_text1' => $text1,
      'ms_text2' => $text2,
      'ms_text3' => $text3,
      'ms_image_src1' => $this->get_normal_image('ms', $imageSource1),
      'ms_image_src2' => $this->get_normal_image('ms', $imageSource2),
      'ms_image_src3' => $this->get_normal_image('ms', $imageSource3),
      'ms_image_src4' => $this->get_normal_image('ms', $imageSource4),
      'ms_image_src5' => $this->get_normal_image('ms', $imageSource5),
      'ms_image_src6' => $this->get_normal_image('ms', $imageSource6),
      'ms_required_resolution_label1' => $this->_getImageSizeInfo('ms', 1),
      'ms_required_resolution_label2' => $this->_getImageSizeInfo('ms', 2),
      'ms_required_resolution_label3' => $this->_getImageSizeInfo('ms', 3),
      'ms_required_resolution_label4' => $this->_getImageSizeInfo('ms', 4),
      'ms_required_resolution_label5' => $this->_getImageSizeInfo('ms', 5),
      'ms_required_resolution_label6' => $this->_getImageSizeInfo('ms', 6),
      'ms_image_tpl_width1' => $this->_configHelper->getImageTemplateSize('ms', 'width', 1),
      'ms_image_tpl_height1' => $this->_configHelper->getImageTemplateSize('ms', 'height', 1),
      'ms_image_tpl_width2' => $this->_configHelper->getImageTemplateSize('ms', 'width', 2),
      'ms_image_tpl_height2' => $this->_configHelper->getImageTemplateSize('ms', 'height', 2),
      'ms_image_tpl_width3' => $this->_configHelper->getImageTemplateSize('ms', 'width', 3),
      'ms_image_tpl_height3' => $this->_configHelper->getImageTemplateSize('ms', 'height', 3),
      'ms_image_tpl_width4' => $this->_configHelper->getImageTemplateSize('ms', 'width', 4),
      'ms_image_tpl_height4' => $this->_configHelper->getImageTemplateSize('ms', 'height', 4),
      'ms_image_tpl_width5' => $this->_configHelper->getImageTemplateSize('ms', 'width', 5),
      'ms_image_tpl_height5' => $this->_configHelper->getImageTemplateSize('ms', 'height', 5),
      'ms_image_tpl_width6' => $this->_configHelper->getImageTemplateSize('ms', 'width', 6),
      'ms_image_tpl_height6' => $this->_configHelper->getImageTemplateSize('ms', 'height', 6),
      'ms_large_image_available1' => $this->_getImageZoomLink('ms', $imageSource1),
      'ms_large_image_available2' => $this->_getImageZoomLink('ms', $imageSource2),
      'ms_large_image_available3' => $this->_getImageZoomLink('ms', $imageSource3),
      'ms_large_image_available4' => $this->_getImageZoomLink('ms', $imageSource4),
      'ms_large_image_available5' => $this->_getImageZoomLink('ms', $imageSource5),
      'ms_large_image_available6' => $this->_getImageZoomLink('ms', $imageSource6),
      'ms_randomly_show' => $randomlyShow,
      'ms_url' => $extLink,
      'ms_site' => $this->site_id,
      'ms_function_label' => $_LANG["ms_function_{$function}_label"],
      'ms_function_label2' => $_LANG["ms_function_{$function}_label2"],
      'ms_action' => $action,
      'ms_hidden_fields' => $hiddenFields,
      'ms_autocomplete_contentitem_global_url' => $autoCompleteUrl . '&scope=global',
      'ms_module_action_boxes' => $this->_getContentActionBoxes(),
      "ms_video_class1" => $showTab1 == self::SHOW_TAB_VIDEO ? "active" : "inactive",
      "ms_video_class2" => $showTab2 == self::SHOW_TAB_VIDEO ? "active" : "inactive",
      "ms_video_class3" => $showTab3 == self::SHOW_TAB_VIDEO ? "active" : "inactive",
      "ms_video_visibility1" => $this->_getCssVisibility($showTab1 == self::SHOW_TAB_VIDEO),
      "ms_video_visibility2" => $this->_getCssVisibility($showTab2 == self::SHOW_TAB_VIDEO),
      "ms_video_visibility3" => $this->_getCssVisibility($showTab3 == self::SHOW_TAB_VIDEO),
      "ms_video_type_data_label1" => $videoType1 ? $_LANG["ms_video_type_{$videoType1}_data_label"] : $_LANG["ms_video_type_none_data_label"],
      "ms_video_type_data_label2" => $videoType2 ? $_LANG["ms_video_type_{$videoType2}_data_label"] : $_LANG["ms_video_type_none_data_label"],
      "ms_video_type_data_label3" => $videoType3 ? $_LANG["ms_video_type_{$videoType3}_data_label"] : $_LANG["ms_video_type_none_data_label"],
      "ms_video_type_data_descr1" => $videoType1 ? $_LANG["ms_video_type_{$videoType1}_data_descr"] : $_LANG["ms_video_type_none_data_descr"],
      "ms_video_type_data_descr2" => $videoType2 ? $_LANG["ms_video_type_{$videoType2}_data_descr"] : $_LANG["ms_video_type_none_data_descr"],
      "ms_video_type_data_descr3" => $videoType3 ? $_LANG["ms_video_type_{$videoType3}_data_descr"] : $_LANG["ms_video_type_none_data_descr"],
      "ms_video_type1" => $videoType1 ? $videoType1 : "none",
      "ms_video_type2" => $videoType2 ? $videoType2 : "none",
      "ms_video_type3" => $videoType3 ? $videoType3 : "none",
      "ms_video_data1" => $videoData1,
      "ms_video_data2" => $videoData2,
      "ms_video_data3" => $videoData3,
      "ms_video_url1" => $videoUrl1,
      "ms_video_url2" => $videoUrl2,
      "ms_video_url3" => $videoUrl3,
      "ms_image_class1" => $showTab1 == self::SHOW_TAB_IMAGE ? "active" : "inactive",
      "ms_image_class2" => $showTab2 == self::SHOW_TAB_IMAGE ? "active" : "inactive",
      "ms_image_class3" => $showTab3 == self::SHOW_TAB_IMAGE ? "active" : "inactive",
      "ms_image_class4" => $showTab4 == self::SHOW_TAB_IMAGE ? "active" : "inactive",
      "ms_image_class5" => $showTab5 == self::SHOW_TAB_IMAGE ? "active" : "inactive",
      "ms_image_class6" => $showTab6 == self::SHOW_TAB_IMAGE ? "active" : "inactive",
      "ms_document_class" => $showTab1 == self::SHOW_TAB_DOC ? "active" : "inactive",
      "ms_document_visibility" => $this->_getCssVisibility($showTab1 == self::SHOW_TAB_DOC),
      "ms_image_visibility1" => $this->_getCssVisibility($showTab1 == self::SHOW_TAB_IMAGE),
      "ms_image_visibility2" => $this->_getCssVisibility($showTab2 == self::SHOW_TAB_IMAGE),
      "ms_image_visibility3" => $this->_getCssVisibility($showTab3 == self::SHOW_TAB_IMAGE),
      "ms_image_visibility4" => $this->_getCssVisibility($showTab4 == self::SHOW_TAB_IMAGE),
      "ms_image_visibility5" => $this->_getCssVisibility($showTab5 == self::SHOW_TAB_IMAGE),
      "ms_image_visibility6" => $this->_getCssVisibility($showTab6 == self::SHOW_TAB_IMAGE),
      "ms_page_assignment" => $this->_parseModulePageAssignment($pageParameter),
      "ms_display_on_info_text" => $this->_getDisplayOnInfoText((bool)$randomlyShow, count($this->_readPageAssignments())),
      "ms_save_parameter" => $post->readKey('process_save'),
      "ms_campaign_form_attachment" => $this->_parseModuleCampaignFormAttachment($cgAttached),
    ), $_LANG2['ms']));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Show Contents in a List                                                               //
  ///////////////////////////////////////////////////////////////////////////////////////////
  private function _showList()
  {
    global $_LANG, $_LANG2;

    $defaultCatId = ConfigHelper::get('ms_list_selected_default_category', '', $this->site_id);
    if ($defaultCatId && !$this->_catId) {
      $this->_catId = $defaultCatId;
    }

    // get categories
    $sql = " SELECT MCID, MCTitle "
         . " FROM {$this->table_prefix}module_medialibrary_category "
         . " WHERE FK_SID = $this->site_id "
         . " ORDER BY MCPosition ASC ";
    $catAssoc = $this->db->GetAssoc($sql);
    if (!$catAssoc) {
      $this->setMessage(Message::createFailure($_LANG['ms_message_no_medialibrary_categories']));
    }

    $catOptions = '';

    foreach ($catAssoc as $id => $title)
    {
      $selected = '';
      if ($id == $this->_catId) {
        $selected = 'selected="selected"';
      }
      $catOptions .= '<option value="'.$id.'" '.$selected.'>'.parseOutput($title).'</option>';
    }

    // filtering
    $filter = $this->_getCurrentFilter();

    $maxLength = ConfigHelper::get('m_mod_filtertext_maxlength');
    $aftertext = ConfigHelper::get('m_mod_filtertext_aftertext');
    $shortFilterText = StringHelper::setText($filter['text'])
        ->purge()
        ->truncate($maxLength, $aftertext)
        ->getText();

    // create filter dropdown
    $tmp_filter_type_select = '<select name="filter_type" class="form-control">';
    foreach (array_keys($this->_filters) as $f) {
      $tmp_filter_type_select .= '<option value="'. $f.'"';
      if ($filter['type'] == $f) $tmp_filter_type_select .= ' selected="selected"';
      $tmp_filter_type_select .= '>'.$_LANG["ms_filter_type_$f"].'</option>';
    }
    $filterTypeSelect = $tmp_filter_type_select."</select>";

    // initialize paging
    // If requested result page is greater than the possible amount of pages,
    // redirect to the first page
    $pagination = $this->_getPageNavigation()->getPagination();
    if ($pagination->getCurrentPage() > $pagination->getTotalPages()) {
      header('Location: ' . $this->_parseUrl('', array('offset' => 1)));
      exit;
    }

    $items = array ();
    $result = $this->db->query($this->_readMediaLibraryItemsSql(array($this->_getCurrentFilterSqlWhere()), $pagination->getCurrentPage(), $pagination->getResultsPerPage()));
    while ($row = $this->db->fetch_row($result)) {
      $items[] = array('ms_item' => $this->_getContentMediaLibraryListItem($row));
    }
    $this->db->free_result($result);

    if (!$items) {
      if ($filter['text']) {
        $this->setMessage(Message::createFailure($_LANG['ms_filtermessage_empty']));
      }
      else {
        $this->setMessage(Message::createFailure($_LANG['ms_message_no_medialibrary_boxes']));
      }
    }

    $action = 'index.php?action=mod_medialibrary';
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    // Parse the list template.
    $this->tpl->load_tpl('medialibrary', 'modules/ModuleMultimediaLibrary_list.tpl');
    $this->tpl->parse_if('medialibrary', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ms'));
    $this->tpl->parse_if('medialibrary', 'ms_timing_type_activated', $this->_configHelper->get('ms_timing_activated'));
    $this->tpl->parse_if('medialibrary', 'category', (bool)$catAssoc);
    $this->tpl->parse_if('medialibrary', 'more_pages', $this->_getPageNavigation()->getPagination()->getTotalPages() > 1, array(
      'ms_page_navigation'              => $this->_getPageNavigation()->html()
    ));
    $this->tpl->parse_if('medialibrary', 'filter_set', $filter['text']);
    $this->tpl->parse_if('medialibrary', 'filter_set', $filter['text']);
    $this->tpl->parse_loop('medialibrary', $items, 'medialibrary_items');
    $content = $this->tpl->parsereturn('medialibrary', array_merge(array(
      'ms_action'                       => $action,
      'ms_hidden_fields'                => $hiddenFields,
      'ms_site_selection'               => $this->_parseModuleSiteSelection('medialibrary', $_LANG['ms_site_label']),
      'ms_dragdrop_link_js'             => "index.php?action=mod_medialibrary&site=$this->site_id&cat_id={$this->_catId}&moveID=#moveID#&moveTo=#moveTo#",
      'ms_list_label'                   => $_LANG['ms_function_list_label'],
      'ms_list_label2'                  => $_LANG['ms_function_list_label2'],
      'ms_category_options'             => $catOptions,
      'ms_cat_id'                       => $this->_catId,
      'ms_list_item_with_timing_opened' => $this->_listItemWithTimingOpened,
      'ms_filter_active_label'          => $filter['text'] ? sprintf($_LANG["ms_filter_active_label"], $_LANG["ms_filter_type_" . $filter['type']], parseOutput($filter['text']), parseOutput($shortFilterText)) : $_LANG["ms_filter_inactive_label"],
      'ms_filter_type_select'           => $filterTypeSelect,
      'ms_filter_text'                  => $filter['text'],
    ), $_LANG2['ms']));

    return array(
      'content'      => $content,
      'content_left' => ($catAssoc) ? $this->_getContentLeft() : '',
    );
  }

  /**
   * Returns the content for one multimedia library list item
   *
   * @param array $row
   *
   * @return string
   */
  private function _getContentMediaLibraryListItem(array $row)
  {
    global $_LANG, $_LANG2;

    $types = ConfigHelper::get('ms_video_types');
    if ($this->_catId) {
      $moveId = $row['MCAID'];
      $position = $row['MCAPosition'];
    }
    else {
      $moveId = $row['MID'];
      $position = $row['MPosition'];
    }
    $moveUpPosition = $this->_positionHelper()->getMoveUpPosition((int)$position);
    $moveDownPosition = $this->_positionHelper()->getMoveDownPosition((int)$position);

    $internalLink = $this->getInternalLinkHelper($row['FK_CIID']);
    $intLink = '';
    if ($internalLink->isValid()) {
      $intLink = sprintf($_LANG['ms_intlink_link'], $internalLink->getEditUrl(), $internalLink->getHierarchicalTitle("/"));
    }

    $videoType1 = $videoType2 = $videoType3 = '';
    $videoData1 = $videoData2 = $videoData3 = '';
    $videoUrl1 = $videoUrl2 = $videoUrl3 = '';
    // Video 1
    if ($row["MVideoType1"] && $row["MVideo1"] && isset($types[$row["MVideoType1"]])) {
      $videoType1 = $row["MVideoType1"];
      $video1 = unserialize($row["MVideo1"]);
      $videoData1 = vsprintf($types[$videoType1]["data_format"], $video1);
      $videoUrl1 = vsprintf($types[$videoType1]["url"], $video1);
    }

    // Video 2
    if ($row["MVideoType2"] && $row["MVideo2"] && isset($types[$row["MVideoType2"]])) {
      $videoType2 = $row["MVideoType2"];
      $video2 = unserialize($row["MVideo2"]);
      $videoData2 = vsprintf($types[$videoType2]["data_format"], $video2);
      $videoUrl2 = vsprintf($types[$videoType2]["url"], $video2);
    }

    // Video 3
    if ($row["MVideoType3"] && $row["MVideo3"] && isset($types[$row["MVideoType3"]])) {
      $videoType3 = $row["MVideoType3"];
      $video3 = unserialize($row["MVideo3"]);
      $videoData3 = vsprintf($types[$videoType3]["data_format"], $video3);
      $videoUrl3 = vsprintf($types[$videoType3]["url"], $video3);
    }

    // visibility and timing
    $timingAvailable = $this->_configHelper->get('ms_timing_activated');
    $timingMsg = $row['MDisabled'] ? $_LANG['ms_message_timing_has_no_effect'] : '';
    $timingStartDate = DateHandler::getValidDateTime($row['MShowFromDateTime'], 'd.m.Y');
    $timingEndDate = DateHandler::getValidDateTime($row['MShowUntilDateTime'], 'd.m.Y');
    $timingStartTime = DateHandler::getValidDateTime($row['MShowFromDateTime'], 'H:i');
    $timingEndTime = DateHandler::getValidDateTime($row['MShowUntilDateTime'], 'H:i');
    $timingActive = (($timingStartDate && $timingStartTime) || ($timingEndDate && $timingEndTime)) ? true : false;
    $activationLightLink = $this->_getActivationLink($row);
    $activationLight = $this->_getActivationLight($row);

    $tplName = 'medialibrary_list_item';
    $this->tpl->load_tpl($tplName, 'modules/ModuleMultimediaLibrary_list_item.tpl');
    $this->tpl->parse_if($tplName, 'ms_video_url1', $videoUrl1);
    $this->tpl->parse_if($tplName, 'ms_video_url2', $videoUrl2);
    $this->tpl->parse_if($tplName, 'ms_video_url3', $videoUrl3);
    $this->tpl->parse_if($tplName, 'ms_timing_message', $timingMsg, array('ms_timing_message' => $timingMsg));
    $this->tpl->parse_if($tplName, 'ms_timebox', $timingAvailable);
    $this->tpl->parse_if($tplName, 'ms_timing_type_activated', $timingAvailable);
    $this->tpl->parse_if($tplName, 'ms_timing_active', $timingActive && !$row['MDisabled']);
    $this->tpl->parse_if($tplName, 'ms_timing_not_active', !$timingActive || $row['MDisabled']);

    return $this->tpl->parsereturn($tplName, array_merge($internalLink->getTemplateVars($this->_prefix), array(
      'ms_title1'                      => parseOutput($row['MTitle1']),
      'ms_title2'                      => parseOutput($row['MTitle2']),
      'ms_title3'                      => parseOutput($row['MTitle3']),
      'ms_text1'                       => parseOutput($row['MText1']),
      'ms_text2'                       => parseOutput($row['MText2']),
      'ms_text3'                       => parseOutput($row['MText3']),
      'ms_video_type_data_label1'      => $videoType1 ? $_LANG["ms_video_type_{$videoType1}_label"] : $_LANG['ms_video_type_none_label'],
      'ms_video_type_data_label2'      => $videoType2 ? $_LANG["ms_video_type_{$videoType2}_label"] : $_LANG['ms_video_type_none_label'],
      'ms_video_type_data_label3'      => $videoType3 ? $_LANG["ms_video_type_{$videoType3}_label"] : $_LANG['ms_video_type_none_label'],
      'ms_video_type1'                 => $videoType1,
      'ms_video_type2'                 => $videoType2,
      'ms_video_type3'                 => $videoType3,
      'ms_video_data1'                 => $videoData1,
      'ms_video_data2'                 => $videoData2,
      'ms_video_data3'                 => $videoData3,
      'ms_video_url1'                  => $videoUrl1,
      'ms_video_url2'                  => $videoUrl2,
      'ms_video_url3'                  => $videoUrl3,
      'ms_image_src1'                  => ($row['MImage1'] ? '../' . $row['MImage1'] : ($row['MImage2'] ? '../' . $row['MImage2'] : 'img/no_image.png')),
      'ms_id'                          => $row['MID'],
      'ms_dragdrop_id'                 => $moveId,
      'ms_position'                    => $position,
      'ms_content_link'                => "index.php?action=mod_medialibrary&amp;action2=main;edit&amp;cat_id=$this->_catId&amp;site=$this->site_id&amp;page={$row['MID']}",
      'ms_delete_link'                 => "index.php?action=mod_medialibrary&amp;deletemedialibraryID={$row['MID']}&amp;page={$row['MID']}&amp;cat_id={$this->_catId}",
      'ms_move_up_link'                => "index.php?action=mod_medialibrary&amp;site=$this->site_id&amp;cat_id={$this->_catId}&amp;page={$row['MID']}&amp;moveID={$moveId}&amp;moveTo=$moveUpPosition",
      'ms_move_down_link'              => "index.php?action=mod_medialibrary&amp;site=$this->site_id&amp;cat_id={$this->_catId}&amp;page={$row['MID']}&amp;moveID={$moveId}&amp;moveTo=$moveDownPosition",
      'ms_extlink_link'                => $row['MUrl'] ? sprintf($_LANG['ms_extlink_link'], $row['MUrl'], $row['MUrl']) : '',
      'ms_intlink_link'                => $intLink,
      'ms_activation_light'       => $activationLight,
      'ms_activation_light_label' => $_LANG['global_activation_light_'.$activationLight.'_label'],
      'ms_activation_light_link'  => $activationLightLink,
      'ms_date_from' => $timingStartDate,
      'ms_time_from' => $timingStartTime,
      'ms_date_until' => $timingEndDate,
      'ms_time_until' => $timingEndTime,
      'ms_timing_action' => "index.php?action=mod_medialibrary&site=$this->site_id&cat_id={$this->_catId}&page={$row['MID']}&process_ms_date=1"
    ), $_LANG2['ms']));
  }

  /**
   * Updates a side box.
   */
  private function _updateMedialibraryBox()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'edit') {
      return;
    }

    $timingData = ConfigHelper::get('ms_timing_activated') ? $this->_readTimingData() : array();
    if ($this->_getMessage()) { return; }

    $title1 = $post->readString('ms_title1', Input::FILTER_PLAIN);
    $title2 = $post->readString('ms_title2', Input::FILTER_PLAIN);
    $title3 = $post->readString('ms_title3', Input::FILTER_PLAIN);
    $text1 = $post->readString('ms_text1', Input::FILTER_CONTENT_TEXT);
    $text2 = $post->readString('ms_text2', Input::FILTER_CONTENT_TEXT);
    $text3 = $post->readString('ms_text3', Input::FILTER_CONTENT_TEXT);
    $imageTitles = $post->readImageTitles('ms_image_title');
    $randomlyShow = $this->_isShowRandomlyAvailable() ? (int)$post->exists('ms_randomly_show') : 0;
    list($link, $linkID) = $post->readContentItemLink('ms_link');
    $extLink = $post->readString('ms_url', Input::FILTER_PLAIN);

    if ($extLink) { // validate url protocol
      $valid = false;
      $protocols = $this->_configHelper->getVar('url_protocols', 'ms');

      foreach ($protocols as $protocol) {
        if (mb_substr($extLink, 0, mb_strlen($protocol)) === $protocol) {
          $valid = true;
          break;
        }
      }

      if (!$valid) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['ms_message_invalid_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    // Process attached campaign
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $sql = " SELECT FK_CGAID "
         . " FROM {$this->table_prefix}module_medialibrary "
         . " WHERE MID = $this->item_id ";
    $cgAttached->id = $this->db->GetOne($sql);
    $cgAId = $cgAttached->process(array(
        'm_cg'             => $post->readInt('m_cg'),
        'm_cg_recipient'   => $post->readString('m_cg_recipient', Input::FILTER_PLAIN),
        'm_cg_data_origin' => $post->readString('m_cg_data_origin', Input::FILTER_PLAIN),
    ));
    if ($cgAId === false) {
      $this->setMessage($cgAttached->getMessage());
      return;
    }

    $sql = 'SELECT MImage1, MImage2, MImage3, MImage4, MImage5, MImage6 '
         . "FROM {$this->table_prefix}module_medialibrary "
         . "WHERE MID = $this->item_id ";
    $existingImages = $this->db->GetRow($sql);
    $image1 = $existingImages['MImage1'];
    $image2 = $existingImages['MImage2'];
    $image3 = $existingImages['MImage3'];
    $image4 = $existingImages['MImage4'];
    $image5 = $existingImages['MImage5'];
    $image6 = $existingImages['MImage6'];

    $uploadImage1 = isset($_FILES['ms_image1']) && $_FILES['ms_image1']['size'];
    $uploadImage2 = isset($_FILES['ms_image2']) && $_FILES['ms_image2']['size'];
    $uploadImage3 = isset($_FILES['ms_image3']) && $_FILES['ms_image3']['size'];
    $uploadImage4 = isset($_FILES['ms_image4']) && $_FILES['ms_image4']['size'];
    $uploadImage5 = isset($_FILES['ms_image5']) && $_FILES['ms_image5']['size'];
    $uploadImage6 = isset($_FILES['ms_image6']) && $_FILES['ms_image6']['size'];
    $uploadDocument = isset($_FILES['ms_issuu_document_file']) &&
                      $_FILES['ms_issuu_document_file']['size'];

    $videoType1 = $post->readString('ms_video_type1', Input::FILTER_NONE);
    $videoData1 = $post->readString('ms_video_data1', Input::FILTER_NONE);
    $video1 = $this->_parseVideoData($videoType1, $videoData1, 1);
    $videoType2 = $post->readString('ms_video_type2', Input::FILTER_NONE);
    $videoData2 = $post->readString('ms_video_data2', Input::FILTER_NONE);
    $video2 = $this->_parseVideoData($videoType2, $videoData2, 2);
    $videoType3 = $post->readString('ms_video_type3', Input::FILTER_NONE);
    $videoData3 = $post->readString('ms_video_data3', Input::FILTER_NONE);
    $video3 = $this->_parseVideoData($videoType3, $videoData3, 3);

    if (   !$title1 && !$title2 && !$title3
        && !$text1 && !$text2 && !$text3
        && !$uploadImage1 && !$uploadImage2 && !$uploadImage3
        && !$uploadImage4 && !$uploadImage5 && !$uploadImage6
        && !$image1 && !$image2 && !$image3
        && !$image4 && !$image5 && !$image6
        && empty($video1) && empty($video2) && empty($video3)
        && !$uploadDocument
    ) {
      $this->setMessage(Message::createFailure($_LANG['ms_message_update_failure']));
    }

    if ($this->_getMessage()) {
      return;
    }

    // Video 1
    if ($video1)
    {
      $vData = $this->_getVideoData($videoType1, $video1[0]);
      if (is_array($vData))
      {
        $videoDuration1 = $vData['duration'];
        $videoThumbnail1 = $vData['thumbnail'];
        $videoPublishedDate1 = $vData['published_date'];
      }
      else {
        $videoPublishedDate1 = $videoThumbnail1 = $videoDuration1 = '';
      }
      $video1 = serialize($video1);
    }
    else {
      $videoPublishedDate1 = $videoThumbnail1 = $videoDuration1 = $videoType1 = $video1 = '';
    }

    // Video 2
    if ($video2)
    {
      $vData = $this->_getVideoData($videoType2, $video2[0]);
      if (is_array($vData))
      {
        $videoDuration2 = $vData['duration'];
        $videoThumbnail2 = $vData['thumbnail'];
        $videoPublishedDate2 = $vData['published_date'];
      }
      else {
        $videoPublishedDate2 = $videoThumbnail2 = $videoDuration2 = '';
      }
      $video2 = serialize($video2);
    }
    else {
      $videoPublishedDate2 = $videoThumbnail2 = $videoDuration2 = $videoType2 = $video2 = '';
    }

    // Video 3
    if ($video3)
    {
      $vData = $this->_getVideoData($videoType3, $video3[0]);
      if (is_array($vData))
      {
        $videoDuration3 = $vData['duration'];
        $videoThumbnail3 = $vData['thumbnail'];
        $videoPublishedDate3 = $vData['published_date'];
      }
      else {
        $videoPublishedDate3 = $videoThumbnail3 = $videoDuration3 = '';
      }
      $video3 = serialize($video3);
    }
    else {
      $videoPublishedDate3 = $videoThumbnail3 = $videoDuration3 = $videoType3 = $video3 = '';
    }

    // return, if a message was set during parsing video data
    if ($this->_getMessage()) {
      return;
    }

    // Image upload.
    if (isset($_FILES['ms_image1']) && $uploadedImage = $this->_storeImage($_FILES['ms_image1'], $image1, 'ms', 1)) {
      $image1 = $uploadedImage;
    }
    if (isset($_FILES['ms_image2']) && $uploadedImage = $this->_storeImage($_FILES['ms_image2'], $image2, 'ms', 2)) {
      $image2 = $uploadedImage;
    }
    if (isset($_FILES['ms_image3']) && $uploadedImage = $this->_storeImage($_FILES['ms_image3'], $image3, 'ms', 3)) {
      $image3 = $uploadedImage;
    }
    if (isset($_FILES['ms_image4']) && $uploadedImage = $this->_storeImage($_FILES['ms_image4'], $image1, 'ms', 4)) {
      $image4 = $uploadedImage;
    }
    if (isset($_FILES['ms_image5']) && $uploadedImage = $this->_storeImage($_FILES['ms_image5'], $image2, 'ms', 5)) {
      $image5 = $uploadedImage;
    }
    if (isset($_FILES['ms_image6']) && $uploadedImage = $this->_storeImage($_FILES['ms_image6'], $image3, 'ms', 6)) {
      $image6 = $uploadedImage;
    }

    $now = date('Y-m-d H:i:s');

    $timingFiels = array();
    if ($timingData) {
      $timingFiels[] = $timingData['MShowFromDateTime'] ?
        sprintf("MShowFromDateTime = '%s'", $timingData['MShowFromDateTime']) : "MShowFromDateTime = NULL";
      $timingFiels[] = $timingData['MShowUntilDateTime'] ?
        sprintf("MShowUntilDateTime = '%s'", $timingData['MShowUntilDateTime']) : "MShowUntilDateTime = NULL";
    }

    $sql = "UPDATE {$this->table_prefix}module_medialibrary "
         . "SET MTitle1 = '{$this->db->escape($title1)}', "
         . "    MTitle2 = '{$this->db->escape($title2)}', "
         . "    MTitle3 = '{$this->db->escape($title3)}', "
         . "    MText1 = '{$this->db->escape($text1)}', "
         . "    MText2 = '{$this->db->escape($text2)}', "
         . "    MText3 = '{$this->db->escape($text3)}', "
         . "    MVideo1 = '{$this->db->escape($video1)}', "
         . "    MVideo2 = '{$this->db->escape($video2)}', "
         . "    MVideo3 = '{$this->db->escape($video3)}', "
         . "    MVideoDuration1 = ".(($videoDuration1) ? "'{$this->db->escape($videoDuration1)}'" : "NULL").", "
         . "    MVideoDuration2 = ".(($videoDuration2) ? "'{$this->db->escape($videoDuration2)}'" : "NULL").", "
         . "    MVideoDuration3 = ".(($videoDuration3) ? "'{$this->db->escape($videoDuration3)}'" : "NULL").", "
         . "    MVideoThumbnail1 = '{$this->db->escape($videoThumbnail1)}', "
         . "    MVideoThumbnail2 = '{$this->db->escape($videoThumbnail2)}', "
         . "    MVideoThumbnail3 = '{$this->db->escape($videoThumbnail3)}', "
         . "    MVideoPublishedDate1 = ".(($videoPublishedDate1) ? "'{$this->db->escape($videoPublishedDate1)}'" : "NULL").", "
         . "    MVideoPublishedDate2 = ".(($videoPublishedDate2) ? "'{$this->db->escape($videoPublishedDate2)}'" : "NULL").", "
         . "    MVideoPublishedDate3 = ".(($videoPublishedDate3) ? "'{$this->db->escape($videoPublishedDate3)}'" : "NULL").", "
         . "    MVideoType1 = '{$this->db->escape($videoType1)}', "
         . "    MVideoType2 = '{$this->db->escape($videoType2)}', "
         . "    MVideoType3 = '{$this->db->escape($videoType3)}', "
         . "    FK_CIID = $linkID, "
         . "    MUrl = '{$this->db->escape($extLink)}', "
         . "    MImage1 = '$image1', "
         . "    MImage2 = '$image2', "
         . "    MImage3 = '$image3', "
         . "    MImage4 = '$image4', "
         . "    MImage5 = '$image5', "
         . "    MImage6 = '$image6', "
         . "    MImageTitles = '$imageTitles', "
         . "    MRandomlyShow = '$randomlyShow', "
         . "    FK_CGAID = '$cgAId', "
         . "    MChangeDateTime = '$now' "
         . ($timingFiels ? sprintf(', %s ', implode(',', $timingFiels)) : '')
         . "WHERE MID = $this->item_id ";
    $result = $this->db->query($sql);

    // Get new category assignments
    $newCategories = $post->readArrayIntToString('ms_category');
    // Get existing category assignments
    $sql = " SELECT MCAID, FK_MCID "
         . " FROM {$this->table_prefix}module_medialibrary_category_assignment "
         . " WHERE FK_MID = $this->item_id ";
    $existingCategories = $this->db->GetAssoc($sql);
    $categoriesToDelete = array_diff($existingCategories, $newCategories);
    $categoriesToInsert = array_diff($newCategories, $existingCategories);
    // Delete old category assignments
    if ($categoriesToDelete) {
      // Move category assignments to last position before deleting it.
      foreach ($categoriesToDelete as $mcaId => $categoryId) {
        $catPositionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category_assignment",
                                             'MCAID', 'MCAPosition', 'FK_MCID', $categoryId);
        $catPositionHelper->move($mcaId, $catPositionHelper->getHighestPosition());
      }

      $sql = " DELETE FROM {$this->table_prefix}module_medialibrary_category_assignment "
           . " WHERE MCAID IN ( ".implode(',', array_keys($categoriesToDelete))." ) ";
      $this->db->query($sql);
    }
    // Insert new category assignments
    if ($categoriesToInsert) {
      $categoryValues = array();
      foreach ($categoriesToInsert as $key => $value) {
        $catPositionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category_assignment",
                                             'MCAID', 'MCAPosition', 'FK_MCID', $value);
        $pos = $catPositionHelper->getHighestPosition() + 1;
        $categoryValues[] = " ('$this->item_id', '".$this->db->escape($value)."', $pos) ";
      }
      if ($categoryValues) {
        $sql = " INSERT INTO {$this->table_prefix}module_medialibrary_category_assignment "
             . ' (FK_MID, FK_MCID, MCAPosition) '
             . " VALUES ".implode(',', $categoryValues)." ";
        $this->db->query($sql);
      }
    }

    // Issuu document upload
    $this->_handleDocumentUpload();

    $message = $this->_getMessage() ?: Message::createSuccess($_LANG['ms_message_update_success']);
    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(), $message);
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)), $message);
    }
  }

  /**
   * Handle issuu document upload.
   */
  private function _handleDocumentUpload()
  {
    if (!$this->_isActionDocumentUpload()) {
      return;
    }
    $status = $this->_saveDocument($_FILES['ms_issuu_document_file']);
    if ($status !== true) {
      $msg = $this->_issuu->getErrorMsg('ms');
      $this->setMessage(Message::createFailure($msg));
    }
    // Issuu document successfully created
    else {
      if (isset($_POST['ms_store_file'])) {
        $this->_createCentralFile($_FILES['ms_issuu_document_file']);
      }

      $sql = "UPDATE {$this->table_prefix}module_medialibrary "
           . "SET FK_IDID = '$this->_iDId' "
           . "WHERE MID = $this->item_id ";
      $result = $this->db->query($sql);
    }
  }

  /**
   * @param array $fileArray
   * @return bool True on success
   */
  private function _createCentralFile($fileArray)
  {
    global $_LANG;

    $maxSize = ConfigHelper::get('ms_file_size');
    $fileTypes = ConfigHelper::get('ms_file_types');
    $fixedFileArray = $fileArray;
    self::_fixFilesArray($fixedFileArray);
    $title = ResourceNameGenerator::file($fixedFileArray['name']);
    $file = $this->_storeFile($fileArray, $maxSize, $fileTypes);
    if (!$file) {
      $this->setMessage(Message::createFailure($_LANG['ms_message_fileupload_insufficient_input']));
      return false;
    }

    // save centralfile data
    $now = date('Y-m-d H:i:s');
    $fileSize = (int)$fileArray['size'];
    $sql = "INSERT INTO {$this->table_prefix}centralfile (CFTitle, CFFile, CFCreated, CFSize, FK_SID) "
         . "VALUES ('{$this->db->escape($title)}', '$file', '$now', $fileSize, $this->site_id) ";
    $this->db->query($sql);
    return true;
  }

  /**
   * Changes the activation status.
   */
  private function _changeMediaLibraryBoxActivation()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $id = $get->readInt('changeActivationBoxID');
    $type = $get->readString('changeActivationBoxTo', Input::FILTER_NONE);

    if (!$id || !$type) {
      return;
    }

    switch ( $type ) {
      case ContentBase::ACTIVATION_ENABLED:
        $to = 0;
        break;
      case ContentBase::ACTIVATION_DISABLED:
        $to = 1;
        break;
      default: return; // invalid activation status
    }

    $sql = " UPDATE {$this->table_prefix}module_medialibrary "
         . " SET MDisabled = $to "
         . " WHERE MID = $id ";
    $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['ms_message_activation_' . $type]));
  }

  private function _updateMediaLibraryBoxTiming()
  {
    global $_LANG;

    if (!isset($_REQUEST['process_ms_date'])) {
      return;
    }

    $id = (int)$this->item_id;

    $newDates = $this->_readTimingData();
    if (!empty($newDates)) {
      $sql = " UPDATE {$this->table_prefix}module_medialibrary "
           . " SET MShowFromDateTime = ?, "
           . "     MShowUntilDateTime = ? "
           . " WHERE MID = $id ";
      $this->db->q($sql, array($newDates['MShowFromDateTime'] ?: null,
        $newDates['MShowUntilDateTime'] ?: null));

      $this->setMessage(Message::createSuccess($_LANG['ms_timing_update_success']));

      $this->_listItemWithTimingOpened = $id;
    }

    $row = $this->db->GetRow($this->_readMediaLibraryItemsSql(array(array('MID', $id))));

    return $this->_getContentMediaLibraryListItem($row);
  }

  /* ---------------------------------------------------------------------------
   | further methods
     ------------------------------------------------------------------------ */

  /**
   * Checks if user tries to upload a document.
   *
   * @return boolean
   *         True if user tries to upload a document.
   */
  private function _isActionDocumentUpload()
  {
    if (isset($_FILES['ms_issuu_document_file']) && $_FILES['ms_issuu_document_file']['error'] === 0) {
      return true;
    }
    return false;
  }

  /**
   * Checks if user tries to upload an image.
   * @param int $number
   *        The image number (1-3)
   * @return boolean
   */
  private function _isActionImageUpload($number)
  {
    if (isset($_FILES['ms_image'.$number]) && $_FILES['ms_image'.$number]['error'] === 0) {
      return true;
    }
    return false;
  }

  /**
   * Deletes current document by name if available.
   *
   * @param string $name
   *        the id name string of document
   *
   * @return boolean
   *         True if current document was deleted.
   */
  private function _deleteDocument($name)
  {
    // If document is not available, return.
    if (!$name) {
      return false;
    }

    // Delete from Issuu server
    $this->_issuu->requestDelete($name);
    if ($this->_issuu->isResponseOk() === false) {
      return false;
    }

    // Delete database entry
    $this->_issuu->deleteFromDb($name);
    $sql = " UPDATE {$this->table_prefix}module_medialibrary "
         . "    SET FK_IDID = 0 "
         . "  WHERE MID = {$this->item_id} ";
    $this->db->query($sql);

    return true;
  }

  /**
   * Returns the document name from given document id or first document attached
   * to medialibrary.
   *
   * @param string $documentId [optional]
   *
   * @return string
   */
  private function _getDocumentName($documentId = '')
  {
    $sqlArg = ($documentId) ? " AND IDDocumentId = '$documentId' " : '';
    $sql = " SELECT IDName "
         . " FROM {$this->table_prefix}issuu_document "
         . " JOIN {$this->table_prefix}module_medialibrary "
         . "   ON FK_IDID = IDID "
         . " WHERE MID = {$this->item_id} "
         . " {$sqlArg } ";
    $name = $this->db->GetOne($sql);

    return $name;
  }

  /**
   * Saves a document.
   *
   * @param array $file
   *        The file to upload.
   *
   * @return boolean
   *         True if a document was successfully uploaded and saved.
   */
  private function _saveDocument($file)
  {
    if (!$file) {
      return false;
    }

    // First delete document if available for this multimedia box.
    $this->_deleteDocument($this->_getDocumentName());
    // Issuu document upload
    $response = $this->_issuu->requestUpload($file);
    if ($this->_issuu->isResponseOk() === false) {
      return false;
    }

    // Doucment was successfully uploaded
    $this->_iDId = $this->_issuu->saveToDb();

    return true;
  }

  /**
   * Returns an array of template variables for tab visibility, wheras each tab
   * not available from configuration gets a display:none CSS style
   *
   * @return array
   */
  private function _showFormGetDisplayTabsTemplateVars()
  {
    $tabnames = array(self::SHOW_TAB_IMAGE, self::SHOW_TAB_VIDEO, self::SHOW_TAB_DOC);
    $availableTabs = ConfigHelper::get('ms_available_tabs');
    $vars = array();

    foreach ($tabnames as $tab) {
      $vars['ms_display_tab_' . $tab] = $this->_getCssVisibility(in_array($tab, $availableTabs));
    }

    return $vars;
  }

  /**
   * @return bool
   */
  private function _isShowRandomlyAvailable()
  {
    return (bool)ConfigHelper::get('ms_random_box_for_mixed_category', '', $this->site_id);
  }

  /**
   * @return PositionHelper
   */
  private function _positionHelper()
  {
    if ($this->_positionHelper === null) {
      $this->_positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary",
          'MID', 'MPosition', 'FK_SID', $this->site_id);

      if ($this->_catId) {
        $this->_positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_medialibrary_category_assignment",
            'MCAID', 'MCAPosition', 'FK_MCID', $this->_catId);
      }
    }

    return $this->_positionHelper;
  }

  /**
   * Returns the activation link for a box.
   *
   * @param array $row
   *
   * @return string
   */
  private function _getActivationLink(array $row)
  {
    $activationLightLink = "index.php?action=mod_medialibrary"
        . "&site=$this->site_id&cat_id={$this->_catId}"
        . "&changeActivationBoxID={$row['MID']}&changeActivationBoxTo=";
    if ($row['MDisabled']) {
      $activationLightLink .= ContentBase::ACTIVATION_ENABLED;
    }
    else {
      $activationLightLink .= ContentBase::ACTIVATION_DISABLED;;
    }

    return $activationLightLink;
  }

  /**
   * Returns the activation light for an item.
   *
   * @param array $row
   *
   * @return string
   *           The activation light string i.e. yellow, green, clock, ...
   */
  private function _getActivationLight(array $row)
  {
    if ($row['MDisabled']) {
      $activationLight = ActivationLightInterface::RED;
    }
    else {
      $activationLight = ActivationLightInterface::GREEN;
    }

    // the box is not enabled, so return value as timing does not
    // matter for disabled items
    if ($activationLight != ActivationLightInterface::GREEN)
      return $activationLight;

    // the box is enabled so we have too take a look at timing
    // settings in order to retrieve the correct activation light
    $timingStartDate = DateHandler::getValidDateTime($row['MShowFromDateTime'], 'd.m.Y');
    $timingEndDate = DateHandler::getValidDateTime($row['MShowUntilDateTime'], 'd.m.Y');
    $timingStartTime = DateHandler::getValidDateTime($row['MShowFromDateTime'], 'H:i');
    $timingEndTime = DateHandler::getValidDateTime($row['MShowUntilDateTime'], 'H:i');
    $timingActive = (($timingStartDate && $timingStartTime) || ($timingEndDate && $timingEndTime)) ? true : false;
    if ($timingActive) {

      $fromDateTime = ContentBase::strToTime($row['MShowFromDateTime']);
      $untilDateTime = ContentBase::strToTime($row['MShowUntilDateTime']);
      if ($row['MDisabled']) {
        $visible = false;
      }
      else if ($fromDateTime && $fromDateTime > time()) {
        $visible = false;
      }
      else if ($untilDateTime && $untilDateTime <= time()) {
        $visible = false;
      }
      else {
        $visible = true;
      }

      if ($visible) {
        $activationLight = ActivationClockInterface::GREEN;
      }
      else {
        $activationLight = ActivationClockInterface::RED;
      }
    }

    return $activationLight;
  }

  /**
   * Reads all dates that were input by the user and returns them.
   *
   * @return array
   *        Contains dates that were entered by the user. The array index
   *        is the name of the database column, the array value is the date.
   */
  private function _readTimingData()
  {
    global $_LANG;

    $request = new Input(Input::SOURCE_REQUEST);
    $prefix = $this->_prefix;

    $postDateFrom = $request->readString($prefix . "{$this->item_id}_date_from", Input::FILTER_PLAIN);
    $postTimeFrom = $request->readString($prefix . "{$this->item_id}_time_from", Input::FILTER_PLAIN);
    $postDateUntil = $request->readString($prefix . "{$this->item_id}_date_until", Input::FILTER_PLAIN);
    $postTimeUntil = $request->readString($prefix . "{$this->item_id}_time_until", Input::FILTER_PLAIN);

    // Create date strings and time strings and combine afterwards
    $dateFrom = DateHandler::getValidDate($postDateFrom, 'Y-m-d');
    $timeFrom = DateHandler::getValidDate($postTimeFrom, 'H:i:s');
    $dateUntil = DateHandler::getValidDate($postDateUntil, 'Y-m-d');
    $timeUntil = DateHandler::getValidDate($postTimeUntil, 'H:i:s');

    $datetimeFrom = DateHandler::combine($dateFrom, $timeFrom);
    $datetimeUntil = DateHandler::combine($dateUntil, $timeUntil);

    if (!DateHandler::isValidDate($postDateFrom) && $postDateFrom != ''
      || !DateHandler::isValidDate($postTimeFrom) && $postTimeFrom != ''
      || !DateHandler::isValidDate($postDateUntil) && $postDateUntil != ''
      || !DateHandler::isValidDate($postTimeUntil) && $postTimeUntil != '')
    {
      $this->setMessage(Message::createFailure($_LANG['global_message_invalid_date']));
      return array();
    }
    if (strtotime($datetimeFrom) > strtotime($datetimeUntil)
      && DateHandler::isValidDate($datetimeFrom) && DateHandler::isValidDate($datetimeUntil))
    {
      $this->setMessage(Message::createFailure($_LANG['global_message_wrong_date']));
      return array();
    }

    $dates['MShowFromDateTime'] = $datetimeFrom;
    $dates['MShowUntilDateTime'] = $datetimeUntil;
    return $dates;
  }

  /**
   * Returns the prepared medialibrary SQL
   *
   * @param array $conditions [optional]
   * @param int   $resultsPage [optional]
   * @param int   $resultsPerPage [optional]
   *        0 = unlimited = no paging
   *
   * @return string
   */
  private function _readMediaLibraryItemsSql($conditions = array(), $resultsPage = 1, $resultsPerPage = 0)
  {
    $sqlWhere = '';
    $sqlJoin = '';
    $select = '';

    foreach ($conditions as $condition) {
      if (is_array($condition)) {
        $column = $condition[0];
        $value = $condition[1];
        $sqlWhere .= " AND " . $column . "='" . $this->db->escape($value) .  "'";
      }
      else if ($condition){ // ignore empty strings or other falsy values
        $sqlWhere .= " AND $condition ";
      }
    }

    if ($this->_catId) {
      $sqlJoin = " JOIN {$this->table_prefix}module_medialibrary_category_assignment mlca "
               . "   ON mb.MID = mlca.FK_MID AND mlca.FK_MCID = {$this->_catId} ";
      $orderBy = ' ORDER BY mlca.MCAPosition ASC ';
      $select = 'MCAPosition, MCAID';
    }
    else {
      $orderBy = ' ORDER BY MPosition ASC ';
    }

    $sqlLimit = '';
    if ($resultsPerPage > 0) {
      $sqlLimit = 'LIMIT ';
      if ($resultsPage > 0) {
        $offset = (($resultsPage - 1) * $resultsPerPage);
        $sqlLimit .= (int)$offset . ', ';
      }
      $sqlLimit .= (int)$resultsPerPage . ' ';
    }

    $sql = " SELECT MID, MTitle1, MTitle2, MTitle3, "
         . "       MText1, MText2, MText3, MImage1, MImage2, MImage3, MPosition, MUrl, "
         . "       MVideo1, MVideoType1, MVideo2, MVideoType2, MVideo3, MVideoType3, "
         . "       mb.FK_CIID, CIID, CTitle, CIIdentifier, c.FK_SID, "
         . "       MDisabled, MShowFromDateTime, MShowUntilDateTime "
         . "       ".(($select) ? ",".$select : "")
         . " FROM {$this->table_prefix}module_medialibrary mb "
         . " LEFT JOIN {$this->table_prefix}contentitem c ON mb.FK_CIID = c.CIID "
         . " $sqlJoin "
         . " WHERE mb.FK_SID = '$this->site_id' "
         . " $sqlWhere "
         . " $orderBy "
         . " $sqlLimit ";
    return $sql;
  }

  /**
   * @return array
   */
  private function _cachedItemRow()
  {
    if ($this->_cachedItemRow === null) {
      $id = (int)$this->item_id;
      $sql = " SELECT MID, MTitle1, MTitle2, MTitle3, MText1, MText2, MText3, "
           . "        MImage1, MImage2, MImage3, MImage4, MImage5, MImage6, "
           . "        MUrl, MImageTitles, MRandomlyShow, "
           . "        MVideo1, MVideoType1, MVideoDuration1, "
           . "        MVideo2, MVideoType2, MVideoDuration2, "
           . "        MVideo3, MVideoType3, MVideoDuration3, "
           . "        MShowFromDateTime, MShowUntilDateTime, "
           . "        mb.FK_CGAID, mb.FK_CIID, CIID, CIIdentifier, c.FK_SID, "
           . "        IDID, IDDocumentId, IDUsername, IDName, IDTitle, IDState " // issuu document
           . " FROM {$this->table_prefix}module_medialibrary mb "
           . " LEFT JOIN {$this->table_prefix}contentitem c "
           . "   ON mb.FK_CIID = c.CIID "
           . " LEFT JOIN {$this->table_prefix}issuu_document id "
           . "   ON FK_IDID = IDID "
           . " WHERE MID = $id ";
      $this->_cachedItemRow = $this->db->GetRow($sql);
    }

    return $this->_cachedItemRow;
  }

  /**
   * @return int
   */
  private function _getNumberOfTotalItems()
  {
    $result = $this->db->query($this->_readMediaLibraryItemsSql(array($this->_getCurrentFilterSqlWhere())));
    return (int)$this->db->num_rows($result);
  }

  /**
   * @return PageNavigation
   */
  private function _getPageNavigation()
  {
    global $_LANG;

    if ($this->_pageNavigation === null) {
      $navigation = new PageNavigation(new Pagination());
      $navigation->setLinkCurrentHtml($_LANG['global_results_showpage_current'])
        ->setLinkOtherHtml($_LANG['global_results_showpage_other'])
        ->setLinkFirstHtml($_LANG['global_results_showpage_first'])
        ->setLinkLastHtml($_LANG['global_results_showpage_last'])
        ->setLinkPreviousHtml($_LANG['global_results_showpage_previous'])
        ->setLinkNextHtml($_LANG['global_results_showpage_next'])
        ->setPageUrlParam('offset')
        ->setUrl($this->_parseUrl())
        ->setTotalResults($this->_getNumberOfTotalItems())
        ->setResultsPerPage((int)ConfigHelper::get('ms_results_per_page'))
        ->setLinksPerPage(5);
      $navigation->setCurrentPage(isset($_GET['offset']) ? $_GET['offset'] : (int)$this->session->read('ms_current_page'));
      $this->_pageNavigation = $navigation;

      $this->session->save('ms_current_page', $navigation->getPagination()->getCurrentPage());
    }
    return $this->_pageNavigation;
  }

  /**
   * Returns the current list filter
   *
   * @return array with 'type' and 'text' keys
   */
  private function _getCurrentFilter()
  {
    if ($this->_currentFilter === null) {
      $request = new Input(Input::SOURCE_REQUEST);
      $listFilterKeys = array_keys($this->_filters);
      $filterType = coalesce($request->readString('filter_type'),
        $this->session->read('ml_filter_type'),
        $listFilterKeys[0]);
      if (!isset($this->_filters[$filterType])) {
        $filterType = $listFilterKeys[0];
      }

      // If filter_text was sent with the request it has to be used, even if it's empty.
      if ($request->exists('filter_text')) {
        $filterText = $request->readString('filter_text');
      } else {
        $filterText = coalesce($this->session->read('ms_filter_text'), '');
      }

      $this->session->save('ms_filter_type', $filterType);
      $this->session->save('ms_filter_text', $filterText);

      $this->_currentFilter = array(
        'type' => $filterType,
        'text' => $filterText,
      );
    }

    return $this->_currentFilter;
  }

  /**
   * @return string
   */
  private function _getCurrentFilterSqlWhere()
  {
    $sql = '';
    $filter = $this->_getCurrentFilter();

    if ($filter['type'] && $filter['text'] && isset($this->_filters[$filter['type']])) {
      $sql = $this->_filters[$filter['type']][0] . $this->db->escape($filter['text']) . $this->_filters[$filter['type']][1];
    }

    return $sql;
  }
}