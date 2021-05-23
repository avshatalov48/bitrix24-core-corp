<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

$arComponentDescription = array(
	'NAME' => Loc::getMessage('CRM_PRESET_LIST_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_PRESET_LIST_DESCRIPTION'),
	'ICON' => '/images/icon.gif',
	'SORT' => 50,
	'CACHE_PATH' => 'Y',
	'PATH' => array(
		'ID' => 'crm',
		'NAME' => Loc::getMessage('CRM_NAME'),
		'CHILD' => array(
			'ID' => 'config',
			'NAME' => Loc::getMessage('CRM_CONFIG_NAME'),
			'CHILD' => array(
				'ID' => 'config_preset',
				'SORT' => 20
			)
		)
	),
);

?>