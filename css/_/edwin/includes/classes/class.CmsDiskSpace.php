<?php

/**
 * Handles the disk space usage analysis and other information.
 *
 * Example:
 *   $obj = new CmsDiskSpace($db, $tablePrefix, 99999999, array(1, 2, 3));
 *   $obj->getUsedSpace(); // returns used disk space
 *   // WRONG:
 *   $obj->getFreeSpace(); // returns 0 for 0 limit ( = no limit )
 *   // RIGHT:
 *   // as $obj->getFreeSpace() returns 0 for 0 limit ( = no limit ) we have to
 *   // check if a limit exists first.
 *   if ($obj->getLimit()) {
 *     $obj->getFreeSpace();
 *   }
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mrz 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class CmsDiskSpace
{
  /**
   * Caches disk space usage information for all sites
   *
   * @var null | array
   *      null if not read from database yet, array otherwise
   */
  private static $_cache = null;

  /**
   * The database connection
   *
   * @var Db
   */
  private $_db;

  /**
   * The amount of disk space available
   *
   * @var int
   */
  private $_limit;

  /**
   * The ids of sites to handle
   *
   * @var array
   */
  private $_sites;

  /**
   * The database table prefix
   *
   * @var string
   */
  private $_tablePrefix;

  /**
   * The amount of disk space used
   *
   * @var int
   */
  private $_used;

  /**
   * Construct
   *
   * @param Db $db
   *        the database object
   * @param string $tablePrefix
   *        the database table prefix
   * @param int $limit
   *        the disk space quota / limit
   * @param array $sites [optional]
   *        the ids of sites to handle, if not set all sites are handled ( =
   *        global disk space usage )
   */
  public function __construct(Db $db, $tablePrefix, $limit = 0, $sites = array())
  {
    $this->_db = $db;
    $this->_tablePrefix = $tablePrefix;
    $this->_sites = $sites;
    $this->_limit = $limit;
  }

  /**
   * Returns free space, 0 if no free space left or no limit is set and thus an
   * undefined amount of bytes is available
   *
   * @return int
   */
  public function getFreeSpace()
  {
    return max($this->_limit - $this->getUsedSpace(), 0);
  }

  /**
   * Returns the quota / limit in bytes
   *
   * @return int
   */
  public function getLimit()
  {
    return $this->_limit;
  }

  /**
   * Returns the used disk space in bytes
   *
   * @return int
   */
  public function getUsedSpace()
  {
    $this->_readUsedDiskSpace();
    return $this->_used;
  }

  /**
   * Checks if quota / limit is exceeded.
   *
   * @return bool
   */
  public function isExceeded()
  {
    if ($this->getLimit() && !$this->getFreeSpace()) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Returns true if given bytes are available, false otherwise
   *
   * @param int $bytes
   *        the amount of bytes to check availability for
   *
   * @return bool
   */
  public function isAvailable($bytes)
  {
    $bool = false;

    if (!$this->getLimit()) { $bool = true; }                   // no limit
    else if ($this->getFreeSpace() > $bytes) { $bool = true; }  // available space
    else { $bool = false; }                                     // exceeded

    return $bool;
  }

  /**
   * Reads used disk space from database and stores it within the member variable
   * DiskSpaceUsage::_used. There is data for all sites read from database and
   * stored within the cache.
   *
   * @return void
   */
  private function _readUsedDiskSpace()
  {
    if ($this->_used !== null) { // already set before
      return;
    }

    $this->_used = $this->_getUsedSpaceFromCache();
    if ($this->_used !== null) {
      return;
    }

    $sql = " SELECT FK_SID, CFSize AS bytes "
         . " FROM {$this->_tablePrefix}centralfile "
         . " WHERE CFSize > 0 "
         . " UNION ALL "
         . " SELECT FK_SID, DFSize AS bytes "
         . " FROM {$this->_tablePrefix}contentitem_dl_area_file df "
         . " JOIN {$this->_tablePrefix}contentitem_dl_area da "
         . "   ON df.FK_DAID = da.DAID "
         . " JOIN {$this->_tablePrefix}contentitem ci "
         . "   ON da.FK_CIID = ci.CIID "
         . " WHERE DFSize > 0 "
         . " UNION ALL "
         . " SELECT FK_SID, FSize AS bytes "
         . " FROM {$this->_tablePrefix}file f "
         . " JOIN {$this->_tablePrefix}contentitem ci "
         . "   ON f.FK_CIID = ci.CIID "
         . " WHERE FSize > 0 ";

    $tmp = array();
    $result = $this->_db->query($sql);
    while ($row = $this->_db->fetch_row($result)) {
      $siteId = (int)$row['FK_SID'];
      $bytes = (int)$row['bytes'];

      if (isset($tmp[$siteId])) { $tmp[$siteId] += $bytes; }
      else { $tmp[$siteId] = $bytes; }
    }
    $this->_db->free_result($result);

    // init cache with all sites ( = 0 )
    $cache = array(0 => 0);
    foreach ($tmp as $key => $value) {
      $cache[0] += $value;    // all sites
      $cache[$key] = $value;  // specific site
    }

    self::$_cache = $cache;
    $this->_used = $this->_getUsedSpaceFromCache();
  }

  /**
   * Returns cached values
   *
   * @return int | null
   *         The used disk space in bytes, null if cache has not been set
   */
  private function _getUsedSpaceFromCache()
  {
    $bytes = null;
    // if the cache is set, the disk space data has already been read from
    // database, so we try to fetch used disk space for specified sites from
    // cache. If not available ( = no downloads on sites ) 0 is returned.
    if (self::$_cache !== null) {
      $bytes = 0;

      if (!empty($this->_sites)) { // defined sites
        foreach ($this->_sites as $id) {
          $bytes += isset(self::$_cache[$id]) ? self::$_cache[$id] : 0;
        }
      }
      else { // all sites
        foreach (self::$_cache as $id => $val) {
          $bytes += $val;
        }
      }
    }

    return $bytes;
  }
}