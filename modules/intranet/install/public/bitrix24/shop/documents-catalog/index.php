<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->IncludeComponent(
	'bitrix:catalog.catalog.controller',
	'',
	[
		'BUILDER_CONTEXT' => 'INVENTORY',
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '/shop/documents-catalog/',
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');