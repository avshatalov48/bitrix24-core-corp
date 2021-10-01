<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$id = filter_input(INPUT_GET, 'id');
if (
	!$id
	|| !is_scalar($id)
)
{
	$redirectResponse = \Bitrix\Main\Context::getCurrent()->getResponse()->redirectTo('/');
	\Bitrix\Main\Application::getInstance()->end(0, $redirectResponse);
}

$params = explode('_', $id);
if (count($params) !== 2)
{
	$redirectResponse = \Bitrix\Main\Context::getCurrent()->getResponse()->redirectTo('/');
	\Bitrix\Main\Application::getInstance()->end(0, $redirectResponse);
}

$basePath = (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? "https" : "http")
	."://"
	.(
	(defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '')
		? SITE_SERVER_NAME
		: \Bitrix\Main\Config\Option::get("main", "server_name", $_SERVER['SERVER_NAME'])
	);

$file = \CFile::GetList([], [
	'EXTERNAL_ID' => $params[0],
	'FILE_NAME' => $params[1],
]);
$file = $file->Fetch();

if (!$file)
{
	$redirectResponse = \Bitrix\Main\Context::getCurrent()->getResponse()->redirectTo('/');
	\Bitrix\Main\Application::getInstance()->end(0, $redirectResponse);
}

$link = $basePath . \CFile::GetFileSRC($file);

$redirectResponse = \Bitrix\Main\Context::getCurrent()->getResponse()->redirectTo($link);
$redirectResponse->setStatus(301);

global $USER;
if (function_exists('AddEventToStatFile'))
{
	AddEventToStatFile(
		'tasks',
		'view',
		'QrMobile',
		0,
		'QrMobile',
		(int)$USER->GetID()
	);
}


\Bitrix\Main\Application::getInstance()->end(0, $redirectResponse);