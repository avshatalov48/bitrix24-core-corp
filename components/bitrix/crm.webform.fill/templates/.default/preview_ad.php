<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\UI\Extension::load('ui.sidepanel-content');

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var \CMain $APPLICATION */
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/html">
<head>
	<?$APPLICATION->ShowHead();?>
	<style>
		.ui-slider-no-access {
			height: 100%;
		}
		body {
			background-color: #eef2f4;
		}
	</style>
</head>
<body class="<?$APPLICATION->showProperty("BodyClass")?>">
<div class="ui-slider-no-access">
	<div class="ui-slider-no-access-inner">
		<div class="ui-slider-no-access-title"><?=$arResult['PREVIEW']['AD_TITLE']?></div>
		<div class="ui-slider-no-access-subtitle"><?=$arResult['PREVIEW']['AD_SUBTITLE']?></div>
		<div class="ui-slider-no-access-img">
			<div class="ui-slider-no-access-img-inner"></div>
		</div>
	</div>
</div>
</body>
</html>
<?