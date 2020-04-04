<?
/** @var $arResult array */
/** @var $arParams array */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8" />
	<?php
	/** @var CMain $APPLICATION */
	use Bitrix\Main\Localization\Loc;
	Loc::loadMessages(__FILE__);
	$APPLICATION->ShowHead();
	CJSCore::Init('ajax');
	$APPLICATION->ShowCSS(true, true);
	$APPLICATION->ShowHeadStrings();
	$APPLICATION->ShowHeadScripts();
	?>
</head>
<body style="height: 100%;margin: 0;padding: 0; background: #fff">
<div class="imopenlines-iframe-quick-info">
	<div class="imopenlines-iframe-quick-info-header">
		<?=Loc::getMessage('IMOL_QUICK_ANSWERS_ACCESS_DENIED');?>
	</div>
</div>
</body>
</html>