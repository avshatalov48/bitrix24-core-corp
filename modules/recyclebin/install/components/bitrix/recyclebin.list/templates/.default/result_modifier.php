<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Context;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Recyclebin\Internals\User;

function getUserName($row)
{
	static $cache = [];

	if (array_key_exists($row['USER_ID'] ?? null, $cache))
	{
		return $cache[$row['USER_ID'] ?? null];
	}

	$userIcon = '';
	if ($row['USER_IS_EXTERNAL'])
	{
		$userIcon = 'recyclebin-grid-avatar-extranet';
	}
	if ($row["USER_EXTERNAL_AUTH_ID"] == 'email')
	{
		$userIcon = 'recyclebin-grid-avatar-mail';
	}
	if ($row["USER_IS_CRM"])
	{
		$userIcon = 'recyclebin-grid-avatar-crm';
	}
	if ($row["USER_IS_COLLABER"] ?? false)
	{
		$userIcon = 'recyclebin-grid-avatar-collaber';
	}

	$userAvatar = 'recyclebin-grid-avatar-empty';
	if ($row['USER_AVATAR'])
	{
		$userAvatar = '';
	}

	$userName = '<span class="recyclebin-grid-avatar  '.$userAvatar.' '.$userIcon.'" 
			'.($row['USER_AVATAR'] ? 'style="background-image: url(\''.Uri::urnEncode($row['USER_AVATAR']).'\')"' : '').'></span>';

	$userName .= '<span class="recyclebin-grid-username-inner '.
				 $userIcon.
				 '">'.
				 htmlspecialcharsbx($row['USER_DISPLAY_NAME']).
				 '</span>';

	$cache[$row['USER_ID'] ?? null] = '<div class="recyclebin-grid-username-wrapper"><a href="'.
							  htmlspecialcharsbx($row['USER_PROFILE_URL']).
							  '" class="recyclebin-grid-username">'.
							  $userName.
							  '</a></div>';

	return $cache[$row['USER_ID'] ?? null];
}

/**
 * @return string
 */
function getLicensePopupShowAction()
{
	$popupTitle = Loc::getMessage('RECYCLEBIN_LICENSE_POPUP_TITLE');
	$popupText = GetMessageJS('RECYCLEBIN_LICENSE_POPUP_TEXT');

	return "BX.Bitrix24.LicenseInfoPopup.show('recyclebin', '{$popupTitle}', '{$popupText}');";
}

/**
 * @param $entityId
 * @param $entityType
 * @param $restoreDisablingOptions
 * @return string
 * @throws \Bitrix\Main\LoaderException
 */
function getColumnRestoreAction($entityId, $entityType, $restoreDisablingOptions)
{
	if (!Loader::includeModule('bitrix24'))
	{
		return "BX.Recyclebin.List.restore({$entityId}, '{$entityType}')";
	}

	if (!Feature::isFeatureEnabled("recyclebin") || ($restoreDisablingOptions['IS_ON'] ?? false) === true)
	{
		if ($restoreDisablingOptions['EXISTS'])
		{
			if ($restoreDisablingOptions['IS_ON'])
			{
				if ($restoreDisablingOptions['FEATURE_ID'])
				{
					return "BX.Recyclebin.List.showLimit('{$restoreDisablingOptions['FEATURE_ID']}');";
				}
				else
				{
					return "BX.UI.InfoHelper.show('{$restoreDisablingOptions['SLIDER_CODE']}', {isLimit: true, limitAnalyticsLabels: {module: 'recyclebin', source: '{$restoreDisablingOptions['MODULE']}'}});";
				}
			}
			return "BX.Recyclebin.List.restore({$entityId}, '{$entityType}')";
		}

		return getLicensePopupShowAction();
	}

	return "BX.Recyclebin.List.restore({$entityId}, '{$entityType}')";
}

/**
 * @param $entityId
 * @param $entityType
 * @return string
 */
function getColumnRemoveAction($entityId, $entityType)
{
	return "BX.Recyclebin.List.remove({$entityId}, '{$entityType}')";
}

/**
 * @param $row
 * @param $restoreDisablingOptions
 * @return array
 * @throws \Bitrix\Main\LoaderException
 */
function prepareActionsColumn($row, $restoreDisablingOptions)
{
	$list = [];

	$entityId = (int)$row['ID'];
	$entityType = $row['ENTITY_TYPE'] ?? null;

	$list[] = [
		"text" => GetMessageJS('RECYCLEBIN_CONTEXT_MENU_TITLE_RESTORE'),
		'onclick' => getColumnRestoreAction($entityId, $entityType, $restoreDisablingOptions),
		'className' => ($restoreDisablingOptions['IS_ON'] ? 'recyclebin-list-menu-popup-item-lock' : ''),
	];

	if (User::isSuper())
	{
		$list[] = [
			"text" => GetMessageJS('RECYCLEBIN_CONTEXT_MENU_TITLE_REMOVE'),
			'onclick' => getColumnRemoveAction($entityId, $entityType, $restoreDisablingOptions),
		];
	}

	return $list;
}

/**
 * @param $entityTypes
 * @param $entityAdditionalData
 * @return array
 */
function getRestoreDisablingOptions($entityTypes, $entityAdditionalData)
{
	$restoreDisablingExists = false;
	$isRestoreDisablingOn = false;
	$restoreDisablingType = '';
	$sliderCode = '';
	$module = 'recyclebin';
	$featureId = null;

	foreach ($entityTypes as $typeId => $type)
	{
		$entityLimitData = $entityAdditionalData[$typeId]['LIMIT_DATA'] ?? null;
		if (isset($entityLimitData['RESTORE']['DISABLE']))
		{
			$restoreDisablingExists = true;
			if ($entityLimitData['RESTORE']['DISABLE'])
			{
				$isRestoreDisablingOn = true;
				$restoreDisablingType = $typeId;
				$sliderCode = $entityLimitData['RESTORE']['SLIDER_CODE'];
				$module = $entityAdditionalData[$typeId]['MODULE_ID'];
				$featureId = $entityLimitData['RESTORE']['FEATURE_ID'] ?? null;
				
				break;
			}
		}
	}

	return [
		'EXISTS' => $restoreDisablingExists,
		'IS_ON' => $isRestoreDisablingOn,
		'TYPE' => $restoreDisablingType,
		'SLIDER_CODE' => $sliderCode,
		'MODULE' => $module,
		'FEATURE_ID' => $featureId,
	];
}

/**
 * @param $restoreDisablingOptions
 * @return string
 * @throws \Bitrix\Main\LoaderException
 */
function getGroupRestoreAction(array $restoreDisablingOptions = [], bool $useDeletionManager = false): string
{
	$action = 'BX.Recyclebin.List.restoreBatch(' . $useDeletionManager . ');';

	$defaultFeatureCheck = !Loader::includeModule('bitrix24') || Feature::isFeatureEnabled('recyclebin');

	if ($defaultFeatureCheck && !(($restoreDisablingOptions['IS_ON'] ?? false) === true))
	{
		return $action;
	}

	if ($restoreDisablingOptions['EXISTS'])
	{
		if ($restoreDisablingOptions['IS_ON'])
		{
			$featureId = (string)$restoreDisablingOptions['FEATURE_ID'];
			$code = $restoreDisablingOptions['SLIDER_CODE'];
			$source = $restoreDisablingOptions['MODULE'];
			$params = "{isLimit: true, limitAnalyticsLabels: {module: 'recyclebin', source: '{$source}'}}";
			
			if ($featureId)
			{
				return "BX.Recyclebin.List.showLimit('{$featureId}');";
			}
			else
			{
				return "BX.UI.InfoHelper.show('{$code}', {$params});";
			}
		}

		return $action;
	}

	return getLicensePopupShowAction();
}

function getGroupRemoveAction($useDeletionManager = false): string
{
	return 'BX.Recyclebin.List.removeBatch(' . $useDeletionManager . ');';
}

function prepareGroupActions($restoreDisablingOptions, $arResult = [])
{
	$iconLock = ($restoreDisablingOptions['IS_ON'] ? ' ui-btn-icon-lock' : '');
	$useDeletionManager = (bool)($arResult['USE_FOR_ALL_CHECKBOX'] ?? false);

	$items = [];
	$items[] = [
		"TYPE"     => \Bitrix\Main\Grid\Panel\Types::BUTTON,
		"TEXT"     => GetMessage("RECYCLEBIN_GROUP_ACTIONS_RESTORE"),
		"VALUE"    => "restore",
		"CLASS" => 'ui-btn ui-btn-light-border ui-btn-medium'.$iconLock, // Added a new button with a lock icon
		"ONCHANGE" => [
			[
				"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				"DATA"   => [['JS' => getGroupRestoreAction($restoreDisablingOptions, $useDeletionManager)]],
			],
		],
	];

	if (User::isSuper())
	{
		$items[] = [
			"TYPE"     => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT"     => GetMessage("RECYCLEBIN_GROUP_ACTIONS_DELETE"),
			"VALUE"    => "delete",
			"ONCHANGE" => [
				[
					"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					"DATA"   => [['JS' => getGroupRemoveAction($useDeletionManager)]],
				],
			],
		];
	}

	if ($useDeletionManager)
	{
		$snippet = new Snippet();
		$items[] = $snippet->getForAllCheckbox();
	}

	return [
		'GROUPS' => [
			[
				'ITEMS' => $items,
			],
		],
	];
}

function formatDateRecycle($date): string
{
	$culture = Context::getCurrent()->getCulture();
	$dateFormat = (
		date('Y') !== date('Y', strtotime($date))
			? $culture->getLongDateFormat()
			: $culture->getDayMonthFormat()
	);
	$format = "{$dateFormat}, {$culture->getShortTimeFormat()}";

	return FormatDate($format, MakeTimeStamp($date));
}

$restoreDisablingOptions = getRestoreDisablingOptions($arResult['ENTITY_TYPES'], $arResult['ENTITY_ADDITIONAL_DATA']);

$arResult['ROWS'] = [];
if (!empty($arResult['GRID']['DATA']))
{
	foreach ($arResult['GRID']['DATA'] as $row)
	{
		$arResult['ROWS'][] = [
			'id' => $row['ID'],
			'actions' => prepareActionsColumn($row, $restoreDisablingOptions),
			'columns' => [
				'ID' => $row['ID'],
				'ENTITY_ID' => $row['ENTITY_ID'],
				'ENTITY_TYPE' => $arResult['ENTITY_TYPES'][$row['ENTITY_TYPE']] ?? null,
				'NAME' => htmlspecialcharsbx($row['NAME']),
				'MODULE_ID' => isset($row['MODULE_ID']) ? $arResult['MODULES_LIST'][$row['MODULE_ID']] : null,
				'TIMESTAMP' => formatDateRecycle($row['TIMESTAMP']),
				'USER_ID' => getUserName($row),
			],
		];
	}
}
$arResult['GROUP_ACTIONS'] = prepareGroupActions($restoreDisablingOptions, $arResult);