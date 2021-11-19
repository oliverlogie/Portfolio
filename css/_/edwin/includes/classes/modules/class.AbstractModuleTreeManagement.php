<?php
/**
 * AbstractModuleTreeManagement Module class
 *
 * $LastChangedDate: 2017-10-16 07:32:10 +0200 (Mo, 16 Okt 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
abstract class AbstractModuleTreeManagement extends Module
{
  /**
   * The source page the sitemap is called for
   *
   * @var NavigationPage
   */
  protected $_sourcePage;

  /**
   * The prefix to use for template / language variables
   *
   * @var string
   */
  protected $_prefix = 'tm';

  /**
   * The module shortname. Set within Module class extending this class.
   *
   * @var string
   */
  protected $_shortname = '';

  /**
   * The template anchor to source page
   *
   * @var string
   */
  protected $_tplAnchor = '';

  /**
   * @see AbstractModuleTreeManagement::_navigationPageMover()
   * @var NavigationPageMover
   */
  private $_navigationPageMover;

  /**
   * Set the source page, if allowed.
   *
   * Use this method to set valid source pages only. There exist multiple
   * content types, which can not be moved (depending on Module).
   *
   * @param NavigationPage $page
   *        The source page
   *
   * @return void
   */
  protected abstract function _setSourcePage(NavigationPage $page);

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
   * Sends data to the client when the action is "response", handles special
   * requests for ModuleTreeManagement.
   *
   * @param string $request
   *        The content of the "request" variable inside GET or POST data.
   *
   * @return mixed
   */
  public function sendResponse($request)
  {
    switch ($request) {
      case 'TreeView':
        return $this->_sendResponseTreeView();
        break;
      case 'TreeMove':
        return $this->_sendResponseTreeMove();
        break;
      default:
        // Call the sendResponse() method of the parent Module class.
        return parent::sendResponse($request);
        break;
    }
  }

  private function _move(NavigationPage $page, NavigationPage $target, &$position)
  {
    if ($page->isRoot())
      return false;
    else if ($page->isPositionLocked())
      return false;

    $moved = $this->_navigationPageMover()->move($page, $target, $position);

    if ($moved) {
      Container::make('ContentItemLogService')->logSwitched(array(
        'FK_CIID'      => $page->getID(),
        'CIIdentifier' => $page->getDirectPath(),
        'FK_UID'       => $this->_user->getID(),
      ));

      // Always increment position. This is necessary if multiple pages are moved at once.
      // E.g. If requested position of movement is 2, position of next page must be 3.
      $position++;
    }

    return $moved;
  }

  /**
   * Parse one single sitemap page item
   *
   * @param NavigationPage $page
   *        The NavigationPage object.
   * @param string $tplName
   *        The path to the template file.
   * @param array $items
   *        An array containing parsed childpage items
   * @param string $positionStr
   *        A string defining the unique position of the page including positions
   *        of predecessors.
   *        e.g. Item 1
   *                 Subitem 1
   *                 Subitem 2
   *                        Subsubitem 1
   *                        Subsubitem 2
   *                        ...
   *                 Subitem 3
   *                 ...
   *             Item 2
   *             ...
   *             The unique position string for 'Subsubitem 2' is '1_2_2'
   *             containing the parent positions combined with its own position.
   *
   * @return string
   *         The parsed sitemap item of the given page.
   */
  private function _parseSitemapItem(NavigationPage $page, $tplName, $items, $positionStr)
  {
    global $_LANG;

    $class = '';

    // page is source page
    if ($page == $this->_sourcePage) {
      $class = '_active';
      $this->_tplAnchor = $positionStr;
    }
    // page is root page or another parent of source page
    else if ($page->isRoot() || mb_strpos($this->_sourcePage->getDirectPath(), $page->getDirectPath()) === 0) {
      $class = '_below';
    }
    // page can not be moved to itself (or a child page)
    // page is child of the source page (or source page itself), so output a
    // special display class
    else
    {
      $tmpPage = $page;
      while ($tmpPage) {
        if ($tmpPage == $this->_sourcePage) {
          $class = '_unavailable';
          break;
        }
        $tmpPage = $tmpPage->getParent();
      }
    }

    $parent = $page->getParent();
    $parentId = $parent ? $parent->getId() : 0;
    $positionPre = $page->getPosition();
    $positionSuc = $page->getPosition() + 1;

    // get content type / tree for root pages
    if ($page->isRoot())
      $ctypeStr = $page->getTree();
    else
      $ctypeStr = ContentItem::getTypeShortname($page->getContentTypeId());

    // determine the first position pages might be inserted at
    // if the current page has siblings with locked position, it is not allowed
    // to insert an item at a position lower to the sibling
    $unavailablePos = array();
    if ($parent)
    {
      $children = $parent->getAllChildren();
      foreach ($children as $child)
      {
        if ($child->isPositionLocked() || $child == $this->_sourcePage)
          $unavailablePos[] = $child->getPosition();
      }
    }

    // determine available links
    $availablePre = ($page->getPosition() == 1) && !$page->isPositionLocked() && !$page->isRoot() && ($page != $this->_sourcePage);
    $availableSuc = !in_array($positionSuc, $unavailablePos) && !$page->isRoot() && ($page != $this->_sourcePage);
    $availableInside = !$page->hasChildren() && ($page->getType() == ContentType::TYPE_LOGICAL_WITH_NAV) && !$page->isRoot() && ($page != $this->_sourcePage);

    // Add the items itself with all subitems ( = visible child pages to the
    // sitemap navigation)
    $this->tpl->load_tpl("tm_nav_$positionStr", $tplName);
    $this->tpl->parse_if("tm_nav_$positionStr", "tm_items", $page->hasChildren());
    $this->tpl->parse_if("tm_nav_$positionStr", "tm_move_pre", $availablePre);
    $this->tpl->parse_if("tm_nav_$positionStr", "tm_move_suc", $availableSuc);
    $this->tpl->parse_if("tm_nav_$positionStr", "tm_move_inside", $availableInside);
    $this->tpl->parse_loop("tm_nav_$positionStr", $items, "tm_items");
    return $this->tpl->parsereturn("tm_nav_$positionStr", array(
      "tm_item_class"               => $class,
      "tm_item_level"               => $page->getLevel() + 1,
      "tm_item_move_pre_url"        => "index.php?action=mod_response_{$this->_shortname}&amp;request=TreeMove&amp;site={$page->getSite()->getID()}&amp;page={$this->_sourcePage->getID()}&amp;parent={$parentId}&amp;position=$positionPre",
      "tm_item_move_suc_url"        => "index.php?action=mod_response_{$this->_shortname}&amp;request=TreeMove&amp;site={$page->getSite()->getID()}&amp;page={$this->_sourcePage->getID()}&amp;parent={$parentId}&amp;position=$positionSuc",
      "tm_item_move_inside_url"     => "index.php?action=mod_response_{$this->_shortname}&amp;request=TreeMove&amp;site={$page->getSite()->getID()}&amp;page={$this->_sourcePage->getID()}&amp;parent={$page->getID()}&amp;position=1",
      "tm_item_position"            => $page->getPosition(),
      "tm_item_title"               => $page->isRoot() ? $_LANG['global_sites_backend_root_' . $page->getTree() . '_title'] : parseOutput($page->getTitle()),
      "tm_item_type_shortname"      => $ctypeStr,
      "tm_item_type_title"          => $ctypeStr ? $_LANG["m_nv_ctype_label_$ctypeStr"] : '',
      "tm_item_unique_position"     => $positionStr,
      "tm_item_url"                 => "index.php?action=content&amp;site={$page->getSite()->getID()}&amp;page={$page->getID()}",
      'main_cache_resource_version' => ConfigHelper::get('m_cache_resource_version'),
      'main_theme'                  => ConfigHelper::get('m_backend_theme'),
    ));
  }

  /**
   * Return the sitemap for specified page and all children.
   *
   * Method calls itself recursively for all children (subpages).
   *   - default template = modules/ModuleTreeManagament.tpl,
   *   - possible custom templates = modules/<classname>.tpl
   *
   * @param NavigationPage $page
   *        The NavigationPage object.
   * @param string $positionStr
   *        A string defining the unique position of the page including positions
   *        of predecessors.
   *        e.g. Item 1
   *                 Subitem 1
   *                 Subitem 2
   *                        Subsubitem 1
   *                        Subsubitem 2
   *                        ...
   *                 Subitem 3
   *                 ...
   *             Item 2
   *             ...
   *             The unique position string for 'Subsubitem 2' is '1_2_2'
   *             containing the parent positions combined with its own position.
   * @param string $tplName
   *        The path to the template file.
   *
   * @return string
   *         The parsed sitemap of the given page.
   */
  private function _readSitemap(NavigationPage $page, $positionStr, $tplName)
  {
    $childrenArray = array();
    $backlink = false;
    // Level of subitems
    $subLevel = $page->getLevel() + 1;
    $pageID = $page->getID();

    // The page is not a leaf (logical level or AR, BE, VA)
    if (!$page->isLeaf() || $page->isArchive() || $page->isBlog() || $page->isVariation())
    {
      $children = $page->getAllChildren();
      $subPosition = 1;

      // Recursively call the ModuleSitemapNavMain::_readSitemap() method for
      // visible children of the current page.
      foreach ($children as $childPage)
      {
        $item = $this->_readSitemap($childPage, $positionStr . '_' . $subPosition, $tplName);
        if ($item) $childrenArray[]["{$this->_prefix}_item"] = $item;
        $subPosition++;
      }
    }

    return $this->_parseSitemapItem($page, $tplName, $childrenArray, $positionStr);
  }

  /**
   * Return the parsed tree management template
   *   - default template = modules/ModuleTreeManagament.tpl,
   *   - possible custom templates = modules/<classname>.tpl
   *
   * @return string
   *         the parsed tree management template
   */
  private function _sendResponseTreeView()
  {
    global $_LANG, $_LANG2;

    $request = new Input(Input::SOURCE_REQUEST);
    $pageId = $request->readInt('page');
    $siteId = $request->readInt('site');

    if (!$pageId || !$siteId)
      return '';

    $this->_setSourcePage($this->_navigation->getPageByID($pageId));
    if ($this->_sourcePage === null)
      return '';

    $site = $this->_navigation->getSiteByID($siteId);
    if ($site === null)
      return '';

    $root = $site->getRootPage($this->_sourcePage->getTree());
    $tplName = is_file('modules/'.get_class($this).'_levelX.tpl') ? 'modules/'.get_class($this).'_levelX.tpl' : 'modules/ModuleTreeManagement_levelX.tpl';
    $items = $this->_readSitemap($root, '1', $tplName);
    $ctypeStr = ContentItem::getTypeShortname($this->_sourcePage->getContentTypeId());
    $separator = ConfigHelper::get('hierarchical_title_separator', $this->_prefix);

    $tplName = is_file('modules/'.get_class($this).'.tpl') ? 'modules/'.get_class($this).'.tpl' : 'modules/ModuleTreeManagement.tpl';
    $this->tpl->load_tpl('content_tm', $tplName);
    $this->tpl->parse_if('content_tm', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('tm'));
    $this->tpl->parse_if('content_tm', 'm_backend_live_mode', ConfigHelper::get('m_backend_live_mode'));
    $this->tpl->parse_if('content_tm', 'm_backend_dev_mode', !ConfigHelper::get('m_backend_live_mode'));
    return $this->tpl->parsereturn('content_tm', array_merge(array (
      'tm_items'                    => $items,
      'tm_page_title'               => parseOutput($this->_sourcePage->getTitle()),
      'tm_page_type_shortname'      => $ctypeStr,
      'tm_page_type_title'          => $ctypeStr ? $_LANG["m_nv_ctype_label_$ctypeStr"] : '',
      'tm_page_path'                => $this->_getHierarchicalTitle($this->_sourcePage->getID(), $separator),
      'tm_page_subitems'            => $this->_sourcePage->hasChildren() ? 1 : 0,
      'tm_page_anchor'              => $this->_tplAnchor,
      'main_cache_resource_version' => ConfigHelper::get('m_cache_resource_version'),
      'main_theme'                  => ConfigHelper::get('m_backend_theme'),
    ), $_LANG2[$this->_prefix]));
  }

  /**
   * Check if a page can be moved to specified target page and move if possible.
   */
  private function _sendResponseTreeMove()
  {
    global $_LANG;

    header('Content-Type: application/json');

    $get = new Input(Input::SOURCE_GET);

    $siteId = $get->readInt('site');
    $pageId = $get->readInt('page');
    $parentId = $get->readInt('parent');
    $position = $get->readInt('position');
    $items = $get->readInt('items');

    if (!$siteId) {
      return Json::Encode(array(
        'status'  => "-1",
        'message' => "Invalid parameter 'site' ü!",
      ));
    }
    else if (!$pageId) {
      return Json::Encode(array(
        'status'  => "-1",
        'message' => "Invalid parameter 'page'!",
      ));
    }
    else if (!$parentId) {
      return Json::Encode(array(
        'status'  => "-1",
        'message' => "Invalid parameter 'parent'!",
      ));
    }
    else if (!$position) {
      return Json::Encode(array(
        'status'  => "-1",
        'message' => "Invalid parameter 'position'!",
      ));
    }

    $page = $this->_navigation->getPageByID($pageId);
    // Do not allow to move last active element
    if ($page->isLastActiveItem()) {
      return Json::Encode(array(
        'status'  => "0",
        'message' => $_LANG["tm_message_move_last_active_item"],
      ));
    }

    $site = $this->_navigation->getSiteByID($siteId);
    $this->_setSourcePage($page);
    $parent = $this->_navigation->getPageByID($parentId);
    $url = "index.php?action=content&site={$siteId}&page={$parentId}";

    if (!$this->_sourcePage) {
      return Json::Encode(array(
        'status'  => "-1",
        'message' => "Invalid source page!",
      ));
    }

    $tmpPage = $parent;
    // page can not be moved to itself
    while ($tmpPage) {
      if ($tmpPage == $this->_sourcePage) {
        return Json::Encode(array(
          'status'  => "0",
          'message' => $_LANG["tm_message_move_item_to_itself"],
        ));
      }
      $tmpPage = $tmpPage->getParent();
    }

    /**
     * 2 possible actions requested:
     *   (1) move one page (including its children) to target
     *   (2) move children of page to target (= move multiple items)
     */
    if ($items)
    {
      // do not move the page itself, but items within it (children).
      $children = $this->_sourcePage->getAllChildren();

      // manually check if the overall number of items within the target level
      // is not going to be exceeded, if all children of source page are moved
      // to it.
      $count = count($children) + count($parent->getAllChildren());
      $level = $parent->getLevel() + 1;
      $configMax = ConfigHelper::get('lo_max_items');
      $configMax = isset($configMax[$site->getID()][$parent->getTree()][$level + 1]) ?
                   (int)$configMax[$site->getID()][$parent->getTree()][$level + 1] :
                   (isset($configMax[0][$parent->getTree()][$level + 1]) ?
                    $configMax[0][$parent->getTree()][$level + 1] : 0);

      if ($count > $configMax) {
        return Json::Encode(array(
          'status'  => "0",
          'message' => $_LANG[$this->_prefix.'_message_max_items'],
        ));
      }

      $failure = false;
      // foreach child page, check if it is allowed at target level
      foreach ($children as $child)
      {
        $child->setParent($parent);
        // item can not be moved
        if (!$this->_validateMove($parent, $child)) {
          $failure = true;
          break;
        }
        // set actual parent again
        $child->setParent($this->_sourcePage);
      }

      // all children are allowed at target level, so execute the move action
      // foreach item
      if (!$failure)
      {
        $childrenArray = array();
        $index = count($children) - 1;
        // reverse children (ordered by position) as the last item moved will be
        // at the requested position
        foreach ($children as $child) {
          $childrenArray[$index--] = $child;
        }

        foreach ($childrenArray as $child)
        {
          // Reload parent each turn in order to guarantee up-to-date data.
          // Within the AbstractModuleTreeManagament::_move() method the parent
          // of child is changed and the NavigationCache is cleared, so we
          // fetch the parent page NavigationPage object.
          $parent = $this->_navigation->getPageByID($parentId);
          $this->_move($child, $parent, $position);
        }

        BackendRequest::removeCachedFiles($this->site_id);
        return Json::Encode(array(
          'status'  => "1",
          'message' => $_LANG["tm_message_move_item_success"],
          'url'     => $url,
        ));
      }
    }
    else
    {
      // move page
      // check if the page is allowed at target level and move it.
      // modify the NavigationPage object
      // add target page as page's parent in order to simulate that is has
      // already been inserted at its destination, save the original parent
      $originalParent = $this->_sourcePage->getParent();
      $this->_sourcePage->setParent($parent);
      // parent is not going to change / page (and children) is allowed at parent
      if (($originalParent == $parent) || $this->_validateMove($parent, $this->_sourcePage))
      {
        // set the original parent again
        $this->_sourcePage->setParent($originalParent);
        $result = $this->_move($this->_sourcePage, $parent, $position);
        if ($result)
        {
          BackendRequest::removeCachedFiles($this->site_id);
          return Json::Encode(array(
            'status'  => "1",
            'message' => $_LANG["tm_message_move_item_success"],
            'url'     => $url,
          ));
        }
      }
    }

    $message = $this->_configHelper->getMessage() ? $this->_configHelper->getMessage() : $_LANG["tm_message_move_item"];
    return Json::Encode(array(
      'status'  => "0",
      'message' => $message,
    ));
  }

  /**
   * Check if page is allowed
   *
   * @param NavigationPage $parent
   *        The target parent page
   * @param NavigationPage $page
   *        The page to check
   *
   * @return bool
   */
  private function _validateMove(NavigationPage $parent, NavigationPage $page)
  {
    $children = $page->getAllChildren();
    if ($children)
    {
      foreach ($children as $child)
      {
        $result = $this->_validateMove($page, $child);
        if (!$result) {
          return false;
        }
      }
    }

    if (!$this->_configHelper->newItemAt($parent, $page->getRealContentType(), $this->_prefix)) {
      return false;
    }
    else {
      return true;
    }
  }
}
