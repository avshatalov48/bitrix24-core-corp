<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

return array(
	'block' => array(
		'name' => Loc::getMessage('LANDING_BLOCK_FORM_33.14'),
		'section' => array('sidebar'),
		'dynamic' => false,
		'subtype' => 'form',
		'type' => ['page', 'store', 'smn'],
	),
	'nodes' => array(),
	'style' => array(
		'block' => [
			'type' => ['block-default', 'block-border'],
		],
		'nodes' => [],
	),
	'assets' => array(
		'ext' => array('landing_form'),
	),
);