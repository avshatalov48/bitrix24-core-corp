<?php

use Bitrix\Main\Web\Json;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */

if ($errors = $this->getComponent()->getErrors()):
	ShowError(reset($errors)->getMessage());
	return;
endif;

/** @see \Bitrix\Crm\Component\Base::addJsRouter() */
$this->getComponent()->addJsRouter($this);

if (!$arResult['isIframe'])
{
	\Bitrix\Main\UI\Extension::load(['sidepanel']); ?>
<script>
	BX.ready(function()
	{
		// conditions here should be without a root
		var rules = [
			{
				condition: [
					"type/(\\d+)/automation/(\\d+)/",
				],
				loader: 'bizproc:automation-loader',
				stopParameters: ['id'],
				options: {
					cacheable: false,
					customLeftBoundary: 0
				}
			},
			{
				condition: [
					"type/(\\d+)/automation/(\\d+)/",
				],
				loader: 'bizproc:automation-loader',
				options: {
					cacheable: false
				}
			},
			{
				condition: [
					"type/(\\d+)/categories/",
				],
				options: {
					customLeftBoundary: 40,
					allowChangeHistory: false,
					cacheable: false
				}
			},
			{
				condition: [
					"type/detail/(\\d+)"
				],
				options: {
					width: 876,
					cacheable: false,
					allowChangeHistory: false
				}
			},
			{
				condition: [
					"type/automated_solution/details/(\\d+)/?$",
				],
				options: {
					width: 876,
					cacheable: false,
					allowChangeHistory: false
				}
			},
			{
				condition: [
					"type/(\\d+)/merge/?$"
				],
				options: {
					width: 876,
					cacheable: false,
					allowChangeHistory: false
				}
			},
			{
				condition: [
					"perms/[A-Za-z0-9-_]+/?",
					"type/automated_solution/permissions/?",
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					customLeftBoundary: 0
				}
			},
			{
				condition: [
					"copilot-call-assessment/details/[0-9]+/?",
				],
				options: {
					cacheable: false,
					allowChangeHistory: false,
					width: 700,
				},
			},
		];

		const roots = <?=\CUtil::PhpToJSObject($arResult['roots'])?>;
		if (BX.Type.isArray(roots) && BX.Type.isArray(rules))
		{
			BX.Crm.Component.Router.bindAnchors(roots, rules);
		}

		const rulesByCustomSections = [
			{
				condition: [
					"perms/?",
				],
				options: {
					cacheable: false,
					allowChangeHistory: true,
					customLeftBoundary: 0
				}
			},
			{
				condition: [
					"perms/[A-Za-z0-9-_]+/?",
				],
				options: {
					cacheable: false,
					allowChangeHistory: true,
					customLeftBoundary: 0
				}
			},
		];

		const customSectionRoots = <?= Json::encode($arResult['customSectionRoots']) ?>;
		if (
			BX.Type.isArray(rulesByCustomSections)
			&& BX.Type.isArray(customSectionRoots)
		)
		{
			BX.Crm.Component.Router.bindAnchors(
				customSectionRoots,
				rulesByCustomSections,
			);
		}
	});
</script>
<?php
}

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => $arResult['componentName'],
		'POPUP_COMPONENT_TEMPLATE_NAME' => $arResult['templateName'],
		'POPUP_COMPONENT_PARAMS' => $arResult['componentParameters'],
		'USE_PADDING' => $arResult['isUsePadding'],
		'PLAIN_VIEW' => $arResult['isPlainView'],
		'USE_UI_TOOLBAR' => $arResult['isUseToolbar'] ? 'Y' : 'N',
		'POPUP_COMPONENT_USE_BITRIX24_THEME' => $arResult['isUseBitrix24Theme'] ? 'Y' : 'N',
		'DEFAULT_THEME_ID' => $arResult['defaultBitrix24Theme'],
		'USE_BACKGROUND_CONTENT' => $arResult['isUseBackgroundContent'],
		'HIDE_TOOLBAR' => $arResult['isHideToolbar'],
	],
	$this->getComponent()
);?>
