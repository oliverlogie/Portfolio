<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2012-03-13 09:20:59 +0100 (Di, 13 Mär 2012) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ContentItemBE extends ContentItemLogical
{
  protected $_configPrefix = 'be';
  protected $_contentPrefix = 'be';
  protected $_columnPrefix = 'B';
  protected $_contentElements = array(
    'Title' => 1,
    'Text'  => 1,
    'Image' => 1,
  );
  protected $_templateSuffix = 'BE';
}
