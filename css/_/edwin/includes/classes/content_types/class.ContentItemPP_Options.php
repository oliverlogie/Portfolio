<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2017-10-09 14:04:21 +0200 (Mo, 09 Okt 2017) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ContentItemPP_Options extends ContentItem
{
  private $_error = false;

  protected $_configPrefix = 'pp'; // "_option" is added in $this->__construct()
  protected $_contentPrefix = 'pp_option';
  protected $_templateSuffix = 'PP'; // "_Options" is added in $this->__construct()
  protected $_columnPrefix = 'PPO';

  /**
   * The parent contentitem ( ContentItemPP )
   *
   * @var ContentItemDL
   */
  private $_parent = null;

  public function __construct($site_id, $page_id, Template $tpl, Db $db,
                              $table_prefix, User $user, Session $session = null,
                              Navigation $navigation, ContentItemPP $parent)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, '', '',
                        $user, $session, $navigation);
    $this->_configPrefix .= '_option';
    $this->_templateSuffix .= '_Options';
    $this->_parent = $parent;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                           //
  //////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_option "
         . " WHERE FK_CIID = $this->page_id ";
    $this->db->query($sql);
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT {$this->_columnPrefix}ID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_CIID = {$this->page_id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      parent::duplicateContent($pageId, $newParentId, "FK_CIID", $id, "{$this->_columnPrefix}ID");
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                             //
  //////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    $this->_createItem();
    $this->_updateItem();
    $this->_moveItem();
    $this->_deleteItem();
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2, $_MODULES;

    $urlPart = "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}";
    $editOptionID = isset($_GET['editOptionID']) ? (int)$_GET['editOptionID'] : 0;
    $editOptionData = array();

    $post = new Input(Input::SOURCE_POST);
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_option ",
                                         'PPOID', 'PPOPosition',
                                         'FK_CIID', $this->page_id);

    // read options
    $optionItems = array();
    $sql = " SELECT PPOID, FK_CIID, FK_OPID, PPOPosition, PPOPrice, "
         . "        OPCode, OPName, OPText, OPProduct "
         . " FROM {$this->table_prefix}contentitem_pp_option "
         . " JOIN {$this->table_prefix}contentitem_pp_option_global "
         . "   ON FK_OPID = OPID "
         . " WHERE FK_CIID = $this->page_id "
         . " ORDER BY PPOPosition ASC ";
    $result = $this->db->query($sql);
    $optionCount = $this->db->num_rows($result);
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $tmpId = (int)$row['PPOID'];
      $tmpPrice = $row['PPOPrice'];
      $tmpOptionId = (int)$row['FK_OPID'];

      $class = $tmpId == $editOptionID ? 'edit' : 'normal';
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['PPOPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['PPOPosition']);

      $optionItems[$tmpId] = array(
        'pp_option_class'          => $class,
        'pp_option_id'             => $tmpId,
        'pp_option_price'          => parseOutput($tmpPrice,99),
        'pp_option_text'           => parseOutput($row['OPText']),
        'pp_option_title'          => parseOutput($row['OPName']),
        'pp_option_position'       => (int)$row['PPOPosition'],
        'pp_option_product'        => (int)$row['OPProduct'],
        'pp_option_option_id'      => $tmpOptionId,
        'pp_option_edit_link'      => "$urlPart&amp;editOptionID={$tmpId}&amp;scrollToAnchor=pp_options",
        'pp_option_move_up_link'   => "$urlPart&amp;moveOptionID={$tmpId}&amp;moveOptionTo=$moveUpPosition&amp;scrollToAnchor=pp_options",
        'pp_option_move_down_link' => "$urlPart&amp;moveOptionID={$tmpId}&amp;moveOptionTo=$moveDownPosition&amp;scrollToAnchor=pp_options",
        'pp_option_delete_link'    => "$urlPart&amp;deleteOptionID={$tmpId}&amp;scrollToAnchor=pp_options",
      );

      // this row has to be edited
      if ($tmpId == $editOptionID)
      {
        // read data from post source ( default = actual value ) in case of failure
        $tmpPrice = $post->readFloat('pp_option_price', $tmpPrice);
        $tmpOptionId = $post->readInt('pp_option_option_id', $tmpOptionId);
        $tmpOptionName = parseOutput($post->readInt('pp_option_option', $row['OPName']));

        $editOptionData = array(
          'pp_option_id_edit'        => $tmpId,
          'pp_option_price_edit'     => $tmpPrice,
          'pp_option_option_edit'    => $tmpOptionName,
          'pp_option_option_id_edit' => $tmpOptionId,
        );
      }
    }
    $this->db->free_result($result);

    $maximumReached = $optionCount >= $this->_parent->getConfig('number_of_options');

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    // fill new entry form with previously input data (in case of an error)
    $this->tpl->parse_if($tplName, 'entry_create', !$maximumReached, array(
      'pp_option_option'    => $this->_error ? $post->readString('pp_option_option') : '',
      'pp_option_option_id' => $this->_error ? $post->readString('pp_option_option_id') : '',
      'pp_option_price'     => $this->_error ? $post->readString('pp_option_price') : '',
    ));
    $this->tpl->parse_if($tplName, 'entries_maximum_reached', $maximumReached);
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('pp_option'));
    $this->tpl->parse_if($tplName, 'entry_edit', $editOptionData, $editOptionData);
    $this->tpl->parse_loop($tplName, $optionItems, 'entries');
    $pp_option_items_output = $this->tpl->parsereturn($tplName, array(
      'pp_option_count' => $optionCount,
      'pp_option_dragdrop_link_js' => "index.php?action=content&site={$this->site_id}&page={$this->page_id}&moveOptionID=#moveID#&moveOptionTo=#moveTo#&scrollToAnchor=pp_options",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $pp_option_items_output,
      'count'   => $optionCount,
    );
  }

  protected function _processedValues()
  {
    return array( 'deleteOptionID',
                  'moveOptionID',
                  'process_pp_option_create',
                  'process_pp_option_edit',);
  }

  /**
   * Check if an option has already been added to product
   *
   * @param int $optionId
   *            The option to add
   * @param int $editId [optional] [default : 0]
   *            Id of the product option line edited
   *
   * @return bool
   *         true if option has already been added
   *         false otherwise / if the option has been added for the currently
   *         edited product option line, false is returned as saving the option
   *         is allowed
   */
  private function _checkMultipleOptions($optionId, $editId = 0)
  {
    global $_LANG;

    $sql = " SELECT PPOID, FK_OPID "
         . " FROM {$this->table_prefix}contentitem_pp_option "
         . " WHERE FK_CIID = $this->page_id "
         . "   AND FK_OPID = $optionId ";
    $row = $this->db->GetRow($sql);

    // Option has been added and there is not a product option edited or the
    // option to add has been added for another product option line
    if ($row && (!$editId || $editId != $row['PPOID'])) {
      $this->_error = true;
      $this->setMessage(Message::createFailure($_LANG['pp_message_option_existing_failure']));
      return true;
    }

    return false;
  }

  /**
   * Creates a file if the POST parameter process_pp_option_create is set.
   */
  private function _createItem()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_pp_option_create') )
      return;

    $price = $post->readFloat("pp_option_price");
    $id = $post->readInt("pp_option_option_id");

    if (!$id) {
      $this->_error = true;
      $this->setMessage(Message::createFailure($_LANG['pp_message_option_insufficient_input']));
      return;
    }

    if ($this->_checkMultipleOptions($id))
      return;

    $sql = " SELECT MAX(PPOPosition) "
         . " FROM {$this->table_prefix}contentitem_pp_option "
         . " WHERE FK_CIID = $this->page_id ";
    $pos = $this->db->GetOne($sql) + 1;

    $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_option "
         . " (FK_CIID, FK_OPID, PPOPrice, PPOPosition) "
         . " VALUES "
         . " ($this->page_id, $id, $price, {$pos})";
    $result = $this->db->query($sql);

    if ($result)
      $this->setMessage(Message::createSuccess($_LANG['pp_message_option_create_success']));
  }

  /**
   * Deletes an item if the GET parameter deleteOptionID is set.
   */
  private function _deleteItem()
  {
    global $_LANG;

    if (!isset($_GET['deleteOptionID']))
      return;

    $id = (int)$_GET['deleteOptionID'];

    // move item to last position before deleting it
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_option",
                                         'PPOID', 'PPOPosition',
                                         'FK_CIID', $this->page_id);
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_option "
         . " WHERE PPOID = $id "
         . "   AND FK_CIID = $this->page_id ";
    $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['pp_message_option_delete_success']));
  }

  /**
   * Moves a file if the GET parameters moveOptionID and moveOptionTo are set.
   */
  private function _moveItem()
  {
    global $_LANG;

    if (!isset($_GET['moveOptionID'], $_GET['moveOptionTo']))
      return;

    $moveID = (int)$_GET['moveOptionID'];
    $moveTo = (int)$_GET['moveOptionTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_option",
                                         'PPOID', 'PPOPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved)
      $this->setMessage(Message::createSuccess($_LANG['pp_message_option_move_success']));
  }

  /**
   * Updates an option if the POST parameter process_pp_option_edit is set.
   */
  private function _updateItem()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process_pp_option_edit'))
      return;

    $price = $post->readFloat("pp_option_price_edit");
    $optionId = $post->readInt("pp_option_option_id_edit");
    $productOptionId = $post->readInt("pp_option_id_edit");

    if (!$productOptionId)
      return;

    if (!$optionId) {
      $this->setMessage(Message::createFailure($_LANG['pp_message_option_insufficient_input']));
      return;
    }

    if ($this->_checkMultipleOptions($optionId, $productOptionId))
      return;

    $sql = " UPDATE {$this->table_prefix}contentitem_pp_option "
         . " SET FK_OPID = $optionId, "
         . "     PPOPrice = $price "
         . " WHERE PPOID = $productOptionId ";
    $result = $this->db->query($sql);

    if ($result)
      $this->setMessage(Message::createSuccess($_LANG['pp_message_option_edit_success']));
  }
}

