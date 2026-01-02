<?php

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 * @global \CMain $APPLICATION
 */

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.buttons.icons',
	'sidepanel',
	'documentpreview',
	'ui.design-tokens',
]);

if (!empty($arResult['ERROR']))
{
	?><div>
		<div class='docs-template-info-inner'>
			<div
				class='docs-template-info-message docs-template-error-message'
				id='docgen-templates-error-message'
				style="display: block;"
			><?= htmlspecialcharsbx($arResult['ERROR']) ?></div>
		</div>
	</div><?php

	return;
}

if (\Bitrix\Main\Loader::includeModule('ui'))
{
	Toolbar::deleteFavoriteStar();
	Toolbar::addFilter($arResult['FILTER']);

	Bitrix24Manager::addFeedbackButtonToToolbar(
		$arParams['PROVIDER'] ?? '',
	);

	Toolbar::addButton(
		(new \Bitrix\UI\Buttons\SettingsButton())
			->addAttribute('id', 'docgen-templates-settings-button')
		,
	);

	Toolbar::addButton(
		new \Bitrix\UI\Buttons\Button([
			'color' => \Bitrix\UI\Buttons\Color::PRIMARY,
			'className' => 'ui-btn-primary-docs-template',
			'text' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_UPLOAD'),
			'onclick' => new \Bitrix\UI\Buttons\JsCode('BX.DocumentGenerator.TemplateList.edit()'),
		]),
	);
}

?><div>
	<div class="docs-template-info-inner">
		<div class="docs-template-info-message docs-template-error-message" id="docgen-templates-error-message"></div>
		<div class='docs-template-info-message'><?=
			Loc::getMessage('DOCGEN_TEMPLATE_LIST_MORE_INFO');
			?><a class="docs-template-info-link" onclick="BX.DocumentGenerator.TemplateList.openMoreLink(event);"><?=
				Loc::getMessage('DOCGEN_TEMPLATE_LIST_MORE');
			?></a>
		</div>
	</div>
	<div class='docs-template-grid'><?php
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
		BX.DocumentGenerator.TemplateList.init(
			'<?=CUtil::JSEscape($arResult['GRID']['GRID_ID']);?>',
			<?=CUtil::PhpToJSObject($arResult['params']);?>,
		);
		BX.message(<?= \Bitrix\Main\Web\Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
	});
</script>
