<?php

/*
 * EDWIN 3.1.2 update script
 *
 * $LastChangedDate: 2014-12-18 08:10:58 +0100 (Do, 18 Dez 2014) $
 * $LastChangedBy: ulb $
 *
 * @package admin
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */

include '../includes/bootstrap.php';

$tablePrefix = ConfigHelper::get('table_prefix');

executeUpdate($db, $tablePrefix);

$db->close();

// functions -----------------------------------------------------------------//

/**
 * The update entry function
 *
 * @param db $db
 * @param unknown_type $tablePrefix
 *
 * @return void
 */
function executeUpdate(db $db, $tablePrefix)
{
  echo '<pre>';
  updateUserRights($db, $tablePrefix);
  echo '</pre>';
}

/**
 * Outputs the SQL statements for updating user specific tables.
 * Sets UModuleRights column in mc_user from UModules in mc_user_rights and
 * removes UModules column afterwards.
 *
 * @param $db
 * @param $tablePrefix
 *
 * @return void
 */
function updateUserRights(db $db, $tablePrefix)
{
  $out = <<<SQL
/* Update der Benutzerrechte */
/* SQL Statements ausführen */

SQL;

  $siteindexRights = array();

  $sql = " SELECT * "
       . " FROM {$tablePrefix}user_rights ";
  $result = $db->query($sql);

  while ($row = $db->fetch_row($result)) {

    $siteId = (int)$row['FK_SID'];
    $userId = (int)$row['FK_UID'];
    $modules = trim($row['UModules']);
    $userSiteindexOnSite = isset($siteindexRights[$userId]) &&
                           in_array($siteId, $siteindexRights[$userId]);

    if (!$siteId) {
      $out .= <<<SQL
UPDATE {$tablePrefix}user SET UModuleRights = '$modules'
WHERE UID = $userId;

SQL;
    }
    else if ($modules == 'siteindex' && !$userSiteindexOnSite ) {
      $siteindexRights[$userId][] = $siteId;
    }
  }

  foreach ($siteindexRights as $userId => $siteIds) {
    foreach ($siteIds as $siteId) {
      $out .= <<<SQL
INSERT INTO {$tablePrefix}user_rights (FK_UID, FK_SID, UScope)
VALUES ($userId, $siteId, 'siteindex');

SQL;
    }
  }

  $out .= "ALTER TABLE {$tablePrefix}user_rights DROP UModules;\n";
  $out .= "DELETE FROM {$tablePrefix}user_rights WHERE FK_SID = 0;\n";

  echo $out;
}