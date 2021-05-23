<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// make template`s checklist items sutable for bitrix:tasks.task.detail.parts to swallow
if(is_array($arResult['DATA']) && is_array($arResult['DATA']['CHECKLIST_ITEMS']))
{
	foreach($arResult['DATA']['CHECKLIST_ITEMS'] as &$item)
	{
		if(is_array($item))
		{
			$keys = array();
			foreach($item as $fld => $value)
			{
				$keys[] = $fld;
			}

			foreach($keys as $key)
			{
				if(!isset($item['~'.$key]))
					$item['~'.$key] = $item[$key];
			}

			$item['IS_COMPLETE'] = $item['CHECKED'] == '1' ? 'Y' : 'N';
			if(!isset($item['ID']))
				$item['ID'] = 'task-detail-checklist-item-xxx_'.rand(0,999999); // newly created item, ID should be defined anyway
		}
	}
	unset($item);
}

$arResult['RESPONSIBLE_NAME_FORMATTED'] =	($arResult['DATA']["RESPONSIBLE_NAME"] || $arResult['DATA']["RESPONSIBLE_LAST_NAME"] || $arResult['DATA']["RESPONSIBLE_LOGIN"] ? CUser::FormatName($arParams["NAME_TEMPLATE"], array("NAME" => $arResult['DATA']["RESPONSIBLE_NAME"], "LAST_NAME" => $arResult['DATA']["RESPONSIBLE_LAST_NAME"], "LOGIN" => $arResult['DATA']["RESPONSIBLE_LOGIN"], "SECOND_NAME" => $arResult['DATA']["RESPONSIBLE_SECOND_NAME"]), true, false) : "");
$arResult['CREATED_BY_NAME_FORMATTED'] =	($arResult['DATA']["CREATED_BY_NAME"] || $arResult['DATA']["CREATED_BY_LAST_NAME"] || $arResult['DATA']["CREATED_BY_LOGIN"] ? CUser::FormatName($arParams["NAME_TEMPLATE"], array("NAME" => $arResult['DATA']["CREATED_BY_NAME"], "LAST_NAME" => $arResult['DATA']["CREATED_BY_LAST_NAME"], "LOGIN" => $arResult['DATA']["CREATED_BY_LOGIN"], "SECOND_NAME" => $arResult['DATA']["CREATED_BY_SECOND_NAME"]), true, false) : "");

$arResult['USER_CREATE_TEMPLATE'] = $arResult['DATA']['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER;

$arResult['CSS_MODES'] = array();
if(intval($arResult['DATA']['BASE_TEMPLATE_ID']))
	$arResult['CSS_MODES'][] = 'state-base-template-choosen';
if($arResult['USER_CREATE_TEMPLATE'])
	$arResult['CSS_MODES'][] = 'state-user-create-template';

$arResult['RESPONSIBLE_DISABLED'] = $arResult['DATA']["CREATED_BY"] != $USER->GetID() || $arResult['USER_CREATE_TEMPLATE'];
$arResult['BX24_MODE'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');