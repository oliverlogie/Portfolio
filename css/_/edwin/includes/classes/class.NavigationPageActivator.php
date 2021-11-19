<?php

/**
 * Navigation page activator handles the activation/deactivation of navigation
 * pages.
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package Core
 * @author Anton Jungwirth
 * @copyright (c) 2016 Q2E GmbH
 */
class NavigationPageActivator
{
  /**
   * Describes the 'enabled' state of a content item.
   *
   * @var string
   */
  const ACTIVATION_ENABLED = 'enabled';

  /**
   * Describes the 'disabled' state of a content item.
   *
   * @var string
   */
  const ACTIVATION_DISABLED = 'disabled';

  /**
   * @var Navigation
   */
  private $_navigation;

  /**
   * @var NavigationPage The page to modify its activation status.
   */
  private $_page;

  /**
   * @var int
   */
  private $_siteId;

  /**
   * @var DB
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
   * @var boolean
   */
  private $_structureLinksAvailable;

  /**
   * @var Message
   */
  private $_message;

  /**
   * @param Navigation $navigation
   * @param NavigationPage $page The page to modify its activation status.
   * @param Db $db
   * @param $tablePrefix
   * @param Session $session
   * @param Template $tpl
   * @param User $user
   * @param $structureLinksAvailable
   */
  public function __construct(
    Navigation $navigation,
    NavigationPage $page,
    Db $db,
    $tablePrefix,
    Session $session,
    Template $tpl,
    User $user,
    $structureLinksAvailable
  ) {
    $this->_navigation = $navigation;
    $this->_db = $db;
    $this->_tablePrefix = $tablePrefix;
    $this->_session = $session;
    $this->_tpl = $tpl;
    $this->_user = $user;
    $this->_structureLinksAvailable = $structureLinksAvailable;
    $this->_page = $page;
    $this->_siteId = $this->_page->getSite()->getID();
  }

  /**
   * @param string $changeActivationTo NavigationPageActivator::ACTIVATION_ constant.
   * @return bool
   */
  public function change($changeActivationTo)
  {
    global $_LANG;

    $activationChanged = false;

    $sql = " SELECT CDisabled, CDisabledLocked, CIIdentifier, CType, FK_CIID, "
         . "        FK_CTID, FK_SID "
         . " FROM {$this->_tablePrefix}contentitem "
         . " WHERE CIID = {$this->_page->getID()} ";
    $row = $this->_db->GetRow($sql);

    $existingActivation = $row['CDisabled'] ? self::ACTIVATION_DISABLED : self::ACTIVATION_ENABLED;

    // If the item is locked as disabled we must not change the activation.
    if (   ($row['CDisabled'] && $row['CDisabledLocked'])
        || $changeActivationTo == $existingActivation
    ) {
      $this->_setMessage(Message::createFailure($_LANG['lo_message_enableitem_failure_ci_locked']));
      return false;
    }

    switch ($changeActivationTo) {
      case self::ACTIVATION_ENABLED:
        if ($this->enable()) {
          $activationChanged = true;
        }
        break;
      case self::ACTIVATION_DISABLED:
        if ($this->disable()) {
          $activationChanged = true;
        }
        break;
      default:
        return false;
    }

    return $activationChanged;
  }

  /**
   * Disables this content item.
   *
   * @return bool
   */
  public function disable()
  {
    global $_LANG;

    $page = $this->_page;
    // The only case in which we may not disable something is if that item
    // is the last enabled child under its (enabled) parent.
    // (But we may allow this in the future, because it is also possible to
    // delete the last enabled child in which case the parent is disabled.)
    if ($page->isLastActiveItem()) {
      $this->_setMessage(Message::createFailure($_LANG['lo_message_deactivate_last_active_not_possible']));
      return false;
    }

    // Disable this item and everything below it.
    $sql = " UPDATE {$this->_tablePrefix}contentitem "
         . " SET CDisabled = 1 "
         . " WHERE ( "
         . "   CIIdentifier = '{$page->getDirectPath()}' "
         . "   OR CIIdentifier LIKE '{$page->getDirectPath()}/%' "
         . " ) "
         . " AND FK_SID = $this->_siteId ";
    $this->_db->q($sql);

    $message = $_LANG['lo_message_activation_disable_success'];
    // Contentitem successfully enabled, disable linked contentitems if
    // enable / disable funtion is activated for linked items ( "m_sructure_links_enable_disable" )
    if ($this->_structureLinksAvailable && in_array($page->getContentTypeId(), ConfigHelper::get('m_sructure_links_enable_disable'))) {
      $count = 0;
      $sql = " SELECT CIID, CIIdentifier, CType, FK_CTID, FK_SID, ci.FK_CIID "
           . " FROM {$this->_tablePrefix}structurelink sl "
           . " JOIN {$this->_tablePrefix}contentitem ci "
           . "       ON sl.FK_CIID_Link = CIID "
           . " WHERE sl.FK_CIID = :id ";
      $result = $this->_db->q($sql, array('id' => $page->getID()));
      // Get instance of linked items parent ( logical level ) and disable item.
      while ($row = $result->fetch()) {
        $pageActivator = new NavigationPageActivator(
          $this->_navigation,
          $this->_navigation->getPageByID($row['CIID']),
          $this->_db,
          $this->_tablePrefix,
          $this->_session,
          $this->_tpl,
          $this->_user,
          $this->_structureLinksAvailable
        );
        if ($pageActivator->disable()) {
          $count++;
        }
      }
      $message = sprintf($_LANG['lo_message_activation_disable_linked_success'], $count);
    }

    NavigationCache::getInstance($this->_db, $this->_tablePrefix)->clear();
    $this->_setMessage(Message::createSuccess($message));
    return true;
  }

  /**
   * Enables this content item.
   *
   * @return bool
   */
  public function enable()
  {
    global $_LANG;

    $page = $this->_page;

    // check if item is disabled and locked and set message if it is
    $sql = " SELECT CIID "
         . " FROM {$this->_tablePrefix}contentitem "
         . " WHERE CIID = {$page->getId()} "
         . " AND CDisabled != 0 "
         . " AND CDisabledLocked = 0 ";
    $exists = $this->_db->GetOne($sql);
    if (!$exists) {
      $this->_setMessage(Message::createFailure($_LANG['lo_message_enableitem_failure_ci_locked']));
      return false;
    }

    // if the item is of type NORMAL ( a leaf with direct subcontent ) and has
    // subcontent - enable it
    if ($page->getType() == ContentType::TYPE_NORMAL)
    {
      $sql = " UPDATE {$this->_tablePrefix}contentitem "
           . " SET CDisabled = 0 "
           . " WHERE CIID = {$page->getId()} "
           . " AND CDisabled != 0 "
           . " AND CDisabledLocked = 0 "
           . " AND CHasContent = 1 ";
      $res = $this->_db->q($sql);
      if ($res->rowCount() <= 0) {
        $this->_setMessage(Message::createFailure($_LANG['lo_message_activate_without_valid_content_not_possible']));
        return false;
      }
    }
    // if the item is of type LOGICAL_WITH_NAV (archive, logical, teaser)
    else if ($page->getType() == ContentType::TYPE_LOGICAL_WITH_NAV) {

      // A logical level can be activated when it has at least one normal
      // page with content somewhere underneath it that is not locked as
      // disabled. Normal pages with content have CType = 1
      $sql = " SELECT CIID "
           . " FROM {$this->_tablePrefix}contentitem "
           . " WHERE CIIdentifier LIKE '{$page->getDirectPath()}/%' "
           . " AND FK_SID = $this->_siteId "
           . " AND CType = 1 "
           . " AND CDisabledLocked = 0 "
           . " AND CHasContent = 1 "
           . " LIMIT 1 ";
      $normalID = $this->_db->GetOne($sql);
      if (!$normalID) {
        // if only disabled & locked but content exists actually create a different message
        $sql = " SELECT CIID "
             . " FROM {$this->_tablePrefix}contentitem "
             . " WHERE CIIdentifier LIKE '{$page->getDirectPath()}/%' "
             . " AND FK_SID = $this->_siteId "
             . " AND CType = 1 "
             . " AND CDisabledLocked = 1 "
             . " AND CHasContent = 1 "
             . " LIMIT 1 ";
        $normalID = $this->_db->GetOne($sql);
        if ($normalID) {
          $this->_setMessage(Message::createFailure($_LANG['lo_message_activate_without_enabled_subpages_not_possible']));
          return false;
        }
        else {
          $this->_setMessage(Message::createFailure($_LANG['lo_message_activate_without_valid_subpages_not_possible']));
          return false;
        }
      }
      // logical level has subpages so activate them
      $sql = " UPDATE {$this->_tablePrefix}contentitem "
           . " SET CDisabled = 0 "
           . " WHERE CIIdentifier LIKE '{$page->getDirectPath()}/%' "
           . " AND FK_SID = $this->_siteId "
           . " AND CType = 1 "
           . " AND CDisabledLocked = 0 "
           . " AND CHasContent = 1 ";
      $this->_db->q($sql);

      // retrieve all logical subpages
      $sql = " SELECT CIID, CIIdentifier "
           . " FROM {$this->_tablePrefix}contentitem "
           . " WHERE CIIdentifier LIKE '{$page->getDirectPath()}/%' "
           . " AND FK_SID = $this->_siteId "
           . " AND CType = 90 "
           . " AND CDisabled != 0 "
           . " AND CDisabledLocked = 0 ";
      $result = $this->_db->q($sql);
      // if logical subpage has at least one normal
      // page with content somewhere underneath
      while ($row = $result->fetch()) {
        $sql = " SELECT CIID "
             . " FROM {$this->_tablePrefix}contentitem "
             . " WHERE CIIdentifier LIKE '{$row['CIIdentifier']}/%' "
             . " AND FK_SID = $this->_siteId "
             . " AND CType = 1 "
             . " AND CDisabledLocked = 0 "
             . " AND CHasContent = 1 "
             . " LIMIT 1 ";
        $normalID = $this->_db->GetOne($sql);
        if ($normalID) {
          // normal page exists -> enable logical subpage
          $sql = " UPDATE {$this->_tablePrefix}contentitem "
               . " SET CDisabled = 0 "
               . " WHERE CIID = {$row['CIID']} "
               . " AND FK_SID = $this->_siteId ";
          $this->_db->q($sql);
        }
      }

      // check if at least one direct subpages (type doesn't matter) is enabled
      // if true - enable current logical page
      $sql = " SELECT CIID "
           . " FROM {$this->_tablePrefix}contentitem "
           . " WHERE FK_CIID = {$page->getID()} "
           . " AND CDisabled = 0 "
           . " LIMIT 1 ";
      $enabled = $this->_db->GetOne($sql);
      if (!$enabled) {
        $this->_setMessage(Message::createFailure($_LANG['lo_message_activate_without_enabled_subpages_not_possible']));
        return false;
      }
      $sql = " UPDATE {$this->_tablePrefix}contentitem "
           . " SET CDisabled = 0 "
           . " WHERE CIID = {$page->getID()} ";
      $this->_db->q($sql);
    }

    $message = $_LANG['lo_message_activation_enable_success'];
    // contentitem successfully enabled, enable linked contentitems if current
    // enable / disable funtion is activated for linked items ( "m_sructure_links_enable_disable" )
    if ($this->_structureLinksAvailable && in_array($page->getContentTypeId(), ConfigHelper::get('m_sructure_links_enable_disable'))) {
      $count = 0;
      $sql = " SELECT CIID, CIIdentifier, CType, FK_CTID, FK_SID, ci.FK_CIID "
           . " FROM {$this->_tablePrefix}structurelink sl "
           . " JOIN {$this->_tablePrefix}contentitem ci "
           . "       ON sl.FK_CIID_Link = CIID "
           . " WHERE sl.FK_CIID = :id ";
      $result = $this->_db->q($sql, array('id' => $page->getID()));
      // Get instance of linked items parent ( logical level ) and enable item.
      while ($row = $result->fetch()) {
        $pageActivator = new NavigationPageActivator(
          $this->_navigation,
          $this->_navigation->getPageByID($row['CIID']),
          $this->_db,
          $this->_tablePrefix,
          $this->_session,
          $this->_tpl,
          $this->_user,
          $this->_structureLinksAvailable
        );
        if ($pageActivator->enable()) {
          $count++;
        }
      }
      $message = sprintf($_LANG['lo_message_activation_enable_linked_success'], $count);
    }

    NavigationCache::getInstance($this->_db, $this->_tablePrefix)->clear();
    $this->_setMessage(Message::createSuccess($message));
    return true;
  }

  /**
   * @return Message
   */
  public function getMessage()
  {
    return $this->_message;
  }

  /**
   * @param Message $message
   */
  protected function _setMessage(Message $message)
  {
    $this->_message = $message;
  }
}