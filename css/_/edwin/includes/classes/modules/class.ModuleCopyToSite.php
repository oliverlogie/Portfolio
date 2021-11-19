<?php

/**
 * Copy content pages to other websites in system
 *
 * $LastChangedDate: 2016-04-07 10:40:32 +0200 (Do, 07 Apr 2016) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Benjamin Ulmer
 * @copyright (c) 2016 Q2E GmbH
 */
class ModuleCopyToSite extends Module
{
  protected $_prefix = 'cs';

  /**
   * @see ModuleCustomText::_input()
   * @var Input
   */
  private $_input;

  /**
   * @see AbstractModuleTreeManagement::_navigationPageMover()
   * @var NavigationPageMover
   */
  private $_navigationPageMover;

  public function show_innercontent()
  {
    $this->_copy();

    return $this->_getContent();
  }

  protected function _getContentLeftLinks()
  {
    return array();
  }

  /***
   * @return Input
   */
  protected function _input()
  {
    if ($this->_input === null) {
      $this->_input = new Input(Input::SOURCE_REQUEST);
    }

    return $this->_input;
  }

  /**
   * @return NavigationPageMover
   */
  protected function _navigationPageMover()
  {
    if ($this->_navigationPageMover === null) {
      $this->_navigationPageMover = new NavigationPageMover(
        $this->_navigation,
        $this->db,
        $this->table_prefix,
        $this->session,
        $this->tpl,
        $this->_user,
        Container::make('ContentItemLogService')
      );
    }

    return $this->_navigationPageMover;
  }

  /**
   * Edit an item
   */
  private function _copy()
  {
    global $_LANG;

    if (!$this->_input()->exists('process') && !$this->_input()->exists('process_and_edit')) {
      return;
    }

    $from = $this->_input()->readContentItemLink('cs_from');
    $to = $this->_input()->readContentItemLink('cs_to');

    try {
      $sourcePage = $this->_navigation->getPageByID($from[1]);
    }
    catch(Exception $e) {
      $this->setMessage(Message::createFailure($_LANG['cs_message_copy_failure_page_not_found']));
      return;
    }

    try {
      $targetPage = $this->_navigation->getPageByID($to[1]);
    }
    catch(Exception $e) {
      $this->setMessage(Message::createFailure($_LANG['cs_message_copy_failure_page_not_found']));
      return;
    }

    $ci = ContentItem::create(
      $this->site_id,
      $sourcePage->getID(),
      $this->tpl,
      $this->db,
      $this->table_prefix,
      '',
      $this->_user,
      $this->session,
      $this->_navigation
    );

    if (   $targetPage->isLevel()
        && $sourcePage->isRealLeaf()
        && !$sourcePage->isRoot()
        && $sourcePage->getParent()
        && $this->_configHelper->newItemAt($targetPage, $sourcePage->getRealContentType(), $this->_prefix)
    ) {
      try {
        $pageId = $ci->duplicate($sourcePage->getParent()->getID());

        // After duplicating the content item, we remove all relations to data,
        // that is site specific and can not bei used on target site for this
        // page
        //
        // 1. remove frontend user group relation
        $sql = " DELETE FROM {$this->table_prefix}frontend_user_group_pages "
             . " WHERE FK_CIID = $pageId ";
        $this->db->query($sql);

        // 2. remove attached campaigns
        $sql = " DELETE FROM {$this->table_prefix}campaign_contentitem "
             . " WHERE FK_CIID = $pageId ";
        $this->db->query($sql);

        // 3. remove internal links
        $sql = " DELETE FROM {$this->table_prefix}internallink "
             . " WHERE FK_CIID = $pageId ";
        $this->db->query($sql);

        // 4. remove external links
        $sql = " DELETE FROM {$this->table_prefix}externallink "
             . " WHERE FK_CIID = $pageId ";
        $this->db->query($sql);

        // 5. remove central downloads ( decentral downloads are not copied anyway )
        $sql = " DELETE FROM {$this->table_prefix}file "
             . " WHERE FK_CIID = $pageId ";
        $this->db->query($sql);

        // Clear cache and reload possible out to date NavigationPage objects

        Navigation::clearCache($this->db, $this->table_prefix);
        $targetPage = $this->_navigation->getPageByID($targetPage->getID());

        $this->_navigationPageMover()->move(
          $this->_navigation->getPageByID($pageId),
          $targetPage,
          $targetPage->getAllChildren()->count() + 1
        );
      }
      catch(Exception $e) {
        $this->setMessage(Message::createFailure($_LANG['cs_message_copy_failure_copy_not_available']));
        return;
      }
    }
    else {
      $this->setMessage(Message::createFailure($this->_configHelper->getMessage() ?: $_LANG['cs_message_copy_failure']));
      return;
    }

    if ($this->_input()->exists('process_and_edit')) {
      $this->_redirect('index.php?action=content&site=' . $targetPage->getSite()->getID() . '&page=' . $pageId,
        Message::createSuccess($_LANG['cs_message_copy_item_success']));
    }
    else {
      $this->_redirect($this->_parseUrl(),
        Message::createSuccess($_LANG['cs_message_copy_item_success']));
    }

  }

  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $from = $this->_input()->readContentItemLink('cs_from');
    $to = $this->_input()->readContentItemLink('cs_to');

    $autoCompleteUrl = 'index.php?action=mod_response_copytosite&site=' . $this->site_id . '&request=ContentItemAutoComplete';

    $this->tpl->load_tpl('content_cs', 'modules/ModuleCopyToSite.tpl');
    $this->tpl->parse_if('content_cs', 'message', (bool)$this->_getMessage(), $this->_getMessageTemplateArray('cs'));
    $content = $this->tpl->parsereturn('content_cs', array_merge(array(
      'cs_hidden_fields'                     => $hiddenFields,
      'cs_site'                              => $this->site_id,
      'cs_action'                            => 'index.php?action=mod_copytosite',
      'cs_autocomplete_contentitem_from_url' => $this->_getFromAutocompleteContentItemUrl($autoCompleteUrl),
      'cs_autocomplete_contentitem_to_url'   => $this->_getTargetAutocompleteContentItemUrl($autoCompleteUrl),
      'cs_from'                              => $from[0],
      'cs_from_id'                           => $from[1],
      'cs_to'                                => $to[0],
      'cs_to_id'                             => $to[1],
      'cs_site_selection'                    => $this->_parseModuleSiteSelection('copytosite', $_LANG['cs_site_label']),
    ), $_LANG2['cs']));

    return array(
      'content' => $content,
    );
  }

  /**
   * @param string $baseUrl
   *
   * @return string
   */
  private function _getFromAutocompleteContentItemUrl($baseUrl)
  {
    return $baseUrl . '&includeContentTypes=' . ContentType::TYPE_NORMAL;
  }

  /**
   * @param string $baseUrl
   *
   * @return string
   */
  private function _getTargetAutocompleteContentItemUrl($baseUrl)
  {
    $siteIds = $this->_user->getPermittedSites();
    unset($siteIds[$this->site_id]);
    $siteIds = array_keys($siteIds);

    return $baseUrl
      . '&includedSiteIDs=' . urlencode(implode(',', $siteIds))
      . '&includeContentTypes=' . ContentType::TYPE_LOGICAL_WITH_NAV;
  }
}