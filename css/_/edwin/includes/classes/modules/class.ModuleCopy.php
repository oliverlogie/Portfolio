<?php

/**
 * class.ModuleCopy.php
 *
 * $LastChangedDate: 2016-03-17 16:42:02 +0100 (Do, 17 Mrz 2016) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

class ModuleCopy extends Module
{
  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'cy';

  /**
   * Module's shortname.
   *
   * @var string
   */
  protected $_shortname = 'copy';

  /**
   * Check if copy function is available for given page
   *
   * @see Module::isAvailableOnPage($page)
   */
  public function isAvailableOnPage(NavigationPage $page)
  {
    // Copy function is only available for leaf pages
    if (!$page->isRealLeaf() || $page->hasChildren()) {
      return false;
    }

    // Check configuration
    $allowedContentTypes = $this->_configHelper->get('ci_copy_allowed_ctypes');
    if (!in_array($page->getContentTypeId(), $allowedContentTypes)) {
      return false;
    }

    // Check if a new content item is allowed on this level
    $parent = $page->getParent();
    if (!$this->_configHelper->newItemAt($parent, $page->getRealContentType(), $this->_prefix)) {
      return false;
    }

    return true;
  }

  /**
   * Sends ajax response.
   *
   * @see ContentBase::sendResponse()
   */
  public function sendResponse($request)
  {
    switch ($request) {
      case 'duplicate':
        return $this->_sendResponseDuplicate();
        break;
      default:
        // Call the sendResponse() method of the parent Module class.
        parent::sendResponse($request);
        break;
    }
  }

  /**
   * Duplicates given page to parent.
   *
   * @param int $pageId
   *        The id of the page to copy.
   * @param int $parentId
   *        The id of the parent to insert the page copy.
   * @return int
   *         The new page id.
   */
  private function _duplicate($pageId, $parentId)
  {
    $pageToCopy = $this->_navigation->getPageByID($pageId);
    if (!$pageToCopy) {
      return;
    }
    $cTypeClass = $pageToCopy->getContentTypeClass();
    $ci = ContentItem::create($pageToCopy->getSite()->getID(), $pageId, $this->tpl, $this->db,
                              $this->table_prefix, $this->action, $this->_user,
                              $this->session, $this->_navigation);
    $itemId = $ci->duplicate($parentId);

    return $itemId;
  }

  /**
   * Redirect/Reload current page.
   * (ContentItemLogical opens box)
   * @throws Exception
   */
  private function _redirectToPage(NavigationPage $page)
  {
    $parent = $page->getParent();
    if (!$parent) {
      throw new Exception('Can not redirect. Unknown parent! Set parent of page duplicate.');
    }
    $parentId = $parent->getID();
    $position = $page->getPosition();
    $partentItemsCount = count($parent->getAllChildren());
    $resultsPerPage = (int)ConfigHelper::get('lo_results_per_page');
    $offset = (int) ceil($partentItemsCount / $resultsPerPage);
    $location = "index.php?action=content"
              . "&site={$this->site_id}"
              . "&page={$parentId}"
              . "&offset={$offset}"
              . "&page_copy={$page->getID()}"
              . "#anchor_lobox{$position}";
    header("Location: $location");
    exit;
  }

  /**
   * Duplicates page with id from request.
   */
  private function _sendResponseDuplicate()
  {
    $request = new Input(Input::SOURCE_REQUEST);
    $pageId = $request->readInt('page');
    if (!$pageId) {
      $this->redirect_page('invalid_path');
    }
    $pageToCopy = $this->_navigation->getPageByID($pageId);
    if (!$pageToCopy) {
      $this->redirect_page('invalid_path');
    }
    $parentId = $pageToCopy->getParent()->getID();
    $itemId = $this->_duplicate($pageId, $parentId);
    $pageCopy = $this->_navigation->getPageByID($itemId);
    $this->_redirectToPage($pageCopy);
  }
}
