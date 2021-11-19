<?php
// This call to chdir() is necessary because all includes in the system are
// relative to /edwin/index.php.
chdir('..');
require_once 'config.php';

// Initialize the database.
require_once 'includes/db.inc.php';
$tablePrefix = $_CONFIG['table_prefix'];

// Create the template object.
require_once 'includes/classes/core/class.Template.php';
$tpl = new Template;

// Check the authorisation of the logged in user.
require_once 'includes/classes/core/class.User.php';
require_once 'includes/classes/core/class.Session.php';
require_once 'includes/classes/core/class.Login.php';
$session = new Session('edw_be');
$login = new Login($tpl, $db, $tablePrefix, $session);
ob_start();
$user = $login->check();
ob_end_clean();
if ($user->isValid() && $user->AvailableModule('usermgmt')) {
  executeUpdate($db, $tablePrefix);
} else {
  echo '<h1>Sie müssen als Administrator eingeloggt sein, um das Update durchführen zu können.</h1><h2>Bitte loggen Sie sich <a href="../">hier</a> ein.</h2>';
}

// Destroy the template and close the database.
$tpl->destroy();
$db->close();

/**
 * This function performs the update, it is only called if a valid user is
 * logged in and if the user has "admin rights" (rights to the module usermgmt).
 */
function executeUpdate(db $db, $tablePrefix)
{
  echo '<h1>Gesamtes Update [start]</h1>';

  migrateTextInternalLinks($db, $tablePrefix);

  writeSiteLanguageConfigIntoDB($db, $tablePrefix);

  fillContentItemTableHasContentField($db, $tablePrefix);

  echo '<h2>Gesamtes Update [ende]</h2>';
}

function migrateTextInternalLinks(db $db, $tablePrefix)
{
  global $_CONFIG;

  echo '<h2>Interne Links in Texten migrieren [start]</h2>';
  $columns = array(
    "{$tablePrefix}contentitem_bc" => array('BID', array('BText1', 'BText2', 'BText3')),
    "{$tablePrefix}contentitem_bg" => array('GID', array('GText1', 'GText2', 'GText3')),
    "{$tablePrefix}contentitem_bg_image" => array('BIID', array('BIText')),
    "{$tablePrefix}contentitem_bi" => array('BID', array('BText')),
    "{$tablePrefix}contentitem_bo" => array('BID', array('BText1', 'BText2', 'BText3', 'BText4', 'BText5', 'BText6', 'BText7')),
    "{$tablePrefix}contentitem_cb" => array('CBID', array('CBText1', 'CBText2', 'CBText3')),
    "{$tablePrefix}contentitem_cb_box" => array('CBBID', array('CBBText')),
    "{$tablePrefix}contentitem_cb_box_biglink" => array('BLID', array('BLText')),
    "{$tablePrefix}contentitem_cc" => array('CCID', array('CCText1', 'CCText2', 'CCText3')),
    "{$tablePrefix}contentitem_dl" => array('DLID', array('DLText1', 'DLText2')),
    "{$tablePrefix}contentitem_dl_area" => array('DAID', array('DAText')),
    "{$tablePrefix}contentitem_ec" => array('ECID', array('ECText1', 'ECText2', 'ECText3')),
    "{$tablePrefix}contentitem_es" => array('EID', array('EText1', 'EText2', 'EText3')),
    "{$tablePrefix}contentitem_ib" => array('IID', array('IText')),
    "{$tablePrefix}contentitem_im" => array('IID', array('IText1', 'IText2', 'IText3')),
    "{$tablePrefix}contentitem_ls" => array('SID', array('SText1', 'SText2')),
    "{$tablePrefix}contentitem_nl" => array('NLID', array('NLText1', 'NLText2', 'NLText3')),
    "{$tablePrefix}contentitem_pa" => array('PID', array('PText1', 'PText2', 'PText3')),
    "{$tablePrefix}contentitem_pi" => array('PID', array('PText1', 'PText2', 'PText3')),
    "{$tablePrefix}contentitem_po" => array('PID', array('PText1', 'PText2', 'PText3')),
    "{$tablePrefix}contentitem_pt" => array('PID', array('PText1', 'PText2', 'PText3')),
    "{$tablePrefix}contentitem_qs" => array('QID', array('QText1', 'QText2', 'QText3')),
    "{$tablePrefix}contentitem_qs_statement" => array('QSID', array('QSText')),
    "{$tablePrefix}contentitem_sc" => array('SID', array('SText1', 'SText2', 'SText3')),
    "{$tablePrefix}contentitem_se" => array('SID', array('SText')),
    "{$tablePrefix}contentitem_sp" => array('PID', array('PText1', 'PText2', 'PText3')),
    "{$tablePrefix}contentitem_su" => array('SID', array('SText1', 'SText2', 'SText3')),
    "{$tablePrefix}contentitem_ti" => array('TID', array('TText1', 'TText2', 'TText3')),
    "{$tablePrefix}contentitem_to" => array('TID', array('TText1', 'TText2', 'TText3')),
    "{$tablePrefix}contentitem_ts" => array('TID', array('TText1', 'TText2', 'TText3')),
    "{$tablePrefix}contentitem_ts_block" => array('TBID', array('TBText')),
    "{$tablePrefix}contentitem_vc" => array('VID', array('VText1', 'VText2', 'VText3')),
    "{$tablePrefix}module_attribute" => array('AVID', array('AVText')),
    "{$tablePrefix}module_attribute_global" => array('AID', array('AText')),
    "{$tablePrefix}module_infoticker" => array('IID', array('IText')),
    "{$tablePrefix}module_leaguemanager_game" => array('GID', array('GReport', 'GText1', 'GText2', 'GText3')),
    "{$tablePrefix}module_leaguemanager_game_ticker" => array('TID', array('TText')),
    "{$tablePrefix}module_newsticker" => array('TID', array('TText')),
    "{$tablePrefix}module_sidebox" => array('BID', array('BText1', 'BText2', 'BText3')),
    "{$tablePrefix}module_siteindex_compendium" => array('SIID', array('SIText1', 'SIText2', 'SIText3')),
    "{$tablePrefix}module_siteindex_compendium_area" => array('SAID', array('SAText')),
    "{$tablePrefix}module_siteindex_compendium_area_box" => array('SBID', array('SBText')),
    "{$tablePrefix}module_siteindex_textonly" => array('SBID', array('SBText1', 'SBText2', 'SBText3')),
    "{$tablePrefix}module_survey" => array('SID', array('SText', 'SShortText')),
    "{$tablePrefix}module_survey_answer" => array('AID', array('AText1')),
    "{$tablePrefix}module_survey_question" => array('QID', array('QText1', 'QText2', 'QText3')),
  );

  // Read all available sites from the configuration.
  echo '<h3>Lese verfügbare Sites im System</h3>';
  $hosts = $_CONFIG['site_hosts'];
  $hostCount = count($hosts);
  echo "<h4>Anzahl: $hostCount</h4>";
  echo '<ul>';
  $sites = array();
  foreach ($hosts as $siteHost => $siteID) {
    $sites[$siteID] = "http://$siteHost";
    echo "<li>Site $siteID: $sites[$siteID]</li>";
  }
  echo '</ul>';

  // Loop over all tables.
  foreach ($columns as $tableName => $columnNames) {
    echo "<h3>Verarbeite Tabelle $tableName</h3>";

    // Read the existing text columns from the database.
    list($idColumn, $textColumns) = $columnNames;
    $sqlTextColumns = implode(', ', $textColumns);
    $sql = "SELECT $idColumn, $sqlTextColumns "
         . "FROM $tableName ";
    $result = $db->query($sql);
    $count = $db->num_rows($result);
    echo "<h4>Anzahl der Zeilen: $count</h4>";
    echo '<ul>';
    while ($row = $db->fetch_row($result)) {
      echo "<li>ID $row[$idColumn]: ";

      // Migrate the links inside the existing texts.
      $changedColumns = migrateLinks($db, $tablePrefix, $row, $textColumns, $sites);

      // If text colums were changed we update the database.
      if ($changedColumns) {
        $sql = "UPDATE $tableName "
             . 'SET ';
        foreach ($changedColumns as $changedColumnName => $changedColumnValue) {
          $sql .= "$changedColumnName = '{$db->escape($changedColumnValue)}', ";
        }
        $sql .= "$idColumn = $idColumn "
             .  "WHERE $idColumn = $row[$idColumn] ";
        $db->query($sql);
        echo 'Datensatz aktualisiert';
      } else {
        echo 'keine Änderungen';
      }

      echo '</li>';
    }
    echo '</ul>';
  }

  echo '<h2>Interne Links in Texten migrieren [ende]</h2>';
}

function migrateLinks(db $db, $tablePrefix, $row, $textColumns, $sites)
{
  $changedColumns = array();

  // Loop over each text column.
  foreach ($textColumns as $textColumnName) {
    $text = $row[$textColumnName];

    $countAllLinks = 0;
    $countDeadLinks = 0;
    // Loop over each available site (to find internal links to that site).
    foreach ($sites as $siteID => $siteHost) {
      // Process each found internal link.
      $regexFindLink = "#<a class=\"nlink3i\"( title=\"[^\"]*\")? href=\"{$siteHost}((?:/[a-z0-9_]+)+)\">#i";
      while (preg_match($regexFindLink, $text, $matches, PREG_OFFSET_CAPTURE)) {
        $pagePath = $matches[2][0];
        $pagePath = substr($pagePath, 1);

        // Determine the ID of the linked page. Dead links get ID 0.
        $sql = 'SELECT CIID '
             . "FROM {$tablePrefix}contentitem "
             . "WHERE FK_SID = $siteID "
             . "AND CIIdentifier = '$pagePath' ";
        $pageID = (int)$db->GetOne($sql);

        $replacement = "<a{$matches[1][0]} href=\"edwin-link://internal/$pageID\">";

        // Replace the old a-Tag with the new one.
        $text = substr_replace($text, $replacement, $matches[0][1], strlen($matches[0][0]));

        $countAllLinks++;
        if (!$pageID) {
          $countDeadLinks++;
        }
      }
    }

    // If the text was changed we include the changed column in the return value.
    if ($countAllLinks) {
      $changedColumns[$textColumnName] = $text;
      echo "<b>Spalte $textColumnName ($countAllLinks Links";
      if ($countDeadLinks) {
        echo ", <span style=\"color: red\">davon $countDeadLinks tote Links</span>";
      }
      echo ') - </b>';
    }
  }

  return $changedColumns;
}

/**
 * Write site language settings from $_CONFIG['site_languages'] into DB
 * If $_CONFIG['site_languages'] isn't set nothing is changed
 * @return
 */
function writeSiteLanguageConfigIntoDB(db $db, $tablePrefix) {
  global $_CONFIG;
  // if config variable exists from previous edwin version
  if (isset($_CONFIG["site_languages"])) {
    echo '<h2>Seitensprache com CONFIG File in die Datenbank schreiben [start]</h2>';

    $sql = "SELECT SID FROM {$_CONFIG['table_prefix']}site";
    $siteIDs = $db->GetCol($sql);
    // write language variables into db
    foreach ($siteIDs as $id) {
      // if language is defined for site insert into site table SLanguage column
      // if language isn't defined for this site change do not change database entry
      if (isset($_CONFIG["site_languages"][$id])) {
        $_CONFIG["site_languages"][$id];
        $sql = "UPDATE {$_CONFIG['table_prefix']}site "
              ."SET SLanguage='{$_CONFIG['site_languages'][$id]}' "
              ."WHERE SID = $id";
        $result = $db->query($sql);
        if ($result) {
          echo "Seitensprache von Seite mit ID: ".$id." auf {$_CONFIG['site_languages'][$id]} ge&auml;dert";
        }
      }
    }
    echo '<h2>Seitensprache com CONFIG File in die Datenbank schreiben [ende]</h2>';
  }
}

/**
 * The field CHasContent shows if a content item has already content saved in a
 * subtable - this function reads all subtables and updates the CHasContent field
 * in the contentitem table if an entry exists in the subtable
 * @return
 */
function fillContentItemTableHasContentField(db $db, $tablePrefix) {
  global $_CONFIG;

  // return if column isn't available in contentitem table
  $sql = "SELECT * FROM {$_CONFIG['table_prefix']}contentitem";
  $row = $db->GetRow($sql);
  if (!isset($row['CHasContent'])) {
    echo "ACHTUNG: contentitem Spalte CHasContent wird nicht befüllt.<br/> "
        ."SPALTE 'CHasContent' IN TABELLE {$_CONFIG['table_prefix']}contentitem ANLEGEN! <br/>"
        ."Danach dieses Skript erneut ausf&uuml;hren! <br/>";
    return;
  }

  // if column is available start updating table
  echo '<h2>contentitem Spalte CHasContent befüllen [start]</h2><br/>';

  // all table prefixes
  $tablePrefixes = array('bc', 'bg', 'bi', 'bo', 'cb', 'cc', 'dl', 'ec', 'es', 'im', 'ls',
                     'nl', 'pa', 'pi', 'po', 'pt', 'qs', 'sc', 'se', 'sp', 'su',
                     'ti', 'to', 'ts', 'vc', 'xs', 'xu',);

  // search all subtables and set contentitem CHasContent to 1 if subcontent exists
  foreach ($tablePrefixes as $prefix) {
    echo "<b>Tabelle {$_CONFIG['table_prefix']}contentitem_{$prefix} wird durchsucht:</b><br/>";
    try {
      $sql = "SELECT DISTINCT(FK_CIID) FROM {$_CONFIG['table_prefix']}contentitem_{$prefix}";
      $foreignKeys = $db->GetCol($sql);
      if ($foreignKeys) {
        $foreignKeys = implode(', ', $foreignKeys);
        $sql = "UPDATE {$_CONFIG['table_prefix']}contentitem "
              ."SET CHasContent = 1 "
              ."WHERE CIID IN ( $foreignKeys )";
        $result = $db->query($sql);
        echo "{$_CONFIG['table_prefix']}contentitem wurde aktualisiert<br/>";
      }
      else {
        echo "Keine Einträge in {$_CONFIG['table_prefix']}contentitem_{$prefix}<br/>";
      }
    }
    catch (Exception $e) {
      echo "Die Tabelle {$_CONFIG['table_prefix']}contentitem_{$prefix} wurde nicht gefunden<br/>";
    }
    echo "<br/>";
  }
  echo '<h2>contentitem Spalte CHasContent befüllen [ende]</h2><br/>';
}
