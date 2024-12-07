<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var \CMain $APPLICATION */
/** @var array $arParams */
/** @var string $templateFolder */
/** @var array $arResult */

Loc::loadLanguageFile(__DIR__ . '/template.php');

\Bitrix\Main\UI\Extension::load([
	'crm.entity-editor',
	'sign.v2.ui.tokens',
]);

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.item.kanban',
		'POPUP_COMPONENT_PARAMS' => [
			'entityTypeId' => $arResult['ENTITY_TYPE_ID'],
			'categoryId' => '0',
			'performance' => [
				'layoutFooterEveryItemRender' => 'Y',
			],
		],
		'USE_UI_TOOLBAR' => 'Y',
	],
	$this->getComponent()
);

if ($arResult['SHOW_TARIFF_SLIDER'] ?? false):
?>
<script>
	BX.ready(function()
	{
		top.BX.UI.InfoHelper.show('limit_office_e_signature');

		const el = document.getElementsByClassName('sign-b2e-js-tarriff-slider-trigger');
		if (el && el[0])
		{
			BX.bind(el[0], 'click', function()
			{
				top.BX.UI.InfoHelper.show('limit_office_e_signature');
			});
		}
	});
</script>
<?php
endif;
?>
