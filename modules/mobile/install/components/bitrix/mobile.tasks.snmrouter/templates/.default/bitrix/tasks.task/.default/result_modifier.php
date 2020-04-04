<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var TasksBaseComponent $component */
$arResult["TEMPLATE_DATA"] = array("ERRORS" => array());
if (is_array($arResult["ERROR"]) && !empty($arResult["ERROR"]))
{
	foreach($arResult["ERROR"] as $error)
	{
		if ($error["TYPE"] == "FATAL")
		{
			$arResult["TEMPLATE_DATA"]["ERROR"] = $error;
			return;
		}
		else
		{
			$arResult["TEMPLATE_DATA"]["ERRORS"][] = $error;
		}
	}
}

$task = &$arResult["DATA"]["TASK"];

//User Name Template
$arParams["NAME_TEMPLATE"] = empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arParams['AVATAR_SIZE'] = ($arParams['AVATAR_SIZE'] ?: 58);

$users = array(
	$task["RESPONSIBLE_ID"] => array(
		"ID" => $task["RESPONSIBLE_ID"],
		"NAME" => $task["RESPONSIBLE_NAME"],
		"LAST_NAME" => $task["RESPONSIBLE_LAST_NAME"],
		"SECOND_NAME" => $task["RESPONSIBLE_SECOND_NAME"],
		"LOGIN" => $task["RESPONSIBLE_LOGIN"],
		"PERSONAL_PHOTO" => $task["RESPONSIBLE_PHOTO"]
	),
	$task["CREATED_BY"] => $task["SE_ORIGINATOR"]
);

if (!array_key_exists("ID", $task))
{
	$task["ID"] = 0;
	$task["TITLE"] = "";
	$task["DESCRIPTION"] = "";
	$task["DECLINE_REASON"] = "";
	$task["STATUS"] = 0;
	$task["RESPONSIBLE_ID"] = $task["CREATED_BY"];
}

foreach ($task["SE_ACCOMPLICE"] as $user)
	$users[$user["ID"]] = $user;
foreach ($task["SE_AUDITOR"] as $user)
	$users[$user["ID"]] = $user;

foreach ($users as &$user)
{
	$user["NAME"] = CUser::FormatName($arParams["NAME_TEMPLATE"], $user, true, false);
	$user["AVATAR"] = "";
	if ($user["PERSONAL_PHOTO"] && ($file = CFile::GetFileArray($user["PERSONAL_PHOTO"])) && $file !== false)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$file,
			array(
				"width"  => $arParams['AVATAR_SIZE'],
				"height" => $arParams['AVATAR_SIZE']
			),
			BX_RESIZE_IMAGE_EXACT,
			false
		);
		$user["AVATAR"] = $arFileTmp['src'];
	}
}

$task["SE_RESPONSIBLE"] = $users[$task["RESPONSIBLE_ID"]];
$task["SE_ORIGINATOR"] = $users[$task["CREATED_BY"]];
$task["SE_ACCOMPLICE"] = array();
$task["SE_AUDITOR"] = array();
$task["ACCOMPLICES"] = (is_array($task["ACCOMPLICES"]) ? $task["ACCOMPLICES"] : array());
$task["AUDITORS"] = (is_array($task["AUDITORS"]) ? $task["AUDITORS"] : array());
$task["SE_CHECKLIST"] = (is_array($task["SE_CHECKLIST"]) ? $task["SE_CHECKLIST"] : array());
$task["SE_TAG"] = (is_array($task["SE_TAG"]) ? $task["SE_TAG"] : array());

foreach ($task["ACCOMPLICES"] as $id)
	$task["SE_ACCOMPLICE"][$id] = $users[$id];
foreach ($task["AUDITORS"] as $id)
	$task["SE_AUDITOR"][$id] = $users[$id];
if (array_key_exists("GROUP", $arResult["DATA"]) && is_array($arResult["DATA"]["GROUP"]))
{
	foreach ($arResult["DATA"]["GROUP"] as &$group)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$group["IMAGE_ID"],
			array(
				"width"  => $arParams['AVATAR_SIZE'],
				"height" => $arParams['AVATAR_SIZE']
			),
			BX_RESIZE_IMAGE_EXACT,
			false
		);
		$group["AVATAR"] = $arFileTmp['src'];
	}
}