<?php

/**
 * Edwin Updater
 *
 * $LastChangedDate: 03.12.2010 21:22:51 +0430 (Mo, 01 Nov 2010) $
 * $LastChangedBy: jua $

 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2010 Q2E GmbH
 */

/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/*
 * Das Backup wird aus der in config.php eingetragenen DB generiert.
 * Dabei wird primaer versucht das SQL Dump File unter dem Backup Pfad zu
 * speichern (standardmaessig: backup/). Sollte das Verzeichnis nicht
 * existieren oder keine Schreibrechte vorhanden sein, wird das File
 * nur generiert. In jedem Fall wird das File zum Download angeboten.
 *
 * Die Liste mit den Versionen wird dynamisch erstellt aus den vorhandenen
 * SQL Update Files. Wichtig ist, dass folgende Struktur eingehalten wird:
 * update-ALTE_VERSION-NEUE_VERSION.sql
 *
 * Ein SQL Update File wird zerlegt nach dem Strichpunkt (;) und danach jedes
 * Statement einzeln ausgefuehrt und ausgegeben.
 * Sollte ein Fehler auftreten stoppt das Skript und der User muss eine
 * weitere Fortsetzung bestaetigen.
 *
 * PHP Update Files koennen wie gehabt erstellt werden. Wichtig ist,
 * dass es eine Funktion executeUpdate(db $db, $tablePrefix) gibt und alle
 * anderen Funktionen DANACH (weiter unten) definiert werden.
 * Der Namen der Datei sollte der bisherigen Kovention folgen:
 * update-ALTE_VERSION-NEUE_VERSION.php
 *
 */

// This call to chdir() is necessary because all includes in the system are
// relative to /edwin/index.php.
chdir('../..');
// set path of include_dir configuration
$baseDir = getcwd().'/';

include $baseDir . 'includes/bootstrap.php';

// path where sql dump files should be saved; path must end with a slash
if (!isset($_CONFIG['updater_backup_path'])) $_CONFIG['updater_backup_path'] = 'backup/';
// path to all update sql/php files
if (!isset($_CONFIG['updater_update_files_dir'])) $_CONFIG['updater_update_files_dir'] = '../';
// print after each sql file update information, if set to true
if (!isset($_CONFIG['updater_print_sql_info'])) $_CONFIG['updater_print_sql_info'] = true;
// update script will stop after each update sql file, that contains sql file update information, if set to true
if (!isset($_CONFIG['updater_stop_after_update'])) $_CONFIG['updater_stop_after_update'] = true;

$tablePrefix = $_CONFIG['table_prefix'];
$tpl = new Template;
$session = new Session($_CONFIG['m_session_name_backend']);
$login = new Login($tpl, $db, $tablePrefix, $session);
ob_start();
$user = $login->check();
ob_end_clean();

chdir('admin/updater');

if ($user->isValid() && $user->AvailableModule('usermgmt', 0)) {
  // user is allowed to update EDWIN
  ob_start();

  // create version log db table if not exists
  createVersionLog($db, $tablePrefix);

  // get current EDWIN version
  $currentVersion = getCurrentVersion();

  if (isset($_GET['export'])) {
    $content = getSQLDump(($currentVersion) ? $currentVersion : 'N/A');
    export_sql_dump($content, $_GET['export']);
    // exit to avoid html output
    exit();
  } else if (isset($_POST['error'])) {
    // if error during update occurs and user wants to go on
    // show previous content
    echo $_POST['contents'];
    $sqlInfos = unserialize($_POST['sqlInfos']);
    // go on with EDWIN update
    doUpdatePrintLog($_POST['oldVersion'], $_POST['newVersion'], $_POST['currentVersion'], $_POST['currentQuery'], $sqlInfos, $_POST['phpNext']);
  } else {
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <title>EDWIN Updater</title>
      <link rel="stylesheet" type="text/css" href="updater.css" />
      <script type="text/javascript" src="updater.js"></script>
    </head>
    <body>
    <h1>EDWIN Updater</h1>';
    show_content();
  }
  echo '</body></html>';
  ob_end_flush();
} else {
  echo '<h1>Sie müssen als Administrator eingeloggt sein, um ein Update durchführen zu können.</h1><h2>Bitte loggen Sie sich <a href="../../">hier</a> ein.</h2>';
}

// destroy the template and close the database.
$tpl->destroy();
$db->close();

/***************************************************/
/*                    FUNCTIONS                     /
/***************************************************/

/**
 * Show content of EDWIN updater
 */
function show_content() {
  global $_CONFIG, $currentVersion, $db, $tablePrefix;

  $versions = getAllVersions();

  // show backup information
  echo '<div id="backup">';
  $fileName = '';
  // save backup, if backup path is available and update button was not clicked
  if (@is_writable($_CONFIG['updater_backup_path']) && !isset($_POST['update']))
  {
    $content = getSQLDump(($currentVersion) ? $currentVersion : 'N/A');
    $fileName = getFileName();
    save_sql_dump($content, $fileName);
    echo 'Backup Pfad hat Schreibrechte. Datei wurde gespeichert.<br/>';
    echo 'Datenbank-Backup Download Link: <a href="'.$_CONFIG['updater_backup_path'].$fileName.'" target="_blank">'.$fileName.'</a>';
  }
  // export backup file if backup path is not available
  else if (!@is_writable($_CONFIG['updater_backup_path']))
  {
    $fileName = getFileName();
    echo 'Info: Backup Pfad hat KEINE Schreibrecht. Datei wurde NICHT gespeichert. SQL Backup bitte manuell herunterladen.<br/>';
    echo 'Datenbank-Backup Download Link: <a href="index.php?export='.$fileName.'" target="_blank">'.$fileName.'</a>';
  }
  // output link to download already generated backup file, if filename is available
  else if (isset($_POST['filename'])) {
    echo 'Datenbank-Backup Download Link: <a href="'.$_CONFIG['updater_backup_path'].$_POST['filename'].'" target="_blank">'.$_POST['filename'].'</a>';
  }
  echo '</div>';

  // show update options
  echo '<hr /><form action="index.php" method="post" accept-charset="UTF-8">';
  if ($currentVersion) {
    echo 'Aktuelle Version: <select name="oldVersion">'.getVersionListOptions($versions, $currentVersion).'</select>';
  } else {
    echo 'Hinweis: KEINE Versionsangabe in der Datenbank gefunden.<br />
          Aktuelle Version ausw&auml;hlen: <select name="oldVersion">'.getVersionListOptions($versions, (isset($_POST['oldVersion'])) ? $_POST['oldVersion'] : null).'</select>';
  }
  $show = array_search($currentVersion, $versions);

  $show = false;
  if ($show === false) {
    // if current version could not be found set selected target version to second lowest version
    $show = 1;
  } else {
    // else set selected target version to next version after current version
    $show ++;
  }
  if ($currentVersion) {
    $select = array_search($currentVersion, $versions) + 1;
  } else if (isset($_POST['newVersion'])) {
    $select = $_POST['newVersion'];
  } else {
    $select = null;
  }
  echo ' -&gt; Zielversion: <select name="newVersion">'.getVersionListOptions($versions, $select, $show).'</select>
        <input type="submit" name="update" />
        <input type="hidden" name="filename" value="'.$fileName.'" />
        </form>';

  if (isset($_POST['update'])) {
    // get index of old and new version
    doUpdatePrintLog($_POST['oldVersion'], $_POST['newVersion']);
  }
}

/**
 * Runs sql updates and prints live update log
 * @param int $oldVersion old EDWIN version
 * @param int $newVersion target update version
 * @param int $currentVersion optional; update will start at this version
 * @param int $currentQuery optional; update will start at this query statement number
 * @param array $sqlInfos optional; sql infos of sql update files, array contains version number and info ()
 * @return NULL on issues or sql query error
 */
function doUpdatePrintLog($oldVersion, $newVersion, $currentVersion=0, $currentQuery=0, $sqlInfos=null, $phpNext=false) {
  global $_CONFIG, $db, $tablePrefix;

  $versions = getAllVersions();

  if ($oldVersion >= $newVersion) return null;
  // get all versions between old version and new version
  $affectedVersions = array_slice($versions, $oldVersion, ($newVersion - $oldVersion + 1));
  $n = count($affectedVersions);
  $sqlInfos = (empty($sqlInfos)) ? array() : $sqlInfos;
  echo '<table cellspacing="1" cellpadding="2" class="update_log">';

  // load possible php file
  if ($phpNext) {
    $filePHP = 'update-'.$affectedVersions[$currentVersion-1].'-'.$affectedVersions[$currentVersion];
    if (is_readable($_CONFIG['updater_update_files_dir'].$filePHP.'.php')) {
      echo '<tr><th class="php" colspan="3" align="left">'.$filePHP.'.php</th></tr>
            <tr><td colspan="3" align="left">';
      $content = file_get_contents($_CONFIG['updater_update_files_dir'].$filePHP.'.php');
      $start = mb_strpos(mb_strtolower($content), mb_strtolower("function executeUpdate"));
      $content = mb_substr($content, $start);
      $content = str_replace('function executeUpdate', "function executeUpdate$currentVersion", $content);
      eval($content);
      echo '<a href="javascript:showhide_box(\'query_'.$currentVersion.'\');">Zeige vollst&auml;ndige Ausgabe...</a>';
      echo '<div id="query_'.$currentVersion.'" style="display:none;visibility:hidden;">';
      call_user_func_array("executeUpdate$currentVersion", array(&$db, &$tablePrefix));
      echo '</div></td></tr></table>';
      // get form (with go button)
      echo getGoOnForm(ob_get_contents(), $oldVersion, $newVersion, $sqlInfos, $currentVersion, 0);
      // show update log
      ob_flush();
      // stop update script
      return null;
    }
  }

  for ($i = $currentVersion; $i < ($n-1); $i ++) {
    $file = 'update-'.$affectedVersions[$i].'-'.$affectedVersions[$i + 1];
    // load sql file
    if (is_readable($_CONFIG['updater_update_files_dir'].$file.'.sql')) {
      $content = file_get_contents($_CONFIG['updater_update_files_dir'].$file.'.sql');
      // replace standard prefix (mc_) with given prefix
      $content = str_replace('mc_', $tablePrefix, $content);
      // replace os dependend line breaks or linebreaks after whitespaces with \n
      $content = str_replace(array(";\r\n", ";\r", "; \r\n", "; \r", "; \n"), ";\n", $content);
      // split the update file into queries. each query ends with a semicolon and a linebreak
      $queries = explode(";\n", $content, -1);
      $q_count = count($queries);
      if ($currentQuery < $q_count) {
        // only print row header if there is at least one unpublished query available
        echo '<tr><th colspan="3" align="left">'.$file.'.sql</th></tr>';
      }
      $startInfoPosition = 0;
      $startedInfoContent = false;
      $infoReady = false;
      $infoContent = '';
      for ($q = $currentQuery; $q < $q_count; $q ++) {
        $startInfo = mb_strpos(mb_strtolower($queries[$q]), mb_strtolower("[info]"));
        $endInfo = mb_strpos(mb_strtolower($queries[$q]), mb_strtolower("[/info]"));
        $startInfoPosition = ($startInfo) ? $startInfo : 0;
        // query with start and end info tag
        if ($startInfo && $endInfo) {
          $infoReady = true;
          $infoContent = $queries[$q];
        }
        // end info tag found
        else if ($endInfo && $startedInfoContent) {
          $infoReady = true;
          $infoContent .= $queries[$q];
          $startedInfoContent = false;
          continue;
        }
        // query contains only start info tag. occurs if info comment contains semicolons
        else if (($startInfo && !$endInfo) || $startedInfoContent) {
          $startedInfoContent = true;
          // add semicolon and line break again, we lost it while exploding
          $infoContent .= $queries[$q].";\n";
          continue;
        }
        else if ($infoContent) {
          $infoReady = true;
        }

        if ($infoReady) {
          // get info after start info tag to end info tag and remove last asterisk (-2)
          $info = mb_substr($infoContent, $startInfoPosition + 6, $endInfo - $startInfoPosition);
          $infoContent = '';
          $infoReady = false;
          $sqlInfos[$file] = $info;
          if ($_CONFIG['updater_print_sql_info']) echo '<tr><td colspan="3"><strong>Update SQL Information:</strong>'.nl2br($info).'</td></tr>';
        }
        // remove multiline comments
        $shortQuery = preg_replace("/(\/\*.*\*\/)/usU", "", $queries[$q]);
        // replace os dependend line breaks to \n
        $shortQuery = str_replace(array("\r\n", "\r"), "\n", $shortQuery);
        // remove empty lines
        $shortQuery = preg_replace("/(^[\r\n|\r|\n]*|[\r\n|\r|\n]+)[\s\t]*[\r\n|\r|\n]+/u", "", $shortQuery);
        //$shortQuery = mb_substr($shortQuery, 0, 110);
        // cut text before first html break
        $cutQueryAtPosition = mb_strpos($shortQuery, "\n", 20);
        if ($cutQueryAtPosition) {
          $shortQuery = mb_substr($shortQuery, 0, $cutQueryAtPosition);
        }
        echo '<tr>
                <td width="2%" align="right" valign="top">'.($q+1).'</td>
                <td width="73%" align="left" valign="top">
                  <a href="javascript:showhide_box(\'query_'.$i.'-'.$q.'\');">'.$shortQuery.'...</a>
                  <div id="query_'.$i.'-'.$q.'" style="display:none;visibility:hidden;" class="query">'.nl2br($queries[$q]).'</div>
                </td>';
        // run sql query
        try {
          $result = $db->query($queries[$q]);
        } catch(Exception $e) { }
        if (!$result) {
          echo '<td width="25%" class="error" align="left" valign="top">'.$db->get_error().'</td></tr></table>';
          // get form (with go button)
          echo getGoOnForm(ob_get_contents(), $oldVersion, $newVersion, $sqlInfos, $i, ++$q);
          // show update log
          ob_flush();
          // stop update script
          return null;
        } else {
          echo '<td width="25%" class="success" align="center" valign="top">[OK]</td></tr>';
        }
      }
      // prevent to jump over one or more queries after error -> reset $currentQuery
      $currentQuery = 0;

      if ($_CONFIG['updater_stop_after_update'] && isset($sqlInfos[$file])) {
        // stop update script after update sql file
        echo '</table>';
        // get form (with go button)
        echo getGoOnForm(ob_get_contents(), $oldVersion, $newVersion, $sqlInfos, ++$i, 0, true);
        // show update log
        ob_flush();
        // stop update script
        return null;
      }

    } else {
        echo "Datei $file.sql ist nicht lesbar.";
        return null;
    }
  }
  echo '</table>';

  // show sql infos
  echo '<a name="button_go" /><table cellspacing="1" cellpadding="2"><tr><th>Version</th><th>Info</th></tr>';
  foreach ($sqlInfos as $key => $value) {
    echo "<tr><td>$key</td><td>".nl2br($value)."</td></tr>";
  }
  if (!$sqlInfos) {
    echo '<tr><td colspan="2" align="left">Keine SQL Informationen verf&uuml;gbar.</td></tr>';
  }
  echo '</table>';

  // save old and new version with timestamp
  if (!updateVersionLog($versions[$oldVersion], $versions[$newVersion])) {
    echo '<h3 class="error error_b">Update Log konnte nicht in die Datenbank geschrieben werden!
          <br />Ist die DB Tabelle '.$tablePrefix.'edwin_update_log vorhanden?
          </h3>';
  } else {
    echo '<h3 class="success success_b">Update Log wurde in die Datenbank geschrieben.</h3>';
  }
  echo '<h3 class="success success_b">Update erfolgreich abgeschlossen. (<a href="index.php">Seite aktualisieren / Neues Update</a>)</h3>';
}

/**
 * Returns form, that contains hidden fields with current query options and "go on" button
 * @param unknown_type $contents
 * @param unknown_type $oldVersion
 * @param unknown_type $newVersion
 * @param unknown_type $sqlInfos
 * @param unknown_type $i
 * @param unknown_type $q
 * @param unknown_type $phpNext
 * @return string
 */
function getGoOnForm($contents, $oldVersion, $newVersion, $sqlInfos, $i, $q, $phpNext=false) {

  // use serialize to save sql infos (array) in hidden input control
  $data = serialize($sqlInfos);
  $encoded = htmlentities($data, ENT_COMPAT, ConfigHelper::get('charset'));

  $form = '<strong>Ausgabe kontrollieren!<strong>
          <form action="index.php#button_go" method="post" accept-charset="UTF-8">
            <input type="hidden" name="contents" value="'.htmlspecialchars($contents, ENT_COMPAT, ConfigHelper::get('charset')).'" />
            <input type="hidden" name="oldVersion" value="'.$oldVersion.'" />
            <input type="hidden" name="newVersion" value="'.$newVersion.'" />
            <input type="hidden" name="sqlInfos" value="'.$encoded.'" />
            <input type="hidden" name="currentVersion" value="'.$i.'" />
            <input type="hidden" name="currentQuery" value="'.$q.'" />
            <input type="hidden" name="phpNext" value="'.$phpNext.'" />
            <input type="submit" name="error" class="button_go" value="Weiter" />
          </form><a name="button_go" />';
  return $form;
}

/**
 * Gets full sql dump (DROPs, CREATEs and INSERTs) of connected database
 * @param string $version current EDWIN version
 * @return string sql dump
 */
function getSQLDump($version) {
  global $_CONFIG, $db;

  // general information of sql dump file
  $final = "--  EDWIN SQL Dump\n";
  $final .= "--\n";
  $final .= "--  EDWIN version: $version \n";
  $final .= "--  PHP version: ".phpversion()."\n";
  $final .= "--  MySQL version: ".mysqli_get_server_info()."\n";
  $final .= "--  Generation time: ".date("d M Y \a\\t H:i:s")."\n\n";

  // get all tables
  $tablesResult = $db->query('SHOW TABLES');
  while ($tablesRow = $db->fetch_row($tablesResult, MYSQLI_NUM))
  {
    $tableResult = $db->query('SELECT * FROM '.$tablesRow[0]);
    $num_fields = $db->num_columns($tableResult);

    $final .= 'DROP TABLE IF EXISTS `'.$tablesRow[0].'`;';
    $tableCreate = $db->fetch_row($db->query('SHOW CREATE TABLE '.$tablesRow[0]), MYSQLI_NUM);
    // add complete table create structure
    $final .= "\n".$tableCreate[1].";\n\n";

    while ($row = $db->fetch_row($tableResult, MYSQLI_NUM))
    {
      // open insert into statement
      $final.= 'INSERT INTO `'.$tablesRow[0].'` VALUES(';
      for ($j = 0; $j < $num_fields; $j++)
      {
        if ($row[$j] === null) {
          $final .= 'NULL';
        } else {
          // add slash to special chars, important to import this sql dump again
          $row[$j] = addslashes($row[$j]);
          // prevent new line in differnet ways (OS dependent)
          $row[$j] = preg_replace('/\r\n|\r|\n/u', '\r\n', $row[$j]);
          // add apostrophes to column field
          $final .= "'$row[$j]'" ;
        }
        if ($j < ($num_fields - 1)) {
          // add comma after column field content
          $final .= ',';
        }
      }
      // close insert into statement
      $final .= ");\n";
    }
    $final .= "\n";
  }

  return $final;
}

/**
 * Generates file name of sql dump file
 * @return string file name with current timestamp
 */
function getFileName() {
  global $_CONFIG;

  return $_CONFIG['dbname'].'-backup-'.date("Ymd-His").'.sql';
}

/**
 * Shows sql dump in browser
 * @param string $content contains sql dump
 * @param string $fileName the filename of exported sql dump file
 */
function export_sql_dump($content, $fileName) {
  header('Content-Type: sql/plain');
  header('Content-Disposition: attachment; filename='.$fileName);
  header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
  header('Pragma: no-cache');

  // print complete sql dump content
  echo $content;
}

/**
 * Saves sql dump file to server backup path
 * @param string $content contains sql dump
 * @param string $fileName the sql dump filename
 * @return boolean true on success, otherwise false
 */
function save_sql_dump($content, $fileName) {

  global $_CONFIG;

  // open new file in backup path to save sql dump
  $handle = fopen($_CONFIG['updater_backup_path'].$fileName, 'w');

  if (@fwrite($handle, $content) === false) {
    echo "Kann in die Datei $fileName nicht schreiben";
    return false;
  }
  fclose($handle);

  return true;
}

/**
 * Get current EDWIN version (latest entry in database)
 * @return string current version or false if there is no current version available
 */
function getCurrentVersion() {
  global $db, $tablePrefix;

  $sql = "SELECT EULNewVersion
          FROM {$tablePrefix}edwin_update_log
          ORDER BY EULDateTime DESC
          LIMIT 0,1";

  return $db->GetOne($sql);
}

/**
 * Reads all available EDWIN update sql files and extract their version number
 * @return array contains string with version numbers (e.g. 1_5_0)
 *         or null if update files could not be found in directory $dir
 */
function getAllVersions() {
  global $_CONFIG;

  $dir = $_CONFIG['updater_update_files_dir'];
  $versions = array();
  if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
      while (($file = readdir($dh)) !== false) {
        $fullPieces = explode('.', $file);
        if (isset($fullPieces[1]) && $fullPieces[1] == 'sql') {
          // just get version number of sql file names
          $namePieces = explode('-', $fullPieces[0]);
          if ($namePieces[0] == 'update') {
            if (empty($versions)) {
              // save old version number of update sql file name if $versions is empty (update-1_5_0-1_6_0.sql => 1_5_0)
              $versions[] = $namePieces[1];
            }
            // save new version number of update sql file name (update-1_5_0-1_6_0.sql => 1_6_0)
            $versions[] = $namePieces[2];
          }
        }
      }
      closedir($dh);
      // sort versions from lowest to highest version number
      sort($versions);
    }
  } else {
    return null;
  }
  return $versions;
}

/**
 * Generates an option list of given version array
 * @param array $versions contains all available versions
 * @param string $version version that should be selected
 * @param int $offset array index of version array; option list starts on $offset
 * @return string - html option list
 */
function getVersionListOptions($versions, $version = null, $offset = 0) {
  $options = '';
  $n = 0;
  foreach ($versions as $value) {
    if ($n >= $offset) {
      $options .= (($version == $value) || (is_int($version) && ($version == $n))) ? "<option value=\"$n\" selected=\"selected\" class=\"selected\">$value</option>" : "<option value=\"$n\">$value</option>";
    }
    $n ++;
  }
  return $options;
}

/**
 * Creates database table PREFIXedwin_update_log (if not already exists)
 * @param object $db database object
 * @param string $tablePrefix prefix of database tables
 */
function createVersionLog($db, $tablePrefix) {
  $sql = "CREATE TABLE IF NOT EXISTS {$tablePrefix}edwin_update_log (
            EULID int(11) NOT NULL AUTO_INCREMENT,
            EULDateTime datetime NOT NULL,
            EULOldVersion varchar(10) NOT NULL,
            EULNewVersion varchar(10) NOT NULL,
            PRIMARY KEY (EULID)
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1";
  return $db->query($sql);
}

/**
 * Saves old and new EDWIN version to database with current timestamp
 * @param string $oldVersion old EDWIN version
 * @param string $newVersion target update version
 * @return true on success otherwise false
 */
function updateVersionLog($oldVersion, $newVersion) {
  global $db, $tablePrefix;

  $sql = "INSERT INTO {$tablePrefix}edwin_update_log
          (EULDateTime, EULOldVersion, EULNewVersion)
          VALUES ('".date('Y-m-d H:i:s')."', '".$db->escape($oldVersion)."', '".$db->escape($newVersion)."')";
  return $db->query($sql);
}