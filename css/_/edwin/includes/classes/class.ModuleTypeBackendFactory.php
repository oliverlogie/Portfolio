<?php

/**
 * Factory for retrieving ModuleType objects
 *
 * $LastChangedDate: 2019-01-21 08:50:18 +0100 (Mo, 21 Jan 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */
class ModuleTypeBackendFactory
{
  /**
   * Contains all ModuleType objects created and loaded from db
   *
   * @var array
   */
  private static $_cache = array();

  /**
   * Moduletype ids of all moduletypes available from database
   *
   * @var array
   */
  private static $_ids = null;

  /**
   * Moduletype ids of active moduletypes available from database
   *
   * @var array
   */
  private static $_activeIds = null;

  /**
   * The database conntection
   *
   * @var db
   */
  private $_db;

  /**
   * The database table prefix
   *
   * @var string
   */
  private $_tablePrefix;

  /**
   * Constructs the factory object
   *
   * @param db $db
   *        the database connection
   * @param string $tablePrefix
   *        the database table prefix
   */
  public function __construct(db $db, $tablePrefix)
  {
    $this->_db = $db;
    $this->_tablePrefix = $tablePrefix;
  }

  /**
   * Returns all active moduletypes
   *
   * @return array
   */
  public function getAllActive()
  {
    $mts = $this->_get($this->getActiveIds());
    return $mts;
  }

  /**
   * Returns all moduletypes
   *
   * @return array
   */
  public function getAll()
  {
    $mts = $this->_get($this->getIds());
    return $mts;
  }

  /**
   * Returns one single moduletype
   *
   * @return InterfaceModuleType
   */
  public function getById($id)
  {
    $id = (int)$id;
    $mts = $this->_get($id);
    return isset($mts[$id]) ? $mts[$id] : null;
  }

  /**
   * Returns one single moduletype
   *
   * @param string $shortname
   *        Module's shortname.
   * @return InterfaceModuleType | null
   */
  public function getByShortname($shortname)
  {
    $mts = $this->_get($this->getIds());
    foreach ($mts as $moduleType) {
      if ($shortname == $moduleType->getShortname()) {
        return $moduleType;
      }
    }

    return null;
  }

  /**
   * Returns ids of all moduletypes available
   *
   * @return array
   */
  public function getIds()
  {
    $this->_readIds();
    return self::$_ids;
  }

  /**
   * Returns ids of all moduletypes available
   *
   * @return array
   */
  public function getActiveIds()
  {
    $this->_readActiveIds();
    return self::$_activeIds;
  }

  /**
   * Reads and returns ModuleType objects
   *
   * @param array | int $ids
   *        one or more moduletype ids
   *
   * @throws Exception
   * @return array
   *         An array containing the retrieved ModuleType objects
   */
  private function _get($ids)
  {
    $ids = (array)$ids;
    $tmpIds = array_diff($ids, array_keys(self::$_cache));

    // Read the moduletypes from the database.
    if ($tmpIds) {
      $this->_readBackendModuleTypesWithIds($tmpIds);

      // If moduletype is not available set its value to null
      foreach ($ids as $id) {
        if (!array_key_exists($id, self::$_cache)) {
          self::$_cache[$id] = null;
        }
      }
    }

    $result = array();
    foreach ($ids as $id) {
      if (isset(self::$_cache[$id])) {
        $result[$id] = self::$_cache[$id];
      }
    }

    return $result;
  }

  /**
   * @param array $ids
   */
  private function _readBackendModuleTypesWithIds(array $ids)
  {
    $sqlIDs = implode(', ', $ids);
    $sql = " SELECT MID, MShortname, MClass, MActive, MPosition, MRequired "
      . " FROM {$this->_tablePrefix}moduletype_backend "
      . " WHERE MID IN ($sqlIDs) ";
    $rows = $this->_db->q($sql)->fetchAll() ?: array();

    foreach ($rows as $row) {
      $id = (int)$row['MID'];
      $ct = new ModuleTypeBackend($this->_db, $this->_tablePrefix, $row);
      self::$_cache[$id] = $ct;
    }
  }

  /**
   * Reads active moduletype ids and stores them within
   * ModuleTypeFactory::_activeIds
   *
   * @return void
   */
  private function _readActiveIds()
  {
    if (self::$_activeIds === null) {
      $order = '';
      // Only backend modules have got a position
      if ($this->_type == self::TYPE_BACKEND) {
        $order = ' ORDER BY MPosition ASC ';
      }
      $sql = " SELECT MID "
        . " FROM {$this->_tablePrefix}moduletype_backend "
        . " WHERE MActive = 1 "
        . " {$order} ";
      self::$_activeIds = $this->_db->GetCol($sql);
    }
  }

  /**
   * Reads available moduletype ids and stores them within
   * ModuleTypeFactory::_ids
   *
   * @return void
   */
  private function _readIds()
  {
    if (self::$_ids === null) {
      $sql = " SELECT MID "
           . " FROM {$this->_tablePrefix}moduletype_backend ";
      self::$_ids = $this->_db->GetCol($sql);
    }
  }
}