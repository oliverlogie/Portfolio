<?php

/*
 * EDWIN 3.6.7 update script
 *
 * $LastChangedDate: 2014-03-13 15:33:56 +0100 (Do, 13 Mrz 2014) $
 * $LastChangedBy: jua $
 *
 * @package admin
 * @author Anton Jungwirth
 * @copyright (c) 2014 Q2E GmbH
 */

// This call to chdir() is necessary because all includes in the system are
// relative to /edwin/index.php.
chdir('..');

include 'includes/bootstrap.php';

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
  updateMedialibraryCategoryAssignmentPositions($db, $tablePrefix);
  echo '</pre>';
}

/**
 * @param $db
 * @param $tablePrefix
 * @return void
 */
function updateMedialibraryCategoryAssignmentPositions(db $db, $tablePrefix)
{
  $out = <<<TEXT



/*******************************************************************************
 Update der Medialibrary Kategoriezuweisung Positionen [START]
 ******************************************************************************/

TEXT;


  $sql = " SELECT MCAID, FK_MID, FK_MCID, MCAPosition "
       . " FROM {$tablePrefix}module_medialibrary_category_assignment "
       . " ORDER BY FK_MCID, FK_MID ASC ";
  $res = $db->query($sql);
  $out .= "\n";
  $mcId = 0;
  while ($row = $db->fetch_row($res)) {
    if ($mcId != $row['FK_MCID']) {
      $mcId = $row['FK_MCID'];
      $position = 1;
    }
    $out .= " UPDATE {$tablePrefix}module_medialibrary_category_assignment "
          . "    SET MCAPosition = '$position' "
          . "  WHERE MCAID = {$row['MCAID']} ;\n";
    $position ++;
  }
  $out .= " ALTER TABLE {$tablePrefix}module_medialibrary_category_assignment ADD UNIQUE FK_MCID_MCAPosition_UN (FK_MCID,MCAPosition); ";
  $out .= "\n\n";

  $out .= <<<TEXT
/*******************************************************************************
 Update der Medialibrary Kategoriezuweisung Positionen [END]
 ******************************************************************************/
TEXT;

  echo $out;
}