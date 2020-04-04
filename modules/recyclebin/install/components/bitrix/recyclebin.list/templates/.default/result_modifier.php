<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arResult['ROWS'] = [];

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Localization\Loc;
use Bitrix\Recyclebin\Internals\User;

function getUserName($row)
{
	static $cache = [];

	if (array_key_exists($row['USER_ID'], $cache))
	{
		return $cache[$row['USER_ID']];
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

	$userAvatar = 'recyclebin-grid-avatar-empty';
	if ($row['USER_AVATAR'])
	{
		$userAvatar = '';
	}

	$userName = '<span class="recyclebin-grid-avatar  '.$userAvatar.' '.$userIcon.'" 
			'.($row['USER_AVATAR'] ? 'style="background-image: url(\''.$row['USER_AVATAR'].'\')"' : '').'></span>';

	$userName .= '<span class="recyclebin-grid-username-inner '.
				 $userIcon.
				 '">'.
				 htmlspecialcharsbx($row['USER_DISPLAY_NAME']).
				 '</span>';

	$cache[$row['USER_ID']] = '<div class="recyclebin-grid-username-wrapper"><a href="'.
							  htmlspecialcharsbx($row['USER_PROFILE_URL']).
							  '" class="recyclebin-grid-username">'.
							  $userName.
							  '</a></div>';

	return $cache[$row['USER_ID']];
}

function prepareActionsColumn($row)
{
	$list = [];

	if (\CModule::IncludeModule('bitrix24'))
	{
		if (Feature::isFeatureEnabled("recyclebin"))
		{
			$restoreAction = 'BX.Recyclebin.List.restore('.(int)$row['ID'].', "'.$row['ENTITY_TYPE'].'")';
		}
		else
		{
			$restoreAction = 'BX.Bitrix24.LicenseInfoPopup.show("recyclebin", "'.
							 Loc::getMessage('RECYCLEBIN_LICENSE_POPUP_TITLE').
							 '", "'.
							 GetMessageJS('RECYCLEBIN_LICENSE_POPUP_TEXT').
							 '");';
		}
	}
	else
	{
		$restoreAction = 'BX.Recyclebin.List.restore('.(int)$row['ID'].', "'.$row['ENTITY_TYPE'].'")';
	}

	$list[] = [
		"text"    => GetMessageJS('RECYCLEBIN_CONTEXT_MENU_TITLE_RESTORE'),
		'onclick' => $restoreAction,
	];

	if (User::isSuper() || User::isAdmin())
	{
		$deleteAction = 'BX.Recyclebin.List.remove('.(int)$row['ID'].', "'.$row['ENTITY_TYPE'].'")';
		$list[] = [
			"text"    => GetMessageJS('RECYCLEBIN_CONTEXT_MENU_TITLE_REMOVE'),
			'onclick' => $deleteAction,
		];
	}

	return $list;
}

/**
 * @return array
 */
function prepareGroupActions()
{
	if (\CModule::IncludeModule('bitrix24'))
	{
		if (Feature::isFeatureEnabled("recyclebin"))
		{
			$restoreAction = 'BX.Recyclebin.List.restoreBatch();';
		}
		else
		{
			$restoreAction = 'BX.Bitrix24.LicenseInfoPopup.show("recyclebin", "'.
							 Loc::getMessage('RECYCLEBIN_LICENSE_POPUP_TITLE').
							 '", "'.
							 GetMessageJS('RECYCLEBIN_LICENSE_POPUP_TEXT').
							 '");';
		}
	}
	else
	{
		$restoreAction = 'BX.Recyclebin.List.restoreBatch();';
	}

	$items = [
		[
			"TYPE"     => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT"     => GetMessage("RECYCLEBIN_GROUP_ACTIONS_RESTORE"),
			"VALUE"    => "restore",
			"ONCHANGE" => [
				[
					"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					"DATA"   => [['JS' => $restoreAction]]
				]
			]
		]
	];

	if (User::isSuper() || User::isAdmin())
	{
		$items[] = [
			"TYPE"     => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT"     => GetMessage("RECYCLEBIN_GROUP_ACTIONS_DELETE"),
			"VALUE"    => "delete",
			"ONCHANGE" => [
				[
					"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					"DATA"   => [['JS' => "BX.Recyclebin.List.removeBatch();"]]
				]
			]
		];
	}
	$groupActions = [
		'GROUPS' => [
			[
				'ITEMS' => $items
			]
		]
	];

	return $groupActions;
}

function getDateTimeFormat()
{
	if (defined('FORMAT_DATETIME'))
	{
		$format = FORMAT_DATETIME;
	}
	else
	{
		$format = \CSite::GetDateFormat("FULL");
	}

	return $GLOBALS['DB']->DateFormatToPHP($format); // have to make php format from site format
}

function formatDateTime($stamp, $format = false)
{
	$simple = false;

	// accept also FORMAT_DATE and FORMAT_DATETIME as ones of the legal formats
	if ((defined('FORMAT_DATE') && $format == FORMAT_DATE) ||
		(defined('FORMAT_DATETIME') && $format == FORMAT_DATETIME))
	{
		$format = $GLOBALS['DB']->dateFormatToPHP($format);
		$simple = true;
	}

	$default = getDateTimeFormat();
	if ($format === false)
	{
		$format = $default;
		$simple = true;
	}

	if ($simple)
	{
		// its a simple format, we can use a simpler function
		return date($format, $stamp);
	}
	else
	{
		return \FormatDate($format, $stamp);
	}
}

function formatDateRecycle($date)
{
	$curTimeFormat = "HH:MI:SS";
	$format = 'j F';
	if (LANGUAGE_ID == "en")
	{
		$format = "F j";
	}
	if (LANGUAGE_ID == "de")
	{
		$format = "j. F";
	}

	if (date('Y') != date('Y', strtotime($date)))
	{
		if (LANGUAGE_ID == "en")
		{
			$format .= ",";
		}

		$format .= ' Y';
	}

	$rsSite = CSite::GetByID(SITE_ID);
	if ($arSite = $rsSite->Fetch())
	{
		$curDateFormat = $arSite["FORMAT_DATE"];
		$curTimeFormat = str_replace($curDateFormat." ", "", $arSite["FORMAT_DATETIME"]);
	}

	if ($curTimeFormat == "HH:MI:SS")
	{
		$currentDateTimeFormat = " G:i";
	}
	else //($curTimeFormat == "H:MI:SS TT")
	{
		$currentDateTimeFormat = " g:i a";
	}

	if (date('Hi', strtotime($date)) > 0)
	{
		$format .= ', '.$currentDateTimeFormat;
	}

	$str = formatDateTime(MakeTimeStamp($date), $format);

	return $str;
}

if (!empty($arResult['GRID']['DATA']))
{
	$users = [];
	foreach ($arResult['GRID']['DATA'] as $row)
	{
		$users[] = $row['USER_ID'];
	}

	foreach ($arResult['GRID']['DATA'] as $row)
	{
		$arResult['ROWS'][] = [
			"id"      => $row["ID"],
			'actions' => prepareActionsColumn($row),
			'columns' => [
				'ID'          => $row['ID'],
				'ENTITY_ID'   => $row['ENTITY_ID'],
				'ENTITY_TYPE' => $arResult['ENTITY_TYPES'][$row['ENTITY_TYPE']],
				'NAME'        => htmlspecialcharsbx($row['NAME']),
				'MODULE_ID'   => $arResult['MODULES_LIST'][$row['MODULE_ID']],
				'TIMESTAMP'   => formatDateRecycle($row['TIMESTAMP']),
				'USER_ID'     => getUserName($row)
			]
		];
	}
}
$arResult['GROUP_ACTIONS'] = prepareGroupActions();