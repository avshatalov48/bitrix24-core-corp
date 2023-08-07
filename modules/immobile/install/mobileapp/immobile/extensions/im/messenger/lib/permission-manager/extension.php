<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('im'))
{
	return;
}

return [
	'call_server_max_users' => \Bitrix\Main\Config\Option::get('im', 'call_server_max_users'),
	'userChatOptions' => CIMChat::GetChatOptions(),
];
