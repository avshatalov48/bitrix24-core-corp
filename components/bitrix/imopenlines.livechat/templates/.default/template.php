<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @global \CMain $APPLICATION
 * @var \CBitrixComponentTemplate $this
 */

if($arResult['CUSTOMIZATION']['CSS_PATH'])
{
	$this->addExternalCss($arResult['CUSTOMIZATION']['CSS_PATH']);
}

$APPLICATION->SetTitle($arResult['LINE_NAME']);

?>
<div id="imopenlines-page-placeholder" class="imopenlines-page-placeholder"></div>
<script>
	<?=$arResult['WIDGET_CODE']?>
</script>
