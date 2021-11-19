<?php

/**
 * Database Connection
 *
 * $LastChangedDate: 2017-02-14 11:59:09 +0100 (Di, 14 Feb 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */

switch(ConfigHelper::get('dbms')) {
  case "mysql": require_once ConfigHelper::get('INCLUDE_DIR') . '../includes/core/class.db.mysql.php';
    break;
}

$db = new Db(
    ConfigHelper::get('dbhost'),
    ConfigHelper::get('dbuser'),
    ConfigHelper::get('dbpasswd'),
    ConfigHelper::get('dbname'),
    ConfigHelper::get('dbcharset'),
    ConfigHelper::get('table_prefix')
);

if (!$db->is_connected()){
  die("Could not connect to the database.</br>".$db->get_error());
}

$db->throwQueryExceptions(mb_stristr(ConfigHelper::get('DEBUG_SQL'), 'strict') !== false);

$factory = new \Core\Db\Loggers\LoggerFactory(ConfigHelper::get('table_prefix'));
$db->setLogger($factory->make(ConfigHelper::get('DEBUG_SQL')));