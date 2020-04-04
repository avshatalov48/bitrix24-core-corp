<?
require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$USER_ID = intval($_GET["user_id"]) > 0 ? intval($_GET["user_id"]) : false;
?>
<?$APPLICATION->IncludeComponent("bitrix:socialnetwork.user_profile", "mobile", array(
		"PATH_TO_USER" => SITE_DIR."mobile/users/?user_id=#user_id#",
		"PATH_TO_GROUP" => SITE_DIR."mobile/?group_id=#group_id#",
		"PATH_TO_MESSAGES_CHAT" => SITE_DIR."mobile/im/dialog.php?id=#user_id#",
		'PATH_TO_TASKS_SNM_ROUTER' => SITE_DIR.'mobile/tasks/snmrouter/'
			. '?routePage=__ROUTE_PAGE__'
			. '&USER_ID=#USER_ID#',
		"ID" => $USER_ID,
		"NAME_TEMPLATE" => "",
		"USER_FIELDS_MAIN" => array(
			0 => "PERSONAL_BIRTHDAY",
			1 => "WORK_POSITION",
			2 => "WORK_COMPANY",
			3 => "SECOND_NAME",
		),
		"USER_PROPERTY_MAIN" => array(
			0 => "UF_DEPARTMENT",
		),
		"USER_FIELDS_CONTACT" => array(
			0 => "EMAIL",
			1 => "PERSONAL_WWW",
			2 => "PERSONAL_MOBILE",
			3 => "WORK_PHONE",
		),
		"USER_PROPERTY_CONTACT" => array(
			0 => "UF_PHONE_INNER",
			1 => "UF_SKYPE",
			2 => "UF_TWITTER",
			3 => "UF_FACEBOOK",
			4 => "UF_LINKEDIN",
			5 => "UF_XING",
		),
		"USER_FIELDS_PERSONAL" => array(
			0 => "TIME_ZONE",
		),
		"USER_PROPERTY_PERSONAL" => array(
			0 => "UF_SKILLS",
			1 => "UF_INTERESTS",
			2 => "UF_WEB_SITES",
		),
	),
	false,
	Array("HIDE_ICONS" => "Y")
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>