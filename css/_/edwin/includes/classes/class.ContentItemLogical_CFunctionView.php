<?php

/**
 * Logical level item core function view allows parsing common template IFs,
 * which are used for displaying the appropriate view depending on page and
 * user permission.
 *
 * $LastChangedDate: 2014-03-13 08:50:10 +0100 (Do, 13 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class ContentItemLogical_CFunctionView
{
  /**
   * @var Db
   */
  private $_db;
  private $_tablePrefix;
  /**
   * @var Template
   */
  private $_tpl;
  /**
   * @var User
   */
  private $_user;

  public function __construct(Db $db, $tablePrefix, Template $tpl, User $user)
  {
    $this->_db = $db;
    $this->_tablePrefix = $tablePrefix;
    $this->_tpl = $tpl;
    $this->_user = $user;
  }

  /**
   * Parses the template for core function view within the logical level
   * @return void
   */
  public function parse($templateName, InterfaceCFunction $function, NavigationPage $page)
  {
    $prefix = "lo_{$function->getShortname()}_";
    $availableForUserOnPage = $function->isAvailableForUserOnPage($this->_user, $page);
    $this->_tpl->parse_if($templateName, $prefix . 'active',
                          $function->isActive());
    $this->_tpl->parse_if($templateName, $prefix . 'available',
                          $function->isAvailableOnPage($page));
    $this->_tpl->parse_if($templateName, $prefix . 'editable',
                          $availableForUserOnPage);
    $this->_tpl->parse_if($templateName, $prefix . 'locked',
                          !$availableForUserOnPage);
  }
}