<?php

/*
 * EDWIN 3.3.2 update script
 *
 * $LastChangedDate: 2014-12-18 08:10:58 +0100 (Do, 18 Dez 2014) $
 * $LastChangedBy: ulb $
 *
 * @package admin
 * @author Anton Jungwirth
 * @copyright (c) 2013 Q2E GmbH
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
  updateShipmentModePositions($db, $tablePrefix);
  echo '</pre>';
}

/**
 * @param $db
 * @param $tablePrefix
 * @return void
 */
function updateShipmentModePositions(db $db, $tablePrefix)
{
  $out = <<<TEXT



/*******************************************************************************
 Update der Versandart Positionen [START]
 ******************************************************************************/

TEXT;


  $sql = " SELECT CPSID, FK_SID "
       . " FROM {$tablePrefix}contentitem_cp_shipment_mode "
       . " ORDER BY FK_SID, CPSID ASC ";
  $res = $db->query($sql);
  $out .= "\n";
  $sId = 0;
  while ($row = $db->fetch_row($res)) {
    if ($sId != $row['FK_SID']) {
      $sId = $row['FK_SID'];
      $position = 1;
    }
    $out .= " UPDATE {$tablePrefix}contentitem_cp_shipment_mode "
          . "    SET CPSPosition = '$position' "
          . "  WHERE CPSID = {$row['CPSID']};\n";
    $position ++;
  }
  $out .= " ALTER TABLE {$tablePrefix}contentitem_cp_shipment_mode ADD UNIQUE FK_SID_CPSPosition_UN (FK_SID,CPSPosition); ";
  $out .= "\n\n";

  $out .= <<<TEXT
/*******************************************************************************
 Update der Versandart Positionen [END]
 ******************************************************************************/
TEXT;

  echo $out;
}