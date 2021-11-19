<?php

/**
 * Script for handling different actions, that can be called from outside the
 * EDWIN CMS without having to log in.
 *
 * Actions:
 * - "activate_user" When activating a frontend user, the specified user is
 *                   removed from groups specified within
 *                   $_CONFIG["login_activation_delete_group"] and added to groups
 *                   from $_CONFIG["login_activation_add_group"]. This function
 *                   can only be called once as the activationcode is deleted.
 * - "delete_user" Delete a frontend user. Users with user pages created can not
 *                 be deleted from this script, but have to be removed using
 *                 module FrontendUserManagement. This function can only be called
 *                 once.
 * - "mailjet_push_clients" Pushes new and updated clients of PREFIX_clients to Mailjet.
 * - "cleverreach_push_clients"  Pushes new and updated clients of PREFIX_clients to Cleverreach.
 * - "rapidmail_push_clients"  Pushes new and updated clients of PREFIX_clients to Rapidmail.
 *
 * $LastChangedDate: 2018-10-29 07:25:27 +0100 (Mo, 29 Okt 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

include 'includes/bootstrap.php';

$action = $_GET["action"];
$tablePrefix = ConfigHelper::get('table_prefix');

switch($action) {
  case "activate_user":
    // [start] activate_user
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : false;
    $code = isset($_GET["code"]) ? $_GET["code"] : false;

    if ($id && $code) { // user id and code url parameters okay

      $addGroup = ConfigHelper::get('login_activation_add_group');
      $delGroup = ConfigHelper::get('login_activation_delete_group');;
      if (!$addGroup) exit("Missing configuration value 'login_activation_add_group'!");
      if (!is_array($addGroup)) $addGroup = array($addGroup);
      if (!is_array($delGroup)) $delGroup = array($delGroup);

      $sql = " SELECT FUID, FUNick, FUEmail, FUFirstname, FULastname "
           . " FROM {$tablePrefix}frontend_user "
           . " WHERE FUID = '$id' "
           . "   AND FUActivationCode = '{$db->escape($code)}' "
           . "   AND FUActivationCode != '' ";
      $row = $db->GetRow($sql);

      if ((int)$row['FUID'] == $id) { // user id and code valid
        // reset user activation code ( so this function can not be called again )
        $sql = " UPDATE {$tablePrefix}frontend_user "
             . " SET FUActivationCode = '' "
             . " WHERE FUID = '$id' ";
        $db->query($sql);

        foreach ($delGroup as $groupId) { // delete user from specified groups
          $sql = " DELETE FROM {$tablePrefix}frontend_user_rights "
               . " WHERE FK_FUID = '$id' "
               . "   AND FK_FUGID = '$groupId' ";
          $db->query($sql);
        }

        foreach ($addGroup as $groupId) { // add user to specified groups
          $sql = " INSERT INTO {$tablePrefix}frontend_user_rights "
               . " (FK_FUID, FK_FUGID) VALUES ('$id', '$groupId') ";
          $db->query($sql);
        }

        echo "\nUser {$row['FUNick']} ({$row['FUEmail']}) successfully activated.\n";
        $email = $row['FUEmail'];

        // send info mail to user //////////////////////////////////////////////
        // TODO: select correct language file and mail template as soon as
        // language is available for user
        $init = true;          // lang.core only
        $language = "german";  // user language
        include("language/$language-default/lang.core.php");
        if (is_file("language/$language/lang.core.php")) {
          include("language/$language/lang.core.php");
        }

        $tpl = new Template; // Create Template
        $tpl->show_warnings((ConfigHelper::get('DEBUG_TPL') ? 1 : 0));

        $mailFormat = 1;
        $mailTemplate = "";
        if (is_file($tpl->get_root()."/mail/german/login_user_activation.tpl")) {
          $mailTemplate = "/mail/german/login_user_activation.tpl";
          $mailFormat = 1;
        }
        else if (is_file($tpl->get_root()."/mail/german/login_user_activation-html.tpl")) {
          $mailTemplate = "/mail/german/login_user_activation-html.tpl";
          $mailFormat = 2;
        }

        if ($mailTemplate && $email) {
          $tpl->load_tpl("mail_main", $mailTemplate);
          $mailText = $tpl->parsereturn("mail_main", array(
            "nick"      => $row["FUNick"],
            "firstname" => $row["FUFirstname"],
            "lastname"  => $row["FULastname"],
            "email"     => $row["FUEmail"],
          ));

          $mail = new htmlMimeMail5();
          $mail->setTextCharset(ConfigHelper::get('charset'));
          $mail->setHTMLCharset(ConfigHelper::get('charset'));
          $mail->setHeadCharset(ConfigHelper::get('charset'));
          $mail->setReturnPath(ConfigHelper::get('sender_system_mailbox_address'));

          $mailSenderLabel = ConfigHelper::get('m_mail_sender_label', null, 0);
          $mail->setFrom($mailSenderLabel);
          $mail->setSubject($_LANG["edw_jobs_activate_user_mail_subject"]);
          if ($mailFormat == 1)
            $mail->setText(parseMailOutput($mailText, 0));
          else if ($mailFormat == 2)
            $mail->setHTML($mailText,"./img/mail/");

          $smtp = Core\Helpers\SmtpCredentials::getBySender($mailSenderLabel);
          if ($smtp) {
            $mail->setSMTPParams($smtp['host'], $smtp['port'], $smtp['helo'],
              $smtp['auth'], $smtp['user'], $smtp['pass'], $smtp['secure']);
          }

          $mail->send(array($email), $smtp ? 'smtp' : 'mail');
        }
        ////////////////////////////////////////////////////////////////////////
      }
      else {
        echo "\nUser has already been activated or deleted.\n";
      }
    }
    // [end] activate_user
    break;
  case "delete_user":
    // [start] delete_user
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : false;
    $code = isset($_GET["code"]) ? $_GET["code"] : false;

    if ($id && $code) { // user id and code url parameters okay

      $sql = " SELECT FUID, FUNick, FUEmail "
           . " FROM {$tablePrefix}frontend_user "
           . " WHERE FUID = '$id' "
           . "   AND FUActivationCode = '{$db->escape($code)}' "
           . "   AND FUActivationCode != '' "
           . "   AND FUDeleted != 1 ";
      $row = $db->GetRow($sql);

      if ((int)$row['FUID'] == $id) { // user id and code valid

        // Check if there have been created pages for user, if so, do not delete
        // user and output a warning instead.
        $sql = " SELECT CIID "
             . " FROM {$tablePrefix}contentitem "
             . " WHERE FK_FUID = $id "
             . " AND CType != '".ContentType::TYPE_ROOT."' ";
        if (!$db->GetOne($sql)) { // No pages for user, delete user and root page if existing

          $sql = "UPDATE {$tablePrefix}frontend_user SET FUDeleted = 1 WHERE FUID = $id";
          $db->query($sql);

          echo "\nUser {$row['FUNick']} ({$row['FUEmail']}) successfully deleted.\n";
        }
        else { // existing user pages, do not delete user
          exit("Can not delete frontend users with pages! Pleas log in and try from EDWIN CMS.");
        }
      }
      else {
        echo "\nUser has already been activated or deleted.\n";
      }
    }
    // [end] delete_user
    break;

  /**
   * Pushes new and updated clients of PREFIX_clients to Mailjet.
   */
  case "mailjet_push_clients":
    $mailjetService = new Core\Services\Mailjet\MailjetService($db, $tablePrefix);
    $mailjetService->pushClients();
    break;

  /**
   * Pushes new and updated clients of PREFIX_clients to Cleverreach.
   * Provide optional site GET Parameter to allow the API to set the
   * source information of the pushed clients.
   */
  case "cleverreach_push_clients":
    $siteId = isset($_GET['site']) ? $_GET['site'] : 0;
    $cleverReachService = new CleverReachService($db, $tablePrefix, $siteId);
    $cleverReachService->pushClients();
    break;

  /**
   * Pushes new and updated clients of PREFIX_clients to Rapidmail.
   */
  case "rapidmail_push_clients":
    $service = new Core\Services\Rapidmail\RapidmailService($db, $tablePrefix);
    $service->pushClients();
    break;
}