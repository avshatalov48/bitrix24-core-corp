<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @var array $arResult
 * @var object $APPLICATION
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'ui.fonts.opensans',
	'ui.counterpanel',
	'sign.v2.document-counter'
]);

$prefix = mb_strtolower($arResult['GUID']);
$containerId = htmlspecialcharsbx("{$prefix}_container");

$data = $arResult['DATA'] ?? [];
$returnAsHtml = $arParams['RETURN_AS_HTML_MODE'] ?? false;
$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';

$isChangeViewTarget = !$returnAsHtml && $isBitrix24Template;

if ($isChangeViewTarget)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'sign-pagetitle-view');

	$this->SetViewTarget('below_pagetitle', 1000);
}

$phrases = Loc::loadLanguageFile(__FILE__);

?>

<div id="sign-counter" class="sign-counter"></div>
<script>
	BX.ready(function() {
		const counter = new BX.Sign.V2.DocumentCounter({
			target: document.querySelector('#sign-counter'),
			multiSelect: <?= Json::encode($arResult['IS_MULTISELECT']) ?>,
			items: <?= \CUtil::PhpToJSObject($arResult['ITEMS']) ?>,
			filterId: "<?= \CUtil::JSEscape($arResult['FILTER_ID']) ?>",
			title: '<?= \CUtil::JSEscape($arResult['TITLE']) ?>',
			resetAllFields: <?= Json::encode((bool)($arParams['RESET_ALL_FIELDS'] ?? false)) ?>
		});

		counter.init();
	});
</script>

<?php
if ($isChangeViewTarget)
{
	$this->EndViewTarget();
}
