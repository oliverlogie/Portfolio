<?php

/**
 * Activation clock interface constants collection
 *
 * $LastChangedDate: 2014-12-10 15:29:30 +0100 (Mi, 10 Dez 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2014 Q2E GmbH
 */
interface ActivationClockInterface
{
  /**
   * The green clock activation light indicates that the timing function is enabled
   * for the item itself or one of its parents.
   *
   * NOTE: It indicates that the content item is 'enabled'
   *
   * @var string
   */
  const GREEN = 'clock';

  /**
   * The orange clock indicates that the timing function of a parent disables
   * this item.
   *
   * @var string
   */
  const ORANGE = 'clock_orange';

  /**
   * The red clock indicates that the element is inactive, due to its timing
   * settings.
   *
   * @var string
   */
  const RED = 'clock_red';
}