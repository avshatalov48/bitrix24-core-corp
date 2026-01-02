<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @global \CMain $APPLICATION */

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.buttons.icons',
	'sidepanel',
	'popup',
	'clipboard',
	'ui.fonts.opensans',
]);

?><div class='docs-placeholder-wrap'>
	<div class="docs-template-grid"><?php
		$APPLICATION->IncludeComponent(
			'bitrix:main.ui.grid',
			'',
			$arResult['GRID'],
		);
	?></div>
</div>
<script>
	BX.ready(function()
	{
		BX.DocumentGenerator.Placeholders.init(<?=\Bitrix\Main\Web\Json::encode($arResult['params']);?>);
		BX.message(<?= \Bitrix\Main\Web\Json::encode(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)) ?>);
	});
</script>
