<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('voximplant'))
{
	ShowError(GetMessage('VOXIMPLANT_MODULE_NOT_INSTALLED'));
	return;
}

$arResult = array(
	'ACTIVITY' => $arParams['~ACTIVITY']
);
$activity = $arResult['ACTIVITY'];

$call = Bitrix\VoxImplant\StatisticTable::getList(array(
	'select' => array('*'),
	'filter' => array(
		'=CALL_ID' => $arParams['CALL_ID']
	)
))->fetch();

$arResult["RECORDS"] = array();
if (is_array($activity["STORAGE_ELEMENT_IDS"]) && count($activity["STORAGE_ELEMENT_IDS"]) > 0)
{
	$mediaExtensions = array("flv", "mp3", "mp4", "vp6", "aac");
	foreach($activity["STORAGE_ELEMENT_IDS"] as $elementID)
	{
		$info = Bitrix\Crm\Integration\StorageManager::getFileInfo(
			$elementID, $activity["STORAGE_TYPE_ID"],
			false,
			array('OWNER_TYPE_ID' => CCrmOwnerType::Activity, 'OWNER_ID' => $activity['ID'])
		);
		if(is_array($info) && in_array(GetFileExtension(strtolower($info["NAME"])), $mediaExtensions))
		{
			$recordUrl = CCrmUrlUtil::ToAbsoluteUrl($info["VIEW_URL"]);
			if($activity["STORAGE_TYPE_ID"] == CCrmActivityStorageType::WebDav)
			{
				//Hacks for flv player
				if(substr($recordUrl, -1) !== "/")
				{
					$recordUrl .= "/";
				}
				$recordUrl .= !empty($info["NAME"]) ? $info["NAME"] : "dummy.flv";
			}
			$arResult["RECORDS"][] = array(
				"URL" =>$recordUrl,
				"NAME" => $info["NAME"],
				"INFO" => $info
			);
		}
		$arResult["STORAGE_ELEMENTS"][] = $info;
	}
}

if($call !== false)
{
	$arResult['CALL'] = CVoxImplantHistory::PrepereData($call);

	if ($arResult['CALL']['INCOMING'] == CVoxImplantMain::CALL_INCOMING)
		$arResult['CALL']['CALL_TYPE_TEXT'] = GetMessage('CRM_ACTIVITY_CALL_VI_INCOMING_CALL');
	else if ($arResult['CALL']['INCOMING'] == CVoxImplantMain::CALL_INCOMING_REDIRECT)
		$arResult['CALL']['CALL_TYPE_TEXT'] = GetMessage('CRM_ACTIVITY_CALL_VI_INCOMING_REDIRECT_CALL');
	else if ($arResult['CALL']['INCOMING'] == CVoxImplantMain::CALL_OUTGOING)
		$arResult['CALL']['CALL_TYPE_TEXT'] = GetMessage('CRM_ACTIVITY_CALL_VI_OUTGOING_CALL');
	else if ($arResult['CALL']['INCOMING'] == CVoxImplantMain::CALL_CALLBACK)
		$arResult['CALL']['CALL_TYPE_TEXT'] = GetMessage('CRM_ACTIVITY_CALL_VI_CALLBACK_CALL');

}

$this->IncludeComponentTemplate();
return $arResult;
