<?php

/**
 * SideBox Module Class
 *
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleSideBox extends Module
{
  /**
   * The database column prefix.
   *
   * @var string
   */
  protected $_dbColumnPrefix = 'B';

  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'sb';

  /**
   * Creates a side box.
   */
  private function _createSideBox()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new') {
      return;
    }

    $title1 = $post->readString('sb_title1', Input::FILTER_PLAIN);
    $title2 = $post->readString('sb_title2', Input::FILTER_PLAIN);
    $title3 = $post->readString('sb_title3', Input::FILTER_PLAIN);
    $text1 = $post->readString('sb_text1', Input::FILTER_CONTENT_TEXT);
    $text2 = $post->readString('sb_text2', Input::FILTER_CONTENT_TEXT);
    $text3 = $post->readString('sb_text3', Input::FILTER_CONTENT_TEXT);
    $noRandom = (int)!$post->readBool('sb_random');
    list($link, $linkID) = $post->readContentItemLink('sb_link');
    $extLink = $post->readString('sb_url', Input::FILTER_PLAIN);

    // Process attached campaign
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $cgAId = $cgAttached->process(array(
      'm_cg'             => $post->readInt('m_cg'),
      'm_cg_recipient'   => $post->readString('m_cg_recipient', Input::FILTER_PLAIN),
      'm_cg_data_origin' => $post->readString('m_cg_data_origin', Input::FILTER_PLAIN),
    ));

    if ($extLink) { // validate url protocol
      $valid = false;
      $protocols = $this->_configHelper->getVar('url_protocols', 'sb');

      foreach ($protocols as $protocol) {
        if (mb_substr($extLink, 0, mb_strlen($protocol)) === $protocol) {
          $valid = true;
          break;
        }
      }

      if (!$valid) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['sb_message_invalid_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    $image1 = isset($_FILES['sb_image1']) ? $this->_storeImage($_FILES['sb_image1'], null, 'sb', 1, null, false, false) : '';
    $image2 = isset($_FILES['sb_image2']) ? $this->_storeImage($_FILES['sb_image2'], null, 'sb', 2, null, false, false) : '';
    $image3 = isset($_FILES['sb_image3']) ? $this->_storeImage($_FILES['sb_image3'], null, 'sb', 3, null, false, false) : '';

    if (   !$title1 && !$title2 && !$title3
        && !$text1 && !$text2 && !$text3
        && !$image1 && !$image2 && !$image3
    ) {
      $this->setMessage(Message::createFailure($_LANG['sb_message_create_failure']));
      return;
    }

    $sql = 'SELECT COUNT(BID) + 1 '
         . "FROM {$this->table_prefix}module_sidebox "
         . "WHERE FK_SID = $this->site_id ";
    $position = $this->db->GetOne($sql);
    $sql = "INSERT INTO {$this->table_prefix}module_sidebox "
         . '(BTitle1, BTitle2, BTitle3, BText1, BText2, BText3, BPosition, '
         . ' BNoRandom, BUrl, FK_CGAID, FK_CIID, FK_SID) '
         . "VALUES ('{$this->db->escape($title1)}', '{$this->db->escape($title2)}', "
         . "        '{$this->db->escape($title3)}', '{$this->db->escape($text1)}', "
         . "        '{$this->db->escape($text2)}', '{$this->db->escape($text3)}', "
         . "        $position, $noRandom, '{$this->db->escape($extLink)}', "
         . "        '{$cgAId}', $linkID, $this->site_id) ";
    $result = $this->db->query($sql);
    $this->item_id = $this->db->insert_id();

    $sql = "UPDATE {$this->table_prefix}module_sidebox "
         . "SET BImage1 = '$image1', "
         . "    BImage2 = '$image2', "
         . "    BImage3 = '$image3' "
         . "WHERE BID = $this->item_id ";
    $result = $this->db->query($sql);

    $message = $this->_getMessage() ?: Message::createSuccess($_LANG['sb_message_create_success']);
    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(), $message);
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)), $message);
    }
  }

  /**
   * Updates a side box.
   */
  private function _updateSideBox()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'edit') {
      return;
    }

    $title1 = $post->readString('sb_title1', Input::FILTER_PLAIN);
    $title2 = $post->readString('sb_title2', Input::FILTER_PLAIN);
    $title3 = $post->readString('sb_title3', Input::FILTER_PLAIN);
    $text1 = $post->readString('sb_text1', Input::FILTER_CONTENT_TEXT);
    $text2 = $post->readString('sb_text2', Input::FILTER_CONTENT_TEXT);
    $text3 = $post->readString('sb_text3', Input::FILTER_CONTENT_TEXT);
    $noRandom = (int)!$post->readBool('sb_random');
    list($link, $linkID) = $post->readContentItemLink('sb_link');
    $extLink = $post->readString('sb_url', Input::FILTER_PLAIN);

    // Process attached campaign
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $cgAttached->id = $this->db->GetOne("SELECT FK_CGAID FROM {$this->table_prefix}module_sidebox WHERE BID = $this->item_id ");
    $cgAId = $cgAttached->process(array(
      'm_cg'             => $post->readInt('m_cg'),
      'm_cg_recipient'   => $post->readString('m_cg_recipient', Input::FILTER_PLAIN),
      'm_cg_data_origin' => $post->readString('m_cg_data_origin', Input::FILTER_PLAIN),
    ));
    if ($cgAId === false)
    {
      $this->setMessage($cgAttached->getMessage());
      return;
    }

    if ($extLink) { // validate url protocol
      $valid = false;
      $protocols = $this->_configHelper->getVar('url_protocols', 'sb');

      foreach ($protocols as $protocol) {
        if (mb_substr($extLink, 0, mb_strlen($protocol)) === $protocol) {
          $valid = true;
          break;
        }
      }

      if (!$valid) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['sb_message_invalid_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    $uploadImage1 = isset($_FILES['sb_image1']) && $_FILES['sb_image1']['size'];
    $uploadImage2 = isset($_FILES['sb_image2']) && $_FILES['sb_image2']['size'];
    $uploadImage3 = isset($_FILES['sb_image3']) && $_FILES['sb_image3']['size'];

    // Image upload.
    $sql = 'SELECT BImage1, BImage2, BImage3 '
         . "FROM {$this->table_prefix}module_sidebox "
         . "WHERE BID = $this->item_id ";
    $existingImages = $this->db->GetRow($sql);
    $image1 = $existingImages['BImage1'];
    $image2 = $existingImages['BImage2'];
    $image3 = $existingImages['BImage3'];

    if (   !$title1 && !$title2 && !$title3
        && !$text1 && !$text2 && !$text3
        && !$uploadImage1 && !$uploadImage2 && !$uploadImage3
        && !$image1 && !$image2 && !$image3
    ) {
      $this->setMessage(Message::createFailure($_LANG['sb_message_update_failure']));
      return;
    }

    if (isset($_FILES['sb_image1']) && $uploadedImage = $this->_storeImage($_FILES['sb_image1'], $image1, 'sb', 1, null, false, false)) {
      $image1 = $uploadedImage;
    }
    if (isset($_FILES['sb_image2']) && $uploadedImage = $this->_storeImage($_FILES['sb_image2'], $image2, 'sb', 2, null, false, false)) {
      $image2 = $uploadedImage;
    }
    if (isset($_FILES['sb_image3']) && $uploadedImage = $this->_storeImage($_FILES['sb_image3'], $image3, 'sb', 3, null, false, false)) {
      $image3 = $uploadedImage;
    }

    $sql = "UPDATE {$this->table_prefix}module_sidebox "
         . "SET BTitle1 = '{$this->db->escape($title1)}', "
         . "    BTitle2 = '{$this->db->escape($title2)}', "
         . "    BTitle3 = '{$this->db->escape($title3)}', "
         . "    BText1 = '{$this->db->escape($text1)}', "
         . "    BText2 = '{$this->db->escape($text2)}', "
         . "    BText3 = '{$this->db->escape($text3)}', "
         . "    BNoRandom = $noRandom, "
         . "    FK_CIID = $linkID, "
         . "    BUrl = '{$this->db->escape($extLink)}', "
         . "    BImage1 = '$image1', "
         . "    BImage2 = '$image2', "
         . "    BImage3 = '$image3', "
         . "    FK_CGAID = '{$cgAId}' "
         . "WHERE BID = $this->item_id ";
    $result = $this->db->query($sql);

    $message = $this->_getMessage() ?: Message::createSuccess($_LANG['sb_message_update_success']);
    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(), $message);
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)), $message);
    }
  }

  /**
   * Moves a side box if the GET parameters moveID and moveTo are set.
   */
  private function _moveSideBox()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $moveID = $get->readInt('moveID');
    $moveTo = $get->readInt('moveTo');

    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_sidebox",
                                         'BID', 'BPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['sb_message_move_success']));
    }
  }

  /**
   * Deletes a side box if the GET parameter deleteSideBoxID is set.
   */
  private function _deleteSideBox()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $ID = $get->readInt('deleteSideBoxID');
    if (!$ID) {
      return;
    }

    // Delete images.
    $sql = 'SELECT BImage1, BImage2, BImage3 '
         . "FROM {$this->table_prefix}module_sidebox "
         . "WHERE BID = $ID ";
    $images= $this->db->GetRow($sql);
    self::_deleteImageFiles($images);

    // Delete the side boxes assignments.
    $sql = "DELETE FROM {$this->table_prefix}module_sidebox_assignment "
         . "WHERE FK_BID = $ID ";
    $result = $this->db->query($sql);

    // Delete attached campaigns
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $cgAttached->id = $this->db->GetOne("SELECT FK_CGAID FROM {$this->table_prefix}module_sidebox WHERE BID = $ID ");
    $cgAttached->delete();

    // move item to last position before deleting it
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_sidebox",
                                         'BID', 'BPosition',
                                         'FK_SID', $this->site_id);
    $positionHelper->move($ID, $positionHelper->getHighestPosition());

    // Delete side box.
    $sql = "DELETE FROM {$this->table_prefix}module_sidebox "
         . "WHERE BID = $ID ";
    $result = $this->db->query($sql);

    if ($result) {
      if (isset($_LANG['sb_message_deleteitem_success'])) {
        return $_LANG['sb_message_deleteitem_success'];
      }

      return $_LANG['global_message_deleteitem_success'];
    }

    return false;
  }

  /**
   * Deletes a side box image if the GET parameter dimg is set.
   */
  private function _deleteSideBoxImage()
  {
    $get = new Input(Input::SOURCE_GET);

    $imageNumber = $get->readInt('dimg');
    if (!$imageNumber) {
      return;
    }

    $this->delete_content_image('sidebox', 'sidebox', 'BID', 'BImage', $imageNumber);
  }

  /**
   * Shows the form for creating or editing side boxes.
   */
  private function _showForm()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    $noRandomConfig = ConfigHelper::get('sb_no_random', '', $this->site_id);
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);

    // edit sidebox -> load data
    $row = array();
    if ($this->item_id)
    {
      $sql = 'SELECT BID, BTitle1, BTitle2, BTitle3, BText1, BText2, BText3, '
           . '       BImage1, BImage2, BImage3, BNoRandom, BUrl, '
           . '       sb.FK_CIID, CIID, CIIdentifier, c.FK_SID, sb.FK_CGAID '
           . "FROM {$this->table_prefix}module_sidebox sb "
           . "LEFT JOIN {$this->table_prefix}contentitem c ON sb.FK_CIID = c.CIID "
           . "WHERE BID = $this->item_id ";
      $row = $this->db->GetRow($sql);

      $title1 = $row['BTitle1'];
      $title2 = $row['BTitle2'];
      $title3 = $row['BTitle3'];
      $text1 = $row['BText1'];
      $text2 = $row['BText2'];
      $text3 = $row['BText3'];
      $imageSource1 = $row['BImage1'];
      $imageSource2 = $row['BImage2'];
      $imageSource3 = $row['BImage3'];
      $random = $row['BNoRandom'] ? '' : 'checked="checked"';
      $extLink = $row['BUrl'];
      $cgAttached = $cgAttached->readCampaignAttachedById($row['FK_CGAID']);
      $function = 'edit';
    }
    else { // new sidebox
      $title1 = $post->readString('sb_title1', Input::FILTER_PLAIN);
      $title2 = $post->readString('sb_title2', Input::FILTER_PLAIN);
      $title3 = $post->readString('sb_title3', Input::FILTER_PLAIN);
      $text1 = $post->readString('sb_text1', Input::FILTER_CONTENT_TEXT);
      $text2 = $post->readString('sb_text2', Input::FILTER_CONTENT_TEXT);
      $text3 = $post->readString('sb_text3', Input::FILTER_CONTENT_TEXT);
      $extLink = $post->readString('sb_url', Input::FILTER_PLAIN);
      $cgAttached->parentId = $post->readInt('m_cg');
      $cgAttached->dataOrigin = $post->readString('m_cg_data_origin', Input::FILTER_PLAIN);
      $cgAttached->recipient = $post->readString('m_cg_recipient', Input::FILTER_PLAIN);
      $imageSource1 = '';
      $imageSource2 = '';
      $imageSource3 = '';
      $random = 'checked="checked"';
      $function = 'new';
    }

    $hiddenFields = '<input type="hidden" name="action" value="mod_sidebox" />'
                  . '<input type="hidden" name="action2" value="main;' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $autoCompleteUrl = 'index.php?action=mod_response_sidebox&site=' . $this->site_id
                     . '&request=ContentItemAutoComplete';

    $this->tpl->load_tpl('content_sidebox', 'modules/ModuleSideBox.tpl');
    $this->tpl->parse_if('content_sidebox', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('sb'));
    $this->tpl->parse_if('content_sidebox', 'delete_image1', $imageSource1, $this->get_delete_image('sidebox', 'sb', 1));
    $this->tpl->parse_if('content_sidebox', 'delete_image2', $imageSource2, $this->get_delete_image('sidebox', 'sb', 2));
    $this->tpl->parse_if('content_sidebox', 'delete_image3', $imageSource3, $this->get_delete_image('sidebox', 'sb', 3));
    $this->tpl->parse_if('content_sidebox', 'create_assignments', !$noRandomConfig);
    $this->tpl->parse_if('content_sidebox', 'item_is_edited', $this->item_id);
    $this->tpl->parse_if('content_sidebox', 'item_is_edited', $this->item_id);

    $content = $this->tpl->parsereturn('content_sidebox', array_merge(
      $this->_getUploadedImageDetails($imageSource1, $this->_prefix, $this->_prefix, 1),
      $this->_getUploadedImageDetails($imageSource2, $this->_prefix, $this->_prefix, 2),
      $this->_getUploadedImageDetails($imageSource3, $this->_prefix, $this->_prefix, 3),
      $this->getInternalLinkHelper($row['FK_CIID'] ?? 0)->getTemplateVars($this->_prefix),
      array(
        'sb_title1' => $title1,
        'sb_title2' => $title2,
        'sb_title3' => $title3,
        'sb_text1' => $text1,
        'sb_text2' => $text2,
        'sb_text3' => $text3,
        'sb_required_resolution_label1' => $this->_getImageSizeInfo('sb', 1),
        'sb_required_resolution_label2' => $this->_getImageSizeInfo('sb', 2),
        'sb_required_resolution_label3' => $this->_getImageSizeInfo('sb', 3),
        'sb_large_image_available1' => $this->_getImageZoomLink('sb', $imageSource1),
        'sb_large_image_available2' => $this->_getImageZoomLink('sb', $imageSource2),
        'sb_large_image_available3' => $this->_getImageZoomLink('sb', $imageSource3),
        'sb_random' => $random,
        'sb_url' => $extLink,
        'sb_site' => $this->site_id,
        'sb_function_label' => $_LANG["sb_function_{$function}_label"],
        'sb_function_label2' => $_LANG["sb_function_{$function}_label2"],
        'sb_action' => 'index.php',
        'sb_hidden_fields' => $hiddenFields,
        'sb_autocomplete_contentitem_global_url' => $autoCompleteUrl . '&scope=global',
        'sb_module_action_boxes' => $this->_getContentActionBoxes(),
        'sb_campaign_form_attachment' => $this->_parseModuleCampaignFormAttachment($cgAttached),
        'sb_page_assignment' => $this->_parseModulePageAssignment(),
        'sb_display_on_info_text' => $this->_getDisplayOnInfoText($random, count($this->_readPageAssignments())),
    ), $_LANG2['sb']));

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

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_sidebox",
                                         'BID', 'BPosition',
                                         'FK_SID', $this->site_id);

    // read sideboxes
    $sidebox_items = array ();
    $sql = " SELECT BID, BTitle1, BText1, BImage1, BImage2, BPosition, BUrl, "
         . "        BNoRandom, COUNT(sba.FK_BID) AS NumberOfAssignments, "
         . "        sb.FK_CIID, CIID, CTitle, CIIdentifier, c.FK_SID "
         . " FROM {$this->table_prefix}module_sidebox sb "
         . " LEFT JOIN {$this->table_prefix}contentitem c "
         . "        ON sb.FK_CIID = c.CIID "
         . " LEFT JOIN {$this->table_prefix}module_sidebox_assignment sba "
         . "        ON sba.FK_BID = BID "
         . " WHERE sb.FK_SID = '$this->site_id' "
         . " GROUP BY BID, BTitle1, BText1, BImage1, BImage2, BPosition, BUrl, "
         . "          BNoRandom, FK_CIID, CIID, CTitle, CIIdentifier, c.FK_SID "
         . " ORDER BY BPosition ASC ";

    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['BPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['BPosition']);

      // Detect invalid and invisible links.
      $internalLink = $this->getInternalLinkHelper($row['FK_CIID']);
      $intLink = '';
      if ($internalLink->isValid()) {
        $intLink = sprintf($_LANG['sb_intlink_link'], $internalLink->getEditUrl(), $internalLink->getHierarchicalTitle("/"));
      }

      $sidebox_items[] = array_merge($internalLink->getTemplateVars($this->_prefix), array(
        'sb_title1' => parseOutput($row['BTitle1']),
        'sb_text1' => parseOutput($row['BText1']),
        'sb_image_src1' => ($row['BImage1'] ? '../'.$row['BImage1'] : ( $row['BImage2'] ? '../'.$row['BImage2'] : 'img/no_image.png')),
        'sb_id' => $row['BID'],
        'sb_position' => $row['BPosition'],
        'sb_content_link' => "index.php?action=mod_sidebox&amp;action2=main;edit&amp;site=$this->site_id&amp;page={$row['BID']}",
        'sb_delete_link' => "index.php?action=mod_sidebox&amp;deleteSideBoxID={$row['BID']}",
        'sb_move_up_link' => "index.php?action=mod_sidebox&amp;site=$this->site_id&amp;moveID={$row['BID']}&amp;moveTo=$moveUpPosition",
        'sb_move_down_link' => "index.php?action=mod_sidebox&amp;site=$this->site_id&amp;moveID={$row['BID']}&amp;moveTo=$moveDownPosition",
        'sb_extlink_link' => $row['BUrl'] ? sprintf($_LANG['sb_extlink_link'], $row['BUrl'], $row['BUrl']) : '',
        'sb_intlink_link' => $intLink,
        'sb_display_on_info_text' => $this->_getDisplayOnInfoText(!$row['BNoRandom'], (int)$row['NumberOfAssignments']),
      ));
    }
    $this->db->free_result($result);

    if (!$sidebox_items) {
      $this->setMessage(Message::createFailure($_LANG['sb_message_no_sidebox']));
    }

    $action = 'index.php?action=mod_sidebox';
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    // Parse the list template.
    $this->tpl->load_tpl('sidebox', 'modules/ModuleSideBox_list.tpl');
    $this->tpl->parse_if('sidebox', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('sb'));
    $this->tpl->parse_loop('sidebox', $sidebox_items, 'sidebox_items');
    $content = $this->tpl->parsereturn('sidebox', array_merge(array(
      'sb_action' => $action,
      'sb_hidden_fields' => $hiddenFields,
      'sb_site_selection' => $this->_parseModuleSiteSelection('sidebox', $_LANG['sb_site_label']),
      'sb_dragdrop_link_js' => "index.php?action=mod_sidebox&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
      'sb_list_label' => $_LANG['sb_function_list_label'],
      'sb_list_label2' => $_LANG['sb_function_list_label2'],
    ), $_LANG2['sb']));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * Shows module's content.
   *
   * @see Module::show_innercontent()
   */
  public function show_innercontent()
  {
    // Perform create/update/move/delete of a side box if necessary
    $this->_createSideBox();
    $this->_updateSideBox();
    $this->_moveSideBox();
    $this->_deleteSideBox();

    // Delete a side box image.
    $this->_deleteSideBoxImage();

    if (!empty($this->action[0])) {
      return $this->_showForm();
    } else {
      return $this->_showList();
    }
  }
}

