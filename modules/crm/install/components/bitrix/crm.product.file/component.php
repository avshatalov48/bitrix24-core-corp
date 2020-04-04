<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @global CMain $APPLICATION */
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */

global $APPLICATION;

$this->setFrameMode(false);

$showErrors = !(isset($arParams['HIDE_ERRORS']) && $arParams['HIDE_ERRORS'] === 'Y');
$arResult['ERRORS'] = array();

if (!CModule::IncludeModule('crm'))
{
	$errMsg = GetMessage('CRM_PRODUCT_FILE_CRM_MODULE_NOT_INSTALLED');
	$arResult['ERRORS'][] = $errMsg;
	if ($showErrors)
		ShowError($errMsg);
	return array('ERRORS' => $arResult['ERRORS']);
}

if (!CModule::IncludeModule('iblock'))
{
	$errMsg = GetMessage('CRM_PRODUCT_FILE_IBLOCK_MODULE_NOT_INSTALLED');
	$arResult['ERRORS'][] = $errMsg;
	if ($showErrors)
		ShowError($errMsg);
	return array('ERRORS' => $arResult['ERRORS']);
}

$arParams['PATH_TO_PRODUCT_FILE'] = CrmCheckPath(
	'PATH_TO_PRODUCT_FILE', $arParams['PATH_TO_PRODUCT_FILE'],
	$APPLICATION->GetCurPage().'?product_id=#product_id#&field_id=#field_id#&file_id=#file_id#&file'
);

$IBLOCK_ID = is_array($arParams["~CATALOG_ID"])? 0: intval($arParams["~CATALOG_ID"]);
$ELEMENT_ID = is_array($arParams["~PRODUCT_ID"])? 0: intval($arParams["~PRODUCT_ID"]);

$options = is_array($arParams['OPTIONS'])? $arParams['OPTIONS']: array();

$authToken = isset($options['oauth_token']) ? strval($options['oauth_token']) : '';
if($authToken !== '')
{
	$authData = array();
	if(!(CModule::IncludeModule('rest')
		&& CRestUtil::checkAuth($authToken, CCrmRestService::SCOPE_NAME, $authData)
		&& CRestUtil::makeAuth($authData)))
	{
		$errMsg = GetMessage('CRM_PRODUCT_FILE_PERMISSION_DENIED');
		$arResult['ERRORS'][] = $errMsg;
		if ($showErrors)
			ShowError($errMsg);
		return array('ERRORS' => $arResult['ERRORS']);
	}
}

if(!CCrmPerms::IsAdmin())
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if (!(CCrmPerms::IsAccessEnabled($userPermissions) && $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')))
	{
		$errMsg = GetMessage('CRM_PRODUCT_FILE_PERMISSION_DENIED');
		$arResult['ERRORS'][] = $errMsg;
		if ($showErrors)
			ShowError($errMsg);
		return array('ERRORS' => $arResult['ERRORS']);
	}
}

if (!CCrmProductFile::CheckFieldId($IBLOCK_ID, $arParams["FIELD_ID"]))
{
	$errMsg = GetMessage('CRM_PRODUCT_FILE_UNKNOWN_ERROR');
	$arResult['ERRORS'][] = $errMsg;
	if ($showErrors)
		ShowError($errMsg);
	return array('ERRORS' => $arResult['ERRORS']);
}

$arIBlock = CIBlock::GetArrayByID(intval($arParams["~CATALOG_ID"]));

$arResult["FILES"] = array();
$arResult["ELEMENT"] = false;

if ($ELEMENT_ID > 0)
{
	$rsElement = CIBlockElement::GetList(
		array(),
		array(
			"CATALOG_ID" => $arIBlock["ID"],
			"=ID" => $ELEMENT_ID,
			"CHECK_PERMISSIONS" => "N",
		),
		false,
		false,
		array("ID", $arParams["FIELD_ID"])
	);
	while ($ar = $rsElement->GetNext())
	{
		if (isset($ar[$arParams["FIELD_ID"]]))
		{
			$arResult["FILES"][] = $ar[$arParams["FIELD_ID"]];
		}
		else if (isset($ar[$arParams["FIELD_ID"]."_VALUE"]))
		{
			if (is_array($ar[$arParams["FIELD_ID"]."_VALUE"]))
				$arResult["FILES"] = array_merge($arResult["FILES"], $ar[$arParams["FIELD_ID"]."_VALUE"]);
			else
				$arResult["FILES"][] = $ar[$arParams["FIELD_ID"]."_VALUE"];
		}
		$arResult["ELEMENT"] = $ar;
	}
}

if (!in_array($arParams["FILE_ID"], $arResult["FILES"]))
{
	$errMsg = GetMessage('CRM_PRODUCT_FILE_WRONG_FILE');
	$arResult['ERRORS'][] = $errMsg;
	if ($showErrors)
		ShowError($errMsg);
}
else
{
	$arFile = CFile::GetFileArray($arParams["FILE_ID"]);
	if (is_array($arFile))
	{
		$bForceDownload = isset($_REQUEST["download"]) && $_REQUEST["download"] === "y";

		CFile::ViewByUser($arParams["FILE_ID"], array(
			"content_type" => $arFile["CONTENT_TYPE"],
			"force_download" => $bForceDownload,
		));
	}
}
?>
