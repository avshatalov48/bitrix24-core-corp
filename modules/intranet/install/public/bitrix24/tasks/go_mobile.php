<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$redirectResponse = \Bitrix\Main\Context::getCurrent()->getResponse()->redirectTo('/');
if (!\Bitrix\Main\Loader::includeModule("mobile"))
{
	\Bitrix\Main\Application::getInstance()->end(0, $redirectResponse);
}

$hash = filter_input(INPUT_GET, 'h');
$userId = (int)filter_input(INPUT_GET, 'u');

$secret = \CUserOptions::GetOption('tasks', 'qr_mobile_auth', false, $userId);

if (
	!is_array($secret)
	|| !array_key_exists('SECRET', $secret)
	|| !password_verify($secret['SECRET'], $hash)
)
{
	\Bitrix\Main\Application::getInstance()->end(0, $redirectResponse);
}

$link = \Bitrix\Mobile\Deeplink::getAuthLink('preset_task', $userId);

$redirectResponse = \Bitrix\Main\Context::getCurrent()->getResponse()->redirectTo($link);
$redirectResponse
	->setSkipSecurity(true)
	->setStatus(302);


if (function_exists('AddEventToStatFile'))
{
	AddEventToStatFile(
		'tasks',
		'go',
		'QrMobile',
		0,
		'QrMobile',
		(int)$userId
	);
}

\Bitrix\Main\Application::getInstance()->end(0, $redirectResponse);