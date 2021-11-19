<?php

/**
 * class.ModuleEmployeeDepartment.php
 *
 * $LastChangedDate: 2017-10-16 07:32:10 +0200 (Mo, 16 Okt 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

class ModuleEmployeeDepartment extends AbstractModuleAttribute
{
  protected $_prefix = 'ep';

  public function show_innercontent($subModuleShortname = '')
  {
    $attrGlobal = new AttributeGlobal($this->db, $this->table_prefix);
    $ident = $attrGlobal->readAttributeGlobalByIdentifier($this->_prefix.'_department');
    $this->_typeId = $ident->id;

    return parent::show_innercontent('department');
  }

  /**
   * Shows form to create or edit form.
   * @see Module::show_innercontent
   */
  protected function _showForm()
  {
    global $_LANG, $_LANG2;

    $mod = "mod_{$this->getShortname()}";
    $post = new Input(Input::SOURCE_POST);
    $fieldsToHide = array('id', 'position', 'code', 'parentId', 'attributeLinkGroup', 'image', 'text',);

    if (!$this->_typeId) {
      header('Location: index.php?action='.$mod);
      exit;
    }

    // Edit data -> load data
    if ($this->item_id) {
      // Read model data, if form has not been processed yet.
      if (!$post->exists('process')) {
        $this->_model = $this->_model->readAttributeById($this->item_id);
      }
      $function = 'edit';
    }
    // New data
    else {
      $function = 'new';
    }

    $fields = $this->_generateFormFieldLoopArray($fieldsToHide);
    $hiddenFields = '<input type="hidden" name="action" value="mod_'.$this->getShortname().'" />'
                  . '<input type="hidden" name="action2" value="'.$this->_subModuleShortname.';'.$function.'" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="type" value="' . $this->_typeId . '" />';
    $tplName = 'content_'.$this->_prefix;
    $this->tpl->load_tpl($tplName, "modules/ModuleEmployeeDepartment.tpl");
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('at'));
    $this->tpl->parse_loop($tplName, $fields, 'fields');
    $this->_parseModuleFormFieldMsg($tplName);
    $content = $this->tpl->parsereturn($tplName, array(array(
      'at_action'           => 'index.php',
      'at_function_label'   => $this->_langVar('function_'.$function.'_label'),
      'at_function_label2'  => $this->_langVar('function_'.$function.'_label2'),
      'at_hidden_fields'    => $hiddenFields,
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['at']));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(true),
    );
  }
}