<?php

// todo move to the intranet

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

global $APPLICATION;

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$APPLICATION->includeComponent(
	'bitrix:crm.signdocument.view', '',
	[
		'documentId' => $request->get('documentId'),
		'memberHash' => $request->get('memberHash') ?? null,
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
