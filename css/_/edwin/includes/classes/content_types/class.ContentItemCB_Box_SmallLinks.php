<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemCB_Box_SmallLinks extends ContentItem
{
  protected $_configPrefix = 'cb'; // "_box_smalllink" is added in $this->_construct()
  protected $_contentPrefix = 'cb_box_smalllink';
  protected $_columnPrefix = 'SL';
  protected $_contentElements = array(
    'Title' => 1,
    'Link' => 1,
  );
  protected $_templateSuffix = 'CB'; // "_Box_SmallLink" is added in $this->_construct()

  /**
   * The box id.
   *
   * @var integer
   */
  private $box_id;
  /**
   * The box number (starting with 1).
   *
   * @var integer
   */
  private $box_position;

  /**
   * The parent contentitem ( ContentItemCB )
   *
   * @var ContentItemCB
   */
  private $_parent = null;

  /**
   * Checks if link already exists
   * @param int $linkId
   *            Link id of content item
   * @param int $editId
   *            Id of the link that should be edited
   * @return boolean true if link already exists
   */
  private function _checkMultipleLinks($linkId, $editId = 0)
  {
    global $_LANG;

    $editId = ($editId) ? 'AND SLID != '.$editId : '';
    $sql = 'SELECT SLID '
         . "FROM {$this->table_prefix}contentitem_cb_box_smalllink "
         . "WHERE SLLink   = {$linkId} "
         . "  AND FK_CBBID = {$this->box_id} "
         . " $editId ";

    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['cb_message_multiple_link_failure']));
      return true;
    }

    return false;
  }

  /**
   * Creates a small link if the POST parameter process_cb_box_smalllink_create is set.
   */
  private function createSmallLink() {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['box'], $_POST['process_cb_box_smalllink_create']) || (int)$_POST['box'] != $this->box_id) {
      return;
    }

    $boxID = $post->readKey('process_cb_box_smalllink_create');

    $title = $post->readString("cb_box{$boxID}_smalllink_title", Input::FILTER_PLAIN);
    list($link, $linkID) = $post->readContentItemLink("cb_box{$boxID}_smalllink_link");

    // A content item must not be linked more than once
    if ($this->_checkMultipleLinks($linkID))
      return false;

    if (!$title || !$linkID) {
      $this->setMessage(Message::createFailure($_LANG['cb_message_box_smalllink_insufficient_input']));
      return;
    }

    $sql = 'SELECT COUNT(SLID) + 1 '
         . "FROM {$this->table_prefix}contentitem_cb_box_smalllink "
         . "WHERE FK_CBBID = $boxID ";
    $position = $this->db->GetOne($sql);
    $sql = "INSERT INTO {$this->table_prefix}contentitem_cb_box_smalllink "
         . '(SLTitle, SLLink, SLPosition, FK_CBBID) '
         . "VALUES('{$this->db->escape($title)}', $linkID, $position, $boxID) ";
    $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['cb_message_box_smalllink_success']));

    unset($_POST["cb_box{$boxID}_smalllink_title"]);
    unset($_POST["cb_box{$boxID}_smalllink_link"]);
    unset($_POST["cb_box{$boxID}_smalllink_link_id"]);
  }

  /**
   * Updates a small link if the POST parameter process_cb_box_smalllink_edit is set.
   */
  private function updateSmallLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $ID = $post->readKey('process_cb_box_smalllink_edit');
    if (!$ID) {
      return;
    }
    if ($post->readInt('box') != $this->box_id) {
      return;
    }

    // Read all content elements.
    $input['Title'] = $this->_readContentElementsTitles($ID);
    $input['Link'] = $this->_readContentElementsLinks($ID);

    // A content item must not be linked more than once
    if ($this->_checkMultipleLinks($input['Link']['SLLink'], $ID))
      return false;


    // Check required fields (title + link).
    if (!$input['Title']['SLTitle'] || !$input['Link']['SLLink']) {
      $this->setMessage(Message::createFailure($_LANG['cb_message_box_smalllink_insufficient_input']));
      $_GET['editSmallLinkID'] = $ID;
      return;
    }

    // Update the database. Do not use ContentItem::_buildContentElementsUpdateStatement
    $sql = "UPDATE {$this->table_prefix}contentitem_cb_box_smalllink "
         . "SET SLTitle = '{$this->db->escape($input['Title']['SLTitle'])}', "
         . "    SLLink = {$input['Link']['SLLink']} "
         . "WHERE SLID = $ID ";
    $result = $this->db->query($sql);

    $this->_updateExtendedData($ID);

    $this->setMessage(Message::createSuccess($_LANG['cb_message_box_smalllink_success']));
  }

  /**
   * Moves a small link if the GET parameters moveSmallLinkID and moveSmallLinkTo are set.
   */
  private function moveSmallLink() {
    global $_LANG;

    if (!isset($_GET['box']) || (int)$_GET['box'] != $this->box_id) {
      return;
    }
    if (!isset($_GET['moveSmallLinkID'], $_GET['moveSmallLinkTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveSmallLinkID'];
    $moveTo = (int)$_GET['moveSmallLinkTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cb_box_smalllink",
                                         'SLID', 'SLPosition',
                                         'FK_CBBID', $this->box_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['cb_message_box_smalllink_success']));
    }
  }

  /**
   * Deletes a small link if the GET parameter deleteSmallLinkID is set.
   */
  private function deleteSmallLink() {
    global $_LANG;

    if (isset($_GET["box"], $_GET["deleteSmallLinkID"]) && (int)$_GET["box"] == $this->box_id) {
      $slid = (int)$_GET["deleteSmallLinkID"];

      $deletedPosition = $this->db->GetOne(<<<SQL
SELECT SLPosition
FROM {$this->table_prefix}contentitem_cb_box_smalllink
WHERE SLID = $slid
SQL
      );

      // delete small link database entry
      $this->db->query(<<<SQL
DELETE FROM {$this->table_prefix}contentitem_cb_box_smalllink
WHERE SLID = $slid
SQL
      );

      // move following small links one position up
      $this->db->query(<<<SQL
UPDATE {$this->table_prefix}contentitem_cb_box_smalllink
SET SLPosition = SLPosition - 1
WHERE FK_CBBID = $this->box_id
AND SLPosition > $deletedPosition
ORDER BY SLPosition ASC
SQL
      );

      $this->_deleteExtendedData($slid);

      $this->setMessage(Message::createSuccess($_LANG["cb_message_box_smalllink_success"]));
    }
  }

  protected function _processedValues()
  {
    return array( 'deleteSmallLinkID',
                  'moveSmallLinkID',
                  'process_cb_box_smalllink_create',
                  'process_cb_box_smalllink_edit',);
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Constructor                                                                           //
  ///////////////////////////////////////////////////////////////////////////////////////////

  public function __construct($site_id, $page_id, Template $tpl, db $db,
                              $table_prefix, $action = '', $page_path = '',
                              User $user = null, Session $session = null,
                              Navigation $navigation, ContentItemCB $parent,
                              $box_id, $box_position)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
                        $page_path, $user, $session, $navigation);
    $this->box_id = $box_id;
    $this->box_position = $box_position;
    $this->_configPrefix .= '_box_smalllink';
    $this->_templateSuffix .= '_Box_SmallLink';
    $this->_parent = $parent;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content(){
    // not used
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT {$this->_columnPrefix}ID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_CBBID = {$id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      parent::duplicateContent($pageId, $newParentId, "FK_CBBID", $id, "{$this->_columnPrefix}ID");
    }
  }

  public function edit_content()
  {
    $this->createSmallLink();
    $this->updateSmallLink();
    $this->moveSmallLink();
    $this->deleteSmallLink();
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $editSmallLinkID = isset($_GET["editSmallLinkID"]) ? (int)$_GET["editSmallLinkID"] : 0;
    $editSmallLinkData = array();

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_cb_box_smalllink",
                                         'SLID', 'SLPosition',
                                         'FK_CBBID', $this->box_id);

    // read box small links
    $cb_box_smalllink_items = array();
    $sql = 'SELECT SLID, SLTitle, SLPosition, CIID, CIIdentifier '
         . "FROM {$this->table_prefix}contentitem_cb_box_smalllink sl "
         . "LEFT JOIN {$this->table_prefix}contentitem ci "
         . '          ON SLLink = CIID '
         . "WHERE sl.FK_CBBID = $this->box_id "
         . 'ORDER BY SLPosition ASC ';
    $result = $this->db->query($sql);
    $cb_box_smalllink_count = $this->db->num_rows($result);
    $invalidLinks = 0;
    $invisibleLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['SLPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['SLPosition']);

      $smallLinkClass = 'normal';
      // Detect invalid and invisible links.
      if ($row['CIID']) {
        $linkedPage = $this->_navigation->getPageByID((int)$row['CIID']);
        if (!$linkedPage->isVisible()) {
          $smallLinkClass = 'invisible';
          $invisibleLinks++;
        }
      } else {
        $smallLinkClass = 'invalid';
        $invalidLinks++;
      }
      // the 'edit' class overrides the others as it is the most important in the UI
      if ($row['SLID'] == $editSmallLinkID) {
        $smallLinkClass = 'edit';
      }

      $cb_box_smalllink_items[$row["SLID"]] = array_merge(array(
        "cb_box_smalllink_title" => parseOutput($row["SLTitle"]),
        "cb_box_smalllink_link" => $row["CIIdentifier"],
        "cb_box_smalllink_link_id" => $row["CIID"],
        "cb_box_smalllink_id" => $row["SLID"],
        "cb_box_smalllink_position" => $row['SLPosition'],
        "cb_box_smalllink_class" => $smallLinkClass,
        "cb_box_smalllink_edit_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$this->box_id}&amp;editSmallLinkID={$row["SLID"]}&amp;scrollToAnchor=a_box{$this->box_position}_smalllinks",
        "cb_box_smalllink_move_up_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$this->box_id}&amp;moveSmallLinkID={$row["SLID"]}&amp;moveSmallLinkTo=$moveUpPosition&amp;scrollToAnchor=a_box{$this->box_position}_smalllinks",
        "cb_box_smalllink_move_down_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$this->box_id}&amp;moveSmallLinkID={$row["SLID"]}&amp;moveSmallLinkTo=$moveDownPosition&amp;scrollToAnchor=a_box{$this->box_position}_smalllinks",
        "cb_box_smalllink_delete_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;box={$this->box_id}&amp;deleteSmallLinkID={$row["SLID"]}&amp;scrollToAnchor=a_box{$this->box_position}_smalllinks",
      ), $this->_getContentExtensionData($row['SLID']));

      // this row has to be edited
      if ($row['SLID'] == $editSmallLinkID) {
        $post = new Input(Input::SOURCE_POST);
        $cb_box_smalllink_title = $cb_box_smalllink_items[$row['SLID']]['cb_box_smalllink_title'];
        $cb_box_smalllink_link = $cb_box_smalllink_items[$row['SLID']]['cb_box_smalllink_link'];
        $cb_box_smalllink_link_id = $cb_box_smalllink_items[$row['SLID']]['cb_box_smalllink_link_id'];
        if (isset($_POST["cb_box_smalllink{$editSmallLinkID}_title"])) {
          $cb_box_smalllink_title = $post->readString("cb_box_smalllink{$editSmallLinkID}_title", Input::FILTER_PLAIN);
        }
        if (isset($_POST["cb_box_smalllink{$editSmallLinkID}_link"])) {
          $cb_box_smalllink_link = trim($post->readString("cb_box_smalllink{$editSmallLinkID}_link", Input::FILTER_PLAIN));
        }
        if (isset($_POST["cb_box_smalllink{$editSmallLinkID}_link_id"])) {
          $cb_box_smalllink_link_id = (int)$_POST["cb_box_smalllink{$editSmallLinkID}_link_id"];
        }
        $editSmallLinkData = array(
          'cb_box_smalllink_id' => $row['SLID'],
          'cb_box_smalllink_title_edit' => $cb_box_smalllink_title,
          'cb_box_smalllink_link_edit' => $cb_box_smalllink_link,
          'cb_box_smalllink_link_id_edit' => $cb_box_smalllink_link_id,
        );
      }
    }
    $this->db->free_result($result);

    $maxItems = (int)$this->_parent->getConfig('number_of_smalllinks');
    $maximumReached = count($cb_box_smalllink_items) >= $maxItems;

    $this->tpl->load_tpl("content_site_cb_box_smalllink", "content_types/ContentItemCB_Box_SmallLink.tpl");
    $this->tpl->parse_if("content_site_cb_box_smalllink", "entry_create", !$maximumReached, array(
      "cb_box_smalllink_title" => isset($_POST["cb_box{$this->box_id}_smalllink_title"]) ? $_POST["cb_box{$this->box_id}_smalllink_title"] : "",
      "cb_box_smalllink_link_id" => isset($_POST["cb_box{$this->box_id}_smalllink_link_id"]) ? $_POST["cb_box{$this->box_id}_smalllink_link_id"] : 0,
      "cb_box_smalllink_link" => isset($_POST["cb_box{$this->box_id}_smalllink_link"]) ? $_POST["cb_box{$this->box_id}_smalllink_link"] : "",
    ));
    $this->tpl->parse_if("content_site_cb_box_smalllink", "entries_maximum_reached", $maximumReached);
    $this->tpl->parse_if('content_site_cb_box_smalllink', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('cb_box_smalllink'));
    $this->tpl->parse_if("content_site_cb_box_smalllink", "entry_edit", $editSmallLinkData, $editSmallLinkData);
    $this->tpl->parse_loop("content_site_cb_box_smalllink", $cb_box_smalllink_items, "entries");
    $cb_box_smalllink_items_output = $this->tpl->parsereturn("content_site_cb_box_smalllink", array(
      "cb_box_smalllink_count" => $cb_box_smalllink_count,
      'cb_box_smalllink_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&box=$this->box_id&moveSmallLinkID=#moveID#&moveSmallLinkTo=#moveTo#&scrollToAnchor=a_box{$this->box_position}_smalllinks",
    ));

    return array(
      "message" => $this->_getMessage(),
      "content" => $cb_box_smalllink_items_output,
      'invalidLinks' => $invalidLinks,
      'invisibleLinks' => $invisibleLinks,
    );
  }

  /**
   * Returns the id of the box this smalllink belongs to
   *
   * @return int
   */
  public function getBoxId()
  {
    return $this->box_id;
  }
}