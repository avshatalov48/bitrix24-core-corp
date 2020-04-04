<?php

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
		'GROUP_PERMISSIONS'        => unserialize(COption::GetOptionString('intranet', 'import_GROUP_PERMISSIONS', '', $SITE_ID)),
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
		'UPDATE_PROPERTIES'        => unserialize(COption::GetOptionString('intranet', 'import_UPDATE_PROPERTIES', '', $SITE_ID)),
		'LDAP_ID_PROPERTY_XML_ID'  => COption::GetOptionString('intranet', 'import_LDAP_ID_PROPERTY_XML_ID', '', $SITE_ID),
		'LDAP_SERVER'              => COption::GetOptionString('intranet', 'import_LDAP_SERVER', '', $SITE_ID),
	),
	null, array('HIDE_ICONS' => 'Y')
);
