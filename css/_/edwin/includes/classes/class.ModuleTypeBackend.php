<?php

/**
 * Object provides methods for accessing moduletype data.
 *
 * $LastChangedDate: 2019-01-21 08:50:18 +0100 (Mo, 21 Jän 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */
class ModuleTypeBackend implements InterfaceModuleType
{
  /**
   * The database object.
   *
   * @var db
   */
  private $_db;

  /**
   * The table prefix for accessing the database.
   *
   * @var string
   */
  private $_tablePrefix;

  private $_active;
  private $_class;
  private $_id;
  private $_position;
  private $_required;
  private $_shortname;

  /**
   * Constructs a new moduletype object.
   *
   * @param db $db
   *        The database object.
   * @param string $tablePrefix
   *        The table prefix for accessing the database.
   * @param array $row
   *        The rest of the information in form of a result row from the database.
   */
  public function __construct(db $db, $tablePrefix, $row)
  {
    $this->_db = $db;
    $this->_tablePrefix = $tablePrefix;

    $this->_id = (int)$row['MID'];
    $this->_class = $row['MClass'];
    $this->_active = (bool)$row['MActive'];
    $this->_position = (int)$row['MPosition'];
    $this->_required = (int)$row['MRequired'];
    $this->_shortname = (string)$row['MShortname'];
  }

  /**
   * Returns the moduletype class
   *
   * @return string
   */
  public function getClass()
  {
    return $this->_class;
  }

  /**
   * Getter: _id
   * @return String
   */
  public function getId()
  {
    return $this->_id;
  }

  /**
   * Returns moduletype position
   *
   * @return int
   */
  public function getPosition()
  {
    return $this->_position;
  }

  /**
   * Returns moduletype shortname
   *
   * @return string
   */
  public function getShortname()
  {
    return $this->_shortname;
  }

  /**
   * Checks if the moduletype is active.
   *
   * @return bool
   */
  public function isActive()
  {
    return $this->_active;
  }

  /**
   * Checks if the moduletype is required.
   *
   * @return bool
   */
  public function isRequired()
  {
    return $this->_required;
  }

  /**
   * Sets the moduletype active
   *
   * @param bool $active
   *        the active state of moduletype
   *
   * @return void
   */
  public function setActive( $active )
  {
    $this->_active = $active;
  }

  /**
   * Sets the moduletype class
   *
   * @param string $class
   *        the moduletype class
   *
   * @return void
   */
  public function setClass( $class )
  {
    $this->_class = $class;
  }

  /**
   * Sets the moduletype id
   *
   * @param string $id
   *        the new moduletype id
   *
   * @return void
   */
  public function setId( $id )
  {
    $this->_id = $id;
  }

  /**
   * Sets the position
   *
   * @param int $position
   *        the new position
   *
   * @return void
   */
  public function setPosition( $position )
  {
    $this->_position = $position;
  }

  /**
   * Sets the moduletype required
   *
   * @param bool $required
   *        the required state of moduletype
   *
   * @return void
   */
  public function setRequired( $required )
  {
    $this->_required = $required;
  }

  /**
   * Sets the moduletype shortname
   *
   * @param string $shortname
   *        the new moduletype shortname
   *
   * @return void
   */
  public function setShortname( $shortname )
  {
    $this->_shortname = $shortname;
  }

  /**
   * @param Navigation $navigation
   * @return bool
   */
  public function isAvailableWithNavigation(Navigation $navigation)
  {
    // TODO: Implement isAvailableWithNavigation() method.
    throw new Exception('Implement isAvailableWithNavigation() method');
  }
}