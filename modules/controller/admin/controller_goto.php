<?php
define('NOT_CHECK_PERMISSIONS', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */
$member_id = intval($_REQUEST['member']);

if (!CModule::IncludeModule('controller'))
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

$bCanAuthorize = false;
$bAsAdmin = false;
if ($USER->CanDoOperation('controller_member_auth_admin'))
{
	$bCanAuthorize = true;
	$bAsAdmin = true;
}
elseif ($USER->CanDoOperation('controller_member_auth'))
{
	$bCanAuthorize = true;
	$bAsAdmin = false;
}
elseif ($member_id > 0 && $USER->IsAuthorized())
{
	foreach (\Bitrix\Controller\AuthGrantTable::getControllerMemberScopes($member_id, $USER->GetID(), $USER->GetUserGroupArray()) as $grant)
	{
		if ($grant['SCOPE'] === 'user')
		{
			$bCanAuthorize = true;
		}
		elseif ($grant['SCOPE'] === 'admin')
		{
			$bCanAuthorize = true;
			$bAsAdmin = true;
		}
	}
}

if (!$bCanAuthorize)
{
	LocalRedirect('/bitrix/admin/controller_member_admin.php');
}

require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/controller/prolog.php';

IncludeModuleLangFile(__FILE__);

$dbr = CControllerMember::GetByID($member_id);
$ar = $dbr->GetNext();
if (!$ar)
{
	LocalRedirect('/bitrix/admin/controller_member_admin.php');
}

if ($bAsAdmin)
{
//Authorize as admin
	$param = 'Array(
		"LOGIN"=>"' . EscapePHPString($USER->GetParam('LOGIN')) . '",
		"NAME"=>"' . EscapePHPString($USER->GetParam('FIRST_NAME')) . '",
		"LAST_NAME"=>"' . EscapePHPString($USER->GetParam('LAST_NAME')) . '",
		"EMAIL"=>"' . EscapePHPString($USER->GetParam('EMAIL')) . '",
	)';
	$query = '
	CControllerClient::AuthorizeAdmin(' . $param . ');
	LocalRedirect("/");
	';
	$arControllerLog = [
		'NAME' => 'AUTH',
		'CONTROLLER_MEMBER_ID' => $ar['ID'],
		'DESCRIPTION' => GetMessage('CTRLR_LOG_GOADMIN') . ' (' . $USER->GetParam('LOGIN') . ')',
		'STATUS' => 'Y',
	];
}
else
{
//Authorize as user
	$arGroups = [];
	$arUserGroups = $USER->GetUserGroupArray();
	$arLocGroups = \Bitrix\Controller\GroupMapTable::getMapping('CONTROLLER_GROUP_ID', 'REMOTE_GROUP_CODE');
	foreach ($arLocGroups as $arTGroup)
	{
		foreach ($arUserGroups as $group_id)
		{
			if ($arTGroup['FROM'] == $group_id)
			{
				$arGroups[] = EscapePHPString($arTGroup['TO']);
			}
		}
	}

	if (count($arGroups) > 0)
	{
		$strGroups = '"GROUP_ID" => Array("' . implode('", "', $arGroups) . '"),';
	}
	else
	{
		$strGroups = '';
	}

	$param = 'Array(
		' . $strGroups . '
		"LOGIN"=>"' . EscapePHPString($USER->GetParam('LOGIN')) . '",
		"NAME"=>"' . EscapePHPString($USER->GetParam('FIRST_NAME')) . '",
		"LAST_NAME"=>"' . EscapePHPString($USER->GetParam('LAST_NAME')) . '",
		"EMAIL"=>"' . EscapePHPString($USER->GetParam('EMAIL')) . '",
	)';
	$query = '
	CControllerClient::AuthorizeUser(' . $param . ');
	LocalRedirect("/");
	';
	$arControllerLog = [
		'NAME' => 'AUTH',
		'CONTROLLER_MEMBER_ID' => $ar['ID'],
		'DESCRIPTION' => GetMessage('CTRLR_LOG_GOUSER') . ' (' . $USER->GetParam('LOGIN') . ')',
		'STATUS' => 'Y',
	];
}

CControllerLog::Add($arControllerLog);
if (\Bitrix\Controller\AuthLogTable::isEnabled())
{
	\Bitrix\Controller\AuthLogTable::logControllerToSiteAuth(
		$ar['ID'],
		$USER->GetID(),
		true,
		'CONTROLLER_GOTO',
		$USER->GetParam('FIRST_NAME') . ' ' . $USER->GetParam('LAST_NAME') . ' (' . $USER->GetParam('LOGIN') . ')'
	)->isSuccess();
}

$result = CControllerMember::RunCommandRedirect($ar['ID'], $query, [], false);
if ($result !== false)
{
	LocalRedirect($ar['URL'] . '/bitrix/main_controller.php?lang=' . LANGUAGE_ID, true);
}
else
{
	$e = $APPLICATION->GetException();
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
	ShowError('Error: ' . $e->GetString());
	?>
	<a href="/bitrix/admin/controller_member_admin.php?lang=<?=LANGUAGE_ID?>"><?php echo GetMessage('CTRLR_GOTO_BACK') ?></a>
	<?php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}
