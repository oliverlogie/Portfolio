<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2014-03-06 08:37:21 +0100 (Do, 06 Mrz 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemES_EXT03 extends ContentItemES {

/**
   * Parses the properties from the Form Input
   * @return array contains the properties values
   */
  protected function _parse_properties_input() {

    $this->ext_id = "03";

    $properties = parent::_parse_properties_input();

    return $properties;
  }

  /**
   * Loads the properties from the DB
   * @param int $ext_id id of the external source (optional)
   * @return array contains the template vars with the labels and values of all properties
   */
  protected function _load_properties_db($ext_id = 0) {
    global $_LANG2;

    $this->ext_id = "03";

    $lang_vars = parent::_load_properties_db($this->ext_id);

    // preset for quantity
    $prefix = array_unique(array(
      $this->_configPrefix . '_ext03',
      $this->_contentPrefix . '_ext03',
    ));
    $preset = ConfigHelper::get('property_preset', $prefix);
    if (empty($lang_vars["es_property1"])) $lang_vars["es_property1"] = $preset;

    return $lang_vars;
  }
}

