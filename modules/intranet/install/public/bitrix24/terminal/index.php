<?php

use Bitrix\Main;

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

if (Main\Loader::includeModule('crm'))
{
	$requestTerminalUri = $_SERVER['REQUEST_URI'];
	$requestTerminalUri = parse_url($requestTerminalUri);

	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		'bitrix:crm.terminal.payment.controller',
		'',
		[
			'SEF_MODE' => 'Y',
			'SEF_FOLDER' => '/terminal/',
			'REQUESTED_PAGE' => $requestTerminalUri['path'],
		]
	);
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';