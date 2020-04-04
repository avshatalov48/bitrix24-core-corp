<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("iblock")):
	ShowError(GetMessage("F_NO_MODULE_IBLOCK"));
	return 0;
elseif (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("F_NO_MODULE_WEBDAV"));
	return 0;
endif;

if(CModule::IncludeModule("disk") && \Bitrix\Disk\Configuration::isSuccessfullyConverted())
{
	return 0;
}

$path = dirname(__FILE__);
include_once($path . '/functions.php');

$componentPage = 'edit';
/********************************************************************
				Input params
********************************************************************/

$arParams["EDIT"] = (($arParams["EDIT"] == 'Y') ? $arParams["EDIT"] : 'N');
$arParams["PARAMS"] = (is_array($arParams["PARAMS"]) ? $arParams["PARAMS"] : array());
$arParams["RESULT"] = (is_array($arParams["RESULT"]) ? $arParams["RESULT"] : array());
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")):$arParams["DATE_TIME_FORMAT"]);
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams["PARAMS"]['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'];
/********************************************************************
				/Input params
********************************************************************/

$arResult['UID'] = strtolower($arParams['PARAMS']['arUserField']['ENTITY_ID'])."_".$this->randString(6);
$arResult['allowExtDocServices'] = CWebDavTools::allowUseExtServiceGlobal();
$arResult['allowCreateDocByExtServices'] = CWebDavTools::allowCreateDocByExtServiceGlobal();

if(!empty($arParams['PARAMS']['arUserField']['ENTITY_ID']))
{
 	if($arParams['PARAMS']['arUserField']['ENTITY_ID'] == 'BLOG_COMMENT')
	{
		$arResult['URL_TO_POST'] = $arParams['PARAMS']['arUserField']['URL_TO_POST'];
		$arResult['ID_TO_POST'] = $arParams['PARAMS']['arUserField']['POST_ID'];

		if($arResult['IS_HISTORY_DOC'] = $arParams['PARAMS']['arUserField']['USER_TYPE_ID'] == 'webdav_element_history')
		{
			$arResult['HISTORY_DOC'] = CUserTypeWebdavElementHistory::getDataFromValue($arParams['PARAMS']['arUserField']['VALUE']);
			$arResult['HISTORY_DOC'] = array_merge($arResult['HISTORY_DOC'][0], $arResult['HISTORY_DOC'][1]);
		}
	}
	if($arParams['PARAMS']['arUserField']['ENTITY_ID'] == 'BLOG_POST')
	{
		$arResult['URL_TO_POST'] = $arParams['PARAMS']['arUserField']['URL_TO_POST'];
		$arResult['ID_TO_POST'] = $arParams['PARAMS']['arUserField']['POST_ID'];
	}
}
if ($arParams['EDIT'] == 'Y')
{
	if($arParams['PARAMS']['arUserField']['ENTITY_ID'] == 'BLOG_POST')
	{
		$arResult['showCheckboxToAllowEdit']= $arResult['allowExtDocServices'] && !empty($arParams['PARAMS']['arUserField']['SETTINGS']['UF_TO_SAVE_ALLOW_EDIT']);
		if($arResult['showCheckboxToAllowEdit'])
		{
			global $USER_FIELD_MANAGER;
			$arResult['ufToSaveAllowEdit'] = array();
			$arResult['ufToSaveAllowEdit']['FIELD'] = $arParams['PARAMS']['arUserField']['SETTINGS']['UF_TO_SAVE_ALLOW_EDIT'];
			$arResult['ufToSaveAllowEdit']['VALUE'] = $USER_FIELD_MANAGER->GetUserFieldValue(
				$arParams['PARAMS']["arUserField"]["ENTITY_ID"],
				$arResult['ufToSaveAllowEdit']['FIELD'],
				$arParams['PARAMS']["arUserField"]['ENTITY_VALUE_ID']
			);

		}
	}

	WDUFUserFieldEdit($arParams["PARAMS"], $arParams["RESULT"]);
}
else
{
	$componentPage = ($arParams['VIEW_THUMB'] == 'Y' ? 'view_with_features' : 'view');
	WDUFUserFieldView($arParams["PARAMS"], $arParams["RESULT"]);

	if($componentPage == 'view' || $componentPage == 'view_with_features')
	{
		WDUFSetAccessToEdit($arParams['RESULT']['FILES'], $arParams['PARAMS']['arUserField']['ENTITY_ID'] . '_' . $arParams['PARAMS']['arUserField']['ENTITY_VALUE_ID']);
		if(!empty($arResult['IS_HISTORY_DOC']))
		{
			$fileH = reset($arParams['RESULT']['FILES']);
			$arResult['THROUGH_VERSION'] = is_array($fileH) && !empty($fileH['THROUGH_VERSION'])? $fileH['THROUGH_VERSION'] : 0;
		}
	}
}
$arResult = array_merge($arResult, $arParams['RESULT']);
foreach (GetModuleEvents("webdav", "webdav.user.field", true) as $arEvent)
{
	if (!ExecuteModuleEventEx($arEvent, array($arResult, $arParams)))
		return;
}
foreach (GetModuleEvents("main", $arParams['PARAMS']['arUserField']["USER_TYPE_ID"], true) as $arEvent)
{
	if (!ExecuteModuleEventEx($arEvent, array($arResult, $arParams)))
		return;
}
CJSCore::Init(array('viewer'));
$this->IncludeComponentTemplate($componentPage);
?>