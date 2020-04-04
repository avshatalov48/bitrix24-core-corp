<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
?>

<?

$dbRes = CSite::GetList($b='SORT', $o='ASC', array('DEF'=>'Y'));
if ($arRes = $dbRes->Fetch())
{
	if (!CModule::IncludeModule('extranet') || (CModule::IncludeModule('extranet') && !CExtranet::IsExtranetSite($arRes['ID'])))
	{
		$iablock = COption::GetOptionInt('intranet', 'iblock_absence', '', $arRes['ID']);
	}
}
if (empty($iablock))
{
	$iablock = COption::GetOptionInt('intranet', 'iblock_absence', '', SITE_ID);
}

$APPLICATION->IncludeComponent("bitrix:intranet.users.import.1c", "", array(
	"IBLOCK_TYPE"	=>	COption::GetOptionString('intranet', 'iblock_type'),
	"DEPARTMENTS_IBLOCK_ID"	=>	COption::GetOptionInt('intranet', 'iblock_structure'),
	"ABSENCE_IBLOCK_ID"	=>	$iablock,
	"STATE_HISTORY_IBLOCK_ID"	=>	COption::GetOptionInt('intranet', 'iblock_state_history'),
	"SITE_ID"	=>	COption::GetOptionString('intranet', 'import_SITE_ID'),
	"STRUCTURE_CHECK"	=>	COption::GetOptionString('intranet', 'import_STRUCTURE_CHECK'),
	"INTERVAL"	=>	COption::GetOptionString('intranet', 'import_INTERVAL'),
	"GROUP_PERMISSIONS"	=>	unserialize(COption::GetOptionString('intranet', 'import_GROUP_PERMISSIONS')),
	"EMAIL_PROPERTY_XML_ID"	=>	COption::GetOptionString('intranet', 'import_EMAIL_PROPERTY_XML_ID'),
	"LOGIN_PROPERTY_XML_ID"	=>	COption::GetOptionString('intranet', 'import_LOGIN_PROPERTY_XML_ID'),
	"PASSWORD_PROPERTY_XML_ID"	=>	COption::GetOptionString('intranet', 'import_PASSWORD_PROPERTY_XML_ID'),
	"FILE_SIZE_LIMIT"	=>	COption::GetOptionString('intranet', 'import_FILE_SIZE_LIMIT'),
	"USE_ZIP"	=>	COption::GetOptionString('intranet', 'import_USE_ZIP'),
	"DEFAULT_EMAIL"	=>	COption::GetOptionString('intranet', 'import_DEFAULT_EMAIL'),
	"UNIQUE_EMAIL" => COption::GetOptionString('intranet', 'import_UNIQUE_EMAIL', '', $SITE_ID),
	"LOGIN_TEMPLATE"	=>	COption::GetOptionString('intranet', 'import_LOGIN_TEMPLATE'),
	"EMAIL_NOTIFY"	=>	COption::GetOptionString('intranet', 'import_EMAIL_NOTIFY'),
	"EMAIL_NOTIFY_IMMEDIATELY"	=>	COption::GetOptionString('intranet', 'import_EMAIL_NOTIFY_IMMEDIATELY'),
	"UPDATE_PROPERTIES"	=>	unserialize(COption::GetOptionString('intranet', 'import_UPDATE_PROPERTIES')),
	"LDAP_ID_PROPERTY_XML_ID"	=>	COption::GetOptionString('intranet', 'import_LDAP_ID_PROPERTY_XML_ID'),
	"LDAP_SERVER"	=>	COption::GetOptionString('intranet', 'import_LDAP_SERVER'),
	),
	null, array('HIDE_ICONS' => 'Y')
);?>