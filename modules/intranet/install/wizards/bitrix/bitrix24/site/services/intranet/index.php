<?

use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("intranet"))
	return;

\Bitrix\Main\Entity\Base::destroy(\Bitrix\Main\UserTable::getEntity());

COption::SetOptionString("intranet", "iblock_type", "structure");
COption::SetOptionString("intranet", "search_user_url", WIZARD_SITE_DIR."company/personal/user/#ID#/", false, WIZARD_SITE_ID);

COption::SetOptionString("intranet", "tz_transition", "Y");
COption::SetOptionString("intranet", "tz_transition_daylight", '<transitionRule month="3" day="su" weekdayOfMonth="last" /><transitionTime>2:0:0</transitionTime>');
COption::SetOptionString("intranet", "tz_transition_standard", '<transitionRule month="10" day="su" weekdayOfMonth="last" /><transitionTime>3:0:0</transitionTime>');

COption::SetOptionString('intranet', 'new_portal_structure', 'Y');

\Bitrix\Main\Config\Option::set('ui', 'design_tokens:custom_extension', 'intranet.design-tokens.bitrix24');

//Composite
CHTMLPagesCache::setEnabled(true, false);
RegisterModuleDependences("main", "OnGetStaticCacheProvider", "intranet", "\\Bitrix\\Intranet\\Composite\\CacheProvider", "getObject");
COption::SetOptionString("main", "~show_composite_banner", "N");
COption::SetOptionString("intranet", "composite_enabled", "Y");

$defaultThemeId = in_array(LANGUAGE_ID, ['ru', 'kz', 'by']) ? ThemePicker::DEFAULT_THEME_ID : 'light:dark-silk';
$theme = new ThemePicker(WIZARD_TEMPLATE_ID, WIZARD_SITE_ID);
$theme->setDefaultTheme($defaultThemeId);

CUserOptions::SetOption("intranet", "left_menu_collapsed", "Y", true);

$arIblockCode = Array(
	"iblock_structure" => "departments",
	"iblock_absence" => "absence",
	"iblock_honour" => "honour",
	"iblock_state_history" => "state_history",
);

foreach ($arIblockCode as $option => $iblockCode)
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => "structure"));
	if ($arIBlock = $rsIBlock->Fetch())
		COption::SetOptionString("intranet", $option ,$arIBlock["ID"]);
}

if ($structure_iblock_id = COption::GetOptionInt('intranet', 'iblock_structure', 0))
{
	$obUT = new CUserTypeEntity();
	$dbRes = $obUT->GetList(array('ID' => 'ASC'), array('ENTITY_ID' => 'IBLOCK_'.$structure_iblock_id.'_SECTION', 'FIELD_NAME' => 'UF_HEAD'));
	if (!$dbRes->Fetch())
	{
		$arLabels = array();
		$dbRes = CLanguage::GetList();
		while ($arRes = $dbRes->Fetch())
		{
			if (file_exists(__DIR__.'/'.$arRes['LID'].'/labels.php'))
				require(__DIR__.'/'.$arRes['LID'].'/labels.php');
		}

		$obUT->Add(array(
			'ENTITY_ID' => 'IBLOCK_'.$structure_iblock_id.'_SECTION',
			'FIELD_NAME' => 'UF_HEAD',
			'USER_TYPE_ID' => 'employee',
			'EDIT_FORM_LABEL' => $arLabels,
		));
	}

	$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()->departmentRepository();
	$rootDepartment = $departmentRepository->getRootDepartment();
	if ($rootDepartment)
	{
		$admin = new CUser();
		$admin->Update(
			1,
			[
				"UF_DEPARTMENT" => [$rootDepartment->getId()]
			]
		);
	}
}

COption::SetOptionString("intranet", "iblock_type_calendar", "events");
$rsIBlock = CIBlock::GetList(array(), array("CODE" => "calendar_employees", "TYPE" => "events", "SITE_ID" => WIZARD_SITE_ID));
if ($arIBlock = $rsIBlock->Fetch())
	COption::SetOptionString("intranet", "iblock_calendar",  $arIBlock["ID"], false, WIZARD_SITE_ID);

COption::SetOptionString('intranet', 'path_user', WIZARD_SITE_DIR.'company/personal/user/#USER_ID#/', false, WIZARD_SITE_ID);

COption::SetOptionString('intranet', 'path_task_user', WIZARD_SITE_DIR.'company/personal/user/#USER_ID#/tasks/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_user_entry', WIZARD_SITE_DIR.'company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_group', WIZARD_SITE_DIR.'workgroups/group/#GROUP_ID#/tasks/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_group_entry', WIZARD_SITE_DIR.'workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/', false, WIZARD_SITE_ID);

if(CModule::IncludeModule("dav"))
{
	CAgent::AddAgent("CDavGroupdavClientCalendar::DataSync();", "dav", "N", 60);
	COption::SetOptionString("dav", "agent_calendar_caldav", "Y");

	COption::SetOptionString("dav", "timezone", "Europe/Moscow");

	CDavExchangeCalendar::InitUserEntity();
	CDavGroupdavClientCalendar::InitUserEntity();
}
?>
