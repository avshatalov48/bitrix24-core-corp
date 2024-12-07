<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CBPCrmGoToChat');

class CBPCrmGoToChatWhatsApp extends CBPCrmGoToChat
{
	private const WHATS_APP_CONNECTOR_ID = 'notifications';

	protected function getConnectorId()
	{
		return self::WHATS_APP_CONNECTOR_ID;
	}

	protected static function getFileName(): string
	{
		return __FILE__;
	}
}