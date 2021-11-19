<?php

/**
 * $LastChangedDate: $
 * $LastChangedBy:  $
 *
 * @package EDWIN Backend
 * @author Koppensteiner Raphael
 * @copyright (c) 2018 Q2E GmbH
 */
class ModuleAdmin extends Module
{
  public static $subClasses = array(
    'check'    => 'ModuleAdminCheck',
    'features' => 'ModuleAdminFeatures',
    'log'      => 'ModuleAdminLog',
  );

  protected $_prefix = 'ad';

  public function show_innercontent()
  {
    return $this->_showContent();
  }

  protected function _getContentLeftLinks()
  {
    return array();
  }

  protected function _showContent()
  {
    global $_LANG2;

    $this->tpl->load_tpl('content', 'modules/ModuleAdmin.tpl');
    $this->tpl->parse_loop('content', $this->getSiteLoopItems(), 'ad_site_loop');
    $this->tpl->parse_vars('content', $_LANG2['ad']);

    return array(
      'content'      => $this->tpl->parsereturn('content'),
      'content_left' => $this->_getContentLeft(true),
    );
  }

  private function getSiteLoopItems()
  {
    $protocol = ConfigHelper::get('protocol');
    $mailSenders = ConfigHelper::get('m_mail_sender_label');
    $domains = array_flip(ConfigHelper::get('site_hosts'));
    $siteNames = $this->_allSites;

    $loopArray = array();
    foreach ($domains as $key => $value) {
      if (!isset($mailSenders[$key])) {
        $mailSenders[$key] = $mailSenders[0];
      }

      $loopArray[] = array(
        'ad_name'     => $siteNames[$key],
        'ad_domain'   => $domains[$key],
        'ad_email'    => htmlspecialchars($mailSenders[$key]),
        'ad_protocol' => $protocol,
      );
    }

    return $loopArray;
  }
}