<?php

/**
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas_ElementFactory
{
  /**
   * @var Db
   */
  private $_db;

  /**
   * @var Session
   */
  private $_session;

  /**
   * @var User
   */
  private $_user;

  /**
   * @var int
   */
  private $_siteId;

  /**
   * @var int
   */
  private $_pageId;

  /**
   * @var Template
   */
  private $_tpl;

  /**
   * @var string
   */
  private $_tablePrefix;

  /**
   * @var string
   */
  private $_action;

  /**
   * @var string
   */
  private $_pagePath;

  /**
   * @var Navigation
   */
  private $_navigation;

  /**
   * @var ContentItemCX
   */
  private $_parent;

  /**
   * @var array
   */
  private $_cache = array();

  public function __construct(
    $siteId,
    $pageId,
    Template $tpl,
    db $db,
    $tablePrefix,
    $action,
    $pagePath,
    User $user,
    Session $session,
    Navigation $navigation,
    ContentItemCX $parent
  ) {
    $this->_siteId      = $siteId;
    $this->_pageId      = $pageId;
    $this->_tpl         = $tpl;
    $this->_db          = $db;
    $this->_tablePrefix = $tablePrefix;
    $this->_action      = $action;
    $this->_pagePath    = $pagePath;
    $this->_user        = $user;
    $this->_session     = $session;
    $this->_navigation  = $navigation;
    $this->_parent      = $parent;
  }

  /**
   * @param string $identifier
   * @param array  $options
   * @param array  $row the element row from database result
   *
   * @return ContentItemCX_Areas_Element
   */
  public function make($identifier, $options, $row)
  {
    if (isset($this->_cache[$row['CXAEID']])) {
      return $this->_cache[$row['CXAEID']];
    }

    $className = sprintf('ContentItemCX_Areas_Element_%s', ucfirst($options['type']));

    if (!class_exists($className)) {
      throw new InvalidArgumentException(sprintf("Invalid ContentItemCX_Area_Element_%s for element '%s' of type '%s'. Please use available types only.", ucfirst($options['type']), $identifier, $options['type']));
    }

    $options['data'] = array(
      'id'               => $row['CXAEID'],
      'identifier'       => $row['CXAEIdentifier'],
      'type'             => $row['CXAEType'],
      'disabled'         => $row['CXAEDisabled'],
      'position'         => $row['CXAEPosition'],
      'content'          => $row['CXAEContent'],
      'elementable_id'   => $row['CXAEElementableID'],
      'elementable_type' => $row['CXAEElementableType'],
      'area_id'          => $row['FK_CXAID'],
      'content_item_id'  => $row['FK_CIID'],
    );

    $this->_cache[$row['CXAEID']] = $element = new $className(
      $this->_siteId,
      $this->_pageId,
      $this->_tpl,
      $this->_db,
      $this->_tablePrefix,
      $this->_action,
      $this->_pagePath,
      $this->_user,
      $this->_session,
      $this->_navigation,
      $this->_parent,
      $this,
      $identifier,
      $options
    );

    return $element;
  }

  public function clearCache()
  {
    $this->_cache = array();
  }
}