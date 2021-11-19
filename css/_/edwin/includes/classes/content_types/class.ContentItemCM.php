<?php

/**
 * Content class.
 *
 * Attaches a configured lead management form to content if $_CONFIG['cm_campaign']
 * is set to a non-zero value:
 *
 * Note, that $_CONFIG['cm_campaign'] is usually is different from actual
 * contentitem's campaign id.
 *
 * If die configured campaign id matches a campaign, available on this
 * contentitem's site, 'cm_campaign' is equal to ContentItemCM::$_campaign,
 * otherwise the configured campaign is only used as
 * parent campaign ( defines config ) whereas the actual campaign
 * ContentItemCM::$_campaign is created by the ContentItemCM::_checkDataBase()
 * method from this contentitem itself.
 *
 * ***************************************************************************
 *
 * I.e.
 *
 * Campaign 1 with FK_SID = 1
 * $_CONFIG['cm_campaign'][0] = 1;
 *
 * the actual campaign equals configured as on same site
 * > Site 1 > CM > campaign > 1
 *
 * the actual campaign is different for CM on site 2
 * > Site 2 > CM > creates campaign 2 with FK_SID = 2 and FK_CGID = 1
 *                 campaign > 2
 *
 * ***************************************************************************
 *
 * $LastChangedDate: 2018-02-22 16:13:41 +0100 (Do, 22 Feb 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class ContentItemCM extends ContentItem
{
  protected $_configPrefix = 'cm';
  protected $_contentPrefix = 'cm';
  protected $_columnPrefix = 'CM';
  protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
  );
  protected $_templateSuffix = 'CM';

  /**
   * The campaign the contentitem belongs to
   *
   * @var Campaign
   */
  private $_campaign = null;

  public function __construct($site_id, $page_id, Template $tpl, db $db, $table_prefix,
                              $action = '', $page_path = '', User $user = null,
                              Session $session = null, Navigation $navigation)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
                        $page_path, $user, $session, $navigation);

    $parentId = $this->getConfig('campaign', $this->site_id);
    $this->_campaign = new Campaign($this->db, $this->table_prefix);

    if ($parentId) {
      $sql = " SELECT FK_CGID "
           . " FROM {$this->table_prefix}contentitem_cm "
           . " WHERE FK_CIID = {$this->page_id} ";
      $cid = $this->db->GetOne($sql);

      // campaign already set for CM
      if ($cid) {
        $this->_campaign = $this->_campaign->readCampaignById($cid);
      }
    }
  }

  public function edit_content()
  {
    // Handle default content elements.
    parent::edit_content();

    if ($this->_campaign->id) {
      $post = new Input(Input::SOURCE_POST);
      $recipient = $post->readString('cm_recipient', Input::FILTER_PLAIN);

      // Update the database.
      $sql = " UPDATE {$this->table_prefix}campaign_contentitem "
           . " SET CGCCampaignRecipient = '{$this->db->escape($recipient)}' "
           . " WHERE FK_CIID = $this->page_id "
           . "   AND FK_CGID = {$this->_campaign->id} ";
      $this->db->query($sql);
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $row = $this->_getData();
    $recipient = $row['CGCCampaignRecipient'];

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_vars($tplName, array (
        'cm_recipient' => $recipient,
    ));

    return parent::get_content(array_merge($params, array(
        'row'      => $row,
        'settings' => array( 'tpl' => $tplName ),
    )));
  }

  public function preview()
  {
    $post = new Input(Input::SOURCE_POST);

    $image_titles = $post->readImageTitles('image_title');
    $image_titles = $this->explode_content_image_titles('c_cm',$image_titles);

    $images = $this->_createPreviewImages(array(
      'CMImage1' => 'cm_image1',
      'CMImage2' => 'cm_image2',
      'CMImage3' => 'cm_image3',
    ));
    $image_src1 = $images['cm_image1'];
    $image_src2 = $images['cm_image2'];
    $image_src3 = $images['cm_image3'];
    $image_src_large1 = $this->_hasLargeImage($image_src1);
    $image_src_large2 = $this->_hasLargeImage($image_src2);
    $image_src_large3 = $this->_hasLargeImage($image_src3);

    $this->tpl->set_tpl_dir("../templates");
    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('cm')
    ));
    $this->tpl->parse_if($tplName, 'zoom1', $image_src_large1, array(
      'c_cm_zoom1_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'zoom2', $image_src_large2, array(
      'c_cm_zoom2_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'zoom3', $image_src_large3, array(
      'c_cm_zoom3_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'image1', $image_src1, array( 'c_cm_image_src1' => $image_src1 ));
    $this->tpl->parse_if($tplName, 'image2', $image_src2, array( 'c_cm_image_src2' => $image_src2 ));
    $this->tpl->parse_if($tplName, 'image3', $image_src3, array( 'c_cm_image_src3' => $image_src3 ));
    $this->tpl->parse_vars($tplName, array_merge( $image_titles, array (
      'c_cm_title1' => parseOutput($post->readString('cm_title1', Input::FILTER_CONTENT_TITLE),2),
      'c_cm_title2' => parseOutput($post->readString('cm_title2', Input::FILTER_CONTENT_TITLE),2),
      'c_cm_title3' => parseOutput($post->readString('cm_title3', Input::FILTER_CONTENT_TITLE),2),
      'c_cm_text1' => parseOutput($post->readString('cm_text1', Input::FILTER_CONTENT_TEXT), 1),
      'c_cm_text2' => parseOutput($post->readString('cm_text2', Input::FILTER_CONTENT_TEXT), 1),
      'c_cm_text3' => parseOutput($post->readString('cm_text3', Input::FILTER_CONTENT_TEXT), 1),
      'c_cm_image_src1' => $image_src1,
      'c_cm_image_src2' => $image_src2,
      'c_cm_image_src3' => $image_src3,
      'main_mod_form' => '',
      'c_surl' => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
    $this->tpl->set_tpl_dir("./templates");
    return $content;
  }

  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,CMTitle1,CMTitle2,CMTitle3,CMText1,CMText2,CMText3,CMImageTitles FROM ".$this->table_prefix."contentitem_cm cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)){
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["CMTitle1"];
      $class_content[$row["CIID"]]["c_title2"] = $row["CMTitle2"];
      $class_content[$row["CIID"]]["c_title3"] = $row["CMTitle3"];
      $class_content[$row["CIID"]]["c_text1"] = $row["CMText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["CMText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["CMText3"];
      $ti_image_titles = $this->explode_content_image_titles("cm",$row["CMImageTitles"]);
      $class_content[$row["CIID"]]["c_image_title1"] = $ti_image_titles["cm_image1_title"];
      $class_content[$row["CIID"]]["c_image_title2"] = $ti_image_titles["cm_image2_title"];
      $class_content[$row["CIID"]]["c_image_title3"] = $ti_image_titles["cm_image3_title"];
      $class_content[$row["CIID"]]["c_sub"] = array();
    }
    $this->db->free_result($result);

    return $class_content;
  }

  protected function _checkDataBase()
  {
    parent::_checkDatabase();

    // campaign already set for CM, set in ContentItemCM::__construct()
    if ($this->_campaign->id) { return; }

    $parentId = $this->getConfig('campaign', $this->site_id);

    // as no campaign is configured for CM, we do not have to do further processing
    // here and return, so CM can be used without attached forms
    if (!$parentId) { return; }

    $campaign = new Campaign($this->db, $this->table_prefix);

    $cid = $this->_createIdFromParentId($parentId);
    $campaigns = $campaign->readCampaignByIds(array($cid, $parentId));

    try {
      // 2. campaign for CM exists, no creation required, we only have to set CM's
      //    FK_CGID value
      if ($campaigns->get($cid)->id) {
        $this->_campaign = $campaigns->get($cid);
      }
    }
    catch(Exception $e) { }

    if (!$this->_campaign->id) {

      try {

        // 3. parent ( = source = configured ) campaign is on same site as
        // CM, so it can be used as CM's campaign
        $parentCampaign = $campaigns->get($parentId);
        if ($parentCampaign->siteId == $this->site_id) {
          $this->_campaign = $parentCampaign;
        }
      }
      catch( Exception $e ) {
        trigger_error(__CLASS__ . ": Invalid configuration value for 'cm_campaign'.", E_USER_ERROR);
      }

      // 4. still no campaign found, so we have to create one
      if (!$this->_campaign->id) {
        $this->_createCampaign($parentCampaign);
      }
    }

    $sql = " INSERT INTO {$this->table_prefix}campaign_contentitem "
         . " (FK_CGID, FK_CIID) VALUES ({$this->_campaign->id}, {$this->page_id}) ";
    $this->db->query($sql);

    $sql = " UPDATE {$this->table_prefix}contentitem_cm "
         . " SET FK_CGID = {$this->_campaign->id} "
         . " WHERE FK_CIID = {$this->page_id} ";
    $this->db->query($sql);
  }

  protected function _getData()
  {
    // Create database entries.
    $this->_checkDataBase();

    foreach ($this->_contentElements as $type => $count) {
      for ($i = 1; $i <= $count; $i++) {
        $sqlArgs[] = $this->_getContentElementColumnName($type, $count, $i);
      }
    }

    $sql = ' SELECT ' . implode(', ', $this->_dataFields) . ', '
         . '        CGCCampaignRecipient '
         .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
         .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
         . " FROM {$this->table_prefix}contentitem ci "
         . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
         . '      ON CIID = ci_sub.FK_CIID '
         . " LEFT JOIN {$this->table_prefix}campaign_contentitem ca "
         . '      ON CIID = ca.FK_CIID '
         . " WHERE CIID = $this->page_id ";
    return $this->db->GetRow($sql);
  }

  /**
   * Creates new campaign for CM, and is called from ContentItemCM::_checkDataBase()
   * only.
   *
   * @param Campaign $parent
   *        The parent campaign CM's campaign refers to
   *
   * @return void
   */
  private function _createCampaign(Campaign $parent)
  {
    $typeId = $this->_createIdFromParentId($parent->typeId);
    $type = new CampaignType($this->db, $this->table_prefix);
    $types = $type->readCampaignTypeByIds(array($parent->typeId, $typeId));
    $parentType = $types->get($parent->typeId);

    try {
      $type = $types->get($typeId);
    }
    catch(Exception $e) {

      $type = new CampaignType($this->db, $this->table_prefix);
      $type->id = $typeId;
      $type->siteId = $this->site_id;
      $type->name = $parentType->name;
      $type->create();
    }

    $this->_campaign = new Campaign($this->db, $this->table_prefix);
    $cid = $this->_createIdFromParentId($parent->id);
    $this->_campaign->id = $cid;
    $this->_campaign->name = $parent->name;
    $this->_campaign->status = $parent->status;
    $this->_campaign->typeId = $type->id;
    $this->_campaign->siteId = $this->site_id;
    $this->_campaign->parentId = $parent->id;
    $this->_campaign->create();
  }

  /**
   * Creates an id from given parent id
   *
   * I.e. Assuming that this contentitem is on site 3 the following values are
   *      created:
   *      - ContentItemCM::_createIdFromParentId(1001) => 3001
   *      - ContentItemCM::_createIdFromParentId(3) => 3003
   *
   * @param int $id
   *        the parent id i.e. 1, 2, 2001
   *
   * @return string
   *         the id
   */
  private function _createIdFromParentId($id)
  {
    if (mb_strlen($id) > 3) {
      $id = (int)mb_substr($id, 1);
    }
    $id = $this->site_id . sprintf("00%d", $id);

    return $id;
  }
}