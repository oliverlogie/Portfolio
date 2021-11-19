<?php

/**
 * $LastChangedDate: $
 * $LastChangedBy:  $
 *
 * @package EDWIN Backend
 * @author Koppensteiner Raphael
 * @copyright (c) 2018 Q2E GmbH
 */
class ModuleAdminLog extends Module
{
  protected $_prefix = 'ad_lg';

  private $_subSubClasses = array(
    'log1' => 'ModuleAdminLog1',
    'log2' => 'ModuleAdminLog2',
  );

  public function show_innercontent()
  {
    global $_LANG, $_LANG2;

    $input = new Input(Input::SOURCE_GET);
    $action3 = explode(';', $input->readString('action3'));
    unset($action3[0]);
    $prevAction = implode(';', $action3);
    $action3 = explode(';', $input->readString('action3'))[0];

    if (!in_array($action3, array_keys($this->_subSubClasses))) {
      $action3 = $this->session->read('ad_lg_current_log') ?: array_keys($this->_subSubClasses)[0];
    }
    $this->session->save('ad_lg_current_log', $action3);
    $class = isset($this->_subSubClasses[$action3]) ?
      $this->_subSubClasses[$action3] :
      array_values($this->_subSubClasses)[0];

    // invalid module subclass
    if (!class_exists($class, true)) {
      $this->redirect_page('modulesubclass_notfound');
    }

    /* @var $module Module */
    $module = new $class($this->_allSites, $this->site_id, $this->tpl, $this->db,
      $this->table_prefix, $prevAction, $this->item_id, $this->_user,
      $this->session, $this->_navigation, $this->originalAction);

    $output = $module->show_innercontent();
    if (!isset($output['content_contenttype'])) {
      $output['content_contenttype'] = get_class($module);
    }

    $subClasses = array();
    foreach ($this->_subSubClasses as $key => $className) {
      $subClasses[] = array(
        'ad_lg_item_title' => $_LANG["modtop_{$className}"],
        'ad_lg_item_url'   => "index.php?action=mod_admin&action2=log&action3={$key}&site=1",
        'ad_lg_item_cls'   => $action3 === $key ? 'active' : 'inactive',
      );
    }

    $this->tpl->load_tpl('navigation', 'modules/ModuleAdminLog.tpl');
    $this->tpl->parse_loop('navigation', $subClasses, 'items');
    $this->tpl->parse_vars('navigation', array_merge(array(
      "ad_lg_nav_name" => $action3,
    ), $_LANG2['ad_lg']));

    $output['content'] = sprintf($this->tpl->parsereturn('navigation') . '%s', $output['content']);

    return $output;
  }

  protected function _getContentLeftLinks()
  {
    return array();
  }
}