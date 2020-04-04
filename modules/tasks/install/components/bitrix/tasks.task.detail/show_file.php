<?php

/**
 * This file also can be included from tasks/tools/tasks/getfile.php
 */

// Scripts run directly?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	$oAuthMode = isset($_GET['auth']);

	if ($oAuthMode)
		define('NOT_CHECK_PERMISSIONS', true);

	define("STOP_STATISTICS", true);
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
}
else
	$oAuthMode = false;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arResult = array(
	"MESSAGE" => array(),
	"FILE"    => null
);

$isUserAuthorized = false;

if ( ! $oAuthMode )
{
	if (isset($USER) && is_object($USER) && method_exists($USER, 'getId') && ($USER->getId() > 0))
		$isUserAuthorized = true;
}
else
{
	// Try to authorize throughs oAuth
	if (
		isset($_GET['auth'])
		&& CModule::IncludeModule('rest')
		&& class_exists('CRestUtil')
		&& method_exists('CRestUtil', 'checkAuth')
		&& CRestUtil::checkAuth($_GET['auth'], CTaskRestService::SCOPE_NAME, $res = array())
		&& CRestUtil::makeAuth($res)
	)
	{
		$isUserAuthorized = true;
	}
}

if ($isUserAuthorized)
{
	CModule::IncludeModule("tasks");

	$arParams = array(
		'FILE_ID'     => false,
		'TEMPLATE_ID' => false,
		'TASK_ID'     => false
	);

	if (isset($_GET['fid']))
		$arParams['FILE_ID'] = (int) $_GET['fid'];

	if (isset($_GET['tid']))
		$arParams['TEMPLATE_ID'] = (int) $_GET['tid'];

	if (isset($_GET['TASK_ID']))
		$arParams['TASK_ID'] = (int) $_GET['TASK_ID'];


	$bFound = false;
	if ($arParams["FILE_ID"] > 0)
	{
		if ($arParams["TEMPLATE_ID"])
		{
			$rsTemplate = CTaskTemplates::GetList(array(), array("ID" => $arParams["TEMPLATE_ID"], "CREATED_BY" => $USER->GetID()));
			if ($arTemplate = $rsTemplate->Fetch())
			{
				$arTemplate["FILES"] = unserialize($arTemplate["FILES"]);
				if (is_array($arTemplate["FILES"]) && in_array($arParams["FILE_ID"], $arTemplate["FILES"]))
				{
					$bFound = true;
				}
			}
		}
		else
		{
			if ($arParams['TASK_ID'])
			{
				if (CTaskFiles::isUserfieldFileAccessibleByUser($arParams['TASK_ID'], $arParams['FILE_ID'], $USER->GetID()))
					$bFound = true;
			}

			if ( !$bFound && CTaskFiles::isFileAccessibleByUser( (int) $arParams["FILE_ID"], $USER->GetID()))
				$bFound = true;
		}
	}

	if ($bFound)
		$arResult["FILE"] = CFile::GetFileArray($arParams["FILE_ID"]);
}

if (!$arResult["FILE"])
{
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
	ShowError("File not found");
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");
	die();
}

set_time_limit(0);

if ($oAuthMode)
{
	CFile::ViewByUser(
		$arResult['FILE'],
		array(
			'content_type'   => 'application/octet-stream', 
			'force_download' =>  true
		)
	);
}
else
{
	$options = array();
	if (isset($_GET['action']) && ($_GET['action'] === 'download'))
	{
		$options['force_download'] = true;
	}
	CFile::ViewByUser($arResult["FILE"], $options);
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");
