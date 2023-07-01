<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

if (!is_array($arResult['APP']) || empty($arResult['APP'])) {
	echo "<div style='margin: 21px 25px 25px 25px;'>" . Loc::getMessage('MARKETPLACE_APP_NOT_FOUND') . "</div>";
	return;
}

Extension::load([
	'ui.progressround',
	'ui.hint',
	'ui.textcrop',
	'ui.viewer',
	'ui.carousel',
	'ui.notification',
	// 'ui.icons.b24',
	'ui.icons.service',
	'ui.forms',
	'ui.popup',
	'ui.buttons',
	'ui.ears',
	'sidepanel',
	'access',
	'market.favorites',
	'market.detail',
	'market.application',
]);

?>

<div id="market-wrapper-vue"></div>

<script>
	new BX.Market.Detail(<?=CUtil::PhpToJSObject([
		'params' => $arParams,
		'result' => $arResult,
	])?>);
</script>