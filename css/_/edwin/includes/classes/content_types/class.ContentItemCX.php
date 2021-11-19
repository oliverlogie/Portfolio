<?php

/**
 * $LastChangedDate: 2019-04-19 13:18:09 +0200 (Fr, 19 Apr 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX extends ContentItem
{
  protected $_configPrefix = 'cx';
  protected $_contentPrefix = 'cx';
  protected $_columnPrefix = 'CX';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 3,
  );
  protected $_templateSuffix = 'CX';

  /**
   * @see ContentItemCX::_input()
   * @var Input
   */
  private $_input;

  /**
   * @see ContentItemCX::_areas()
   * @var array
   */
  private $_areas;

  /**
   * {@inheritdoc}
   */
  protected static function hasContentChanged()
  {
    return parent::hasContentChanged()
      || isset($_POST['process_cx_area'])
      || isset($_POST['process_new_element'])
      || isset($_GET['changeActivationID'])
      || isset($_GET['deleteAreaID'])
      || isset($_GET['moveAreaID'])
      || isset($_POST['process_cx_area'])
      || isset($_GET['process_cx_area_element']);
  }

  public function delete_content()
  {
    $this->_subelements->delete_content();
    return parent::delete_content();
  }

  public function edit_content()
  {
    if ($this->_subelements[0]->isProcessed()) {
      $this->_subelements[0]->edit_content();
    }
    else {
      if ($this->_input()->exists('process_new_element')) {
        $this->_processNewElement();
      }
      else {
        parent::edit_content();
      }
    }
  }

  public function get_content($params = array())
  {
    $sub = $this->_getSubelementsContent();
    $maxAreas = $this->_getMaxNumberOfAreas();

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'sub_message', $sub['message'], $sub['message'] ? $sub['message']->getTemplateArray('cx'): array());
    $this->tpl->parse_if($tplName, 'cx_add_subelement', (count($this->_areas()) < $maxAreas), array(
      'cx_add_subelement_type_options' => $this->_getAddSubelementTypeOptionsForTemplate()
    ));
    $this->tpl->parse_if($tplName, 'cx_add_subelement_unavailable', !(count($this->_areas()) < $maxAreas), array());
    $this->tpl->parse_vars($tplName, array(
      'cx_action'                       => "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id",
      'cx_area_dragdrop_link_js'        => "index.php?action=content&site=$this->site_id&page=$this->page_id&moveAreaID=#moveID#&moveAreaTo=#moveTo#",
      'cx_areas'                        => $sub['content'],
      'cx_scroll_to_anchor'             => $this->_input()->readString('cx_scroll_to_anchor'), // FIXME: use alternative scroll to anchor if set from subelements... implement
    ));

    $settings = array(
      'no_preview' => true,
      'tpl' => $tplName,
    );

    return parent::get_content(array_merge($params, array(
      'settings' => $settings,
    )));
  }

  /**
   * {@inheritdoc}
   */
  public function getImageTitles($subcontent = true)
  {
    $titles = parent::getImageTitles();

    if ($subcontent) {
      foreach ($this->_subelements as $sub) {
        $titles = array_merge($titles, $sub->getImageTitles($subcontent));
      }
    }

    return $titles;
  }

  /**
   * {@inheritdoc}
   */
  public function getTexts($subcontent = true)
  {
    $texts = parent::getTexts();

    if ($subcontent) {
      foreach ($this->_subelements as $sub) {
        $texts = array_merge($texts, $sub->getTexts($subcontent));
      }
    }

    return $texts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitles()
  {
    $titles =  parent::getTitles();

    foreach ($this->_subelements as $sub) {
      $titles = array_merge($titles, $sub->getTitles());
    }

    return $titles;
  }

  /**
   * {@inheritdoc}
   */
  protected function _processedValues()
  {
    return array_merge(parent::_processedValues(), array(
      'process_new_element'
    ));
  }

  /**
   * @return Input
   */
  protected function _input()
  {
    if ($this->_input === null) {
      $this->_input = new Input(Input::SOURCE_REQUEST);
    }

    return $this->_input;
  }

  /**
   * Parses the url for this content item
   *
   * @param array $params
   *        URL parameters to add as key value pairs
   *
   * @return string
   */
  protected function _parseUrl($params = array())
  {
    $params = array_merge(array(
      'action' => 'content',
      'site'   => $this->site_id,
      'page'   => $this->page_id,
    ), $params);

    $prepared = array();
    foreach ($params as $key => $val) {
      $prepared[] = sprintf('%s=%s', $key, urlencode($val));
    }

    return edwin_url() . '?' . implode('&', $prepared);
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[] = new ContentItemCX_Areas(
      $this->site_id, $this->page_id, $this->tpl, $this->db,
      $this->table_prefix, '', '', $this->_user, $this->session,
      $this->_navigation, $this);
  }

  /**
   * @return array
   */
  protected function _areas()
  {
    global $_LANG;

    if ($this->_areas === null) {
      $sql = " SELECT CXAID, CXAIdentifier, CXAPosition, CXADisabled, FK_CIID "
           . " FROM {$this->table_prefix}contentitem_cx_area "
           . " WHERE FK_CIID = :FK_CIID ";
      try {
        $result = $this->db->q($sql, array(
          'FK_CIID' => $this->page_id,
        ));
      }
      catch (\Core\Db\Exceptions\QueryException $e) {
        $this->_redirect(
          $this->_parseUrl(),
          Message::createFailure($_LANG['cx_message_internal_error'])
        );
      }

      $this->_areas = $result->fetchAll();
    }

    return $this->_areas;
  }

  /**
   * @return array
   */
  private function _getSubelementsContent()
  {
    return $this->_subelements[0]->get_content();
  }

  /**
   * @return int
   */
  private function _getMaxNumberOfAreas()
  {
    return (int)$this->getConfig('number_of_areas');
  }

  /**
   * @return int
   */
  private function _getNumberOfAreas()
  {
    return count($this->_areas());
  }

  /**
   * @return string
   */
  private function _getAddSubelementTypeOptionsForTemplate()
  {
    global $_LANG;

    $options = '';
    $config = $this->getConfig('areas');

    foreach ($config as $key => $value) {
      // remove disabled area types
      if (   isset($value['settings'])
          && isset($value['settings']['disabled'])
          && $value['settings']['disabled']
      ) {
        unset($config[$key]);
      }
    }

    // all types disabled = invalid configuration
    if (!$config) {
      throw new Exception(
        "Invalid configuration value for \$_CONFIG['cx_areas']. No area types defined or all area types disabled. Please enable at least one area type."
      );
    }

    foreach ($config as $key => $value) {
      $options .= sprintf(
        '<option value="%s">%s</option>',
        $key,
        isset($_LANG[$value['lang']['title']]) ? $_LANG[$value['lang']['title']] : $value['lang']['title']
      );
    }

    return $options;
  }

  private function _processNewElement()
  {
    global $_LANG;

    $existingElements = $this->_getNumberOfAreas();
    $numberOfElements = $this->_getMaxNumberOfAreas();

    if ($existingElements >= $numberOfElements) {
      $this->_redirect(
        $this->_parseUrl(array(
          'cx_scroll_to_anchor' => $this->_input()->readString('cx_scroll_to_anchor')
        )),
        Message::createFailure($_LANG['cx_message_area_create_max_areas_exceeded'])
      );
    }

    if (   !$this->_input()->readString('area')
        || !isset($this->getConfig('areas')[$this->_input()->readString('area')])
    ) {
      $this->_redirect(
        $this->_parseUrl(array(
          'cx_scroll_to_anchor' => $this->_input()->readString('cx_scroll_to_anchor')
        )),
        Message::createFailure($_LANG['cx_message_area_create_invalid_area'])
      );
    }


    $pos = $existingElements + 1;
    $sql = " INSERT INTO {$this->table_prefix}contentitem_cx_area "
         . " ( CXAPosition, FK_CIID, CXAIdentifier ) VALUES "
         . " ( :CXAPosition, :FK_CIID, :CXAIdentifier ) ";
    try {
      $this->db->q($sql, array(
        'CXAPosition'   => $pos,
        'FK_CIID'       => $this->page_id,
        'CXAIdentifier' => $this->_input()->readString('area'),
      ));
    }
    catch (\Core\Db\Exceptions\QueryException $e) {
      $this->_redirect(
        $this->_parseUrl(array(
          'cx_scroll_to_anchor' => $this->_input()->readString('cx_scroll_to_anchor')
        )),
        Message::createFailure($_LANG['cx_message_internal_error'])
      );
    }

    $this->_redirect(
      $this->_parseUrl(array(
        'cx_scroll_to_anchor' => $this->_input()->readString('cx_scroll_to_anchor')
      )),
      Message::createSuccess($_LANG['cx_message_area_create_success'])
    );
  }
}

