<?php
/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var SaleOrderAjax $component
 * @var string $templateFolder
 */

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>

<div class="salescenter-delivery-install-section">
	<div class="salescenter-delivery-install-logo-block">
		<div class="salescenter-delivery-install-logo-rest-delivery"></div>
	</div>
	<div class="salescenter-delivery-install-content-block">
		<h2><?= htmlspecialcharsbx($arResult['restHandler']['NAME']) ?></h2>
		<p><?= htmlspecialcharsbx($arResult['restHandler']['DESCRIPTION']) ?></p>
	</div>
</div>
