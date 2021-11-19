<?php

/**
 * Mobile siteindex module
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2013 Q2E GmbH
 */
class ModuleSiteindexCompendiumMobile extends ModuleSiteindex
{
  protected $_actionBoxTemplate = 'modules/ModuleSiteindexCompendiummobile_action_box.tpl';

  /**
   * @var PositionHelper
   */
  private $_positionHelper;

  /**
   * The database model for mobile siteindex containing status information, all
   * buttons refer to this model with their parent id.
   *
   * @var SiteindexCompendiumMobile
   */
  private $_mainModel;

  /**
   * The module URL action parameter value
   *
   * @var string
   */
  protected $_moduleAction = 'mobile';

  /**
   * Returns the config value prefixes, depending on siteindex type
   *
   * @return array
   *         i.e. array( "si1", "si" )
   */
  public function getConfigPrefix()
  {
    $prefix = array();

    if ($this->getType()) { $prefix[] = 'si_' . $this->getType(); }
    if (parent::getType()) { $prefix[] = 'si' . parent::getType(); }
    $prefix[] = 'si';

    return array_unique($prefix);
  }

  /**
   * @return string 'mobile'
   */
  public function getType()
  {
    return 'mobile';
  }

  protected function _checkDatabase()
  {
    parent::_checkDatabase();

    $config = ConfigHelper::get('si_mobile_buttons', '', $this->site_id);
    $model = new SiteindexCompendiumMobile($this->db, $this->table_prefix);

    // fetch or create the "main" entry
    $this->_mainModel = $model->readBySiteIdAndType($this->site_id, SiteindexCompendiumMobile::TYPE_MAIN);
    if (!$this->_mainModel->id) {
      $this->_mainModel->type = SiteindexCompendiumMobile::TYPE_MAIN;
      $this->_mainModel->text = 0;
      $this->_mainModel->site_id = $this->site_id;
      $this->_mainModel->create();
    }

    // if the number of configured items changed or simple is not equal the
    // configured number of items, we delete all existing items and create a
    // new set of items
    $models = $this->_mainModel->read(array('where' => 'FK_SIMID = ' . $this->_mainModel->id));
    if ($models->count() !== count($config)) {
      $sql = " DELETE FROM "
           . " {$this->table_prefix}module_siteindex_compendium_mobile "
           . " WHERE FK_SIMID = '{$this->_mainModel->id}' ";
      $this->db->query($sql);

      $inserts = array();
      $position = 1;
      foreach ($config as $type => $value) {
        $inserts[] = "('{$this->db->escape($type)}', '$position', 0, '{$this->_mainModel->id}', '{$this->site_id}')";
        $position++;
      }

      $sql = " INSERT INTO {$this->table_prefix}module_siteindex_compendium_mobile "
           . " (SIMType, SIMPosition, SIMActive, FK_SIMID, FK_SID) VALUES "
           . implode(',', $inserts);
      $this->db->query($sql);
    }
  }

  protected function _prepareContent()
  {
    parent::_prepareContent();

    $this->_changeItemActivation();
    $this->_moveItem();
    $this->_updateItem();
    $this->_updateUseContent();
    $this->_updateRemoveContent();

    $this->tpl->parse_if('site_index_data', 'si_siteindex_mobile_custom_data', $this->_mainModel->text);
    $this->tpl->parse_if('site_index_data', 'si_siteindex_mobile_custom_data', $this->_mainModel->text);
    $this->tpl->parse_if('site_index_data', 'si_siteindex_mobile_default_data', !$this->_mainModel->text);

    $this->tpl->parse_vars('site_index_data', array(
        'si_siteindex_mobile_buttons'  => $this->_getContent(),
    ));
  }

  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $config = ConfigHelper::get('si_mobile_buttons', '', $this->site_id);

    $model = new SiteindexCompendiumMobile($this->db, $this->table_prefix);
    $models = $model->readBySiteId($this->site_id);

    $buttons = array();
    foreach ($models as $key => $item) {
      if (isset($config[$item->type])) {
        $buttons[] = $this->_getButtonVariables($item);
      }
    }

    $request = new Input(Input::SOURCE_REQUEST);
    $activePosition = $models->exists($request->readInt('si_mobile_button_item')) ?
                      $models->get($request->readInt('si_mobile_button_item'))->position : 0;

    $this->tpl->load_tpl('content', 'modules/ModuleSiteindexCompendiummobile_Buttons.tpl');
    $this->tpl->parse_loop('content', $buttons, 'buttons');
    return $this->tpl->parsereturn('content', array_merge(array(
        'si_mobile_button_active_position'  => $activePosition,
        'si_mobile_button_dragdrop_link_js' => $this->_parseUrl('', array('site' => $this->site_id, 'moveID' => '#moveID#', 'moveTo' => '#moveTo#')),
    ), $_LANG2['si']));
  }

  private function _getButtonVariables(SiteindexCompendiumMobile $button)
  {
    global $_LANG;

    $moveDownPosition = $this->_getPositionHelper()->getMoveDownPosition($button->position);
    $moveUpPosition = $this->_getPositionHelper()->getMoveUpPosition($button->position);

    if (!$button->active) {
      $activationLight = ActivationLightInterface::RED;
      $activationChangeTo = ContentBase::ACTIVATION_ENABLED;
    }
    else {
      $activationLight = ActivationLightInterface::GREEN;
      $activationChangeTo = ContentBase::ACTIVATION_DISABLED;;
    }
    $activationLightLink = $this->_parseUrl('', array(
        'site'                  => $this->site_id,
        'changeItemActivationID' => $button->id,
        'changeItemActivationTo' => $activationChangeTo,
    ));

    return array(
      'si_mobile_button_activation_light_label' => $_LANG['global_activation_light_' . $activationLight . '_label'],
      'si_mobile_button_activation_light_link'  => $activationLightLink,
      'si_mobile_button_activation_light'       => $activationLight,
      'si_mobile_button_id'                     => $button->id,
      'si_mobile_button_name'                   => $_LANG['si_mobile_button_' . $button->type . '_name'],
      'si_mobile_button_info'                   => $_LANG['si_mobile_button_' . $button->type . '_info'],
      'si_mobile_button_position'               => $button->position,
      'si_mobile_button_text'                   => $button->text,
      'si_mobile_button_type'                   => $button->type,
      'si_mobile_button_move_up_link'           => $this->_parseUrl('', array('site' => $this->site_id, 'moveID' => $button->id, 'moveTo' => $moveUpPosition)),
      'si_mobile_button_move_down_link'         => $this->_parseUrl('', array('site' => $this->site_id, 'moveID' => $button->id, 'moveTo' => $moveDownPosition)),
    );
  }

  /**
   * Enables content for mobile siteindex, copies all default siteindex contents
   * as draft for this siteindex.
   */
  private function _updateUseContent()
  {
    $post = new Input(Input::SOURCE_POST);
    if (!$post->exists('si_mobile_use_content')) {
      return;
    }
    else if ($this->_mainModel->text) { // already activated
      return;
    }

    $this->_copyContentFromNormalSiteindexIfPossible();

    // save status enabled in mc_siteindex_compendium_mobile "main" row
    $this->_mainModel->text = 1;
    $this->_mainModel->update();
  }

  /**
   * Disables content for mobile siteindex, removes all data
   */
  private function _updateRemoveContent()
  {
    $post = new Input(Input::SOURCE_POST);
    if (!$post->exists('si_mobile_remove_content')) {
      return;
    }
    else if (!$this->_mainModel->text) { // already deactivated
      return;
    }

    $sql = " SELECT SIID, SIImage1, SIImage2, SIImage3 "
         . " FROM {$this->table_prefix}module_siteindex_compendium "
         . " WHERE FK_SID = '{$this->site_id}' "
         . "   AND SIType = '{$this->getType()}' ";
    $row = $this->db->GetRow($sql);

    unlinkIfExists('../' . $row['SIImage1']);
    unlinkIfExists('../' . $row['SIImage2']);
    unlinkIfExists('../' . $row['SIImage3']);

    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium "
         . " SET SITitle       = '', "
         . "     SIImage1      = '', "
         . "     SIImage2      = '', "
         . "     SIImage3      = '', "
         . "     SIText1       = '', "
         . "     SIText2       = '', "
         . "     SIText3       = '', "
         . "     SIImageTitles = '', "
         . "     FK_CIID       = '' "
         . " WHERE SIID = {$row['SIID']} ";
    $this->db->query($sql);

    $sql = " SELECT SAID, SAImage "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area "
         . " WHERE FK_SID = '{$this->site_id}' "
         . "   AND SASiteindexType = '{$this->getType()}' "
         . " ORDER BY SAPosition ASC ";
    $result = $this->db->query($sql);

    while ($areaRow = $this->db->fetch_row($result)) {
      unlinkIfExists('../' . $areaRow['SAImage']);

      $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area "
           . " SET SATitle    = '', "
           . "     SAText     = '', "
           . "     SAImage    = '', "
           . "     SADisabled = '0', "
           . "     FK_CIID    = '0' "
           . " WHERE SAID = {$areaRow['SAID']} ";
      $this->db->query($sql);

      $sql = " SELECT SBID, SBImage1, SBImage2, SBImage3 "
           . " FROM {$this->table_prefix}module_siteindex_compendium_area_box "
           . " WHERE FK_SAID = '{$areaRow['SAID']}' "
           . " ORDER BY SBPosition ASC ";
      $result1 = $this->db->query($sql);

      while ($boxRow = $this->db->fetch_row($result1)) {
        unlinkIfExists('../' . $boxRow['SBImage1']);
        unlinkIfExists('../' . $boxRow['SBImage2']);
        unlinkIfExists('../' . $boxRow['SBImage3']);
        $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area_box "
             . " SET SBTitle1         = '', "
             . "     SBTitle2         = '', "
             . "     SBTitle3         = '', "
             . "     SBText1          = '', "
             . "     SBText2          = '', "
             . "     SBText3          = '', "
             . "     SBImage1         = '', "
             . "     SBImage2         = '', "
             . "     SBImage3         = '', "
             . "     SBNoImage        = '0', "
             . "     SBPositionLocked = '0', "
             . "     SBDisabled       = '0', "
             . "     FK_CIID          = '0' "
             . " WHERE SBID = '{$boxRow['SBID']}' ";
        $this->db->query($sql);
      }
    }

    // save status enabled in mc_siteindex_compendium_mobile "main" row
    $this->_mainModel->text = 0;
    $this->_mainModel->update();
  }

  /**
   * Updates item content
   */
  private function _updateItem()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    if (!$post->exists('process_si_mobile_button')) {
      return;
    }

    $id = $post->readKey('process_si_mobile_button');
    $text = $post->readString("si_mobile_button{$id}_text", Input::FILTER_PLAIN);

    $model = new SiteindexCompendiumMobile($this->db, $this->table_prefix);
    $model = $model->readItemById($id);

    if ($model->id == $id && $text) {
      $model->text = $text;
      $model->update();
      $this->setMessage(Message::createSuccess($_LANG['si_mobile_item_message_update_success']));
    }
    else if (!$text) {
      $this->setMessage(Message::createFailure($_LANG['si_mobile_item_message_update_missing_text']));
    }
  }

  /**
   * Change the requested items activation staus if the following $_GET parameters
   * are set
   *   - changeItemActivationID
   *   - changeItemActivationTo
   */
  private function _changeItemActivation()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $id = $get->readInt('changeItemActivationID');
    $type = $get->readString('changeItemActivationTo', Input::FILTER_NONE);

    if (!$id || !$type) {
      return;
    }

    switch ( $type ) {
      case ContentBase::ACTIVATION_ENABLED:
        $to = 1;
        break;
      case ContentBase::ACTIVATION_DISABLED:
        $to = 0;
        break;
      default: return; // invalid activation status
    }

    $model = $this->_mainModel->readItemById($id);

    if ($model->active == $to) {
      return;
    }

    // Enabling an additional item is not allowed
    if (   $type == ContentBase::ACTIVATION_ENABLED
        && $this->_isMaximumAmountOfActiveItemsReached()
    ) {
      $msg = sprintf($_LANG['si_mobile_item_message_maximum_activated'],
                     $this->_getMaximumAllowedActiveItems());
      $this->setMessage(Message::createFailure($msg));
      return;
    }

    if ($model->id == $id) {
      $model->active = $to;
      $model->update();
      $this->setMessage(Message::createSuccess($_LANG['si_mobile_item_message_activation_' . $type]));
    }
  }

  /**
   * Moves the requested item if the $_GET parameters moveID and moveTo are set
   */
  private function _moveItem()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $moveID = $get->readInt('moveID');
    $moveTo = $get->readInt('moveTo');
    if (!$moveID || !$moveTo) {
      return;
    }

    $moved = $this->_getPositionHelper()->move($moveID, $moveTo);
    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['si_mobile_item_message_move_success']));
    }
    else {
      $this->setMessage(Message::createFailure($_LANG['si_mobile_item_message_move_error']));
    }
  }

  /**
   * @return PositionHelper
   */
  private function _getPositionHelper()
  {
    if ($this->_positionHelper === null) {
      $table = "{$this->table_prefix}module_siteindex_compendium_mobile";
      $this->_positionHelper = new PositionHelper($this->db, $table, 'SIMID',
          'SIMPosition', 'FK_SIMID', $this->_mainModel->id);
    }

    return $this->_positionHelper;
  }

  /**
   * @return int
   */
  private function _getMaximumAllowedActiveItems()
  {
    return (int)ConfigHelper::get('si_mobile_max_items', '', $this->site_id);
  }

  /**
   * @return bool
   */
  private function _isMaximumAmountOfActiveItemsReached()
  {
    $existing =  $this->_mainModel->read(array(
        'where' => "FK_SIMID = '{$this->_mainModel->id}' AND SIMActive = 1"
    ));

    if ($existing->count() >= $this->_getMaximumAllowedActiveItems()) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * @return bool
   */
  private function _usesNormalSiteindexConfiguration()
  {
    if (   !ConfigHelper::exists('si_mobile_number_of_boxes')
        && !ConfigHelper::exists('si_mobile_type_of_boxes')
    ) {
      return true;
    }
    else {
      return false;
    }
  }

  private function _copyContentFromNormalSiteindexIfPossible()
  {
    if (!$this->_usesNormalSiteindexConfiguration()) {
      return;
    }

    $defaultType = ConfigHelper::get('si_type', '', $this->site_id);

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}module_siteindex_compendium "
         . " WHERE FK_SID = '{$this->site_id}' "
         . "   AND SIType = '{$defaultType}' ";
    $original = $this->db->GetRow($sql);

    $sql = " SELECT SIID "
         . " FROM {$this->table_prefix}module_siteindex_compendium "
         . " WHERE FK_SID = '{$this->site_id}' "
         . "   AND SIType = '{$this->getType()}' ";
    $id = $this->db->GetOne($sql);

    $image1 = '';
    $image2 = '';
    $image3 = '';
    if (is_file('../' . $original['SIImage1'])) {
      $image1 = $this->_storeImage('../' . $original['SIImage1'], null, $this->_getImagePrefix(), 1, $this->site_id, false, false, '', false, false);
    }
    if (is_file('../' . $original['SIImage2'])) {
      $image2 = $this->_storeImage('../' . $original['SIImage2'], null, $this->_getImagePrefix(), 2, $this->site_id, false, false, '', false, false);
    }
    if (is_file('../' . $original['SIImage3'])) {
      $image3 = $this->_storeImage('../' . $original['SIImage3'], null, $this->_getImagePrefix(), 3, $this->site_id, false, false, '', false, false);
    }

    $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium "
         . " SET SITitle       = '{$original['SITitle']}', "
         . "     SIImage1      = '{$image1}', "
         . "     SIImage2      = '{$image2}', "
         . "     SIImage3      = '{$image3}', "
         . "     SIText1       = '{$original['SIText1']}', "
         . "     SIText2       = '{$original['SIText2']}', "
         . "     SIText3       = '{$original['SIText3']}', "
         . "     SIImageTitles = '{$original['SIImageTitles']}', "
         . "     FK_CIID       = '{$original['FK_CIID']}' "
         . " WHERE SIID = $id ";
    $this->db->query($sql);

    $sql = " SELECT * "
         . " FROM {$this->table_prefix}module_siteindex_compendium_area "
         . " WHERE FK_SID = '{$this->site_id}' "
         . "   AND SASiteindexType = '{$defaultType}' "
         . " ORDER BY SAPosition ASC ";
    $result = $this->db->query($sql);

    while ($original = $this->db->fetch_row($result)) {
      $originalAreaId = $original['SAID'];
      $sql = " SELECT SAID, SAPosition "
           . " FROM {$this->table_prefix}module_siteindex_compendium_area "
           . " WHERE FK_SID = '{$this->site_id}' "
           . "   AND SASiteindexType = '{$this->getType()}' "
           . "   AND SAPosition = '{$original['SAPosition']}' ";
      $row = $this->db->GetRow($sql);
      $targetAreaId = $row['SAID'];
      $targetAreaPosition = $row['SAPosition'];

      $image = '';
      if (is_file('../' . $original['SAImage'])) {
          $image = $this->_storeImage('../' . $original['SAImage'], null, $this->getAreaImagePrefix($targetAreaPosition), 1, array($this->site_id, $targetAreaId), false, false, '', false, false);
      }
      $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area "
           . " SET SATitle    = '{$original['SATitle']}', "
           . "     SAText     = '{$original['SAText']}', "
           . "     SAImage    = '{$image}', "
           . "     SABoxType  = '{$original['SABoxType']}', "
           . "     SADisabled = '{$original['SADisabled']}', "
           . "     FK_CIID    = '{$original['FK_CIID']}' "
           . " WHERE SAID = $targetAreaId ";
      $this->db->query($sql);

      $sql = " SELECT * "
           . " FROM {$this->table_prefix}module_siteindex_compendium_area_box "
           . " WHERE FK_SAID = '$originalAreaId' "
           . " ORDER BY SBPosition ASC ";
      $result1 = $this->db->query($sql);

      while ($row1 = $this->db->fetch_row($result1)) {
        $boxId = $row1['SBID'];
        $boxPosition = $row1['SBPosition'];

        $sql = " SELECT SBID "
             . " FROM {$this->table_prefix}module_siteindex_compendium_area_box "
             . " WHERE FK_SAID = $targetAreaId "
             . "   AND SBPosition = $boxPosition ";
        $targetBoxId = $this->db->GetOne($sql);

        $image = '';
        if (is_file('../' . $row1['SBImage'])) {
            $image = $this->_storeImage('../' . $row1['SBImage'], null, $this->getAreaBoxImagePrefix($targetAreaPosition, $boxPosition), 1, array($this->site_id, $targetAreaId, $targetBoxId), false, false, '', true, false);
        }
        $sql = " UPDATE {$this->table_prefix}module_siteindex_compendium_area_box "
             . " SET SBTitle          = '{$row1['SBTitle']}', "
             . "     SBText           = '{$row1['SBText']}', "
             . "     SBImage          = '{$image}', "
             . "     SBNoImage        = '{$row1['SBNoImage']}', "
             . "     SBPositionLocked = '{$row1['SBPositionLocked']}', "
             . "     SBDisabled       = '{$row1['SBDisabled']}', "
             . "     FK_CIID          = '{$row1['FK_CIID']}' "
             . " WHERE SBID = '$targetBoxId' ";
        $this->db->query($sql);
      }
    }
  }
}