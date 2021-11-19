<?php

/**
 * The AbstractCFunction class provides a common constructor implementation for
 * all EDWIN classes implementing the InterfaceCFunction interface.
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
abstract class AbstractCFunction implements InterfaceCFunction
{

  /**
   * An instance of the database class.
   *
   * @var Db
   */
  protected $_db;

  /**
   * Navigation object
   *
   * @var Navigation
   */
  protected $_navigation;

  /**
   * The table prefix for accessing the database.
   *
   * @var string
   */
  protected $_tablePrefix;

  /**
   * An instance of the user class.
   *
   * @var User
   */
  protected $_user;

  /**
   * An instance of the Session class.
   *
   * @var Session
   */
  protected $_session;

  /**
   * Initializes the AbstractCFunction object.
   *
   * @param db $db
   *        An instance of the database class.
   * @param string $tablePrefix
   *        The table prefix for accessing the database.
   * @param Session $session
   *        the session object
   * @param Navigation $navigation
   *        An instance of the Navigation object
   * @param array $activeModules
   *        An array containing id and shortname of all active modules
   */
  public function __construct(db $db, $tablePrefix, Session $session, Navigation $navigation, $activeModules)
  {
    $this->_db = $db;
    $this->_tablePrefix = $tablePrefix;
    $this->_navigation = $navigation;
    $this->_session = $session;
    $this->_modules = $activeModules;
  }

  public function isAvailableForUserOnPage(User $user, NavigationPage $page)
  {
    return ($this->isAvailableForUser($user, $page->getSite()) && $this->isAvailableOnPage($page));
  }
}
