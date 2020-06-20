<?php

/*
 * This file is part of a BugBuster Contao Bundle
 *
 * @copyright  Glen Langer 2020 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Online-Bundle
 * @license    LGPL-3.0-or-later
 * @see        https://github.com/BugBuster1701/contao-online-bundle
 */

/**
 * Table tl_online_session
 */
$GLOBALS['TL_DCA']['tl_online_session'] = [

	// Config
	'config' => [
		'sql' => [
			'keys' => [
				'id' => 'primary',
				'pid' => 'index',
				'hash' => 'index'
			],
		],
	],

	// Fields
	'fields' => [
		'id' => [
			'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]
		],
		'pid' => [
			'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
		],
		'tstamp' => [
			'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
		],
		'name' => [
			'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
		],
		'hash' => [
			'sql' => ['type' => 'string', 'length' => 64, 'notnull' => false]
		],
	],
];
