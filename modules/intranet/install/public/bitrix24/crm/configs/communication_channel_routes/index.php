<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/public_bitrix24/crm/configs/communication_channel_routes/index.php');
$APPLICATION->SetTitle(GetMessage('TITLE'));
$APPLICATION->IncludeComponent(
	'bitrix:crm.communicationchannel',
	'',
	[
		'SEF_MODE' => 'Y',
		'ELEMENT_ID' => $_REQUEST['detail_id'] ?? '',
		'SEF_FOLDER' => '/crm/configs/communication_channel_routes/',
		'SEF_URL_TEMPLATES' => [
			'index' => 'index.php',
			'details' => 'details/#detail_id#/',
		],
		'VARIABLE_ALIASES' => [
			'index' => [],
			'details' => [],
		],
	]
);
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');