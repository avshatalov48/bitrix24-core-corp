<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule('intranet');

$componentPage = "";

$arDefaultVariableAliases = array(
	"USER_ID" => "USER_ID",
);

$arParams['LIST_URL'] = $APPLICATION->GetCurPage();
if (!$arParams['DETAIL_URL']) $arParams['DETAIL_URL'] = $arParams['LIST_URL'].'?ID=#USER_ID#';

if (!$arParams['FILTER_NAME'])
	$arParams['FILTER_NAME'] = 'USER_FILTER';

$perm = CIBlock::GetPermission($arParams['IBLOCK_ID'] ? $arParams['IBLOCK_ID'] : COption::GetOptionInt('intranet', 'iblock_structure', 0));
$arResult['USER_CAN_SET_HEAD'] = $perm >= 'W';

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";

if (!array_key_exists("PM_URL", $arParams))
	$arParams["PM_URL"] = "/company/personal/messages/chat/#USER_ID#/";
if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";

// for bitrix:main.user.link
$arTooltipFieldsDefault	= serialize(array(
	"EMAIL",
	"PERSONAL_MOBILE",
	"WORK_PHONE",
	"PERSONAL_ICQ",
	"PERSONAL_PHOTO",
	"PERSONAL_CITY",
	"WORK_COMPANY",
	"WORK_POSITION",
));
$arTooltipPropertiesDefault = serialize(array(
	"UF_DEPARTMENT",
	"UF_PHONE_INNER",
));

if (!array_key_exists("SHOW_FIELDS_TOOLTIP", $arParams))
	$arParams["SHOW_FIELDS_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_fields", $arTooltipFieldsDefault));
if (!array_key_exists("USER_PROPERTY_TOOLTIP", $arParams))
	$arParams["USER_PROPERTY_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_properties", $arTooltipPropertiesDefault));

if ($arResult['USER_CAN_SET_HEAD'])
{
	CJSCore::Init(array('ajax'));
}

$arParams['SHOW_DEP_HEAD_ADDITIONAL'] = 'Y';

$this->IncludeComponentTemplate();
?>