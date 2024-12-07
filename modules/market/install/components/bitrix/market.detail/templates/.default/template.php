<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Market\Content;

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
	'rest.app-form',
]);
Content::showAdditional($arResult);
?>

<div id="market-wrapper-vue"></div>

<script>
	BX.ready(function () {
		const marketDetailData = <?=Json::encode([
			'params' => $arParams,
			'result' => $arResult,
		])?>;
		new BX.Market.Detail(marketDetailData);
	});
</script>