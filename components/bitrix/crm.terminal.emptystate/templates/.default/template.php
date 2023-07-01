<?php
/**
 * @var $component \CatalogProductVariationGridComponent
 * @var $this \CBitrixComponentTemplate
 * @var \CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . ' no-background');
use Bitrix\Main;

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	return;
}

Main\UI\Extension::load(['crm.terminal']);
?>
<div class="crm-terminal-emptystate__wrapper"></div>
<script>
	BX.message(<?=Main\Web\Json::encode(Main\Localization\Loc::loadLanguageFile(__FILE__))?>);

	BX.ready(function () {
		const popup = new BX.Crm.Component.TerminalEmptyState({
			renderNode: document.querySelector('.crm-terminal-emptystate__wrapper'),
			zone: '<?=CUtil::JSEscape($arResult['ZONE'])?>',
			templateFolder: '<?=CUtil::JSEscape($templateFolder)?>',
			sberbankPaySystemPath: '<?=CUtil::JSEscape($arResult['SBERBANK_PAY_SYSTEM_PATH'])?>',
			spbPaySystemPath: '<?=CUtil::JSEscape($arResult['SBP_PAY_SYSTEM_PATH'])?>',
		});

		popup.render();
	});
</script>
