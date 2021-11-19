<?php
// This call to chdir() is necessary because all includes in the system are
// relative to /edwin/index.php.
chdir('..');
require_once 'config.php';

if (!isset($_CONFIG['update_cb_box'])) $_CONFIG['update_cb_box'] = false;
if (!isset($_CONFIG['update_qs_statement'])) $_CONFIG['update_qs_statement'] = false;
if (!isset($_CONFIG['update_ts_block'])) $_CONFIG['update_ts_block'] = false;
if (!isset($_CONFIG['update_dl_area'])) $_CONFIG['update_dl_area'] = true;

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

  updateEditorTextFields($db, $tablePrefix);

  echo '<h2>Gesamtes Update [ende]</h2>';
}

/**
 * Change all newlines into <br/> tags for text fields where the tinymce editor
 * is used for the following contentitems (only if configured to do so):
 *    - CB_Box
 *    - QS_Statement
 *    - TS_Block
 *    - DL_Area
 *
 * @param Db $db
 *        The database connection object
 * @param string $tablePrefix
 *        The database table prefix
 */
function updateEditorTextFields(db $db, $tablePrefix)
{
  echo "Newlines durch Breaklines ersetzen [start]<br/><br/>";

  updateTexts($db, $tablePrefix, 'CBBID', 'CBBText', 'cb_box');

  updateTexts($db, $tablePrefix, 'QSID', 'QSText', 'qs_statement');

  updateTexts($db, $tablePrefix, 'TBID', 'TBText', 'ts_block');

  updateTexts($db, $tablePrefix, 'DAID', 'DAText', 'dl_area');

  echo "Newlines durch Breaklines ersetzen [ende]<br/><br/>";
}

/**
 * Update a text and add change all newlines into breaklines <br/>.
 *
 * @param Db $db
 *        The database connection object
 * @param string $tablePrefix
 *        The database table prefix
 * @param $keycol
 *        The key column for retrieving and updating data.
 * @param $valcol
 *        The column name of the text field
 * @param $postfix
 *        The database table postfix for generating the table name.
 */
function updateTexts(db $db, $tablePrefix, $keycol, $valcol, $postfix)
{
  global $_CONFIG;

  if (!isset($_CONFIG["update_$postfix"]) || !$_CONFIG["update_$postfix"]) {
    return;
  }

  echo "Tabelle {$tablePrefix}contentitem_$postfix [start]<br/><br/>";

  $sql = "SELECT $keycol, $valcol FROM {$tablePrefix}contentitem_$postfix";
  $result = $db->GetAssoc($sql);

  foreach ($result as $key => $val)
  {
    // breakline inside text.
    if (!$val || stristr($val, '<br>') || stristr($val, '<br/>') || stristr($val, '<br />')) {
      continue;
    }
    // no breaklines
    else
    {
      $sql = " UPDATE {$tablePrefix}contentitem_$postfix "
           . " SET $valcol = '" . $db->escape(nl2br($val)) . "' "
           . " WHERE $keycol = $key ";
      echo "$sql <br/>";
      $db->query($sql);
    }
  }

  echo "<br/>Tabelle {$tablePrefix}contentitem_$postfix [ende]<br/><br/>";
}