<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
{
	return;
}

if (intval($USER->GetID()) <= 0)
{
	return;
}

if (!CModule::IncludeModule('im'))
{
	return;
}

session_write_close();

$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "ml-notify");
$GLOBALS["APPLICATION"]->SetPageProperty("Viewport", "user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=290");

if (!\Bitrix\Main\Loader::includeModule('im'))
{
	return;
}

$messageId = (int)$this->request->get('messageId');
if (!$messageId)
{
	return;
}

$message = new \Bitrix\Im\V2\Message($messageId);
if($message->checkAccess()->isSuccess())
{
	$arResult['ATTACH'] = $message->toRestFormat()['params'][\Bitrix\Im\V2\Message\Params::ATTACH] ?? [];
}
else
{
	$arResult['ATTACH'] = [];
}

$this->IncludeComponentTemplate();

return $arResult;