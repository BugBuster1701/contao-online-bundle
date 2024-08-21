<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2024 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Online Bundle
 * @link       https://github.com/BugBuster1701/contao-online-bundle
 *
 * @license    LGPL-3.0-or-later
 */

/*
 * Table tl_online_session
 */
$GLOBALS['TL_DCA']['tl_online_session'] = array(
	// Config
	'config' => array(
		'sql' => array(
			'keys' => array(
				'id' => 'primary',
				'pid' => 'index'
			),
		),
	),

	// Fields
	'fields' => array(
		'id' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'autoincrement' => true)
		),
		'pid' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0)
		),
		'tstamp' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0)
		),
		'instanceof' => array(
			'sql' => array('type' => 'string', 'length' => 255, 'default' => '')
		),
		'loginhash' => array(
			'sql' => array('type' => 'string', 'length' => 64, 'notnull' => false)
		),
	),
);
