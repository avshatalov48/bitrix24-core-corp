<?php

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Market\Content;
use Bitrix\Rest\Notification\MarketExpired\Curtain\CurtainPageType;
use Bitrix\Rest\Notification\MarketExpired\MarketExpiredNotification;

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
		$marketExpiredCurtain = MarketExpiredNotification::createByDefault()->getCurtain();

		if ($marketExpiredCurtain->isReadyToShow(CurtainPageType::APPLICATION))
		{
		?>
			BX.loadExt('rest.market-expired')
				.then((exports) => {
					const { MarketExpired, CurtainPage } = exports;
					const curtain = MarketExpired.getCurtain(CurtainPage.APPLICATION);
					curtain.show();
				});
		<?php
		}
		?>
	});
</script>