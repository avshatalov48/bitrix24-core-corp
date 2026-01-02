<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @global \CMain $APPLICATION
 */

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.notification',
	'ui.buttons.icons',
	'sidepanel',
	'ui.design-tokens',
]);

?><div>
	<div class="docs-template-info-inner">
		<div
			class="docs-template-info-message docs-template-error-message"
			id="docgen-default-templates-error-message"
		></div>
	</div>
	<div class="docs-template-grid"><?php
		$APPLICATION->IncludeComponent(
			'bitrix:main.ui.grid',
			'',
			$arResult['GRID']
		);
	?></div>
</div>
<script>
	BX.ready(function()
	{
		BX.DocumentGenerator.TemplatesDefault.init(<?=CUtil::PhpToJSObject($arResult['params']);?>);
		BX.message(<?= \Bitrix\Main\Web\Json::encode(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)) ?>);
	});
</script>
