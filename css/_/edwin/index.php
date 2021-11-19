<?php

  /**
   * Edwin Backend Index
   *
   * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  // If the request goes to "/edwin" redirect to "/edwin/"
  if (mb_substr($_SERVER['REQUEST_URI'], -6) == '/edwin') {
    header('Location: ' . $_SERVER['REQUEST_URI'] . '/', true, 301);
    exit();
  }

  include 'includes/bootstrap.php';

  // Parse Request ////////////////////////////////////////////////////////////////////////////
  $request_id = "";
  if (isset($_GET["page"]))
    $request_id = addslashes(strip_tags($_GET["page"]));
  else if (isset($_POST["page"]))
    $request_id = addslashes(strip_tags($_POST["page"]));
  $action = "";
  if (isset($_GET["action"]))
    $action = addslashes(strip_tags($_GET["action"]));
  else if (isset($_POST["action"]))
    $action = addslashes(strip_tags($_POST["action"]));
  $action2 = "";
  if (isset($_GET["action2"]))
    $action2 = addslashes(strip_tags($_GET["action2"]));
  else if (isset($_POST["action2"]))
    $action2 = addslashes(strip_tags($_POST["action2"]));
  /////////////////////////////////////////////////////////////////////////////////////////////

  // write site language into CONFIG File
  // if language files do not exist take german as default language
  $sql = ' SELECT SID, SLanguage '
       . ' FROM ' . ConfigHelper::get('table_prefix') . 'site';
  $result = $db->query($sql);
  $languages = array();
  while ($row = $db->fetch_row($result)) {
      $languages[(int)$row['SID']] = $row['SLanguage'];
  }
  $db->free_result($result);
  ConfigHelper::set('site_languages', $languages);

  // set init to true - lang.core.php will not include any module / contentitem
  // language files
  $init = true;
  // Include default and customized Language File - german
  $langFileLanguage = "german";
  $langFile = ConfigHelper::get('m_use_compressed_lang_file') ? 'compressed' : 'core';
  $langFileDefault = "language/$langFileLanguage-default/lang.$langFile.php";
  $langFileCustomized = "language/$langFileLanguage/lang.$langFile.php";
  include($langFileDefault);
  if (is_file($langFileCustomized)) { include($langFileCustomized); }
  $init = null;

  $tpl = new Template;
  $tpl->show_warnings((ConfigHelper::get('DEBUG_TPL') ? 1 : 0));
  $tpl->load_tpl('main', 'main.tpl');

  $page = new BackendRequest($request_id, $action, $action2, $tpl, $db, ConfigHelper::get('table_prefix'));
  $page->show();

  if ($db->getLogger()) {
    $o = new \Core\Widgets\BottomOfThePageLogOutput(ConfigHelper::get('DEBUG_SQL'), $db->getLogger());

    if (   !ed_is_ajax()
        || (ed_is_ajax() && mb_stristr(ConfigHelper::get('DEBUG_SQL'), 'ajax'))
    ) {
      echo $o->getOutput();
    }
  }

  $tpl->destroy();