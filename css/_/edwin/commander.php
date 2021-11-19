<?php

/**
 * Commander script for running EDWIN CMS commands (= scripts for various tasks)
 *
 * $LastChangedDate: 2020-02-28 09:34:18 +0100 (Fr., 28 Feb 2020) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2020 Q2E GmbH
 */

// include frontend bootstrap file to make everything work as expected
include __DIR__ . '/../includes/bootstrap.php';

ConfigHelper::set('site_id', ConfigHelper::get('site_id') ?? 1);

// The command token is required as some kind of security measure when calling
// commands via URL.
// NOTE: access without $_CONFIG['m_command_token'] is defacto disabled by
//       expecting a unique id as token
define('__COMMAND_TOKEN', ConfigHelper::get('m_command_token') ?: uniqid());

// Execute the command.
if (ed_is_cli()) {
  $setup = new \Core\Services\Commander\Setup\CliSetup(new Core\Services\Commander\Output\ConsoleOutput());
}
else {
  $setup = new \Core\Services\Commander\Setup\WebSetup(new Core\Services\Commander\Output\WebOutput());
}

$setup->run();