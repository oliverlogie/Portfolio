<?php

/**
 * $LastChangedDate: 2020-02-14 13:40:11 +0100 (Fr, 14 Feb 2020) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2020 Q2E GmbH
 */
class ModuleAdminFeatures extends Module
{
  protected $_prefix = 'ad_ft';

  protected $_shortname = 'features';

  private $_subSubClasses = array(
    'content_types'    => 'ModuleAdminFeaturesContentTypes',
    'modules_backend'  => 'ModuleAdminFeaturesModulesTypeBackend',
    'modules_frontend' => 'ModuleAdminFeaturesModulesTypeFrontend',
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
      $action3 = $this->session->read('ad_ft_current_features') ?: array_keys($this->_subSubClasses)[0];
    }
    $this->session->save('ad_ft_current_features', $action3);
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

    if ($this->getMessage()) {
      $module->setMessage($this->getMessage());
    }

    $output = $module->show_innercontent();
    if (!isset($output['content_contenttype'])) {
      $output['content_contenttype'] = get_class($module);
    }

    $subClasses = array();
    foreach ($this->_subSubClasses as $key => $className) {
      $subClasses[] = array(
        'ad_ft_item_title' => $_LANG["modtop_{$className}"],
        'ad_ft_item_url'   => "index.php?action=mod_admin&action2={$this->_shortname}&action3={$key}&site=1",
        'ad_ft_item_cls'   => $action3 === $key ? 'active' : 'inactive',
      );
    }

    $this->tpl->load_tpl('navigation', 'modules/ModuleAdminFeatures.tpl');
    $this->tpl->parse_loop('navigation', $subClasses, 'items');
    $this->tpl->parse_vars('navigation', array_merge(array(
      "ad_ft_nav_name" => $action3,
    ), $_LANG2['ad_ft']));

    $output['content'] = sprintf($this->tpl->parsereturn('navigation') . '%s', $output['content']);

    return $output;
  }

  protected function _getContentLeftLinks()
  {
    return array();
  }
}