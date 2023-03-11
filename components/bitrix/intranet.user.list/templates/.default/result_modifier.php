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

$gridColumns = $arResult['GRID_COLUMNS'];
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
foreach($arResult['ROWS'] as $key => $row)
{
	if (isset($row['data']['USER_FIELDS']['ID']))
	{
		$userIdList[] = intval($row['data']['USER_FIELDS']['ID']);
	}
}

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

foreach($arResult['ROWS'] as $key => $row)
{
	$userFields = $row['data']['USER_FIELDS'];
	foreach($gridColumns as $column)
	{
		switch($column)
		{
			case 'ID':
				$arResult['ROWS'][$key]['data'][$column] = intval($userFields[$column]);
				break;
			case 'GENDER':
				$arResult['ROWS'][$key]['data'][$column] = (
					!empty($userFields['PERSONAL_GENDER'])
						? Loc::getMessage('INTRANET_USER_LIST_GENDER_'.$userFields['PERSONAL_GENDER'])
						: ''
				);
				break;
			case 'DATE_REGISTER':
				$arResult['ROWS'][$key]['data'][$column] = $userFields['DATE_REGISTER'];
				break;
			case 'BIRTHDAY':
				$birthdayFormat = $personalBirthdayFormat;
				if (
					$showYearValue == 'N'
					|| (
						$userFields['PERSONAL_GENDER'] == 'F'
						&& $showYearValue == 'M'
					)
				)
				{
					$birthdayFormat = $personalBirthdayFormatWithoutYear;
				}
				$arResult['ROWS'][$key]['data'][$column] = ($userFields['PERSONAL_BIRTHDAY'] ? FormatDate($birthdayFormat, $userFields['PERSONAL_BIRTHDAY']->getTimestamp()) : '');
				break;
			case 'EMAIL':
				$arResult['ROWS'][$key]['data'][$column] = (
					$exportMode
						? $userFields[$column]
						: '<a href="mailto:'.htmlspecialcharsbx($userFields['EMAIL']).'">'.htmlspecialcharsEx($userFields[$column]).'</a>'
				);
				break;
			case 'PHONE':
				$arResult['ROWS'][$key]['data'][$column] = (
					$exportMode
						? $userFields['PERSONAL_PHONE']
						: '<a href="callto:'.htmlspecialcharsbx($userFields['PERSONAL_PHONE']).'">'.htmlspecialcharsEx($userFields['PERSONAL_PHONE']).'</a>'
				);
				break;
			case 'PHONE_MOBILE':
				$arResult['ROWS'][$key]['data'][$column] = (
					$exportMode
						? $userFields['PERSONAL_MOBILE']
						: '<a href="callto:'.htmlspecialcharsbx($userFields['PERSONAL_MOBILE']).'">'.htmlspecialcharsbx($userFields['PERSONAL_MOBILE']).'</a>'
				);
				break;
			case 'PROFESSION':
				$arResult['ROWS'][$key]['data'][$column] = ($exportMode ? $userFields['PERSONAL_PROFESSION'] : htmlspecialcharsEx($userFields['PERSONAL_PROFESSION']));
				break;
			case 'DEPARTMENT':
				$arResult['ROWS'][$key]['data'][$column] = UserList::getDepartmentValue([
					'FIELDS' => $userFields,
					'PATH' => ($arResult["EXTRANET_SITE"] != "Y" ? $arParams['PATH_TO_DEPARTMENT'] : ''),
					'EXPORT_MODE' => $exportMode
				]);
				break;
			case 'FULL_NAME':
				$arResult['ROWS'][$key]['data'][$column] = UserList::getNameFormattedValue([
					'FIELDS' => $userFields,
					'PATH' => $arParams['PATH_TO_USER'],
					'EXPORT_MODE' => $exportMode,
					'ADDITIONAL_DATA' => [
						'IS_ADMIN' => in_array($userFields['ID'], $adminsUserIdList),
						'IS_INTEGRATOR' => in_array($userFields['ID'], $integratorsUserIdList)
					]
				]);
				break;
			case 'PHOTO':
				$arResult['ROWS'][$key]['data'][$column] = ($exportMode ? '' : UserList::getPhotoValue([
					'FIELDS' => $userFields,
					'PATH' => $arParams['PATH_TO_USER']
				]));
				break;
			case 'PERSONAL_COUNTRY':
			case 'WORK_COUNTRY':
				$arResult['ROWS'][$key]['data'][$column] = \Bitrix\Main\UserUtils::getCountryValue([
					'VALUE' => $userFields[$column]
				]);
				break;
			case 'POSITION':
				$arResult['ROWS'][$key]['data'][$column] = ($exportMode ? $userFields['WORK_POSITION'] : htmlspecialcharsEx($userFields['WORK_POSITION']));
				break;
			case 'COMPANY':
				$arResult['ROWS'][$key]['data'][$column] = ($exportMode ? $userFields['WORK_COMPANY'] : htmlspecialcharsEx($userFields['WORK_COMPANY']));
				break;
			case 'TAGS':
				$arResult['ROWS'][$key]['data'][$column] = implode(', ', array_map(
					function ($val) use ($arParams)
					{
						$uri = new \Bitrix\Main\Web\Uri($arParams['LIST_URL']);

						$uri->addParams([
							'apply_filter' => 'Y',
							'TAGS' => $val
						]);

						return
							$arParams['EXPORT_MODE'] == 'Y'
								? $val
								: '<a href="'.$uri->getUri().'" rel="nofollow" bx-tag-value="'.htmlspecialcharsBx($val).'">'.htmlspecialcharsEx($val).'</a>'
							;
					},
					$userFields['TAGS']->getNameList()
				));
				break;
			case 'WWW':
				$arResult['ROWS'][$key]['data'][$column] = (
					$exportMode
						? $userFields['PERSONAL_WWW']
						: '<a href="' . htmlspecialcharsbx($userFields['PERSONAL_WWW']) . '" target="_blank">' . htmlspecialcharsEx($userFields['PERSONAL_WWW']) . '</a>'
				);
				break;
			default:
				if (in_array($column, $ufCodesList))
				{
					ob_start();

					$arUserField = $ufList[$column];
					$arUserField['VALUE'] = $userFields[$column];

					$APPLICATION->includeComponent(
						"bitrix:system.field.view",
						$ufList[$column]['USER_TYPE']['USER_TYPE_ID'],
						[
							"arUserField" => $arUserField,
							"TEMPLATE" => '',
							"LAZYLOAD" => 'N',
						],
						null,
						[ 'HIDE_ICONS' => 'Y' ]
					);

					$value = ob_get_clean();
					if ($exportMode)
					{
						$value = \CTextParser::clearAllTags($value);
					}
					$arResult['ROWS'][$key]['data'][$column] = $value;
				}
				else
				{
					$arResult['ROWS'][$key]['data'][$column] = ($exportMode ? $userFields[$column] : htmlspecialcharsEx($userFields[$column]));
				}
		}
	}

	$actions = $row['actions'];
	$arResult['ROWS'][$key]['actions'] = [];

	if (in_array('view_profile', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_VIEW_TITLE'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_VIEW'),
			'ONCLICK' => 'BX.SidePanel.Instance.open(
						"'.htmlspecialcharsbx(str_replace(['#ID#', '#USER_ID#'], $userFields['ID'], $arParams['PATH_TO_USER'])).'",
						{
							cacheable: false,
							allowChangeHistory: true,
							contentClassName: "bitrix24-profile-slider-content",
							loader: "intranet:slider-profile",
							width: 1100
						}
					)',
			'DEFAULT' => true
		];
	}

	if (in_array('add_task', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_TASK_TITLE'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_TASK'),
			'ONCLICK' => 'jsBXIUL.addTask('.$userFields['ID'].')'
		];
	}

	if (in_array('message', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_MESSAGE_TITLE'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_MESSAGE'),
			'ONCLICK' => 'jsBXIUL.sendMessage('.$userFields['ID'].')'
		];
	}

	if (in_array('message_history', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_MESSAGE_HISTORY_TITLE'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_MESSAGE_HISTORY'),
			'ONCLICK' => 'jsBXIUL.viewMessageHistory('.$userFields['ID'].')'
		];
	}

	if (in_array('reinvite', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_REINVITE_TITLE'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_REINVITE'),
			'ONCLICK' => 'jsBXIUL.reinvite('.$userFields["ID"].', '.($userFields['USER_TYPE'] == 'extranet' ? 'true' : 'false').', this.currentTarget)'
		];
	}

	if (in_array('restore', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_RESTORE_TITLE'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_RESTORE'),
			'ONCLICK' => 'jsBXIUL.activityAction("restore", '.$userFields["ID"].')'
		];
	}

	if (in_array('delete', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_DELETE_TITLE'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_DELETE'),
			'ONCLICK' => 'jsBXIUL.activityAction("delete", '.$userFields["ID"].')'
		];
	}

	if (in_array('deactivate', $actions))
	{
		$deactivateTitle = Loc::getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_TITLE');
		$deactivateOnclick = 'jsBXIUL.activityAction("deactivate", '.$userFields["ID"].')';

		if (
			Loader::includeModule("bitrix24")
			&& !Bitrix\Bitrix24\Feature::isFeatureEnabled("user_dismissal")
			&& !\Bitrix\Bitrix24\Integrator::isIntegrator($userFields["ID"])
		)
		{
			$deactivateTitle.= "<span class='intranet-user-list-lock-icon'></span>";
			$deactivateOnclick = "top.BX.UI.InfoHelper.show('limit_dismiss');";
		}

		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_TITLE'),
			'TEXT' => $deactivateTitle,
			'ONCLICK' => $deactivateOnclick
		];
	}

	if (in_array('videocall', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_VIDEOCALL_TITLE'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_VIDEOCALL'),
			'ONCLICK' => 'jsBXIUL.videoCall('.$userFields["ID"].')'
		];
	}

	if (in_array('loginhistory', $actions))
	{
		$arResult['ROWS'][$key]['actions'][] = [
			'TITLE' => Loc::getMessage('INTRANET_USER_LIST_ACTION_LOGINHISTORY'),
			'TEXT' => Loc::getMessage('INTRANET_USER_LIST_ACTION_LOGINHISTORY'),
			'ONCLICK' => "BX.SidePanel.Instance.open('" . TimemanSection::getUserLoginHistoryUrlById((int)$arResult['ROWS'][$key]['id']) . "', {allowChangeHistory: false});",
		];
	}

	unset($arResult['ROWS'][$key]['data']['USER_FIELDS']);
}

if (!$exportMode)
{
	unset($arResult['GRID_COLUMNS']);
}

$arResult['TOOLBAR_MENU'] = [
	[
		'TYPE' => 'EXPORT_EXCEL',
		'TITLE' => Loc::getMessage('INTRANET_USER_LIST_MENU_EXPORT_EXCEL_TITLE'),
		'LINK' => (
			$arResult['EXCEL_EXPORT_LIMITED']
				? "javascript:BX.UI.InfoHelper.show('limit_crm_export_excel');"
				: UrlManager::getInstance()->createByBitrixComponent($this->getComponent(), 'export', [ 'type' => 'excel' ])
		)
	],
	[
		'TYPE' => 'SYNC_OUTLOOK',
		'TITLE' => Loc::getMessage('INTRANET_USER_LIST_MENU_SYNC_OUTLOOK_TITLE'),
		'LINK' => 'javascript:'.\CIntranetUtils::getStsSyncURL(
			[
				'LINK_URL' => $APPLICATION->GetCurPage()
			], 'contacts', ($arResult["EXTRANET_SITE"] != "Y")
		)
	],
	[
		'TYPE' => 'SYNC_CARDDAV',
		'TITLE' => Loc::getMessage('INTRANET_USER_LIST_MENU_SYNC_CARDDAV_TITLE'),
		'LINK' => 'javascript:'.$APPLICATION->getPopupLink(
				[
					"URL" => "/bitrix/groupdav.php?lang=".LANGUAGE_ID."&help=Y&dialog=Y",
				]
			)
	],
];

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
		'TYPE' => 'ADD',
		'TITLE' => Loc::getMessage('INTRANET_USER_LIST_BUTTON_INVITE_TITLE'),
		'CLICK' => new JsHandler(
			"jsBXIUL.showInvitation",
			"jsBXIUL"
		),
	];
}
