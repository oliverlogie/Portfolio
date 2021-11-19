<?php

/**
 * ModuleShopPlusManagementPreferences
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleShopPlusManagementPreferences extends Module
{
  protected $_prefix = 'or';

  public function show_innercontent ()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    if ($post->exists('process')) {

      $array = $post->readArrayIntToString('or_pref', Input::FILTER_PLAIN);
      if ($array) {

        foreach ($array as $key => $val) {
          $sql = " UPDATE {$this->table_prefix}contentitem_cp_preferences "
               . " SET CPPValue = '{$this->db->escape($val)}' "
               . " WHERE FK_SID = $this->site_id "
               . "   AND CPPID = $key ";
          $this->db->query($sql);
        }

        $this->setMessage(Message::createSuccess($_LANG['or_message_success']));

      }
    }

    $hiddenFields = '<input type="hidden" name="action" value="mod_shopplusmgmt" />'
                  . '<input type="hidden" name="action2" value="pref" />'
                  . '<input type="hidden" name="page" value="0" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';

    $items = array();
    $sql = " SELECT CPPID, CPPName, CPPValue "
         . "   FROM {$this->table_prefix}contentitem_cp_preferences "
         . " WHERE FK_SID = $this->site_id ";
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $items[] = array(
        'or_pref_id'    => (int)$row['CPPID'],
        'or_pref_label' => isset($_LANG["or_pref_label"][$row['CPPName']]) ? $_LANG["or_pref_label"][$row['CPPName']] : 'unknown',
        'or_pref_name'  => $row['CPPName'],
        'or_pref_value' => $row['CPPValue'],
      );
    }

    $this->db->free_result($result);

    $this->tpl->load_tpl('content_cr', 'modules/ModuleShopPlusManagementPreferences.tpl');
    $this->tpl->parse_if('content_cr', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('or'));
    $this->tpl->parse_loop('content_cr', $items, 'items');
    $or_content = $this->tpl->parsereturn('content_cr', array_merge(array (
      'or_hidden_fields'    => $hiddenFields,
      'or_action'           => 'index.php',
      'or_site_selection'   => $this->_parseModuleSiteSelection('shopplusmgmt', $_LANG['or_site_label'], 'pref'),
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['or']));

    return array(
        'content' => $or_content,
    );
  }

  protected function _getContentActionBoxButtonsTemplate()
  {
    return 'module_action_boxes_buttons_save_only.tpl';
  }
}