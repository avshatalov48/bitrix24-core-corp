<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->IncludeComponent(
	'bitrix:crm.catalog.controller',
	'',
	[
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/crm/catalog/',
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');