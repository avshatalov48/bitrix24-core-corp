<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_PERMS_ROlE_EDIT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_PERMS_ROlE_EDIT_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 30,
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
        'SORT' => 10,
		'CHILD' => array(
			'ID' => 'config',
            'SORT' => 20,
			'NAME' => GetMessage('CRM_CONFIG_NAME'),
    		'CHILD' => array(
    			'ID' => 'config_perms',
                'SORT' => 20
            )
        )
	),
	'CACHE_PATH' => 'Y'
);
?>