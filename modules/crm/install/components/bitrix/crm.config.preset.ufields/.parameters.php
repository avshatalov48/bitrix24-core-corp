<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

if(!CModule::IncludeModule('crm'))
	return false;

$entityTypes = \Bitrix\Crm\EntityPreset::getEntityTypes();
$entityTypeList = array();
foreach($entityTypes as $key => $ar)
	$entityTypeList[$key] = $ar['NAME'];
				
			
$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'ENTITY_TYPE_ID' => array(
			'PARENT' => 'ADDITIONAL_SETTINGS',
			'NAME' => Loc::getMessage('CRM_PRESET_ENTITY_TYPE_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $entityTypeList,
			'DEFAULT' => \Bitrix\Crm\EntityPreset::Requisite,
		),
		'PRESET_LIST_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => Loc::getMessage('CRM_PRESET_LIST_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'list.php?entity_type=#entity_type#',
		),
		'PRESET_UFIELDS_URL' => array(
			'PARENT' => 'URL_TEMPLATES',
			'NAME' => Loc::getMessage('CRM_PRESET_UFIELDS_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'ufields.php?entity_type=#entity_type#',
		),
	),
);
?>
