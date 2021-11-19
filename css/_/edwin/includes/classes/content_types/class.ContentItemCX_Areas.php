<?php

/**
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
class ContentItemCX_Areas extends ContentItem
{
  protected $_configPrefix = 'cx'; // "_area" is added in $this->__construct()
  protected $_contentPrefix = 'cx_area';
  protected $_columnPrefix = 'CXA';
  protected $_contentElements = array();
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'CX';  // "_Area" is added in $this->__construct()

  /**
   * @see ContentItemCX::_input()
   * @var Input
   */
  private $_input;

  /**
   * NOTE: do not access directly
   * @see ContentItemCX_Areas::_getAreaRows()
   * @var array
   */
  private $_areaRows;

  /**
   * NOTE: do not access directly
   * @see ContentItemCX_Areas::_getAreaElementRows()
   * @var array
   */
  private $_areaElementRows;

  /**
   * NOTE: do not access directly
   * @see ContentItemCX_Areas::_getAreaElements($id)
   * @var array
   */
  private $_areaElements = array();

  /**
   * @var ContentItemCX_Areas_ElementFactory
   */
  private $_contentElementsFactory;

  /**
   * The parent contentitem ( ContentItemCX )
   *
   * @var ContentItemCX
   */
  private $_parent = null;

  public function __construct($site_id, $page_id, Template $tpl, db $db, $table_prefix,
                              $action = '', $page_path = '', User $user = null,
                              Session $session = null, Navigation $navigation,
                              ContentItemCX $parent)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
      $page_path, $user, $session, $navigation);

    $this->_parent = $parent;
    $this->_configPrefix .= '_area';
    $this->_templateSuffix .= '_Area';

    $this->_contentElementsFactory = new ContentItemCX_Areas_ElementFactory(
      $this->site_id,
      $this->page_id,
      $this->tpl,
      $this->db,
      $this->table_prefix,
      $this->action,
      $this->page_path,
      $this->_user,
      $this->session,
      $this->_navigation,
      $this->_parent
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delete_content()
  {
    foreach ($this->_getAreaElementRows() as $row) {
      /** @var \ContentItemCX_Areas_Element $element */
      if ($this->_getAreaElementById($row['CXAEID'])) {
        $this->_getAreaElementById($row['CXAEID'])->deleteElementContent();
      }
    }

    $sql = " DELETE FROM {$this->table_prefix}contentitem_cx_area "
         . " WHERE FK_CIID = '{$this->db->escape($this->page_id)}' ";
    $this->db->query($sql);
  }

  /**
   * {@inheritdoc}
   */
  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $areaIdMapping = array();

    foreach ($this->_getAreaRows() as $row) {
      $areaIdMapping[$row['CXAID']] = parent::duplicateContent($pageId,
        $newParentId, 'FK_CIID', $row['CXAID'], "{$this->_columnPrefix}ID");
    }

    foreach ($areaIdMapping as $oldId => $newId) {
      /** @var \ContentItemCX_Areas_Element $element */
      foreach ($this->_getAreaElements($oldId) as $element) {
        $element->duplicateElementContent($pageId, $newId, $newId);
      }
    }
  }

  public function get_content($params = array())
  {
    global $_LANG;

    try {
      $this->_checkDatabase();
    }
    catch (\Core\Db\Exceptions\QueryException $e) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['cx_message_internal_error'], (string)$e)));
      return array(
        'message' => $this->getMessage(),
        'content' => '',
      );
    }

    $positionHelper = new PositionHelper(
      $this->db,
      "{$this->table_prefix}contentitem_cx_area",
      'CXAID',
      'CXAPosition',
      'FK_CIID',
      $this->page_id
    );

    try {
      $areas = $this->_getAreaRows();
    }
    catch (\Core\Db\Exceptions\QueryException $e) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['cx_message_internal_error'], (string)$e)));
      return array(
        'message' => $this->getMessage(),
        'content' => '',
      );
    }

    $items = array();
    $activePosition = 0;
    foreach ($areas as $row) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['CXAPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['CXAPosition']);
      $title = isset($_LANG[$this->_getAreaTypeConfig($row['CXAIdentifier'])['lang']['title']]) ?
        $_LANG[$this->_getAreaTypeConfig($row['CXAIdentifier'])['lang']['title']] :
        $this->_getAreaTypeConfig($row['CXAIdentifier'])['lang']['title'];

      if ($this->_input()->readInt('cx_area') == $row['CXAID']) {
        $activePosition = $row['CXAPosition'];
      }

      try {
        $content = $this->_getAreaElementsContent($row['CXAID']);
      }
      catch (Exception $e) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['cx_message_internal_error'], (string)$e)));
        return array(
          'message' => $this->getMessage(),
          'content' => '',
        );
      }

      $items[$row['CXAID']] = array_merge(
        $this->_getActivationData($row, array(
          'urlParams' => 'cx_scroll_to_anchor=a_areas',
        )),
        array(
          'cx_area_content'           => $content,
          'cx_area_title'             => $title,
          'cx_area_content_title'     => $this->_getAreaElementsContentTitle($row['CXAID']),
          'cx_area_id'                => $row['CXAID'],
          'cx_area_position'          => $row['CXAPosition'],
          'cx_area_move_up_link'      => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveAreaID={$row['CXAID']}&amp;moveAreaTo=$moveUpPosition&amp;cx_scroll_to_anchor=a_areas",
          'cx_area_move_down_link'    => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveAreaID={$row['CXAID']}&amp;moveAreaTo=$moveDownPosition&amp;cx_scroll_to_anchor=a_areas",
          'cx_area_delete_link'       => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteAreaID={$row['CXAID']}&amp;cx_scroll_to_anchor=a_areas",
          'cx_area_button_save_label' => sprintf($_LANG['cx_area_button_save_label'], $row['CXAPosition']),
          'cx_area_action'            => $this->_parseUrl(),
          'cx_area_is_active'         => $activePosition == $row['CXAPosition'] ? 1 : 0,
        )
      );
    }

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_loop($tplName, $items, 'items');
    foreach ($items as $item) {
      $this->_parseTemplateCommonParts($tplName, $item['cx_area_id']);
      $this->tpl->parse_if(
        $tplName,
        'message_' . $item['cx_area_position'],
        $item['cx_area_is_active'] && $this->getMessage(),
        $this->_getMessageTemplateArray('cx_area')
      );
    }
    $this->tpl->parse_vars($tplName, array(
      'cx_area_active_position' => $activePosition,
      'cx_area_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&cx_scroll_to_anchor=a_areas&moveAreaID=#moveID#&moveAreaTo=#moveTo#",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $this->tpl->parsereturn($tplName),
    );
  }

  public function edit_content()
  {
    $this->_changeActivation();
    $this->_editContentMoveArea();
    $this->_editContentDeleteArea();
    $this->_updateArea();
    $this->_updateElement();
  }

  /**
   * {@inheritdoc}
   */
  public function getImageTitles($subcontent = true)
  {
    $titles = array();

    if ($subcontent) {
      foreach ($this->_getAreaRows() as $row) {
        /** @var \ContentItemCX_Areas_Element $element */
        foreach ($this->_getAreaElements($row['CXAID']) as $element) {
          $titles = array_merge($titles, $element->getElementImageTitles($subcontent));
        }
      }
    }

    return $titles;
  }

  /**
  * {@inheritdoc}
  */
  public function getTexts($subcontent = true)
  {
    $texts = array();

    if ($subcontent) {
      foreach ($this->_getAreaRows() as $row) {
        /** @var \ContentItemCX_Areas_Element $element */
        foreach ($this->_getAreaElements($row['CXAID']) as $element) {
          $texts = array_merge($texts, $element->getElementTexts($subcontent));
        }
      }
    }

    return $texts;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitles()
  {
    $titles = array();

    foreach ($this->_getAreaRows() as $row) {
      /** @var \ContentItemCX_Areas_Element $element */
      foreach ($this->_getAreaElements($row['CXAID']) as $element) {
        $titles = array_merge($titles, $element->getElementTitles());
      }
    }

    return $titles;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _checkDatabase()
  {
    if ($this->_getAreaElementRows() === false) {
      throw new Exception("Error while reading {$this->table_prefix}contentitem_cx_area_element. Please check database.");
    }

    $sql = " INSERT INTO {$this->table_prefix}contentitem_cx_area_element "
         . " (CXAEIdentifier, CXAEType, CXAEElementableType, CXAEElementableID, FK_CXAID, FK_CIID) VALUES ";
    $inserts = array();

    foreach ($this->_getAreaRows() as $areaRow) {
      $config = $this->_getAreaTypeConfig($areaRow['CXAIdentifier'])['elements'];

      foreach ($config as $identifier => $c) {
        $available = false;

        $elementIdentifier = $areaRow['CXAIdentifier'] . '.' . $identifier;

        foreach ($this->_getAreaElementRows() as $areaElementRow) {
          if (   $areaElementRow['FK_CXAID'] == $areaRow['CXAID']
              && $areaElementRow['CXAEIdentifier'] == $elementIdentifier
          ) {
            $available = true;
          }
        }

        if (!$available) {
          $inserts[] = " ( "
                     . " '{$this->db->escape($elementIdentifier)}', "
                     . " '{$this->db->escape($c['type'])}', "
                     . " 'contentitem_cx_area', "
                     . " '{$this->db->escape($areaRow['CXAID'])}', "
                     . " '{$this->db->escape($areaRow['CXAID'])}', "
                     . " '{$this->db->escape($this->page_id)}' "
                     . " ) ";
        }
      }
    }

    if ($inserts) {
      $this->db->q($sql . implode(',', $inserts));
      $this->_reloadCaches();
    }
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
    $params = array_merge($params, array(
      'action' => 'content',
      'site'   => $this->site_id,
      'page'   => $this->page_id,
    ));

    $prepared = array();
    foreach ($params as $key => $val) {
      $prepared[] = sprintf('&%s=%s', $key, urlencode($val));
    }

    return edwin_url() . '?' . implode('&', $prepared);
  }

  protected function _processedValues()
  {
    return array('changeActivationID',
                 'deleteAreaID',
                 'moveAreaID',
                 'process_cx_area',
                 'process_cx_area_element');
  }

  /**
   * Returns the area database result rows array.
   *
   * @return array
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _getAreaRows()
  {
    if ($this->_areaRows === null) {
      $this->_areaRows = array();

      $sql = " SELECT CXAID, CXAIdentifier, CXAPosition, CXADisabled, FK_CIID "
           . " FROM {$this->table_prefix}contentitem_cx_area "
           . " WHERE FK_CIID = :FK_CIID "
           . " ORDER BY CXAPosition ASC ";
      try {
        $result = $this->db->q($sql, array(
          'FK_CIID' => $this->page_id,
        ));

        $this->_areaRows = $result->fetchAll();
      }
      catch (\Core\Db\Exceptions\QueryException $e) {
        throw $e;
      }
    }

    return $this->_areaRows;
  }

  /**
   * Returns the area database result rows array.
   *
   * @return array
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _getAreaElementRows()
  {
    if ($this->_areaElementRows === null) {
      $this->_areaElementRows = array();

      $sql = " SELECT CXAEID, CXAEIdentifier, CXAEType, CXAEDisabled, CXAEContent, "
           . "        CXAEElementableID, CXAEElementableType, CXAEPosition, "
           . "        FK_CXAID, FK_CIID "
           . " FROM {$this->table_prefix}contentitem_cx_area_element "
           . " WHERE FK_CIID = :FK_CIID ";
      try {
        $result = $this->db->q($sql, array(
          'FK_CIID' => $this->page_id,
        ));

        $this->_areaElementRows = $result->fetchAll();
      }
      catch (\Core\Db\Exceptions\QueryException $e) {
        throw $e;
      }
    }

    return $this->_areaElementRows;
  }

  /**
   * @param int $id
   *
   * @return array|null
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _getAreaElementRowById($id)
  {
    $result = null;
    $rows = $this->_getAreaElementRows();

    foreach ($rows as $row) {
      if ($row['CXAEID'] == $id) {
        $result = $row;
        break;
      }
    }

    return $result;
  }

  /**
   * Returns the element object for the given element id
   *
   * @param int $id
   *
   * @return ContentItemCX_Areas_Element|null
   *
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _getAreaElementById($id)
  {
    $element = null;
    $row = $this->_getAreaElementRowById($id);

    if ($row) {
      $elements = $this->_getAreaElements($row['FK_CXAID']);

      if (isset($elements[$id])) {
        $element = $elements[$id];
      }
      else {
        $element = $this->_contentElementsFactory->make(
          $row['CXAEIdentifier'],
          $this->_getAreaTypeConfig($row['CXAEIdentifier']),
          $row
        );
      }
    }

    return $element;
  }

  /**
   * @param int $areaId
   *
   * @return array
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _getAreaElements($areaId)
  {
    if (isset($this->_areaElements[$areaId])) {
      return $this->_areaElements[$areaId];
    }

    $elements = array();
    $areaRow = null;

    foreach ($this->_getAreaRows() as $row) {
      if ($row['CXAID'] == $areaId) {
        $areaRow = $row;
      }
    }

    if (!$areaRow) {
      return $elements;
    }

    $config = $this->_getAreaTypeConfig($areaRow['CXAIdentifier'])['elements'];

    foreach ($config as $identifier => $c) {
      $elementIdentifier = $areaRow['CXAIdentifier'] . '.' . $identifier;

      foreach ($this->_getAreaElementRows() as $areaElementRow) {
        if (   $areaElementRow['FK_CXAID'] == $areaRow['CXAID']
            && $areaElementRow['CXAEIdentifier'] == $elementIdentifier
        ) {
          $elements[$areaElementRow['CXAEID']] =
            $this->_contentElementsFactory->make($elementIdentifier, $c, $areaElementRow);
        }
      }
    }

    return $this->_areaElements[$areaId] = $elements;
  }

  /**
   * @param int $areaId
   *
   * @return string
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _getAreaElementsContent($areaId)
  {
    $content = '';
    $elements = $this->_getAreaElements($areaId);

    if (!$elements) {
      return $content;
    }

    $content = array();
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($elements as $el) {
      $content[] = $el->getElementContent();
    }

    return implode('', $content);
  }

  /**
   * @param int $areaId
   *
   * @return string
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _getAreaElementsContentTitle($areaId)
  {
    $title = '';
    $elements = $this->_getAreaElements($areaId);

    if (!$elements) {
      return $title;
    }

    foreach ($this->_getAreaRows() as $row) {
      if ($row['CXAID'] == $areaId) {
        $areaRow = $row;
      }
    }

    if (!$areaRow) {
      return $title;
    }

    $settings = $this->_getAreaTypeConfig($areaRow['CXAIdentifier'])['settings'] ?? array();

    // if a special field is configured, use this field only
    // otherwise use the first title field with content
    if (isset($settings['area_title_element'])) {
      /** @var ContentItemCX_Areas_Element $el */
      foreach ($elements as $el) {
        if ($el->getIdentifier() === $settings['area_title_element']) {
          $title = $el->getElementTitles()[0] ?? '';
          break;
        }
      }
    }
    else {
      /** @var ContentItemCX_Areas_Element $el */
      foreach ($elements as $el) {
        if ($el->getElementType() == 'title' && $el->getElementTitles()[0]) {
          $title = $el->getElementTitles()[0] ?? '';
          break;
        }
      }
    }

    if ($title) {
      $maxLength = isset($settings['area_title_max_length']) ?
        (int)$settings['area_title_max_length'] : ConfigHelper::get('cx_area_title_max_length');
      $afterText = isset($settings['area_title_after_text']) ?
        $settings['area_title_after_text'] : ConfigHelper::get('cx_area_title_after_text');

      $title = sprintf('| <span title="%s" data-toggle="tooltip">%s</span> ',
        parseOutput($title, 2),
        parseOutput(StringHelper::setText($title)->truncate($maxLength, $afterText)->getText(), 2)
      );
    }

    return $title;
  }

  /**
   * @param string $name
   *        the config value to look up from the $_CONFIG['cx_areas'] variable.
   *        Use dot notation for retrieving nested values,
   *        i.e. area_2.box_1.title_1
   *
   * @return mixed|null
   *         returns the configuration value or null if not found
   */
  private function _getAreaTypeConfig($name)
  {
    $name = explode('.', $name);

    $result = ConfigHelper::get('cx_areas');
    foreach ($name as $key => $value) {
      if (isset($result[$value])) {
        $result = $result[$value];
      }
      else if (isset($result['elements']) && isset($result['elements'][$value])) {
        $result = $result['elements'][$value];
      }
      else {
        $result = null;
      }

      if (!$result) {
        break;
      }
    }

    return $result;
  }

  private function _updateArea()
  {
    global $_LANG;

    $areaId = $this->_input()->readKey('process_cx_area');
    if (!$areaId) {
      return;
    }

    $error = false;
    $elements = $this->_getAreaElements($areaId);
    /** @var ContentItemCX_Areas_Element $el */
    foreach ($elements as $el) {
      $el->updateElementContent();
      if ($el->hasElementError()) {
        $error = true;
      }
    }

    $this->_reloadCaches();

    if ($error) {
      $this->setMessage(Message::createFailure($_LANG['cx_message_area_update_failure']));
    }
    else {
      $this->setMessage(Message::createSuccess($_LANG['cx_message_area_update_success']));
    }
  }

  private function _updateElement()
  {
    global $_LANG;

    if ($this->_input()->readInt('cx_element')) {
      $element = $this->_getAreaElementById($this->_input()->readInt('cx_element'));
      if ($element) {
        $element->processElementAction($this->_input()->readString('cx_element_action'));
        return;
      }
    }

    $this->setMessage(Message::createFailure(
      $_LANG['cx_message_area_process_cx_area_element_action_failure']));
  }


  private function _editContentMoveArea()
  {
    global $_LANG;

    if (   $this->_input()->readInt('moveAreaID')
        && $this->_input()->readInt('moveAreaTo')
    ) {
      $moveID = $this->_input()->readInt('moveAreaID');
      $moveTo = $this->_input()->readInt('moveAreaTo');

      $positionHelper = new PositionHelper(
        $this->db,
        "{$this->table_prefix}contentitem_cx_area",
        'CXAID',
        'CXAPosition',
        'FK_CIID',
        $this->page_id
      );

      $moved = $positionHelper->move($moveID, $moveTo);

      if ($moved) {
        $this->setMessage(Message::createSuccess($_LANG['cx_message_area_success_moved']));
      }
    }
  }

  /**
   * Clears all cached data
   */
  private function _reloadCaches()
  {
    $this->_areaRows = null;
    $this->_areaElementRows = null;
    $this->_contentElementsFactory->clearCache();
  }

  /**
   * Deletes an area if requested
   */
  private function _editContentDeleteArea()
  {
    global $_LANG;

    if (!$this->_input()->readInt('deleteAreaID')) {
      return;
    }

    $id = $this->_input()->readInt('deleteAreaID');

    $positionHelper = new PositionHelper(
      $this->db,
      "{$this->table_prefix}contentitem_cx_area",
      'CXAID',
      'CXAPosition',
      'FK_CIID',
      $this->page_id
    );

    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getAreaElements($id) as $el) {
      $el->deleteElementContent();
    }

    // move element to highest position to resort all other elements
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    // clear statement database entry
    $sql = " DELETE FROM {$this->table_prefix}contentitem_cx_area "
         . " WHERE CXAID = :CXAID ";
    $this->db->q($sql, array('CXAID' => $id));

    $this->setMessage(Message::createSuccess($_LANG['cx_message_area_delete_success']));

    $this->_reloadCaches();
  }
}
