<?php
use Bitrix\Main\Web\Json;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::requireModule('crm');

\Bitrix\Main\Page\Asset::getInstance()->addString('<script>BX.Crm.Report.initFilterSelectors();</script>');

return [
	'js'  => [
		'/bitrix/js/crm/crm.js',
		'/bitrix/js/crm/common.js',
		'/bitrix/js/crm/interface_grid.js',
		'/bitrix/js/crm/report/filterselectors/init.js',
	],
	'rel' => ['ui.fonts.opensans'],
	'css' => [
		'/bitrix/js/crm/css/crm.css',
	],
	'lang_additional' => [
		'crm_type_descriptions' => Json::encode(CCrmOwnerType::GetJavascriptDescriptions())
	]
];
