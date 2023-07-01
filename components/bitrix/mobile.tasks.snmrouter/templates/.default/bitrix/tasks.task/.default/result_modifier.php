<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var TasksBaseComponent $component */

$arResult['TEMPLATE_DATA'] = ['ERRORS' => []];
if (!empty($arResult["ERROR"]) && is_array($arResult["ERROR"]))
{
	foreach ($arResult['ERROR'] as $error)
	{
		if ($error['TYPE'] === 'FATAL')
		{
			$arResult['TEMPLATE_DATA']['ERROR'] = $error;
			return;
		}
		$arResult['TEMPLATE_DATA']['ERRORS'][] = $error;
	}
}

$task = &$arResult['DATA']['TASK'];

// User Name Template
$arParams['NAME_TEMPLATE'] = (
	empty($arParams['NAME_TEMPLATE'])
		? CSite::GetNameFormat(false)
		: str_replace(['#NOBR#','#/NOBR#'], ['',''], $arParams['NAME_TEMPLATE'])
);
$arParams['AVATAR_SIZE'] = ($arParams['AVATAR_SIZE'] ?? null);
$arParams['AVATAR_SIZE'] = ($arParams['AVATAR_SIZE'] ?: 58);

$users = [];
if (
	array_key_exists('RESPONSIBLE_ID', $task)
	&& $task['RESPONSIBLE_ID']
)
{
	$users[$task['RESPONSIBLE_ID']] = [
		'ID' => $task['RESPONSIBLE_ID'],
		'NAME' => $task['RESPONSIBLE_NAME'],
		'LAST_NAME' => $task['RESPONSIBLE_LAST_NAME'],
		'SECOND_NAME' => $task['RESPONSIBLE_SECOND_NAME'],
		'LOGIN' => $task['RESPONSIBLE_LOGIN'],
		'PERSONAL_PHOTO' => $task['RESPONSIBLE_PHOTO'],
	];
}
$users[$task['CREATED_BY']] = $task['SE_ORIGINATOR'];

foreach ($task['SE_ACCOMPLICE'] as $user)
{
	$users[$user['ID']] = $user;
}
foreach ($task['SE_AUDITOR'] as $user)
{
	$users[$user['ID']] = $user;
}

foreach ($users as &$user)
{
	$user['NAME'] = CUser::FormatName($arParams['NAME_TEMPLATE'], $user, true, false);
	$user['AVATAR'] = '';
	if (
		$user['PERSONAL_PHOTO']
		&& ($file = CFile::GetFileArray($user['PERSONAL_PHOTO']))
	)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$file,
			[
				'width'  => $arParams['AVATAR_SIZE'],
				'height' => $arParams['AVATAR_SIZE'],
			],
			BX_RESIZE_IMAGE_EXACT
		);
		$user['AVATAR'] = ($arFileTmp ? $arFileTmp['src'] : null);
	}
}
unset($user);

if (!array_key_exists('ID', $task))
{
	$task['ID'] = 0;
	$task['TITLE'] = '';
	$task['DESCRIPTION'] = '';
	$task['DECLINE_REASON'] = '';
	$task['STATUS'] = 0;
	$task['RESPONSIBLE_ID'] = $task['CREATED_BY'];
}
$task['SE_RESPONSIBLE'] = $users[$task['RESPONSIBLE_ID']];
$task['SE_ORIGINATOR'] = $users[$task['CREATED_BY']];
$task['SE_ACCOMPLICE'] = [];
$task['SE_AUDITOR'] = [];
$task['ACCOMPLICES'] = (isset($task['ACCOMPLICES']) && is_array($task['ACCOMPLICES']) ? $task['ACCOMPLICES'] : []);
$task['AUDITORS'] = (isset($task['AUDITORS']) && is_array($task['AUDITORS']) ? $task['AUDITORS'] : []);
$task['SE_TAG'] = (isset($task['SE_TAG']) && is_array($task['SE_TAG']) ? $task['SE_TAG'] : []);
$task['SE_CHECKLIST'] = (isset($task['SE_CHECKLIST']) && is_array($task['SE_CHECKLIST']) ? $task['SE_CHECKLIST'] : []);
foreach ($task['ACCOMPLICES'] as $id)
{
	$task['SE_ACCOMPLICE'][$id] = $users[$id];
}
foreach ($task['AUDITORS'] as $id)
{
	$task['SE_AUDITOR'][$id] = $users[$id];
}

if (
	array_key_exists('GROUP', $arResult['DATA'])
	&& is_array($arResult['DATA']['GROUP'])
)
{
	foreach ($arResult['DATA']['GROUP'] as &$group)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$group['IMAGE_ID'],
			[
				'width'  => $arParams['AVATAR_SIZE'],
				'height' => $arParams['AVATAR_SIZE'],
			],
			BX_RESIZE_IMAGE_EXACT
		);
		$group['AVATAR'] = ($arFileTmp ? $arFileTmp['src'] : null);
	}
}