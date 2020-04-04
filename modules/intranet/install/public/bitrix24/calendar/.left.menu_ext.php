<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/calendar/.left.menu_ext.php");

$userId = $USER->getId();

$aMenuLinks = array();

if (
	CBXFeatures::IsFeatureEnabled('Calendar')
	&& CModule::IncludeModule('socialnetwork')
)
{
	$arUserActiveFeatures = CSocNetFeatures::GetActiveFeatures(SONET_ENTITY_USER, $userId);
	$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

	if (
		array_key_exists('calendar', $arSocNetFeaturesSettings) &&
		array_key_exists("allowed", $arSocNetFeaturesSettings['calendar']) &&
		in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings['calendar']["allowed"]) &&
		is_array($arUserActiveFeatures) &&
		in_array('calendar', $arUserActiveFeatures)
	)
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_CALENDAR_USER"),
			"/company/personal/user/".$userId."/calendar/",
			array(),
			array(
				"menu_item_id" => "menu_my_calendar",
				"counter_id" => "calendar"
			),
			""
		);
	}
}
if (CBXFeatures::IsFeatureEnabled('CompanyCalendar'))
{
	$aMenuLinks[] = array(
		GetMessage("MENU_CALENDAR_COMPANY"),
		"/calendar/",
		array(),
		array(
			"menu_item_id" => "menu_company_calendar"
		),
		""
	);
}
