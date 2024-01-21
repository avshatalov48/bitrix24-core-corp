<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->includeComponent(
	'bitrix:crm.router',
	'',
	[
		'root' => '/crm/',
		'useUrlParsing' => false,
		'componentName' => 'bitrix:crm.type.list',
		'componentParameters' => [
			'isExternal' => true,
		],
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
