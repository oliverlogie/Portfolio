<?php

/*
 * EDWIN 3.2.0 update script
 *
 * $LastChangedDate: 2014-12-18 08:10:58 +0100 (Do, 18 Dez 2014) $
 * $LastChangedBy: ulb $
 *
 * @package admin
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

// This call to chdir() is necessary because all includes in the system are
// relative to /edwin/index.php.

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
  updateEmployeeDepartmentsAndLocations($db, $tablePrefix);
  updateContentItemPPCheapestProducts($db, $tablePrefix);
  updateDeleteZombieDLAreaFiles($db, $tablePrefix);
  echo '</pre>';
}

/**
 * Outputs the SQL statements for updating employee specific tables.
 * Moves the employee departments and locations into the attributes
 * table.
 *
 * @param $db
 * @param $tablePrefix
 *
 * @return void
 */
function updateEmployeeDepartmentsAndLocations(db $db, $tablePrefix)
{
  $out = <<<SQL
/* Update der Mitarbeiter Abteilungen und Standorte */

SQL;

  // Get employee attribute group for locations
  $sql = " SELECT AID FROM {$tablePrefix}module_attribute_global "
       . " WHERE AIdentifier = 'en_location' ";
  $attrGroupLocationId = $db->GetOne($sql);
  if (!$attrGroupLocationId) {
    echo "Fehler: SQL Updates update-3_1_2-3_2_0.sql ausfuehren!";
    exit;
  }

  // Get employee attribute group for departments
  $sql = " SELECT AID FROM {$tablePrefix}module_attribute_global "
       . " WHERE AIdentifier = 'ep_department' ";
  $attrGroupDepartmentId = $db->GetOne($sql);
  if (!$attrGroupDepartmentId) {
    echo "Fehler: SQL Updates update-3_1_2-3_2_0.sql ausfuehren!";
    exit;
  }

  // Get all employee types (locations)
  $sql = " SELECT * "
       . " FROM {$tablePrefix}module_employee_type "
       . " WHERE FK_SID = 1 "
       . " ORDER BY ETPosition ASC ";
  $result = $db->query($sql);

  $position = 1;
  while ($row = $db->fetch_row($result)) {

    $title = $row['ETTitle'];
    $typeId = $row['ETID'];

    $sql = " INSERT INTO {$tablePrefix}module_attribute "
         . " (AVTitle, AVPosition, FK_AID) "
         . " VALUES('{$title}', {$position}, {$attrGroupLocationId}) ";
    $db->query($sql);
    $avId = $db->insert_id();
    $position ++;

    $sql = " INSERT INTO {$tablePrefix}module_employee_attribute "
         . " (FK_EID, FK_AVID) "
         . " SELECT EID, {$avId} FROM {$tablePrefix}module_employee WHERE FK_ETID = {$typeId}";
    $db->query($sql);
  }

  // Get all employee departments
  $sql = " SELECT * "
       . " FROM {$tablePrefix}module_employee_department "
       . " WHERE FK_SID = 1 "
       . " ORDER BY EDPosition ASC ";
  $result = $db->query($sql);

  $position = 1;
  while ($row = $db->fetch_row($result)) {

    $title = $row['EDTitle'];
    $departmentId = $row['EDID'];

    $sql = " INSERT INTO {$tablePrefix}module_attribute "
         . " (AVTitle, AVPosition, FK_AID) "
         . " VALUES('{$title}', {$position}, {$attrGroupDepartmentId}) ";
    $db->query($sql);
    $avId = $db->insert_id();
    $position ++;

    $sql = " INSERT INTO {$tablePrefix}module_employee_attribute "
         . " (FK_EID, FK_AVID) "
         . " SELECT FK_EID, {$avId} FROM {$tablePrefix}module_employee_department_assignment WHERE FK_EDID = {$departmentId}";
    $db->query($sql);
  }

  $out .= <<<SQL
/* Ueberpruefen ob module_employee_attribute und
/* module_attribute richtig befuellt wurden. */
/* Danach ueberfluessige Tabellen und Spalten loeschen: */

SQL;

  $out .= "ALTER TABLE {$tablePrefix}module_employee DROP FK_ETID;\n";
  $out .= "DROP TABLE {$tablePrefix}module_employee_type;\n";
  $out .= "DROP TABLE {$tablePrefix}module_employee_department;\n";
  $out .= "DROP TABLE {$tablePrefix}module_employee_department_assignment;\n";

  echo $out;
}

function updateContentItemPPCheapestProducts(Db $db, $tablePrefix)
{
  $out = <<<SQL

/* Für jedes ContentItem PP das billigste Produkt zuweisen */

SQL;

  echo $out;

  $sql = " SELECT FK_CIID "
       . " FROM {$tablePrefix}contentitem_pp ";
  $result = $db->query($sql);

  while ($row = $db->fetch_row($result)) {
    updateContentItemPPCheapestProduct($db, $tablePrefix, $row['FK_CIID']);
  }

  $db->free_result($result);

  $out = <<<SQL
/* ...erledigt */

SQL;

  echo $out;
}

function updateContentItemPPCheapestProduct(Db $db, $tablePrefix, $pageId)
{
    $pageId = (int)$pageId;
    if (!$pageId) {
      throw InvalidArgumentException("Invalid 'id' for ContentItemPP.");
    }

    $sql = " SELECT PPPID, PPPPrice, PPPrice "
         . " FROM {$tablePrefix}contentitem_pp_product pp "
         . " JOIN {$tablePrefix}contentitem_pp p "
         . " ON p.FK_CIID = pp.FK_CIID "
         . " WHERE p.FK_CIID = $pageId "
         . " ORDER BY PPPPosition ASC ";
    $result = $db->query($sql);

    // initialize
    $row = $db->fetch_row($result);
    $product = (int)$row['PPPID'];
    $price   = (float)$row['PPPPrice'] ? (float)$row['PPPPrice'] :
               (float)$row['PPPrice'];

    while ($row = $db->fetch_row($result)) {
      $tmpProduct = (int)$row['PPPID'];
      $tmpPrice = (float)$row['PPPPrice'] ? (float)$row['PPPPrice'] :
                  (float)$row['PPPrice'];

      if ($tmpPrice < $price) {
        $price = $tmpPrice;
        $product = $tmpProduct;
      }
    }

    $db->free_result($result);

    $sql = " UPDATE {$tablePrefix}contentitem_pp "
         . " SET FK_PPPID_Cheapest = '$product' "
         . " WHERE FK_CIID = $pageId ";
    $db->query($sql);
}

function updateDeleteZombieDLAreaFiles(Db $db, $tablePrefix)
{
    $sql = " SELECT * "
         . " FROM {$tablePrefix}contentitem_dl_area_file "
         . " WHERE FK_DAID NOT IN ( "
         . "    SELECT DAID "
         . "    FROM mc_contentitem_dl_area "
         . " ) ";
    $result = $db->query($sql);

    // Delete all area files stored within the download area.
    while ($file = $db->fetch_row($result))
    {
      // delete file
      $sql = " DELETE FROM {$tablePrefix}contentitem_dl_area_file "
           . ' WHERE DFID = ' . $file['DFID'];
      $db->query($sql);

      if ($file['DFFile']) {
        unlinkIfExists('../' . $file['DFFile']);
      }
    }

    $sql = " SELECT SID "
         . " FROM {$tablePrefix}site ";
    $siteIds = $db->GetCol($sql);

    $out = <<<SQL
/* Fehlerhafte DL Area Downloads wurden entfernt:
   Folgendes Skripts muss ausgeführt werden: edwin/manage_stuff.php?do=spider_content&site=999999 */

SQL;

    echo $out;
}