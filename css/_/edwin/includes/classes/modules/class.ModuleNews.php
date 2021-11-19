<?php

/**
 * class.ModuleNews.php
 *
 * $LastChangedDate: 2017-10-16 07:32:10 +0200 (Mo, 16 Okt 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

class ModuleNews extends Module
{
  /**
   * The database column prefix.
   *
   * @var string
   */
  protected $_dbColumnPrefix = 'NW';

  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'nw';

  /**
   * Module's model.
   *
   * @var News
   */
  protected $_model = null;

  /**
   * The category id.
   *
   * @var int
   */
  private $_catId = 0;

  /**
   * Shows module's content.
   *
   * @see Module::show_innercontent()
   */
  public function show_innercontent()
  {
    $get = new Input(Input::SOURCE_GET);
    $post = new Input(Input::SOURCE_POST);

    $this->_catId = $get->readInt('cat_id');
    if (!$this->_catId) {
      $this->_catId = $post->readInt('nw_category_id');
    }
    // Create model instance
    $this->_model = new News($this->db, $this->table_prefix, $this->_prefix);
    $fields = $post->readMultipleArrayStringToString($this->_prefix.'_field');
    $textFields = $post->readMultipleArrayStringToString($this->_prefix.'_field', Input::FILTER_CONTENT_TEXT);
    $fields['siteId'] = $this->site_id;
    $fields['parentId'] = $this->_catId;
    $fields['uId'] = $this->_user->getID();
    $this->_model->setFields($fields);
    $this->_model->text = (isset($textFields[$this->_prefix.'_text'])) ? $textFields[$this->_prefix.'_text'] : null;

    // Perform create/update/move/delete of a side box if necessary
    $this->_create();
    $this->_update();
    $this->_delete();

    if (!empty($this->action[0])) {
      return $this->_showForm();
    }
    else {
      return $this->_showList();
    }
  }

  protected function _getModuleUrlParts()
  {
    return array_merge(parent::_getModuleUrlParts(), array(
        'cat_id' => $this->_catId,
    ));
  }

  /**
   * Creates news item.
   *
   * @return boolean
   *         True on success.
   */
  private function _create()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new' || !$this->_catId) {
      return false;
    }
    // Validate form fields
    if ($this->_model->validate() === false) {
      $this->setMessage($this->_model->getValidationMsg());
      return false;
    }
    $this->_model->createDateTime = date('Y-m-d H:i:s');
    // Save model fields
    $this->_model->create();

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($_LANG['nw_message_create_success']));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('cat_id' => $this->_catId, 'page' => $this->_model->id)),
          Message::createSuccess($_LANG['nw_message_create_success']));
    }
  }

  /**
   * Delete an item, if $_GET parameter 'deleteID' is set
   */
  private function _delete()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $id = $get->readInt('deleteID');
    if (!$id) {
      return false;
    }
    $this->_model->id = $id;
    $this->_model->delete();

    $this->setMessage(Message::createSuccess($_LANG['nw_message_delete_success']));
  }

  /**
   * Updates news item.
   *
   * @return boolean
   *         True on success.
   */
  private function _update()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'edit' || !$this->_catId) {
      return false;
    }
    // Validate form fields
    if ($this->_model->validate() === false) {
      $this->setMessage($this->_model->getValidationMsg());
      return false;
    }
    $this->_oldModel = $this->_model->readNewsById($this->item_id);
    $this->_model->id = $this->item_id;
    $this->_model->createDateTime = $this->_oldModel->createDateTime;
    $this->_model->changeDateTime = date('Y-m-d H:i:s');
    // Save model fields
    $this->_model->update();

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($_LANG['nw_message_update_success']));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('cat_id' => $this->_catId, 'page' => $this->_model->id)),
          Message::createSuccess($_LANG['nw_message_update_success']));
    }
  }

  /**
   * Generates HTML options of news category models.
   *
   * @param ModelList $categories
   *        A list with NewsCategory models.
   * @return string
   *         HTML options.
   */
  private function _generateCategoryOptions($categories)
  {
    $options = '';
    foreach ($categories as $category)
    {
      /* @var $category NewsCategory */
      if (!$this->_catId) {
        $this->_catId = $category->id;
      }
      $selected = '';
      if ($category->id == $this->_catId) {
        $selected = 'selected="selected"';
      }
      $options .= '<option value="'.$category->id.'" '.$selected.'>'.parseOutput($category->title).'</option>';
    }

    return $options;
  }

  /**
   * Gets all list items ready to parse into a
   * loop template.
   *
   * @return array
   */
  private function _getListLoopArray()
  {
    global $_LANG;

    $condition = array('select' => array('id', 'title', 'createDateTime'),
                       'where'  => "FK_SID = $this->site_id AND FK_NWCID = $this->_catId ",
                       'order'  => "NWCreateDateTime DESC");
    $models = $this->_model->readNews($condition);
    $items = array();
    foreach ($models as $model) {
      /* @var $model News */
      $items[] = array(
        'nw_title'           => parseOutput($model->title),
        'nw_date'            => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), $this->_prefix), ContentBase::strToTime($model->createDateTime)),
        'nw_end_date_time'   => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), $this->_prefix), ContentBase::strToTime($model->endDateTime)),
        'nw_start_date_time' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), $this->_prefix), ContentBase::strToTime($model->startDateTime)),
        'nw_id'              => $model->id,
        'nw_edit_link'       => "index.php?action=mod_news&amp;action2=main;edit&amp;site=$this->site_id&amp;cat_id=$this->_catId&amp;page={$model->id}",
        'nw_delete_link'     => "index.php?action=mod_news&amp;cat_id=$this->_catId&amp;deleteID={$model->id}",
      );
    }

    if (!$items) {
      $this->setMessage(Message::createFailure($_LANG['nw_message_no_news']));
    }

    return $items;
  }

  /**
   * Shows the form to edit or create news.
   *
   * @return array
   */
  private function _showForm()
  {
    global $_LANG, $_LANG2;

    if (!$this->_catId) {
      header('Location: index.php?action=mod_news');
      exit;
    }
    $post = new Input(Input::SOURCE_POST);

    // Edit data -> load data
    if ($this->item_id) {
      // Read model data, if form has not been processed yet.
      if (!$post->exists('process')) {
        $this->_model = $this->_model->readNewsById($this->item_id);
      }
      $function = 'edit';
    }
    // New data
    else {
      $function = 'new';
    }

    $action = "index.php";
    $hiddenFields = '<input type="hidden" name="action" value="mod_news" />'
                  . '<input type="hidden" name="action2" value="main;'.$function.'" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="nw_category_id" value="' . $this->_catId . '" />';

    $ignore = array('id', 'createDateTime', 'changeDateTime', 'siteId', 'uId', 'parentId');
    $fields = $this->_generateFormFieldLoopArray($ignore);
    $this->tpl->load_tpl('content_nw', 'modules/ModuleNews.tpl');
    $this->tpl->parse_if('content_nw', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('nw'));
    $this->tpl->parse_loop('content_nw', $fields, 'fields');
    $this->_parseModuleFormFieldMsg('content_nw');

    $tc_content = $this->tpl->parsereturn('content_nw', array(array(
      'nw_hidden_fields'    => $hiddenFields,
      'nw_function_label'   => $_LANG['nw_function_'.$function.'_label'],
      'nw_function_label2'  => $_LANG['nw_function_'.$function.'_label2'],
      'nw_action'           => $action,
      'module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['nw']));

    return array(
      'content'      => $tc_content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * Shows a list of available news items.
   *
   * @return array
   */
  private function _showList()
  {
    global $_LANG, $_LANG2;

    // May get session message and set it. Session message variable is usually set
    // after an edit operation
    if ($this->session->read('nw_message')) {
      $this->setMessage($this->session->read('nw_message'));
      $this->session->reset('nw_message');
    }
    $category = new NewsCategory($this->db, $this->table_prefix, $this->_prefix);
    $condition = array('select' => array('id', 'title'),
                       'where'  => "FK_SID = $this->site_id",
                       'order'  => "NWCPosition ASC");
    $categories = $category->readNewsCategories($condition);
    $catAvailable = true;
    if (!count($categories)) {
      $catAvailable = false;
      $this->setMessage(Message::createFailure($_LANG['nw_message_no_categories']));
    }
    $catOptions = $this->_generateCategoryOptions($categories);
    // Read news
    $items = $this->_getListLoopArray();
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />';
    // Parse the list template.
    $this->tpl->load_tpl('news', 'modules/ModuleNews_list.tpl');
    $this->tpl->parse_if('news', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('nw'));
    $this->tpl->parse_if('news', 'categories_available', $catAvailable);
    $this->tpl->parse_if('news', 'items_available', $items);
    $this->tpl->parse_loop('news', $items, 'items');
    $content = $this->tpl->parsereturn('news', array_merge(array(
      'nw_action'           => 'index.php?action=mod_news',
      'nw_category_id'      => $this->_catId,
      'nw_category_options' => $catOptions,
      'nw_create_date_time_label' => $_LANG['nw_create_date_time_label'],
      'nw_hidden_fields'    => $hiddenFields,
      'nw_list_label'       => $_LANG['nw_function_list_label'],
      'nw_list_label2'      => $_LANG['nw_function_list_label2'],
      'nw_news_of_category' => sprintf($_LANG['nw_news_of_category'], $category->readNewsCategoryById($this->_catId)->title),
      'nw_site_selection'   => $this->_parseModuleSiteSelection('news', $_LANG['nw_site_label']),
      'nw_title_label'      => $_LANG['nw_title_label'],
    ), $_LANG2['nw']));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(),
    );
  }
}