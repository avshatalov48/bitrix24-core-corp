<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if ($request->get('IFRAME') === 'Y' && $request->get('IFRAME_TYPE') === 'SIDE_SLIDER')
{
	$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrapper', '', [
		'POPUP_COMPONENT_NAME' => 'bitrix:sender.master.yandex',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'USE_PADDING' => false,
		'PLAIN_VIEW' => true,
	]);
}
else
{
	$APPLICATION->IncludeComponent('bitrix:sender.master.yandex', '');
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
