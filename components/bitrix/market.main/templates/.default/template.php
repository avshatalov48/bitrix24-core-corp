<?

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

Extension::load(\Bitrix\Market\Extension::getList());
?>

<div id="market-wrapper-vue"></div>

<script>
	new BX.Market.Market(<?=CUtil::PhpToJSObject([
		'params' => $arParams,
		'result' => $arResult,
	])?>);
</script>