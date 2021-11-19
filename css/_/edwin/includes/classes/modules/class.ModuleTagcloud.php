<?php

/**
 * ModuleTagCloud
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleTagcloud extends Module
{

  /**
   * Number of invalid (deleted) content links
   *
   * @var int
   */
  private $_invalidLinks = 0;

  /**
   * Number of invisible (disabled, locked, ...) content links
   *
   * @var int
   */
  private $_invisibleLinks = 0;

  /**
   * The category id.
   *
   * @var int
   */
  private $_catId = 0;

  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'tc';

  /* (non-PHPdoc)
   * @see Module::show_innercontent()
   */
  public function show_innercontent ()
  {
    $get = new Input(Input::SOURCE_GET);
    $post = new Input(Input::SOURCE_POST);
    $this->_catId = $get->readInt('cat_id');
    if (!$this->_catId) {
      $this->_catId = $post->readInt('tc_category_id');
    }

    $this->_create();
    $this->_delete();
    $this->_edit();
    $this->_move();

    if (isset($this->action[0]) && $this->action[0]) {
      return $this->_getContent();
    } else {
      return $this->_listContent();
    }
  }

  protected function _getModuleUrlParts()
  {
    return array_merge(parent::_getModuleUrlParts(), array(
        'cat_id' => $this->_catId,
    ));
  }

  /**
   * Creates a new tag
   */
  private function _create()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new' || !$this->_catId) {
      return;
    }

    $title = $post->readString('tc_title');
    $size = $post->readInt('tc_size');
    $extLink = $post->readString('tc_url');
    $customInternalLink = $post->exists('tc_custom_link') ? 1 : 0;
    list($link, $linkID) = $post->readContentItemLink('tc_link');
    // If internal link should be a custom internal link, get it and validate it.
    if ($customInternalLink && $post->readString('tc_link'))
    {
      $link = $post->readString('tc_link');
      // Validate url protocol of custom internal link
      $protocols = $this->_configHelper->getVar('url_protocols', array('tc_int', 'tc'));
      $valid = $this->_validateUrlProtocol($link, $protocols);
      if (!$valid)
      {
        $this->setMessage(Message::createFailure(sprintf($_LANG['tc_message_invalid_internal_url_protocol'], implode(', ', $protocols))));
        return;
      }

      $linkedPage = $this->_navigation->getPageByUrl($link);
      if (!$linkedPage)
      {
        $this->setMessage(Message::createFailure($_LANG['tc_message_invalid_internal_url']));
        return;
      }
      // Set content item id
      else {
        $linkID = $linkedPage->getID();
      }
    }

    // Validate url protocol of external link
    if ($extLink)
    {
      $protocols = $this->_configHelper->getVar('url_protocols', array('tc_ext', 'tc'));
      $valid = $this->_validateUrlProtocol($extLink, $protocols);
      if (!$valid)
      {
        $this->setMessage(Message::createFailure(sprintf($_LANG['tc_message_invalid_external_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    // if there is no title, show error message
    if (!$title)
    {
      $this->setMessage(Message::createFailure($_LANG['tc_message_failure_no_title']));
      return;
    }
    // if there is no link, show error message
    else if (!$link && !$extLink)
    {
      $this->setMessage(Message::createFailure($_LANG['tc_message_no_link']));
      return;
    }

    $sql = " SELECT TCID "
         . " FROM {$this->table_prefix}module_tagcloud "
         . " WHERE TCTitle LIKE '{$this->db->escape($title)}' "
         . "   AND FK_SID = $this->site_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["tc_message_failure_existing"]));
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_tagcloud",
                                         'TCID', 'TCPosition',
                                         'FK_TCCID', $this->_catId);
    $position = $positionHelper->getHighestPosition() + 1;

    $sqlArgs = array(
      'TCTitle'       => "'{$this->db->escape($title)}'",
      'TCSize'        => $size,
      'TCInternalUrl' => ($customInternalLink) ? "'{$this->db->escape($link)}'" : "''",
      'TCUrl'         => "'{$this->db->escape($extLink)}'",
      'TCPosition'    => $position,
      'FK_TCCID'      => $this->_catId,
      'FK_CIID'       => $linkID,
      'FK_SID'        => $this->site_id,
    );

    $sqlFields = implode(',', array_keys($sqlArgs));
    $sqlValues = implode(',', array_values($sqlArgs));

    $sql = " INSERT INTO {$this->table_prefix}module_tagcloud ($sqlFields) "
         . " VALUES ($sqlValues)";
    $result = $this->db->query($sql);

    if ($result) {
      $this->item_id = $this->db->insert_id();
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['tc_message_new_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['tc_message_new_item_success']));
      }
    }
  }

  /**
   * Edit an attribute group
   */
  private function _edit()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'edit' || !$this->_catId) {
      return;
    }

    $title = $post->readString('tc_title');
    $size = $post->readInt('tc_size');
    $extLink = $post->readString('tc_url');
    $customInternalLink = $post->exists('tc_custom_link') ? 1 : 0;
    list($link, $linkID) = $post->readContentItemLink('tc_link');
    // If internal link should be a custom internal link, get it and validate it.
    if ($customInternalLink && $post->readString('tc_link'))
    {
      $link = $post->readString('tc_link');
      // Validate url protocol of custom internal link
      $protocols = $this->_configHelper->getVar('url_protocols', array('tc_int', 'tc'));
      $valid = $this->_validateUrlProtocol($link, $protocols);
      if (!$valid)
      {
        $this->setMessage(Message::createFailure(sprintf($_LANG['tc_message_invalid_internal_url_protocol'], implode(', ', $protocols))));
        return;
      }

      $linkedPage = $this->_navigation->getPageByUrl($link);
      if (!$linkedPage)
      {
        $this->setMessage(Message::createFailure($_LANG['tc_message_invalid_internal_url']));
        return;
      }
      // Set content item id
      else {
        $linkID = $linkedPage->getID();
      }
    }

    // Validate url protocol of external link
    if ($extLink)
    {
      $protocols = $this->_configHelper->getVar('url_protocols', array('tc_ext', 'tc'));
      $valid = $this->_validateUrlProtocol($extLink, $protocols);
      if (!$valid)
      {
        $this->setMessage(Message::createFailure(sprintf($_LANG['tc_message_invalid_external_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    // if there is no title, show error message
    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG['tc_message_failure_no_title']));
      return;
    }
    // if there is no link, show error message
    else if (!$link && !$extLink) {
      $this->setMessage(Message::createFailure($_LANG['tc_message_no_link']));
      return;
    }

    $sql = " SELECT TCID "
         . " FROM {$this->table_prefix}module_tagcloud "
         . " WHERE TCTitle LIKE '{$this->db->escape($title)}' "
         . "   AND FK_SID = $this->site_id "
         . "   AND TCID != $this->item_id ";
    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG["tc_message_failure_existing"]));
      return;
    }

    $link = ($customInternalLink) ? "'{$this->db->escape($link)}'" : "''";
    $sql = " UPDATE {$this->table_prefix}module_tagcloud "
         . "    SET TCTitle = '{$this->db->escape($title)}', "
         . "        TCSize = '$size', "
         . "        TCInternalUrl = $link, "
         . "        TCUrl = '{$this->db->escape($extLink)}', "
         . "        FK_CIID = $linkID "
         . " WHERE TCID = $this->item_id "
         . "   AND FK_SID = $this->site_id ";
    $result = $this->db->query($sql);

    if (!$this->_getMessage() && $result) {
      if ($this->_redirectAfterProcessingRequested('list')) {
        $this->_redirect($this->_getBackLinkUrl(),
            Message::createSuccess($_LANG['tc_message_edit_item_success']));
      }
      else {
        $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)),
            Message::createSuccess($_LANG['tc_message_edit_item_success']));
      }
    }
  }

  /**
   * Delete an item, if $_GET parameter 'deleteID' is set
   */
  private function _delete()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $id = $get->readInt('deleteID');

    if (!$id) {
      return;
    }

    // move item to last position before deleting it
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_tagcloud",
                                         'TCID', 'TCPosition',
                                         'FK_TCCID', $this->_catId);
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    $sql = " DELETE FROM {$this->table_prefix}module_tagcloud "
         . " WHERE FK_SID = $this->site_id "
         . "   AND TCID = $id ";
    $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['tc_message_delete_item_success']));
  }

  /**
   * Generates HTML options of tagcloud category models.
   *
   * @param ModelList $categories
   *        A list with TagcloudCategory models.
   * @return string
   *         HTML options.
   */
  private function _generateCategoryOptions($categories)
  {
    $options = '';
    foreach ($categories as $category) {
      /* @var $category TagcloudCategory */
      if (!$this->_catId) {
        $this->_catId = $category->id;
      }
      $selected = '';
      if ($category->id == $this->_catId) {
        $selected = 'selected="selected"';
      }
      $options .= '<option value="'.$category->id.'" '.$selected.'>'.parseOutput($category->title1).'</option>';
    }

    return $options;
  }

  /**
   * Get edit / create content
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    if (!$this->_catId) {
      header('Location: index.php?action=mod_tagcloud');
      exit;
    }
    $post = new Input(Input::SOURCE_POST);

    // edit tag -> load data
    if ($this->item_id) {
      $sql = " SELECT TCID, TCTitle, TCSize, TCInternalUrl, TCUrl, tc.FK_CIID, TCPosition, "
           . "        c.CIID, c.CIIdentifier, c.FK_SID "
           . " FROM {$this->table_prefix}module_tagcloud tc "
           . " LEFT JOIN {$this->table_prefix}contentitem c "
           . "   ON tc.FK_CIID = c.CIID "
           . " WHERE tc.FK_SID = $this->site_id "
           . "   AND TCID = $this->item_id ";
      $result = $this->db->query($sql);
      $row = $this->db->fetch_row($result);
      $linkDetails = $this->_getInternalLinkDetails($row['CIID'], $row['FK_SID'], $row['TCInternalUrl'], ($row['TCUrl']) ? true : false);
      $link = '';
      if ($row['FK_CIID'] && $row['TCInternalUrl']) {
        $link = $row['TCInternalUrl'];
      }
      else if ($row['FK_CIID']) {
        $link = $row['CIIdentifier'];
      }
      if ($post->exists('tc_link_id')) {
        $linkDetails = array(
          'tc_link_id'          => $post->readInt('tc_link_id'),
          'tc_link_scope'       => '',
          'tc_link_scope_label' => '',
          'tc_link_class'       => '',
        );
      }
      $link = ($post->exists('tc_title')) ? $post->readString('tc_link') : $link;
      $title = ($post->exists('tc_title')) ? $post->readString('tc_title') : $row['TCTitle'];
      $size = ($post->exists('tc_size')) ? $post->readInt('tc_size') : $row['TCSize'];
      $extLink = ($post->exists('tc_url')) ? $post->readString('tc_url') : $row['TCUrl'];
      $customInternalLink = $post->exists('tc_custom_link') ? 1 : ($row['TCInternalUrl'] ? 1 : 0);
      $function = 'edit';
      if ($this->_invalidLinks) {
        $this->setMessage(Message::createFailure($_LANG['tc_message_invalid_link']));
      }
      else if ($this->_invisibleLinks) {
        $this->setMessage(Message::createFailure($_LANG['tc_message_invisible_link']));
      }
      $this->db->free_result($result);
    }
    // new tag
    else {
      $link = $post->readString('tc_link');
      // template variables of the internal link
      $linkDetails = array(
        'tc_link_id'          => $post->readInt('tc_link_id'),
        'tc_link_scope'       => '',
        'tc_link_scope_label' => '',
        'tc_link_class'       => '',
      );
      $title = $post->readString('tc_title', Input::FILTER_PLAIN);
      $size = $post->readInt('tc_size');
      $extLink = $post->readString('tc_url', Input::FILTER_PLAIN);
      $customInternalLink = $post->exists('tc_custom_link') ? 1 : 0;
      $function = 'new';
    }

    $sizeOptions = '';
    $tmpOptions = $_LANG['tc_tag_sizes'];
    foreach ($tmpOptions as $key => $val) {
      $sizeOptions .= '<option value="' . $key . '"';
      if ($size == $key) {
        $sizeOptions .= ' selected="selected" ';
      }

      $sizeOptions .= '>' . $val . '</option>';
    }

    $hiddenFields = '<input type="hidden" name="action" value="mod_tagcloud" />'
                  . '<input type="hidden" name="action2" value="main;'.$function.'" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="tc_category_id" value="' . $this->_catId . '" />';

    $autoCompleteUrl = 'index.php?action=mod_response_tagcloud&site=' . $this->site_id
                     . '&request=ContentItemAutoComplete';

    $this->tpl->load_tpl('content_tc', 'modules/ModuleTagcloud.tpl');
    $this->tpl->parse_if('content_tc', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('tc'));
    $tc_content = $this->tpl->parsereturn('content_tc', array_merge($linkDetails, array (
      'tc_action'           => "index.php",
      'tc_autocomplete_contentitem_global_url' => $autoCompleteUrl . '&scope=global',
      'tc_custom_link_checked' => $customInternalLink ? 'checked="checked"' : '',
      'tc_function_label'   => $this->item_id ? $_LANG['tc_function_edit_label'] : $_LANG['tc_function_new_label'],
      'tc_function_label2'  => $this->item_id ? $_LANG['tc_function_edit_label2'] : $_LANG['tc_function_new_label2'],
      'tc_hidden_fields'    => $hiddenFields,
      'tc_link'             => $link,
      'tc_size_options'     => $sizeOptions,
      'tc_title'            => parseOutput($title),
      'tc_url'              => $extLink,
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['tc']));

    return array(
        'content'      => $tc_content,
        'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * Gets details of an internal link and will detect
   * invalid or invisible links.
   *
   * @param int $cIId
   *        The content item id of the linked page.
   * @param int $siteId
   *        The site id of the linked page.
   * @param int $internalUrl
   *        An internal custom link.
   * @param boolean $external
   * @return array
   *         Array with template variable keys
   */
  private function _getInternalLinkDetails($cIId, $siteId, $internalUrl, $external = false)
  {
    global $_LANG;

    $linkedPage = null;
    $linkClass = 'normal';
    $linkScope = 'none';
    $linkedSiteTitle = '';
    $linkUrl = '#';
    $link = '';
    $linkTitle = '';

    if (!$cIId && !$siteId && !$internalUrl && $external)
    {
      return array(
        'tc_link_id'          => '',
        'tc_link_scope'       => $linkScope,
        'tc_link_scope_label' => '',
        'tc_link_class'       => $linkClass,
        'tc_link_url'         => $linkUrl,
        'tc_link_url_title'   => '',
        'tc_link'             => $link,
      );
    }

    // Detect invisible links and link details
    if ($cIId)
    {
      $linkedPage = $this->_navigation->getPageByID((int)$cIId);
      $linkUrl = ($internalUrl) ? $internalUrl : $linkedPage->getDirectUrl();
      $linkTitle = $linkedPage->getTitle();
      $link = $this->_getHierarchicalTitle($cIId, "/");
      if (!$linkedPage->isVisible())
      {
        $linkClass = 'invisible';
        $this->_invisibleLinks ++;
      }
      $linkScope = ($internalUrl) ? 'custom_local' : 'local';
      if ((int) $siteId != $this->site_id) {
        $linkScope = 'global';
      }
    }
    // If there is no content item id, we have got a deleted (invalid) link
    else
    {
      $linkClass = 'invalid';
      $this->_invalidLinks ++;
    }

    if ($linkedPage) {
      $linkedSiteTitle = $linkedPage->getSite()->getTitle();
    }
    $linkScopeLabel = sprintf($_LANG["tc_link_scope_{$linkScope}_label"], $linkedSiteTitle);

    return array(
      'tc_link_id'          => $cIId,
      'tc_link_scope'       => $linkScope,
      'tc_link_scope_label' => $linkScopeLabel,
      'tc_link_class'       => $linkClass,
      'tc_link_url'         => $linkUrl,
      'tc_link_url_title'   => parseOutput($linkTitle).' - '.$linkUrl,
      'tc_link'             => $link,
    );
  }

  /**
   * Shows a list containing all tags
   *
   * @return array
   *         Contains backend content.
   */
  private function _listContent()
  {
    global $_LANG, $_LANG2;

    // Read tagcloud categories of current site
    $category = new TagcloudCategory($this->db, $this->table_prefix, $this->_prefix);
    $condition = array('select' => array('id', 'title1'),
                       'where'  => "FK_SID = $this->site_id",
                       'order'  => "TCCPosition ASC");
    $categories = $category->readTagcloudCategories($condition);
    $catAvailable = true;
    if (!count($categories)) {
      $catAvailable = false;
      $this->setMessage(Message::createFailure($_LANG['tc_message_no_categories']));
    }
    $categoryOptions = $this->_generateCategoryOptions($categories);

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_tagcloud",
                                         'TCID', 'TCPosition',
                                         'FK_TCCID', $this->_catId);

    $sql = " SELECT TCID, TCTitle, TCSize, TCInternalUrl, TCUrl, tc.FK_CIID, TCPosition, "
         . "        c.CIID, c.CIIdentifier, c.FK_SID "
         . " FROM {$this->table_prefix}module_tagcloud tc "
         . " LEFT JOIN {$this->table_prefix}contentitem c "
         . "   ON tc.FK_CIID = c.CIID "
         . " WHERE tc.FK_SID = $this->site_id "
         . "   AND tc.FK_TCCID = '{$this->db->escape($this->_catId)}' "
         . " ORDER BY TCPosition ASC ";
    $result = $this->db->query($sql);

    $count = $this->db->num_rows($result);
    $items = array();
    while ($row = $this->db->fetch_row($result))
    {
      $tmpId = (int)$row['TCID'];
      $tmpPos = (int)$row['TCPosition'];
      $moveUpPosition = $positionHelper->getMoveUpPosition($tmpPos);
      $moveDownPosition = $positionHelper->getMoveDownPosition($tmpPos);
      $linkDetails = $this->_getInternalLinkDetails($row['CIID'], $row['FK_SID'], $row['TCInternalUrl'], ($row['TCUrl']) ? true : false);
      if (!$row['CIID'] && $row['TCUrl'])
      {
        $linkDetails['tc_link_url'] = $row['TCUrl'];
        $linkDetails['tc_link_url_title'] = $row['TCUrl'];
        $linkDetails['tc_link'] = $row['TCUrl'];
      }

      $items[$tmpId] = array_merge($linkDetails, array(
        'tc_delete_link'    => "index.php?action=mod_tagcloud&amp;cat_id=$this->_catId&amp;site=$this->site_id&amp;deleteID={$tmpId}",
        'tc_edit_link'      => "index.php?action=mod_tagcloud&amp;action2=main;edit&amp;site=$this->site_id&amp;page={$tmpId}&amp;cat_id=$this->_catId",
        'tc_id'             => $tmpId,
        'tc_move_up_link'   => "index.php?action=mod_tagcloud&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveUpPosition&amp;cat_id=$this->_catId",
        'tc_move_down_link' => "index.php?action=mod_tagcloud&amp;site=$this->site_id&amp;moveID={$tmpId}&amp;moveTo=$moveDownPosition&amp;cat_id=$this->_catId",
        'tc_position'       => $tmpPos,
        'tc_size'           => (isset($_LANG['tc_tag_sizes'][$row['TCSize']])) ? $_LANG['tc_tag_sizes'][$row['TCSize']] : '',
        'tc_title'          => parseOutput($row['TCTitle']),
      ));
    }
    $this->db->free_result($result);

    if (!$items) {
      $this->setMessage(Message::createFailure($_LANG['tc_message_no_items']));
    }
    if (($this->_invalidLinks + $this->_invisibleLinks) > 1) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['tc_message_bad_links'], ($this->_invalidLinks + $this->_invisibleLinks))));
    }
    else if ($this->_invalidLinks) {
      $this->setMessage(Message::createFailure($_LANG['tc_message_invalid_link']));
    }
    else if ($this->_invisibleLinks) {
      $this->setMessage(Message::createFailure($_LANG['tc_message_invisible_link']));
    }

    // parse list template
    $this->tpl->load_tpl('content_tc', 'modules/ModuleTagcloud_list.tpl');
    $this->tpl->parse_if('content_tc', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('tc'));
    $this->tpl->parse_loop('content_tc', $items, 'entries');
    $this->tpl->parse_if('content_tc', 'entries_available', $count);
    $this->tpl->parse_if('content_tc', 'categories_available', $catAvailable);
    $tc_content = $this->tpl->parsereturn('content_tc', array_merge(array(
      'tc_action'           => 'index.php?action=mod_tagcloud',
      'tc_category_id'      => $this->_catId,
      'tc_category_options' => $categoryOptions,
      'tc_dragdrop_link_js' => "index.php?action=mod_tagcloud&site=$this->site_id&cat_id={$this->_catId}&moveID=#moveID#&moveTo=#moveTo#",
      'tc_site'             => $this->site_id,
      'tc_site_selection'   => $this->_parseModuleSiteSelection('tagcloud', $_LANG['tc_site_label']),
    ), $_LANG2['tc']));

    return array(
      'content'      => $tc_content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * Moves an tag if the GET parameters moveID and moveTo are set.
   */
  private function _move()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if (!$get->exists('moveID', 'moveTo', 'cat_id')) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_tagcloud",
                                         'TCID', 'TCPosition',
                                         'FK_TCCID', $this->_catId);
    $moved = $positionHelper->move($get->readInt('moveID'), $get->readInt('moveTo'));

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['tc_message_move_success']));
    }
  }

  /**
   * Checks if given url uses a valid protocol
   *
   * @param string $link
   *        The url to validate.
   * @param array $protocols
   *        An array that contains allowed protocols (http://, ..).
   * @return boolean
   *         True on success.
   */
  private function _validateUrlProtocol($link, $protocols)
  {
    $valid = false;
    foreach ($protocols as $protocol)
    {
      if (mb_substr($link, 0, mb_strlen($protocol)) === $protocol)
      {
        $valid = true;
        break;
      }
    }

    return $valid;
  }

}