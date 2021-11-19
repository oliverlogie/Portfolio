<?php

/**
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */
abstract class ContentItemCX_Areas_Element extends ContentItem
{
  protected $_configPrefix = 'cx'; // "_area_element_..." is added in $this->__construct()
  protected $_contentPrefix = 'cx_area_element';
  protected $_columnPrefix = 'CXA';
  protected $_contentElements = array();
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'CX';  // "_Area_Element" is added in $this->__construct()

  /**
   * The parent contentitem ( ContentItemCX )
   *
   * @var ContentItemCX
   */
  protected $_parent = null;

  /**
   * @var string
   */
  protected $_identifier;

  /**
   * @var array
   */
  protected $_options;

  /**
   * @var ContentItemCX_Areas_ElementFactory
   */
  protected $_contentElementsFactory;

  /**
   * NOTE: do not access directly
   * @see ContentItemCX_Areas_Element::_getChildElementRows()
   * @var array
   */
  protected $_childElementRows;

  /**
   * NOTE: do not access directly
   * @see ContentItemCX_Areas_Element::_getChildElements()
   * @var array
   */
  protected $_childElements;

  /**
   * NOTE: do not access directly
   * @see ContentItemCX_Areas_Element::_input()
   * @var Input
   */
  private $_input;

  /**
   * Implement in subclass. Deletes the element.
   * NOTE: Make sure to call
   * ContentItemCX_Areas_Element::_deleteElementContentRow() to remove it's
   * data from database after removing all files, resources and child elements.
   *
   * @return string
   */
  public abstract function deleteElementContent();

  /**
   * Implement in subclasses. Ensure to call
   * ContentItemCX_Areas_Element::_checkDatabase() when implementing.
   *
   * @return string
   */
  public abstract function getElementContent();

  /**
   * Implement in subclass. Updates the element content.
   * Make sure to update the current element object instance's data
   *
   * @return string
   */
  public abstract function updateElementContent();

  public function __construct(
    $site_id,
    $page_id,
    Template $tpl,
    Db $db,
    $table_prefix,
    $action = '',
    $page_path = '',
    User $user = null,
    Session $session = null,
    Navigation $navigation,
    ContentItemCX $parent,
    ContentItemCX_Areas_ElementFactory $contentElementsFactory,
    $identifier,
    $options
  ) {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
      $page_path, $user, $session, $navigation);

    $this->_options        = $this->_parseOptions($options);
    $this->_parent         = $parent;
    $this->_configPrefix   .= '_area_element_' . str_replace('.', '_', $identifier);
    $this->_templateSuffix .= '_Area_Element';
    $this->_identifier     = $identifier;
    $this->_contentElementsFactory = $contentElementsFactory;
  }

  /**
   * Duplicates the element content. Ensure to copy child element contents as
   * well an call parent::duplicateElementContent() when overriding.
   *
   * @param int $pageId        the new page's id
   * @param int $areaId        the new area's id this element belongs to
   * @param int $elementableId the new elementable id (i.e. an area or a parent
   *                           element)
   *
   * @return void
   * @throws \Core\Db\Exceptions\QueryException
   */
  public function duplicateElementContent($pageId, $areaId, $elementableId)
  {
    $content = $this->_getElementOption('data.content');
    if (is_array($content)) {
      $content = json_encode($content, JSON_PRETTY_PRINT);
    }

    $sql = " INSERT INTO {$this->table_prefix}contentitem_cx_area_element "
         . " (CXAEIdentifier, CXAEType, CXAEContent, CXAEDisabled, CXAEElementableType, CXAEElementableID, FK_CXAID, FK_CIID) VALUES "
         . " ( "
         . "    '{$this->db->escape($this->_getElementOption('data.identifier'))}', "
         . "    '{$this->db->escape($this->_getElementOption('data.type'))}', "
         . "    '{$this->db->escape($content)}', "
         . "    '{$this->db->escape($this->_getElementOption('data.disabled') ? 1 : 0)}', "
         . "    '{$this->db->escape($this->_getElementOption('data.elementable_type'))}', "
         . "    '{$this->db->escape($elementableId)}', "
         . "    '{$this->db->escape($areaId)}', "
         . "    '{$this->db->escape($pageId)}' "
         . " ) ";
    $this->db->query($sql);

    $newId = $this->db->insert_id();

    /** @var \ContentItemCX_Areas_Element $element */
    foreach ($this->_getChildElements() as $element) {
      $element->duplicateElementContent($pageId, $areaId, $newId);
    }
  }

  /**
   * @see ContentItem::getImageTitles()
   */
  public function getElementImageTitles($subcontent = true)
  {
    $titles = array();

    if ($subcontent) {
      /** @var \ContentItemCX_Areas_Element $childElement */
      foreach ($this->_getChildElements() as $childElement) {
        $titles = array_merge($titles, $childElement->getElementImageTitles($subcontent));
      }
    }

    return $titles;
  }

  /**
   * @see ContentItem::getTexts()
   */
  public function getElementTexts($subcontent = true)
  {
    $texts = array();

    if ($subcontent) {
      /** @var \ContentItemCX_Areas_Element $childElement */
      foreach ($this->_getChildElements() as $childElement) {
        $texts = array_merge($texts, $childElement->getElementTexts($subcontent));
      }
    }

    return $texts;
  }

  /**
   * @see ContentItem::getTitles()
   */
  public function getElementTitles()
  {
    $titles = array();

    /** @var \ContentItemCX_Areas_Element $childElement */
    foreach ($this->_getChildElements() as $childElement) {
      $titles = array_merge($titles, $childElement->getElementTitles());
    }

    return $titles;
  }

  /**
   * @return array
   */
  public function getElementTemplateData()
  {
    global $_LANG;

    $lang = array();

    foreach ($this->_options['lang'] as $key => $label) {
      $lang[$key] = isset($_LANG[$label]) ? $_LANG[$label] : $label;
    }

    $message = null;
    if ($this->getMessage()) {
      $message = array(
        'text' => $this->getMessage()->getText(),
        'type' => $this->getMessage()->getType(),
      );
    }

    return array_merge($this->_options['data'], array(
      'lang'     => $lang,
      'message'  => $message,
      'name'     => $this->_getElementInputName(),
      'settings' => $this->_options['settings'],
    ));
  }

  /**
   * @return string
   */
  public function getElementType()
  {
    return $this->_getElementOption('type');
  }

  /**
   * @return int
   */
  public function getElementId()
  {
    return (int)$this->_getElementOption('data.id');
  }

  /**
   * @return bool
   */
  public function hasElementError()
  {
    $error = $this->getMessage() && $this->getMessage()->getType() === Message::TYPE_FAILURE;

    if (!$error) {
      /** @var ContentItemCX_Areas_Element $el */
      foreach ($this->_getChildElements() as $el) {
        if ($el->hasElementError()) {
          $error = true;
          break;
        }
      }
    }

    return $error;
  }

  /**
   * Process an action called directly for this element. Use this method to
   * implement custom element functionality, that can not be handled by the
   * elements default ContentItemCX_Areas_Element::updateElementContent()
   * method.
   *
   * Usage:
   *
   * For an URL of format
   * ...&process_cx_area_element&cx_element=<id>&cx_element_action=doExample
   * this method automatically trys to call
   * ContentItemCX_Areas_Element::_processElementActionDoExample() if available
   *
   * @param string $action
   */
  public function processElementAction($action)
  {
    global $_LANG;

    $action = preg_replace('/[^a-z]/ui', '', $action);
    $method = sprintf('_processElementAction%s', ucfirst($action));

    if (is_callable(array($this, $method))) {
      $this->$method();
    }
    else {
      $this->setMessage(Message::createFailure($_LANG['cx_message_area_process_cx_area_element_action_invalid']));
    }
  }

  /**
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _checkDatabase()
  {
    if (!$this->_getElementOption('elements')) {
      return;
    }

    if ($this->_getChildElementRows() === false) {
      throw new Exception("Error while reading {$this->table_prefix}contentitem_cx_area_element. Please check database.");
    }

    $sql = " INSERT INTO {$this->table_prefix}contentitem_cx_area_element "
         . " (CXAEIdentifier, CXAEType, CXAEElementableType, CXAEElementableID, FK_CXAID, FK_CIID) VALUES ";
    $inserts = array();

    foreach ($this->_getElementOption('elements') as $identifier => $c) {
      $available = false;

      $elementIdentifier = $this->_getElementOption('data.identifier') . '.' . $identifier;

      foreach ($this->_getChildElementRows() as $elementRow) {
        if ($elementRow['CXAEIdentifier'] == $elementIdentifier) {
          $available = true;
        }
      }

      if (!$available) {
        $inserts[] = " ( "
          . " '{$this->db->escape($elementIdentifier)}', "
          . " '{$this->db->escape($c['type'])}', "
          . " 'contentitem_cx_area_element', "
          . " '{$this->db->escape($this->_getElementOption('data.id'))}', "
          . " '{$this->db->escape($this->_getElementOption('data.area_id'))}', "
          . " '{$this->db->escape($this->page_id)}' "
          . " ) ";
      }
    }

    if ($inserts) {
      $this->db->q($sql . implode(',', $inserts));

      // Force refresh of properties
      $this->_childElementRows = null;
      $this->_childElements = null;
    }
  }

  /**
   * Deletes this elements data from database. Make sure to remove files and
   * child elements before calling this method.
   *
   * Usually this method should be called at the end of
   * ContentItemCX_Areas_Element::deleteElementContent()
   */
  protected function _deleteElementContentRow()
  {
    $sql = " DELETE FROM {$this->table_prefix}contentitem_cx_area_element "
         . " WHERE CXAEID = :CXAEID ";
    $this->db->q($sql, array('CXAEID' => $this->_getElementOption('data.id')));
  }

  /**
   * Returns the child element database result rows array.
   *
   * @return array
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _getChildElementRows()
  {
    if ($this->_childElementRows === null) {
      $this->_childElementRows = array();

      $sql = " SELECT CXAEID, CXAEIdentifier, CXAEType, CXAEDisabled, CXAEContent, "
           . "        CXAEElementableID, CXAEElementableType, CXAEPosition, "
           . " FK_CXAID, FK_CIID "
           . " FROM {$this->table_prefix}contentitem_cx_area_element "
           . " WHERE FK_CIID = :FK_CIID "
           . "   AND CXAEElementableType = 'contentitem_cx_area_element' "
           . "   AND CXAEElementableID = :CXAEElementableID "
           . " ORDER BY CXAEPosition ASC ";
      try {
        $result = $this->db->q($sql, array(
          'FK_CIID' => $this->page_id,
          'CXAEElementableID' => $this->_getElementOption('data.id'),
        ));

        $this->_childElementRows = $result->fetchAll();
      }
      catch (\Core\Db\Exceptions\QueryException $e) {
        throw $e;
      }
    }

    return $this->_childElementRows;
  }

  /**
   * @return array
   */
  protected function _getChildElements()
  {
    if ($this->_childElements === null) {
      $this->_childElements = array();

      if ($this->_getElementOption('elements')) {
        foreach ($this->_getElementOption('elements') as $identifier => $c) {
          $elementIdentifier = $this->_getElementOption('data.identifier') . '.' . $identifier;

          foreach ($this->_getChildElementRows() as  $childElementRow) {
            if ($childElementRow['CXAEIdentifier'] == $elementIdentifier) {
              $this->_childElements[$childElementRow['CXAEID']] =
                $this->_contentElementsFactory->make($elementIdentifier, $c, $childElementRow);
            }
          }
        }
      }
    }

    return $this->_childElements;
  }

  /**
   * @return array
   */
  protected function _getChildElementsTemplateVars()
  {
    $elements = array();

    /** @var ContentItemCX_Areas_Element $el */
    foreach ($this->_getChildElements() as $el) {
      $elements[] = array_merge($el->getElementTemplateData(), array(
        'content_parsed' => $el->getElementContent(),
      ));
    }

    return $elements;
  }

  /**
   * @return string
   */
  protected function _getElementInputName()
  {
    return str_replace('.', '_', $this->_identifier) . '_' . $this->_getElementOption('data.id');
  }

  /**
   * @param string $name
   *        the config value to look up from element options variable.
   *        Use dot notation for retrieving nested values,
   *        i.e. settings.example
   *
   * @return mixed|null
   *         returns the configuration value or null if not found
   */
  protected function _getElementOption($name)
  {
    $name = explode('.', $name);

    $result = $this->_options;
    foreach ($name as $key => $value) {
      $result = isset($result[$value]) ? $result[$value] : null;

      if (!$result) {
        break;
      }
    }

    return $result;
  }

  /**
   * @param string $action
   * @param array $params
   *
   * @return string
   */
  protected function _getElementProcessUrl($action, $params = array())
  {
    return $this->_parseUrl(array_merge(array(
      'process_cx_area_element' => 1,
      'cx_element'              => $this->_getElementOption('data.id'),
      'cx_element_action'       => $action,
    ), $params));
  }

  /**
   * @return string
   */
  protected function _getElementTemplatePath()
  {
    return sprintf('edwin/templates/content_types/ContentItemCX_Areas_Element_%s.php', ucfirst($this->_options['type']));
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
   * Prepares the options for this element. Override in element classes
   * extending this base class in order to modify i.e. the 'data.content'
   * appropriately, if stored in a special way.
   *
   * NOTE: make sure to call parent::_parseOptions($options); when overriding
   *
   * @param array $options
   *
   * @return array
   *
   * @throws InvalidArgumentException
   */
  protected function _parseOptions($options)
  {
    if (!isset($options['type']) || !$options['type']) {
      throw new InvalidArgumentException("Invalid element configuration. Missing or empty field 'type'.");
    }

    if (!isset($options['lang']) || !is_array($options['lang'])) {
      $options['lang'] = array();
    }

    if (!isset($options['settings']) || !is_array($options['settings'])) {
      $options['settings'] = array();
    }

    return $options;
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
      'action'              => 'content',
      'site'                => $this->site_id,
      'page'                => $this->page_id,
      'cx_area'             => $this->_getElementOption('data.area_id'),
      'cx_scroll_to_anchor' => 'a_areas',
    ), $params);

    $prepared = array();
    foreach ($params as $key => $val) {
      $prepared[] = sprintf('%s=%s', $key, urlencode($val));
    }

    return edwin_url() . '?' . implode('&', $prepared);
  }

  /**
   * @param array|string $content
   *
   * @throws \Core\Db\Exceptions\QueryException
   */
  protected function _updateElementContent($content)
  {
    if (is_array($content)) {
      $content = json_encode($content, JSON_PRETTY_PRINT);
    }

    $sql = " UPDATE {$this->table_prefix}contentitem_cx_area_element "
         . " SET CXAEContent = :CXAEContent "
         . " WHERE CXAEID = :CXAEID ";
    $this->db->q($sql, array(
      'CXAEContent' => $content,
      'CXAEID' => $this->_options['data']['id'],
    ));
  }
}