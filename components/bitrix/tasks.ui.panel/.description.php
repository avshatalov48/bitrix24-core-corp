<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('TASKS_NAME'),
	'DESCRIPTION' => GetMessage('TASKS_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'COMPLEX' => 'N',
	'PATH' => array(
		'ID' => 'content',
		'CHILD' => array(
			'ID' => 'TASKS',
			'NAME' => GetMessage('TASKS_NAME_2_0')
		)
	),
);