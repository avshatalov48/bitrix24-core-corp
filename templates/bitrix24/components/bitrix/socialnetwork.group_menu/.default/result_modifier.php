<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Application;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Uri;

$arResult["menuId"] = "group_panel_menu_".$arResult["Group"]["ID"];
$arResult['inIframe'] = \Bitrix\Main\Context::getCurrent()->getRequest()->getQuery('IFRAME') === 'Y';

$firstMenuItemCode = false;

/** @see \CMainInterfaceButtons::getUserOptions */
$userOptions = \CUserOptions::getOption("ui", $arResult["menuId"]);
$urlGeneralSpecific = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_GENERAL"], array("group_id" => $arResult["Group"]["ID"]));

$arResult['Group']['TypeCode'] = Workgroup::getTypeCodeByParams([
	'fields' => [
		'OPENED' => $arResult['Group']['OPENED'],
		'VISIBLE' => $arResult['Group']['VISIBLE'],
		'PROJECT' => $arResult['Group']['PROJECT'],
		'EXTERNAL' => (isset($arResult['Group']['IS_EXTRANET']) && $arResult['Group']['IS_EXTRANET'] === 'Y' ? 'Y' : 'N'),
	],
	'fullMode' => true,
]);

$arResult['Group']['Type'] = Workgroup::getTypeByCode([
	'code' => $arResult['Group']['TypeCode'],
	'fullMode' => true,
]);

$arResult['Group']['ProjectTypeCode'] = Workgroup::getProjectTypeCodeByParams([
	'fields' => [
		'PROJECT' => $arResult['Group']['PROJECT'],
		'SCRUM_PROJECT' => $arResult['Group']['SCRUM'],
	],
]);

$sampleKeysList = [
	'general' => 0,
	'tasks' => 1,
	'calendar' => 2,
	'files' => 3,
	'chat' => 4,
	'forum' => 5,
	'microblog' => 6,
	'blog' => 7,
	'photo' => 8,
	'group_lists' => 9,
	'wiki' => 10,
	'content_search' => 11,
	'marketplace' => 12,
];

if ($arResult['Group']['PROJECT'] === 'Y')
{
	$sampleKeysList = [
		'tasks' => 0,
		'general' => 1,
		'calendar' => 2,
		'files' => 3,
		'chat' => 4,
		'forum' => 5,
		'microblog' => 6,
		'blog' => 7,
		'photo' => 8,
		'group_lists' => 9,
		'wiki' => 10,
		'content_search' => 11,
		'marketplace' => 12,
	];
}

$sampleKeysList = array_filter($sampleKeysList, static function($key) use ($arResult) {
	return (
		(
			$key === 'general'
			&& array_key_exists('blog', $arResult['CanView'])
			&& $arResult['CanView']['blog']
		)
		|| (
			array_key_exists($key, $arResult['CanView'])
			&& $arResult['CanView'][$key]
		)
	);
}, ARRAY_FILTER_USE_KEY);

reset($sampleKeysList);
$firstKeyDefault = key($sampleKeysList);

if (
	is_array($userOptions)
	&& isset($userOptions["settings"])
	&& !empty($userOptions["settings"])
)
{
	$userOptionsSettings = json_decode($userOptions["settings"], true);

	if (
		is_array($userOptionsSettings)
		&& !empty($userOptionsSettings)
	)
	{
		$menuItems = array_keys($userOptionsSettings);
		foreach ($menuItems as $menuItem)
		{
			if (
				$menuItem === $arResult['menuId'] . '_chat'
				|| $menuItem === $arResult['menuId'] . '_marketplace'
				|| preg_match('/^' . $arResult['menuId'] . '_placement_/i', $menuItem, $matches)
			)
			{
				continue;
			}

			if (preg_match('/^'.$arResult["menuId"].'_(.*)$/i', $menuItem, $matches))
			{
				if (
					(
						array_key_exists($matches[1], $arResult["ActiveFeatures"])
						|| $matches[1] === 'general'
					)
					&& !in_array($matches[1], [ 'landing_knowledge', 'chat', 'marketplace' ], true)
				)
				{
					$firstMenuItemCode = $matches[1];
					break;
				}

				if (
					$matches[1] === 'view_all'
					&& array_key_exists('tasks', $arResult["ActiveFeatures"])
				)
				{
					$firstMenuItemCode = 'tasks';
					break;
				}
			}
		}
	}

	$arResult["Urls"]["General"] = $urlGeneralSpecific;
}
elseif ($firstKeyDefault !== 'general')
{
	$arResult["Urls"]["General"] = $urlGeneralSpecific;
}

unset($arResult['Urls']['chat']);

if ($arParams['PAGE_ID'] === 'group')
{
	$redirectUrl = false;

	if ($firstMenuItemCode)
	{
		if (
			(
				$firstMenuItemCode !== (string)$firstKeyDefault
				|| (string)$firstKeyDefault !== 'general'
			)
			&& isset($arResult["Urls"][$firstMenuItemCode])
		)
		{
			$url = $arResult["Urls"][$firstMenuItemCode];
			if (mb_strpos($url, '/') === 0)
			{
				$redirectUrl = $url;
			}
			elseif (isset($menuItems))
			{
				foreach ($menuItems as $menuItem)
				{
					if (
						preg_match('/^' . $arResult['menuId'] . '_(.*)$/i', $menuItem, $matches)
						&& isset($arResult['Urls'][$matches[1]])
						&& mb_strpos($arResult['Urls'][$matches[1]], '/') === 0
					)
					{
						$redirectUrl = $arResult['Urls'][$matches[1]];
						break;
					}
				}
			}
		}
	}
	elseif (
		$arResult['Group']['ProjectTypeCode'] === 'scrum'
		|| (
			!$arResult['CanView']['blog']
			&& $firstKeyDefault !== 'marketplace'
		)
	)
	{
		$redirectUrl = $arResult['Urls'][$firstKeyDefault];
	}

	if ($redirectUrl)
	{
		if ($arResult['inIframe'])
		{
			$redirectUrl = (new Uri($redirectUrl))->addParams([ 'IFRAME' => 'Y' ])->getUri();
		}

		LocalRedirect($redirectUrl);
	}
}
elseif (
	$arParams['componentPage'] === 'group_tasks'
	&& !$arResult['CanView']['tasks']
)
{
	$redirectUrl = $arResult['Urls']['view'];
	if ($arResult['inIframe'])
	{
		$redirectUrl = (new Uri($redirectUrl))->addParams([ 'IFRAME' => 'Y' ])->getUri();
	}

	LocalRedirect($redirectUrl);
}

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_GROUP_CARD", $this->__component->__parent->arResult))
	$arParams["PATH_TO_GROUP_CARD"] = $this->__component->__parent->arResult["PATH_TO_GROUP_CARD"];
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_MESSAGE_TO_GROUP", $this->__component->__parent->arResult))
	$arParams["PATH_TO_MESSAGE_TO_GROUP"] = $this->__component->__parent->arResult["PATH_TO_MESSAGE_TO_GROUP"];
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_GROUP_FEATURES", $this->__component->__parent->arResult))
	$arParams["PATH_TO_GROUP_FEATURES"] = $this->__component->__parent->arResult["PATH_TO_GROUP_FEATURES"];
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_GROUP_DELETE", $this->__component->__parent->arResult))
	$arParams["PATH_TO_GROUP_DELETE"] = $this->__component->__parent->arResult["PATH_TO_GROUP_DELETE"];
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_GROUP_REQUESTS_OUT", $this->__component->__parent->arResult))
	$arParams["PATH_TO_GROUP_REQUESTS_OUT"] = $this->__component->__parent->arResult["PATH_TO_GROUP_REQUESTS_OUT"];
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_REQUEST_GROUP", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_REQUEST_GROUP"] = $this->__component->__parent->arResult["PATH_TO_USER_REQUEST_GROUP"];
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_LEAVE_GROUP", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_LEAVE_GROUP"] = $this->__component->__parent->arResult["PATH_TO_USER_LEAVE_GROUP"];
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_GROUP_SUBSCRIBE", $this->__component->__parent->arResult))
	$arParams["PATH_TO_GROUP_SUBSCRIBE"] = $this->__component->__parent->arResult["PATH_TO_GROUP_SUBSCRIBE"];

if ($this->__component->__parent && $this->__component->__parent->arParams && array_key_exists("GROUP_USE_BAN", $this->__component->__parent->arParams))
	$arParams["GROUP_USE_BAN"] = $this->__component->__parent->arParams["GROUP_USE_BAN"];
$arParams["GROUP_USE_BAN"] = $arParams["GROUP_USE_BAN"] !== "N" ? "Y" : "N";

if ((int)$arResult["Group"]["IMAGE_ID"] <= 0)
{
	$arResult["Group"]["IMAGE_ID"] = COption::GetOptionInt("socialnetwork", "default_group_picture", false, SITE_ID);
}

$arResult["Group"]["IMAGE_FILE"] = array("src" => "");

if ((int)$arResult["Group"]["IMAGE_ID"] > 0)
{
	$arFileTmp = false;
	$imageFile = CFile::GetFileArray($arResult["Group"]["IMAGE_ID"]);
	if ($imageFile !== false)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$imageFile,
			array("width" => 100, "height" => 100),
			BX_RESIZE_IMAGE_EXACT,
			true
		);
	}

	if ($arFileTmp && array_key_exists("src", $arFileTmp))
	{
		$arResult["Group"]["IMAGE_FILE"] = $arFileTmp;
	}
}

$arResult["Urls"]["Card"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_CARD"], array("group_id" => $arResult["Group"]["ID"]));
$arResult["Urls"]["MessageToGroup"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MESSAGE_TO_GROUP"], array("group_id" => $arResult["Group"]["ID"]));
$arResult["Urls"]["Features"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_FEATURES"], array("group_id" => $arResult["Group"]["ID"]));
$arResult["Urls"]["Delete"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_DELETE"], array("group_id" => $arResult["Group"]["ID"]));
$arResult["Urls"]["GroupRequestsOut"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_REQUESTS_OUT"], array("group_id" => $arResult["Group"]["ID"]));
$arResult["Urls"]["UserRequestGroup"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_REQUEST_GROUP"], array("group_id" => $arResult["Group"]["ID"]));
$arResult["Urls"]["UserLeaveGroup"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_LEAVE_GROUP"], array("group_id" => $arResult["Group"]["ID"]));
$arResult["Urls"]["Subscribe"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_SUBSCRIBE"], array("group_id" => $arResult["Group"]["ID"]));
$arResult["Urls"]["GroupsList"] = \Bitrix\Socialnetwork\ComponentHelper::getWorkgroupSEFUrl();
$arResult["Urls"]["Copy"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_COPY"], array("group_id" => $arResult["Group"]["ID"]));

$arResult['CanView']['general'] = (CSocNetFeatures::isActiveFeature(SONET_ENTITY_GROUP, $arResult['Group']['ID'], 'blog'));

$arResult["Title"]["chat"] = ((array_key_exists("chat", $arResult["ActiveFeatures"]) && $arResult["ActiveFeatures"]["chat"] <> '') ? $arResult["ActiveFeatures"]["chat"] : GetMessage("SONET_UM_CHAT"));
$arResult["OnClicks"] = array(
	"chat" => "BX.Socialnetwork.UI.Common.openMessenger('".$arResult["Group"]["ID"]."');"
);

uksort($arResult["CanView"], function($a, $b) use ($sampleKeysList) {
	$valA = (isset($sampleKeysList[$a]) ? $sampleKeysList[$a] : 100);
	$valB = (isset($sampleKeysList[$b]) ? $sampleKeysList[$b] : 100);
	if ($valA > $valB)
	{
		return 1;
	}

	if ($valA < $valB)
	{
		return -1;
	}

	return 0;
});

if ($USER->isAuthorized())
{
	\Bitrix\Socialnetwork\WorkgroupViewTable::set(array(
		'USER_ID' => $USER->getId(),
		'GROUP_ID' => $arResult["Group"]["ID"]
	));
}

if (
	$arResult["CurrentUserPerms"]["UserRole"] === UserToGroupTable::ROLE_REQUEST
	&& $arResult["Group"]["VISIBLE"] === "Y"
	&& !$arResult["HideArchiveLinks"]
)
{
	$arResult["bShowRequestSentMessage"] = $arResult["CurrentUserPerms"]["InitiatedByType"];
	if (\Bitrix\Socialnetwork\Helper\UserToGroup\RequestPopup::checkHideRequestPopup([
		'userId' => (int)$USER->getId(),
		'groupId' => (int)$arResult['Group']['ID'],
	]))
	{
		$arResult["bShowRequestSentMessage"] = false;
	}

	$arResult["UserRelationId"] = false;
	$res = \Bitrix\Socialnetwork\UserToGroupTable::getList(array(
		'filter' => array(
			'USER_ID' => $USER->getId(),
			'GROUP_ID' => $arResult["Group"]["ID"]
		),
		'select' => array('ID')
	));
	if ($relation = $res->fetch())
	{
		$arResult["UserRelationId"] = $relation['ID'];
	}

	if (empty($arParams["PATH_TO_USER_REQUESTS"]))
	{
		$arParams["PATH_TO_USER_REQUESTS"] = \Bitrix\Socialnetwork\ComponentHelper::getUserSEFUrl().'user/#user_id#/requests/';
	}

	$arResult["Urls"]["UserRequests"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_REQUESTS"], array("user_id" => $USER->getId()));
}

$arResult['Group']['NUMBER_OF_REQUESTS'] = 0;
$res = UserToGroupTable::getList(array(
	'filter' => array(
		'GROUP_ID' => $arResult['Group']['ID'],
		'ROLE' => UserToGroupTable::ROLE_REQUEST,
		'INITIATED_BY_TYPE' => UserToGroupTable::INITIATED_BY_USER
	),
	'runtime' => array(
		new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
	),
	'select' => array('CNT')
));
if ($relation = $res->fetch())
{
	$arResult['Group']['NUMBER_OF_REQUESTS'] = (int)$relation['CNT'];
}

$arResult["HideArchiveLinks"] = (
	$arResult['Group']["CLOSED"] === "Y"
	&& Option::get("socialnetwork", "work_with_closed_groups", "N") !== "Y"
);

$arResult["bUserCanRequestGroup"] = (
	$arResult["Group"]["VISIBLE"] === "Y"
	&& (!isset($arResult["bExtranet"]) || !$arResult["bExtranet"])
	&& !$arResult["HideArchiveLinks"]
	&& (
		!$arResult["CurrentUserPerms"]["UserRole"]
		|| (
			$arResult["CurrentUserPerms"]["UserRole"] == UserToGroupTable::ROLE_REQUEST
			&& $arResult["CurrentUserPerms"]["InitiatedByType"] == UserToGroupTable::INITIATED_BY_GROUP
		)
	)
);

$arResult["FAVORITES"] = false;
if ($USER->IsAuthorized())
{
	$res = \Bitrix\Socialnetwork\WorkgroupFavoritesTable::getList(array(
		'filter' => array(
			'GROUP_ID' => $arResult["Group"]["ID"],
			'USER_ID' => $USER->getId()
		)
	));
	$arResult["FAVORITES"] = ($res->fetch());
}

$sliderPages = [
	'calendar' => [
		'loader' => 'intranet:calendar',
	],
	'files' => [
		'loader' => 'intranet:disk',
	],
	'blog' => [
		'loader' => 'intranet:slider-livefeed',
	],
//	'General' => [],
//	'view' => [],
	'group_lists' => [],
//	'forum' => [],
	'wiki' => [],
	'photo' => [],
	'landing_knowledge' => [
		'newWindowLabel' => false,
		'copyLinkLabel' => false,
		'allowChangeHistory' => false,
	],
];

if (!$firstMenuItemCode)
{
	$firstMenuItemCode = $firstKeyDefault;
}

foreach ($arResult['Urls'] as $key => $value)
{
	if (
		$arResult['inIframe']
		&& in_array(mb_strtolower($key), [ 'view', 'general', 'tasks', $firstMenuItemCode ], true)
	)
	{
		$arResult['Urls'][$key] = (new Uri($value))->addParams([ 'IFRAME' => 'Y' ])->getUri();
	}
	elseif (preg_match('/^javascript:void\((.*)\);$/', $value, $matches))
	{
		$arResult['OnClicks'][$key] = $matches[1];
	}
	elseif (
		isset($sliderPages[$key])
		&& (string)$value !== ''
	)
	{
		$options = [
			'customLeftBoundary' => 270,
			'loader' => $sliderPages[$key]['loader'] ?? '',
			'newWindowLabel' => $sliderPages[$key]['newWindowLabel'] ?? true,
			'copyLinkLabel' => $sliderPages[$key]['copyLinkLabel'] ?? true,
			'allowChangeHistory' => $sliderPages[$key]['allowChangeHistory'] ?? null,
		];

		//to display correct knowledge url;
		if ($key === 'landing_knowledge')
		{
			$knowledgeUrl = '/knowledge/group/';
			$onLoadLink = (new Uri($value))->getPath();
			$onCloseLink = str_replace('#group_id#', (int)$arParams['GROUP_ID'], $arParams['PATH_TO_GROUP']);

			$functions = [
				'onCloseComplete' => $onCloseLink
			];

			if (mb_stripos($onLoadLink, $knowledgeUrl) !== false)
			{
				$functions['onLoad'] = $onLoadLink;
			}

			$config = mb_substr(CUtil::phpToJSObject($options), 0, -1);
			$config .= ',';
			$events = 'events: {';

			foreach ($functions as $functionName => $link)
			{
				$events .= "
					{$functionName}: function(){
						top.window.history.replaceState({}, '', '{$link}');
					},
				";
			}

			$events .= '},';
			$config .= $events;
			$config .= '}';

			$uri = (new Uri($value))->addParams(['IFRAME' => 'Y'])->getUri();
			$arResult['OnClicks'][$key] = "
				top.BX.SidePanel.Instance.open(
					'{$uri}',
					{$config},
				);
			";
		}
		else
		{
			$arResult['OnClicks'][$key] =
				"BX.SidePanel.Instance.open('"
				. (new Uri($value))->addParams(['IFRAME' => 'Y'])->getUri() .
				"', " . CUtil::phpToJSObject($options) . " )";
		}
	}
	elseif ($key === 'marketplace')
	{
		$arResult['OnClicks'][$key] = "if (BX.rest)
		{
			BX.rest.Marketplace.open({
				PLACEMENT: 'SONET_GROUP_DETAIL_TAB',
			});
		}";
	}
	elseif (
		empty($arResult['OnClicks'][$key])
		&& !in_array(mb_strtolower($key), [
			'edit',
			'userrequestgroup',
			'grouprequestsearch',
			'grouprequests',
			'groupmods',
			'groupusers',
			'groupban',
			'delete',
			'features',
			'card',
			'grouprequestsout',
			'userleavegroup',
			'copy',
			'groupslist',
			'view',
			'general',
		])
	)
	{
		$uri = new Uri($value);
		$arResult['OnClicks'][$key] = "top.location.href = '" . $uri->getUri() . "'";
	}
}

$arResult['IS_CURRENT_PAGE_FIRST'] = \Bitrix\Socialnetwork\ComponentHelper::isCurrentPageFirst([
	'componentName' => 'bitrix:socialnetwork_group',
	'page' => $arParams['PAGE_ID'],
	'entityId' => $arResult['Group']['ID'],
	'firstMenuItemCode' => $firstMenuItemCode,
	'canView' => $arResult['CanView'],
]);

$arResult['projectWidgetData'] = [
	'avatar' => ($arResult['Group']['IMAGE_FILE']['src'] ?? ''),
	'name' => $arResult['Group']['NAME'],
	'isProject' => ($arResult['Group']['PROJECT'] === 'Y'),
];

$arResult['isSubscribed'] = (
	in_array($arResult['CurrentUserPerms']['UserRole'], UserToGroupTable::getRolesMember(), true)
	&& CSocNetSubscription::isUserSubscribed($USER->getId(), 'SG' . $arResult['Group']['ID'])
);

$arResult['bindingMenuItems'] = \Bitrix\Intranet\Binding\Menu::getMenuItems(
	'socialnetwork',
	'group_notifications',
	[
		'GROUP_ID' => $arResult['Group']['ID'],
	]
);