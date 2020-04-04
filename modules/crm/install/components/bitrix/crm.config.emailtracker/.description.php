<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => getMessage('CRM_EMAILTRACKER_NAME'),
	'DESCRIPTION' => getMessage('CRM_MAILTRACKER_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 800,
	'CACHE_PATH' => 'Y',
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => getMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'config',
			'NAME' => getMessage('CRM_CONFIG_NAME'),
		)
	),
);
