<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Contacts");
?>

<?$APPLICATION->IncludeComponent("bitrix:intranet.search", ".default", array(
	"STRUCTURE_PAGE" => "",
	"PM_URL" => "#SITE_DIR#contacts/personal/messages/chat/#USER_ID#/",
	"PATH_TO_VIDEO_CALL" => "#SITE_DIR#contacts/personal/video/#USER_ID#/",
	"STRUCTURE_FILTER" => "contacts",
	"FILTER_1C_USERS" => "N",
	"USERS_PER_PAGE" => "50",
	"FILTER_SECTION_CURONLY" => "N",
	"NAME_TEMPLATE" => "",
	"SHOW_ERROR_ON_NULL" => "Y",
	"NAV_TITLE" => "Contacts",
	"SHOW_NAV_TOP" => "N",
	"SHOW_NAV_BOTTOM" => "Y",
	"SHOW_UNFILTERED_LIST" => "Y",
	"AJAX_MODE" => "Y",
	"AJAX_OPTION_SHADOW" => "N",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "Y",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "3600",
	"FILTER_NAME" => "contacts_search",
	"FILTER_DEPARTMENT_SINGLE" => "Y",
	"FILTER_SESSION" => "N",
	"DEFAULT_VIEW" => "list",
	"LIST_VIEW" => "list",
	"USER_PROPERTY_TABLE" => array(
		0 => "PERSONAL_PHOTO",
		1 => "FULL_NAME",
		2 => "PERSONAL_PHONE",
		3 => "PERSONAL_CITY",
		4 => "PERSONAL_COUNTRY",
		5 => "WORK_POSITION",
		6 => "WORK_COMPANY",
	),
	"USER_PROPERTY_EXCEL" => array(
		0 => "FULL_NAME",
		1 => "EMAIL",
		2 => "PERSONAL_PHONE",
		3 => "PERSONAL_FAX",
		4 => "PERSONAL_MOBILE",
		5 => "WORK_POSITION",
		6 => "WORK_COMPANY",
	),
	"USER_PROPERTY_LIST" => array(
		0 => "EMAIL",
		1 => "PERSONAL_ICQ",
		2 => "PERSONAL_PHONE",
		3 => "PERSONAL_FAX",
		4 => "PERSONAL_MOBILE",
		5 => "PERSONAL_CITY",
		6 => "PERSONAL_COUNTRY",
		7 => "WORK_COMPANY",
	),
	"EXTRANET_TYPE" => "",
	"AJAX_OPTION_ADDITIONAL" => ""
	),
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
