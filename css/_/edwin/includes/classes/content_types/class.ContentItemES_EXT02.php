<?php

/**
 * External Source - YouTube Channel (Google API)
 *
 * For Youtube API information see:
 * https://developers.google.com/youtube/v3/docs
 *
 * $LastChangedDate: 2017-08-18 14:13:33 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemES_EXT02 extends ContentItemES {

  /**
   * Parses the properties from the Form Input
   * @return array contains the properties values
   */
  protected function _parse_properties_input()
  {
    $this->ext_id = "02";

    $properties = parent::_parse_properties_input();

    // validate property input
    if ((int)$properties[6] > 20) $properties[6] = 20;
    if ((int)$properties[6] < 1) $properties[6] = 1;

    return $properties;
  }

  /**
   * Loads the properties from the DB
   * @param int $ext_id id of the external source (optional)
   * @return array contains the template vars with the labels and values of all properties
   */
  protected function _load_properties_db($ext_id = 0)
  {
    global $_LANG2;

    $this->ext_id = "02";

    $lang_vars = parent::_load_properties_db($this->ext_id);

    // create source type select
    $tmp_type = '<select id="es_property1_value" name="es_properties[1]" class="form-control"  onChange="if (this.options[this.selectedIndex].value > 0){ change_es_property(1,this.options[this.selectedIndex].value); }">>';
    foreach ($_LANG2["es_ext".$this->ext_id]["es_property1_labels"] as $eid => $evalue){
      $tmp_type .= '<option value="'.$eid.'"';
      if (isset($lang_vars["es_property1"]) && $lang_vars["es_property1"] == $eid) $tmp_type .= ' selected="selected"';
      $tmp_type .= '>'.$evalue.'</option>';
    }
    $lang_vars["es_property1"] = $tmp_type."</select>";

    // preset for quantity
    $prefix = array_unique(array(
      $this->_configPrefix . '_ext02',
      $this->_contentPrefix . '_ext02',
    ));
    $preset = (int)ConfigHelper::get('property_preset', $prefix);

    // create order select
    $tmp_order = '<select id="es_property7_value" name="es_properties[7]" class="form-control">';
    foreach ($_LANG2["es_ext".$this->ext_id]["es_property7_labels"] as $eid => $evalue){
      $tmp_order .= '<option value="'.$eid.'"';
      if (isset($lang_vars["es_property7"]) && $lang_vars["es_property7"] == $eid) $tmp_order .= ' selected="selected"';
      $tmp_order .= '>'.$evalue.'</option>';
    }
    $lang_vars["es_property7"] = $tmp_order."</select>";
    // create language select
    $languages = isset($_LANG2["es_ext".$this->ext_id]["es_property8_labels"][$this->site_id]) ?
                       $_LANG2["es_ext".$this->ext_id]["es_property8_labels"][$this->site_id] :
                       $_LANG2["es_ext".$this->ext_id]["es_property8_labels"][0];
    $tmp_order = '<select id="es_property8_value" name="es_properties[8]" class="form-control">';
    foreach ($languages as $eid => $evalue){
      $tmp_order .= '<option value="'.$eid.'"';
      if (isset($lang_vars["es_property8"]) && $lang_vars["es_property8"] == $eid) $tmp_order .= ' selected="selected"';
      $tmp_order .= '>'.$evalue.'</option>';
    }
    $lang_vars["es_property8"] = $tmp_order."</select>";

    return $lang_vars;
  }
}

