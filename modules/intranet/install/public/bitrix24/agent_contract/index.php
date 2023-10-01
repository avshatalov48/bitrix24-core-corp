<?php

use Bitrix\Main;

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

if (Main\Loader::includeModule('crm'))
{
	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		'bitrix:catalog.agent.contract.controller',
		'',
		[
			'SEF_MODE' => 'Y',
			'SEF_FOLDER' => '/agent_contract/',
		]
	);
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';