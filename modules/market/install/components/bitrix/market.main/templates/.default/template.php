<?php

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

Extension::load(\Bitrix\Market\Extension::getList());
Content::showAdditional($arResult);
?>

<div id="market-wrapper-vue"></div>

<script>
	BX.ready(function () {
		const marketMainData = <?=Json::encode([
			'params' => $arParams,
			'result' => $arResult,
		])?>;
		new BX.Market.Market(marketMainData);

		<?php
		$marketExpiredCurtain = \Bitrix\Rest\Notification\MarketExpiredCurtain::getByDefault();
		if ($marketExpiredCurtain->isReadyToShow("Application"))
		{
			\Bitrix\Main\UI\Extension::load([
				'rest.market-expired-curtain'
			]);
		?>
			const curtain = new BX.Rest.MarketExpiredCurtain("Application");
			curtain.show();
		<?php
		}
		?>
	});
</script>