<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true){
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Uri;

$arResult["menuId"] = "group_panel_menu_".$arResult["Group"]["ID"];
$arResult['inIframe'] = \Bitrix\Main\Context::getCurrent()->getRequest()->getQuery('IFRAME') === 'Y';

$firstMenuItemCode = false;

/** @see \CMainInterfaceButtons::getUserOptions */
$userOptions = \CUserOptions::getOption("ui", $arResult["menuId"]);
$urlGeneralSpecific = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_GENERAL"], array("group_id" => $arResult["Group"]["ID"]));

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

if (!\CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arResult["Group"]["ID"], 'tasks'))
{
	unset($sampleKeysList['tasks']);
}

if (\Bitrix\Main\ModuleManager::isModuleInstalled('intranet'))
{
	$arResult['CanView']['blog'] = false;
}

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

			$firstMenuItem = preg_match('/^'.$arResult["menuId"].'_(.*)$/i', $menuItem, $matches);
			if (!empty($matches))
			{
				if (
					array_key_exists($matches[1], $arResult["ActiveFeatures"])
					|| $matches[1] === 'general'
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

if ($arParams['PAGE_ID'] === 'group')
{
	$redirectUrl = false;

	if ($firstMenuItemCode)
	{
		if (
			(
				(string)$firstMenuItemCode !== (string)$firstKeyDefault
				|| (string)$firstKeyDefault !== 'general'
			)
			&& isset($arResult["Urls"][$firstMenuItemCode])
		)
		{
			$url = $arResult["Urls"][$firstMenuItemCode];
			if (mb_substr($url, 0, 1) === "/")
			{
				$redirectUrl = $url;
			}
		}
	}
/*
	elseif (
		$firstKeyDefault !== 'general'
		&& $arResult['CanView'][$firstKeyDefault]
	)
	{
		$redirectUrl = $arResult['Urls'][$firstKeyDefault];
	}
*/
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

$arResult["CanView"]["chat"] = (
	array_key_exists("chat", $arResult["ActiveFeatures"])
	&& in_array($arResult["CurrentUserPerms"]["UserRole"], UserToGroupTable::getRolesMember())
);
$arResult["CanView"]["general"] = true;

$arResult["Title"]["chat"] = ((array_key_exists("chat", $arResult["ActiveFeatures"]) && $arResult["ActiveFeatures"]["chat"] <> '') ? $arResult["ActiveFeatures"]["chat"] : GetMessage("SONET_UM_CHAT"));
$arResult["OnClicks"] = array(
	"chat" => "top.BXIM.openMessenger('sg".$arResult["Group"]["ID"]."');"
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
	$arResult["CurrentUserPerms"]["UserRole"] == UserToGroupTable::ROLE_REQUEST
	&& $arResult["Group"]["VISIBLE"] === "Y"
	&& !$arResult["HideArchiveLinks"]
)
{
	$arResult["bShowRequestSentMessage"] = $arResult["CurrentUserPerms"]["InitiatedByType"];

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

$arResult['Group']['TypeCode'] = Workgroup::getTypeCodeByParams(array(
	'fields' => array(
		'OPENED' => $arResult['Group']['OPENED'],
		'VISIBLE' => $arResult['Group']['VISIBLE'],
		'PROJECT' => $arResult['Group']['PROJECT'],
		'EXTERNAL' => (isset($arResult['Group']['IS_EXTRANET']) && $arResult['Group']['IS_EXTRANET'] === 'Y' ? 'Y' : 'N')
	),
	'fullMode' => true
));
$arResult['Group']['Type'] = Workgroup::getTypeByCode(array(
	'code' => $arResult['Group']['TypeCode'],
	'fullMode' => true
));

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
	&& !$arResult["bExtranet"]
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
		'loader' => 'intranet:livefeed',
	],
//	'General' => [],
//	'view' => [],
	'group_lists' => [],
	'forum' => [],
	'wiki' => [],
	'photo' => [],
];

foreach ($arResult['Urls'] as $key => $value)
{
	if (
		$arResult['inIframe']
		&& in_array(mb_strtolower($key), [ 'view', 'general', 'tasks' ])
	)
	{
		$arResult['Urls'][$key] = (new Uri($value))->addParams([ 'IFRAME' => 'Y' ])->getUri();
	}
	elseif (isset($sliderPages[$key]))
	{
		$arResult['OnClicks'][$key] = "BX.SidePanel.Instance.open('" . (new Uri($value))->addParams([ 'IFRAME' => 'Y' ])->getUri() . "', {
			customLeftBoundary: 270,
			loader: '" . ($sliderPages[$key]['loader'] ?? '') . "', 
			newWindowLabel: true,
			copyLinkLabel: true,
		})";
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
			'landing_knowledge',
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
