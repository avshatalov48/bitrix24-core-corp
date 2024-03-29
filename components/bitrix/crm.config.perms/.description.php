<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arComponentDescription = array(
	'NAME' => GetMessage('CRM_PERMS_NAME'),
	'DESCRIPTION' => GetMessage('CRM_PERMS_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 10,
	'CACHE_PATH' => 'Y',
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => GetMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'config',
			'NAME' => GetMessage('CRM_CONFIG_NAME'),
    		'CHILD' => array(
    			'ID' => 'config_perms',
                'SORT' => 10
            )
        )
	),
);
