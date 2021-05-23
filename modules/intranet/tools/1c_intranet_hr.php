<?php
define("BX_FORCE_DISABLE_SEPARATED_SESSION_MODE", true);

if(isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "GET")
{
	//from main 20.0.1300 only POST allowed
	if(isset($_GET["USER_LOGIN"]) && isset($_GET["USER_PASSWORD"]) && isset($_GET["AUTH_FORM"]) && isset($_GET["TYPE"]))
	{
		$_POST["USER_LOGIN"] = $_GET["USER_LOGIN"];
		$_POST["USER_PASSWORD"] = $_GET["USER_PASSWORD"];
		$_POST["AUTH_FORM"] = $_GET["AUTH_FORM"];
		$_POST["TYPE"] = $_GET["TYPE"];
	}
}

require($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_admin_before.php');

IncludeModuleLangFile(__FILE__);


$SITE_ID = isset($_GET['SITE_ID']) ? $_GET['SITE_ID'] : false;

if ($SITE_ID !== false)
{
	if (!CSite::GetByID($SITE_ID)->Fetch())
		$SITE_ID = false;
}
else
{
	$SITE_ID = CSite::GetDefSite();
}

if ($SITE_ID === false || CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite($SITE_ID))
{
	die(GetMessage('CC_BSC1_WRONG_SITE'));
}

if (!function_exists('simplexml_load_string'))
{
	die(GetMessage('CC_BSC1_SIMPLE_XML_MISSING'));
}

$APPLICATION->IncludeComponent(
	'bitrix:intranet.users.import.1c.hrxml', '',
	array(
		'IBLOCK_TYPE'              => COption::GetOptionString('intranet', 'iblock_type', '', $SITE_ID),
		'DEPARTMENTS_IBLOCK_ID'    => COption::GetOptionInt('intranet', 'iblock_structure', '', $SITE_ID),
		'ABSENCE_IBLOCK_ID'        => COption::GetOptionInt('intranet', 'iblock_absence', '', $SITE_ID),
		'STATE_HISTORY_IBLOCK_ID'  => COption::GetOptionInt('intranet', 'iblock_state_history', '', $SITE_ID),
		'IBLOCK_TYPE_VACANCY'      => COption::GetOptionString('intranet', 'iblock_type_vacancy', '', $SITE_ID),
		'VACANCY_IBLOCK_ID'        => COption::GetOptionInt('intranet', 'iblock_vacancy', '', $SITE_ID),
		'SITE_ID'                  => COption::GetOptionString('intranet', 'import_SITE_ID', '', $SITE_ID),
//		'STRUCTURE_CHECK'          => COption::GetOptionString('intranet', 'import_STRUCTURE_CHECK', '', $SITE_ID),
//		'INTERVAL'                 => COption::GetOptionString('intranet', 'import_INTERVAL', '', $SITE_ID),
		'GROUP_PERMISSIONS'        => unserialize(COption::GetOptionString('intranet', 'import_GROUP_PERMISSIONS', '', $SITE_ID), ["allowed_classes" => false]),
//		'EMAIL_PROPERTY_XML_ID'    => COption::GetOptionString('intranet', 'import_EMAIL_PROPERTY_XML_ID', '', $SITE_ID),
//		'LOGIN_PROPERTY_XML_ID'    => COption::GetOptionString('intranet', 'import_LOGIN_PROPERTY_XML_ID', '', $SITE_ID),
//		'PASSWORD_PROPERTY_XML_ID' => COption::GetOptionString('intranet', 'import_PASSWORD_PROPERTY_XML_ID', '', $SITE_ID),
//		'FILE_SIZE_LIMIT'          => COption::GetOptionString('intranet', 'import_FILE_SIZE_LIMIT', '', $SITE_ID),
//		'USE_ZIP'                  => COption::GetOptionString('intranet', 'import_USE_ZIP', '', $SITE_ID),
		'DEFAULT_EMAIL'            => COption::GetOptionString('intranet', 'import_DEFAULT_EMAIL', '', $SITE_ID),
		'UNIQUE_EMAIL'             => COption::GetOptionString('intranet', 'import_UNIQUE_EMAIL', '', $SITE_ID),
		'LOGIN_TEMPLATE'           => COption::GetOptionString('intranet', 'import_LOGIN_TEMPLATE', '', $SITE_ID),
		'EMAIL_NOTIFY'             => COption::GetOptionString('intranet', 'import_EMAIL_NOTIFY', '', $SITE_ID),
		'EMAIL_NOTIFY_IMMEDIATELY' => COption::GetOptionString('intranet', 'import_EMAIL_NOTIFY_IMMEDIATELY', '', $SITE_ID),
		'UPDATE_PROPERTIES'        => unserialize(COption::GetOptionString('intranet', 'import_UPDATE_PROPERTIES', '', $SITE_ID), ["allowed_classes" => false]),
		'LDAP_ID_PROPERTY_XML_ID'  => COption::GetOptionString('intranet', 'import_LDAP_ID_PROPERTY_XML_ID', '', $SITE_ID),
		'LDAP_SERVER'              => COption::GetOptionString('intranet', 'import_LDAP_SERVER', '', $SITE_ID),
	),
	null, array('HIDE_ICONS' => 'Y')
);
