<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$APPLICATION->includeComponent(
	'bitrix:crm.feedback', '',
	[
		'SENDER_PAGE' => $request->get('sender_page'),
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');