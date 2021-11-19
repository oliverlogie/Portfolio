<?php

/**
 * Navigation helper class for moving one NavigationPage to another NavigationPage.
 *
 * $LastChangedDate: $
 * $LastChangedBy: $
 *
 * @package Core
 * @author Benjamin Ulmer
 * @copyright (c) 2016 Q2E GmbH
 */
class NavigationPageMover
{
  /**
   * @var Navigation
   */
  private $_navigation;

  /**
   * @var Db
   */
  private $_db;

  /**
   * @var string
   */
  private $_tablePrefix;

  /**
   * @var Session
   */
  private $_session;

  /**
   * @var Template
   */
  private $_tpl;

  /**
   * @var User
   */
  private $_user;
  /**
   * @var ContentItemLogService
   */
  private $_logService;

  public function __construct(
    Navigation $navigation,
    Db $db,
    $tablePrefix,
    Session $session,
    Template $tpl,
    User $user,
    ContentItemLogService $logService
  ) {
    $this->_navigation = $navigation;
    $this->_db = $db;
    $this->_tablePrefix = $tablePrefix;
    $this->_session = $session;
    $this->_tpl = $tpl;
    $this->_user = $user;
    $this->_logService = $logService;
  }

  public function move(NavigationPage $page, NavigationPage $target, $position)
  {
    if ($page->isRoot()) {
      throw new InvalidArgumentException('Can not move root pages.');
    }

    $maxPosition = 0;
    // store old values
    $oldPosition = $page->getPosition();
    $oldParent = $page->getParent();
    $oldPath = $page->getDirectPath();
    // number of children within target level
    $count = count($target->getAllChildren());

    // page is going to be moved to new target page (level), so we insert it at
    // last position within target page
    if ($oldParent != $target) {
      $maxPosition = $count + 1;
      $sql = " UPDATE {$this->_tablePrefix}contentitem "
           . " SET FK_CIID = {$target->getID()}, "
           . "     FK_SID = {$target->getSite()->getID()}, "
           . "     CTree = '{$target->getTree()}', "
           . "     CPosition = $maxPosition "
           . " WHERE CIID = {$page->getID()} ";
      $this->_db->query($sql);
    }
    // requested position exceeds the last position (insert after last item)
    else if ($count < $position) {
      $position = $count;
    }

    // move page to correct position
    // this is required as PositionHelper returns false if current position is
    // equal to target position
    if ($maxPosition != $position)
    {
      $positionHelper = new PositionHelper(
        $this->_db,
        $this->_tablePrefix . 'contentitem',
        'CIID',
        'CPosition',
        'FK_CIID',
        $target->getID(),
        'CPositionLocked'
      );
      $result = $positionHelper->move($page->getID(), $position);
      // item has not been moved successfully - revert changes and quit
      if (!$result) {
        $sql = " UPDATE {$this->_tablePrefix}contentitem "
             . " SET FK_CIID = {$oldParent->getID()}, "
             . "     CPosition = $oldPosition "
             . " WHERE CIID = {$page->getID()} ";
        $this->_db->query($sql);
        return false;
      }
    }

    $newPath = Container::make('Core\Url\ContentItemPathGenerator')->generateChildPath(
      $target->getDirectPath(),
      $page->getTitle(),
      $target->getSite()->getID(),
      $page->getID()
    );

    // path changed (paths do not change for items moved within their old target)
    // so we update the moved page's path as well as all of it's children paths
    if ($newPath != $oldPath) {
      $sql = " UPDATE {$this->_tablePrefix}contentitem "
           . " SET CIIdentifier = '$newPath' "
           . " WHERE CIID = {$page->getID()} ";
      $this->_db->query($sql);

      $sql = " UPDATE {$this->_tablePrefix}contentitem "
           . " SET CIIdentifier = CONCAT('$newPath',SUBSTRING(CIIdentifier FROM ".(mb_strlen($oldPath)+1).")) "
           . " WHERE CIIdentifier LIKE '$oldPath/%' "
           . "   AND FK_SID = {$target->getSite()->getID()} ";
      $this->_db->query($sql);
    }

    // item moved to different target, so fill missing position within old level
    if ($oldParent != $target) {
      $sql = " SELECT CIID,CPosition FROM {$this->_tablePrefix}contentitem "
           . " WHERE FK_CIID = {$oldParent->getID()} "
           . "   AND CPosition > $oldPosition ORDER BY CPosition ASC";
      $result = $this->_db->query($sql);

      while ($row = $this->_db->fetch_row($result)) {
        $sql = " UPDATE {$this->_tablePrefix}contentitem "
             . " SET CPosition = CPosition - 1 "
             . " WHERE CIID = {$row["CIID"]} ";
        $this->_db->query($sql);
      }
      $this->_db->free_result($result);
    }

    // we need up-to-date page data again
    Navigation::clearCache($this->_db, $this->_tablePrefix);

    // refresh old target level data
    // reload old target NavigationPage object (required as children changed)
    $oldParent = $this->_navigation->getPageByID($oldParent->getID());

    $this->_disableForInvisibleChildren($oldParent);

    // no further processing is required for root page parents
    if ($target->isRoot()) {
      return true;
    }

    // fetch ContentItem object(s) and set short texts and images for new and
    // old target levels:
    // retrieve the page to call the ContentItem::setShortTextAndImages() for
    // for level items we have to retrieve the real preferred child
    $tmpPage = $page;
    if ($tmpPage->getRealPreferredChild()) {
      $tmpPage = $tmpPage->getRealPreferredChild();
    }
    // refresh new target level data
    $contentItem = ContentItem::create(
      $tmpPage->getSite()->getID(),
      $tmpPage->getID(),
      $this->_tpl,
      $this->_db,
      $this->_tablePrefix,
      '',
      $this->_user,
      $this->_session,
      $this->_navigation
    );
    $contentItem->setShortTextAndImages(1);

    // call ContentItem::setShortTextAndImages() for preferred child of old
    // target level in case the former preferred child has been moved (= the
    // preferred child changed)
    $child = $oldParent->getRealPreferredChild();

    if ($child) {
      $contentItem = ContentItem::create(
        $child->getSite()->getID(),
        $child->getID(),
        $this->_tpl,
        $this->_db,
        $this->_tablePrefix,
        '',
        $this->_user,
        $this->_session,
        $this->_navigation
      );
      $contentItem->setShortTextAndImages(1);
    }

    return true;
  }

  /**
   * Disables the given page and all parents without any visible children left.
   *
   * @param NavigationPage $page
   *        The page to disable if there are no visible children left (
   *        recursively  to root page )
   */
  private function _disableForInvisibleChildren($page)
  {
    while ($page) {
      // reload NavigationPage object (required as children may changed)
      $page = $this->_navigation->getPageByID($page->getID());
      // if parent item has not got visible children and it is
      // enabled, disable it.
      if (!count($page->getVisibleChildren()) && !$page->isDisabled()) {
        $sql = " UPDATE {$this->_tablePrefix}contentitem "
             . " SET CDisabled = 1 "
             . " WHERE CIID = {$page->getID()} "
             . " AND FK_SID = {$page->getSite()->getID()} ";
        $this->_db->query($sql);

        Navigation::clearCache($this->_db, $this->_tablePrefix);
      }
      $page = $page->getParent();
    }
  }
}