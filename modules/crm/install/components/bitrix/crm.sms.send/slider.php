<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.sms.send',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'ENTITY_TYPE_ID' => $request->get('entityTypeId'),
			'ENTITY_ID' => $request->get('entityId'),
			'TEXT' => $request->getPost('text'),
			'PROVIDER_ID' => $request->getPost('providerId'),
			'IS_PROVIDER_FIXED' => $request->getPost('isProviderFixed'),
			'CAN_USE_BITRIX24_PROVIDER' => $request->getPost('canUseBitrix24Provider'),
			'IS_EDITABLE' => $request->getPost('isEditable'),
			'TEMPLATE_CODE' => $request->getPost('templateCode'),
			'TEMPLATE_PLACEHOLDERS' => $request->getPost('templatePlaceholders'),
		],
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
