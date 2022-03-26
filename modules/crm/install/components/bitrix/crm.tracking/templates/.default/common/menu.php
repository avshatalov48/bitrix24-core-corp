<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var \CAllMain $APPLICATION*/
/** @var array $arResult*/
/** @var array $arParams*/

use Bitrix\Main\Localization\Loc;

global $APPLICATION;

Loc::loadMessages(__FILE__);


$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$sliderMode = $request->get('IFRAME') === 'Y';
$currentMenuItem = $currentMenuItem ?? '';
$shouldDisplayMenu = !$sliderMode || $currentMenuItem === 'list';

if (SITE_TEMPLATE_ID === "bitrix24")
{
	$this->SetViewTarget('above_pagetitle', 100);
}

if ($shouldDisplayMenu)
{
	$menuItems = [];
	$menuItems[] = [
		"TEXT" => Loc::getMessage('CRM_TRACKING_COMMON_MENU_MAIN'),
		"URL" => "/crm/tracking/list/",
		"ID" => "crm-tracking-menu-list",
		"IS_ACTIVE" => $currentMenuItem === 'list',
		'IS_DISABLED'=> false
	];
	$menuItems[] = [
		"TEXT" => Loc::getMessage('CRM_TRACKING_COMMON_MENU_REPORTS'),
		"URL" => "/report/analytics/?analyticBoardKey=crm-ad-payback",
		"ID" => "crm-tracking-menu-reports",
		"IS_ACTIVE" => false,
		'IS_DISABLED'=> false
	];
	$menuItems[] = [
		"TEXT" => Loc::getMessage('CRM_TRACKING_COMMON_MENU_ARCHIVE'),
		"URL" => "/crm/tracking/source/archive/",
		"ID" => "crm-tracking-menu-archive",
		"IS_ACTIVE" => $currentMenuItem === 'archive',
		'IS_DISABLED'=> false
	];
	$menuItems[] = [
		"TEXT" => Loc::getMessage('CRM_TRACKING_COMMON_MENU_SETTINGS'),
		"URL" => "/crm/tracking/settings/",
		"ID" => "crm-tracking-menu-settings",
		"IS_ACTIVE" => $currentMenuItem === 'settings',
		'IS_DISABLED'=> false
	];
	/*
	$menuItems[] = [
		"TEXT" => Loc::getMessage('CRM_TRACKING_COMMON_MENU_PERMISSIONS'),
		"ID" => "crm-tracking-menu-permissions",
		"IS_ACTIVE" => $currentMenuItem === 'permissions',
		'IS_DISABLED'=> true
	];
	*/

	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.buttons",
		"",
		array(
			"ID" => 'crm-tracking-menu',
			"ITEMS" => $menuItems,
		)
	);
}

?>
<script>
	BX.SidePanel.Instance.bindAnchors({
		rules:
			[
				{
					condition: [
						"/crm/tracking/settings/",
						"/crm/tracking/source/archive/"
					],
					options: {
						width: 800,
						cacheable: false
					}
				},
				{
					condition: [
						"/crm/tracking/source/edit/(\\d+)/"
					],
					options: {
						width: 735,
						cacheable: false
					}
				}
			]
	});
</script>

<?
if (SITE_TEMPLATE_ID === "bitrix24")
{
	$this->EndViewTarget();
}