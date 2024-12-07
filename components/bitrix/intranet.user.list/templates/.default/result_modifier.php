<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global \CUserTypeManager $USER_FIELD_MANAGER */

global $USER_FIELD_MANAGER;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

use Bitrix\Intranet\Component\UserList;
use Bitrix\Intranet\Site\Sections\TimemanSection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use Bitrix\UI\Buttons\JsHandler;

$adminsUserIdList = [];
$res  = \Bitrix\Main\UserGroupTable::getList([
	'filter' => [
		'=GROUP_ID' => 1
	],
	'select' => [ 'USER_ID' ]
]);
while ($userGroupFields = $res->fetch())
{
	$adminsUserIdList[] = $userGroupFields['USER_ID'];
}

$integratorsUserIdList = [];
if (\Bitrix\Main\Loader::includeModule('bitrix24'))
{
	$integratorsUserIdList = \Bitrix\Bitrix24\Integrator::getIntegratorsId();
}

$ufList = $USER_FIELD_MANAGER->getUserFields(\Bitrix\Main\UserTable::getUfId(), 0, LANGUAGE_ID, false);
$ufCodesList = array_keys($ufList);
$exportMode = (
	isset($arParams['EXPORT_MODE'])
	&& $arParams['EXPORT_MODE'] == 'Y'
);

$personalBirthdayFormat = Context::getCurrent()->getCulture()->getLongDateFormat();
$personalBirthdayFormatWithoutYear = Context::getCurrent()->getCulture()->getDayMonthFormat();
$showYearValue = Option::get("intranet", "user_profile_show_year", "Y");

$extranetGroupId = (
	isset($arResult['PROCESS_EXTRANET'])
	&& $arResult['PROCESS_EXTRANET'] == 'Y'
	&& \Bitrix\Main\Loader::includeModule('extranet')
		? intval(\CExtranet::GetExtranetUserGroupID())
		: 0
);

$userIdList = [];

if (
	$extranetGroupId
	&& !empty($userIdList)
)
{
	$realExtranetUserIdList = [];
	$res = \Bitrix\Main\UserTable::getList([
		'filter' => [
			'@ID' => $userIdList,
			'=GROUPS.GROUP_ID' => $extranetGroupId
		],
		'select' => [ 'ID' ]
	]);
	while($userFields = $res->fetch())
	{
		$realExtranetUserIdList[] = $userFields['ID'];
	}

	foreach($arResult['ROWS'] as $key => $row)
	{
		if (
			!empty($row['columnClasses'])
			&& !empty($row['columnClasses']['FULL_NAME'])
			&& $row['columnClasses']['FULL_NAME'] == 'intranet-user-list-full-name-extranet'
			&& !in_array($row['id'], $realExtranetUserIdList)
		)
		{
			$arResult['ROWS'][$key]['columnClasses']['FULL_NAME'] .= ' intranet-user-list-full-name-visitor';
			if (is_array($row['actions']))
			{
				array_walk($row['actions'], function(&$item) {
					switch ($item) {
						case 'deactivate':
							$item = 'delete';
							break;
						default:
					}
				});
				$row['actions'] = array_filter($row['actions'], function(&$item) {
					return !in_array($item, [ 'add_task', 'message', 'message_history', 'videocall' ]);
				});
				$arResult['ROWS'][$key]['actions'] = $row['actions'];
			}
		}
	}
}

if (!$exportMode)
{
	unset($arResult['GRID_COLUMNS']);
}

$toolbarMenuId = 'userGridSettingsMenu';
$usageSortParams = \Bitrix\Main\Web\Json::encode([
	'gridId' => $arResult['FILTER_ID'],
	'sortBy' => 'STRUCTURE_SORT',
	'order' => 'DESC',
	'menuId' => $toolbarMenuId,
]);

$arResult['TOOLBAR_MENU'] = new \Bitrix\UI\Buttons\Button([
	'dropdown' => false,
	'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
	'icon' => \Bitrix\UI\Buttons\Icon::SETTING,
	'menu' => [
		'id' => $toolbarMenuId,
		'items' => [
			[
				'className' => 'menu-popup-item-none',
				'TYPE' => 'EXPORT_EXCEL',
				'text' => Loc::getMessage('INTRANET_USER_LIST_MENU_EXPORT_EXCEL_TITLE'),
				'href' => (
				$arResult['EXCEL_EXPORT_LIMITED']
					? "javascript:BX.UI.InfoHelper.show('limit_crm_export_excel');"
					: UrlManager::getInstance()->createByBitrixComponent($this->getComponent(), 'export', [ 'type' => 'excel' ])
				)
			],
			[
				'className' => 'menu-popup-item-none',
				'TYPE' => 'SYNC_OUTLOOK',
				'text' => Loc::getMessage('INTRANET_USER_LIST_MENU_SYNC_OUTLOOK_TITLE'),
				'href' => 'javascript:'.\CIntranetUtils::getStsSyncURL(
						[
							'LINK_URL' => $APPLICATION->GetCurPage()
						], 'contacts', ($arResult["EXTRANET_SITE"] != "Y")
					)
			],
			[
				'className' => 'menu-popup-item-none',
				'TYPE' => 'SYNC_CARDDAV',
				'text' => Loc::getMessage('INTRANET_USER_LIST_MENU_SYNC_CARDDAV_TITLE'),
				'href' => 'javascript:'.$APPLICATION->getPopupLink(
						[
							"URL" => "/bitrix/groupdav.php?lang=".LANGUAGE_ID."&help=Y&dialog=Y",
						]
					)
			],
			['delimiter' => true],
			[
				'className' => $arResult['IS_DEFAULT_SORT'] ? 'menu-popup-item-accept' : 'menu-popup-item-none',
				'text' =>  Loc::getMessage('INTRANET_USER_LIST_MENU_SORT_DEFAULT'),
				'onclick' => new \Bitrix\UI\Buttons\JsCode("jsBXIUL.setSort($usageSortParams)"),
				'dataset' => [
					'unqid' => 'user-grid-sort-btn',
				],
			]
		]
	]
]);

$arResult['TOOLBAR_BUTTONS'] = [];

if (
	(
		!isset($_REQUEST['IFRAME'])
		|| $_REQUEST['IFRAME'] != 'Y'
	)
	&& (
		(
			\Bitrix\Main\Loader::includeModule('bitrix24')
			&& \CBitrix24::isInvitingUsersAllowed()
		)
		|| (
			!\Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24")
			&& $USER->CanDoOperation('edit_all_users')
		)
	)
)
{
	$arResult['TOOLBAR_BUTTONS'][] = [
		'TITLE' => Loc::getMessage('INTRANET_USER_LIST_BUTTON_INVITE_TITLE_MSG_1'),
		'CLICK' => new JsHandler(
			"jsBXIUL.showInvitation",
			"jsBXIUL"
		),
	];
}
