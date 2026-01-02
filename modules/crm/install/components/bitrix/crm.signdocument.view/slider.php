<?php

// todo move to the intranet

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

global $APPLICATION;

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.signdocument.view',
		[
			'documentId' => $request->get('documentId'),
			'memberHash' => $request->get('memberHash') ?? null,
		],
		'USE_UI_TOOLBAR' => 'Y',
		'USE_PADDING' => false,
		'USE_BACKGROUND_CONTENT' => false,
	],
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
