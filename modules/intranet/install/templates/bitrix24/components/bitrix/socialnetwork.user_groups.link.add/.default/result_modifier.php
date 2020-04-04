<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global CUserTypeManager $USER_FIELD_MANAGER */
global $USER_FIELD_MANAGER;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

CJSCore::Init(array('socnetlogdest'));

$arResult["Filter"] = array(
	array(
		'id' => 'NAME',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_NAME'),
		'type' => 'string',
		'default' => true
	),
	array(
		'id' => 'TAG',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_TAG'),
		'type' => 'string'
	),
	array(
		'id' => 'OWNER',
		'name' => Loc::getMessage(ModuleManager::isModuleInstalled('intranet') ? 'SONET_C36_T_FILTER_OWNER_INTRANET' : 'SONET_C36_T_FILTER_OWNER'),
		'default' => true,
		'type' => 'dest_selector',
		'params' => array (
			'apiVersion' => '3',
			'context' => 'SONET_GROUP_LIST_FILTER_OWNER',
			'multiple' => 'N',
			'contextCode' => 'U',
			'enableAll' => 'N',
			'enableSonetgroups' => 'N',
			'allowEmailInvitation' => 'N',
			'allowSearchEmailUsers' => 'N',
			'departmentSelectDisable' => 'Y',
		),
	),
	array(
		'id' => 'MEMBER',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_MEMBER'),
		'default' => true,
		'type' => 'dest_selector',
		'params' => array (
			'apiVersion' => '3',
			'context' => 'SONET_GROUP_LIST_FILTER_MEMBER',
			'multiple' => 'N',
			'contextCode' => 'U',
			'enableAll' => 'N',
			'enableSonetgroups' => 'N',
			'allowEmailInvitation' => 'N',
			'allowSearchEmailUsers' => 'N',
			'departmentSelectDisable' => 'Y',
		),
	)
);

if (ModuleManager::isModuleInstalled('intranet'))
{
	$arResult["Filter"][] = array(
		'id' => 'PROJECT',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_PROJECT'),
		'type' => 'checkbox',
		'default' => true
	);
	$arResult["Filter"][] = array(
		'id' => 'PROJECT_DATE_START',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_PROJECT_DATE_START'),
		'type' => 'date',
		'default' => false,
	);
	$arResult["Filter"][] = array(
		'id' => 'PROJECT_DATE_FINISH',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_PROJECT_DATE_FINISH'),
		'type' => 'date',
		'default' => false,
	);
}

$extranetSiteId = Option::get("extranet", "extranet_site");
$extranetSiteId = ($extranetSiteId && ModuleManager::isModuleInstalled('extranet') ?  $extranetSiteId : false);

if (
	$extranetSiteId
	&& SITE_ID != $extranetSiteId
)
{
	$arResult["Filter"][] = array(
		'id' => 'EXTRANET',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_EXTRANET'),
		'type' => 'checkbox',
	);
}

if (
	SITE_ID != $extranetSiteId
	&& \Bitrix\Main\Loader::includeModule('landing')
	&& \Bitrix\Main\Loader::includeModule('socialnetwork')
	&& class_exists('\Bitrix\Socialnetwork\Integration\Landing\Livefeed')
)
{
	$arResult["Filter"][] = array(
		'id' => 'LANDING',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_LANDING'),
		'type' => 'checkbox',
	);
}

if (COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
{
	$arResult["Filter"][] = array(
		'id' => 'CLOSED',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_CLOSED'),
		'type' => 'checkbox',
	);
}

if ($USER->isAuthorized())
{
	$arResult["Filter"][] = array(
		'id' => 'FAVORITES',
		'name' => Loc::getMessage('SONET_C36_T_FILTER_FAVORITES'),
		'type' => 'list',
		'items' => array(
			'Y' => Loc::getMessage('SONET_C36_FILTER_LIST_YES')
		)
	);
}

$arResult["GROUP_PROPERTIES"] = $USER_FIELD_MANAGER->GetUserFields("SONET_GROUP", 0, LANGUAGE_ID);

$available = ['date', 'datetime', 'string', 'double', 'boolean', 'crm'];
foreach($arResult["GROUP_PROPERTIES"] as $field => $arUserField)
{
	if (
		empty($arUserField['SHOW_FILTER'])
		|| $arUserField['SHOW_FILTER'] == 'N'
	)
	{
		unset($arResult["GROUP_PROPERTIES"][$field]);
		continue;
	}

	$arUserField["EDIT_FORM_LABEL"] = !empty($arUserField["EDIT_FORM_LABEL"]) ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"];
	$arUserField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arUserField["EDIT_FORM_LABEL"]);
	$arUserField["~EDIT_FORM_LABEL"] = $arUserField["EDIT_FORM_LABEL"];

	$type = $arUserField['USER_TYPE_ID'];
	if (!in_array($type, $available))
	{
		$type = 'string';
	}
	if ($type == 'datetime')
	{
		$type = 'date';
	}

	if ($type == 'double')
	{
		$type = 'number';
	}

	if ($type == 'boolean')
	{
		$arResult["Filter"][$arUserField['FIELD_NAME']] = array(
			'id' => $arUserField['FIELD_NAME'],
			'name' => $arUserField['EDIT_FORM_LABEL'],
			'type' => 'list',
			'items' => [
				1 => GetMessage('SONET_C36_FILTER_LIST_YES')
			],
			'uf' => true
		);
	}
	else if ($type == 'crm')
	{
		continue;
	}
	else
	{
		$arResult["Filter"][$arUserField['FIELD_NAME']] = array(
			'id' => $arUserField['FIELD_NAME'],
			'name' => $arUserField['EDIT_FORM_LABEL'],
			'type' => $type,
			'uf' => true
		);
	}
}

$arResult["FilterPresets"] = Bitrix\Socialnetwork\Integration\Main\UIFilter\Workgroup::getFilterPresetList(array(
	'currentUserId' => ($USER->isAuthorized() ? $USER->getId() : false),
	'extranetSiteId' => $extranetSiteId
));

$config = \Bitrix\Main\Application::getConnection()->getConfiguration();
$arResult["ftMinTokenSize"] = (isset($config["ft_min_token_size"]) ? $config["ft_min_token_size"] : CSQLWhere::FT_MIN_TOKEN_SIZE);
?>