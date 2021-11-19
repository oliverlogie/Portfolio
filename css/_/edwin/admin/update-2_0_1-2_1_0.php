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
if ($user->isValid() && $user->AvailableModule('usermgmt', 0)) {
  executeUpdate($db, $tablePrefix);
} else {
  echo '<h1>Sie müssen als Administrator eingeloggt sein, um das Update durchführen zu können.</h1><h2>Bitte loggen Sie sich <a href="../">hier</a> ein.</h2>';
}

// Destroy the template and close the database.
$tpl->destroy();
$db->close();

function executeUpdate(db $db, $tablePrefix)
{
  echo '<h1>Gesamtes Update [start]</h1>';

  createRootPages($db, $tablePrefix);

  updateUserRightsTable($db, $tablePrefix);

  echo '<h2>Gesamtes Update [ende]</h2>';
}

/**
 * Function creates root pages for sites and trees
 */
function createRootPages(db $db, $tablePrefix)
{
    echo '<h2>Login / Pages - Root Seiten hinzuf&uuml;gen [start]</h2><br/>';

    // if any pages in tree 'login' or 'pages' exist do not execute this update
    $sql = "SELECT CTree FROM {$tablePrefix}contentitem WHERE CTree = 'login' OR CTree = 'pages' LIMIT 1";
    $exists = $db->GetOne($sql);

    if ($exists) {
      echo "<p>Es wurde bereits Login / Landing Pages - Seiten angelegt.</p>";
      echo "<p>Root Seiten (falls notwendig) MANUELL anlegen</p>";
      echo '<h2>Login / Pages - Root Seiten hinzuf&uuml;gen [ende]</h2><br/>';
      return;
    }

    $sql = "SELECT DISTINCT (FK_SID) FROM {$tablePrefix}contentitem";

    $siteIDs = $db->GetCol($sql);

    foreach ($siteIDs as $ID)
    {
      echo "<p>Seite: {$ID}</p><br/>";

      $sql="INSERT INTO {$tablePrefix}contentitem "
          ."(CIIdentifier, CTitle, CPosition, CType, FK_CTID, FK_SID, FK_CIID, CTree) "
          ."VALUES(NULL, '(Login)', 0, 0, NULL, {$ID}, NULL, 'login')";
      $result = $db->query($sql);

      if ($result) {
        echo "<p>Login - Root hinzugef&uuml;gt.</p>";
      }
      else {
        echo "<p>Login - Root NICHT hinzugef&uuml;gt.</p>";
      }

      $db->free_result($result);


      $sql="INSERT INTO {$tablePrefix}contentitem "
          ."(CIIdentifier, CTitle, CPosition, CType, FK_CTID, FK_SID, FK_CIID, CTree) "
          ."VALUES(NULL, '(Pages)', 0, 0, NULL, {$ID}, NULL, 'pages')";
      $result = $db->query($sql);

      if ($result) {
        echo "<p>Pages - Root hinzugef&uuml;gt.</p>";
      }
      else {
        echo "<p>Pages - Root NICHT hinzugef&uuml;gt.</p>";
      }

      $db->free_result($result);
    }

    echo '<h2>Login / Pages - Root Seiten hinzuf&uuml;gen [ende]</h2><br/>';
}

/**
 * Function creates root pages for sites and trees - only execute once
 */
function updateUserRightsTable(db $db, $tablePrefix)
{
    echo '<h2>User - Rechte für unterschiedliche Navigationsbäume hinzufügen [start]</h2><br/>';

    // if any pages
    $sql = "SELECT UTree FROM {$tablePrefix}user_rights WHERE UTree = 'pages' OR UTree = 'login' LIMIT 1";
    $exists = $db->GetOne($sql);

    if ($exists) {
      echo "<p>Die Tabelle {$tablePrefix}user_rights scheint bereits verändert zu sein.</p>";
      echo "<p>Möglicherweise muss auch erst das Datenbank-Updateskript ausgeführt werden.</p>";
      echo "<p>Einträge (falls notwendig) MANUELL anlegen</p>";
      echo '<h2>User - Rechte für unterschiedliche Navigationsbäume hinzufügen [ende]</h2><br/>';
      return;
    }

    // our default users (Q2E) need access to all sites and navigation trees
    $userIDs = array(1, 2, 3, 4, 5, 6);

    foreach ($userIDs as $ID)
    {

      echo "<p>Benutzer: {$ID} [start]</p><br/>";

      $sql = "SELECT UID FROM {$tablePrefix}user WHERE UID = $ID";
      $result = $db->GetOne($sql);
      if (!$result) {
        continue;
      }

      $sql = "SELECT DISTINCT (SID) FROM {$tablePrefix}site";
      $siteIDs = $db->GetCol($sql);

      foreach ($siteIDs as $siteId)
      {
        $sql="INSERT INTO {$tablePrefix}user_rights "
            ."(FK_UID, FK_SID, UPaths, UModules, UTree) "
            ."VALUES({$ID}, {$siteId}, NULL, NULL, 'footer')";
        $result = $db->query($sql);
        if ($result) {
          echo "<p>Seite $siteId - Footer Berechtigung hinzugef&uuml;gt.</p>";
        }
        else {
          echo "<p>Seite $siteId - Footer Berechtigung NICHT hinzugef&uuml;gt.</p>";
        }

        $sql="INSERT INTO {$tablePrefix}user_rights "
            ."(FK_UID, FK_SID, UPaths, UModules, UTree) "
            ."VALUES({$ID}, {$siteId}, NULL, NULL, 'login')";
        $result = $db->query($sql);
        if ($result) {
          echo "<p>Seite $siteId - Login Berechtigung hinzugef&uuml;gt.</p>";
        }
        else {
          echo "<p>Seite $siteId - Login Berechtigung NICHT hinzugef&uuml;gt.</p>";
        }

        $sql="INSERT INTO {$tablePrefix}user_rights "
            ."(FK_UID, FK_SID, UPaths, UModules, UTree) "
            ."VALUES({$ID}, {$siteId}, NULL, NULL, 'pages')";
        $result = $db->query($sql);
        if ($result) {
          echo "<p>Seite $siteId - Landing Pages Berechtigung hinzugef&uuml;gt.</p>";
        }
        else {
          echo "<p>Seite $siteId - Landing Pages Berechtigung NICHT hinzugef&uuml;gt.</p>";
        }
      }

      echo "<p>Benutzer: {$ID} [ende]</p><br/>";
    }

    echo '<h2>User - Rechte für unterschiedliche Navigationsbäume hinzufügen [ende]</h2><br/>';
}