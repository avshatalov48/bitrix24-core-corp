<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;

$messages = Loc::loadLanguageFile(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
$APPLICATION->SetTitle(Loc::getMessage('SCP_SALESCENTER_TITLE'));
Extension::load([
	'ui.alerts',
	'ui.tilegrid',
	'ui.fonts.opensans',
	'sidepanel',
	'popup',
	'salescenter.manager',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
Bitrix24Manager::getInstance()->addIntegrationRequestButtonToToolbar(
	[
		Bitrix24Manager::ANALYTICS_SENDER_PAGE => Bitrix24Manager::ANALYTICS_LABEL_SALESHUB_CASHBOX
	]
);
?>

<?php if ($arResult['isCashboxCountryConflict']): ?>
	<div class="ui-alert ui-alert-warning">
		<span class="ui-alert-message">
			<?= Loc::getMessage('SALESCENTER_CASHBOX_ZONE_CONFLICT'); ?>
			<?= Loc::getMessage('SALESCENTER_CASHBOX_ZONE_CONFLICT_RU_LIST', ['#CASHBOXES#' => implode(', ', $arResult['activeCashboxHandlersByCountry']['RU'])]); ?>
			<?= Loc::getMessage('SALESCENTER_CASHBOX_ZONE_CONFLICT_UA_LIST', ['#CASHBOXES#' => implode(', ', $arResult['activeCashboxHandlersByCountry']['UA'])]); ?>
		</span>
	</div>
<?php endif; ?>

<div class="salescenter-cashbox-title"><?=Loc::getMessage('SCP_SALESCENTER_CASHBOX_SUB_TITLE')?></div>
<div id="salescenter-cashbox" class="salescenter-cashbox"></div>

<div class="salescenter-cashbox-title"><?=Loc::getMessage('SCP_SALESCENTER_OFFILINE_CASHBOX_SUB_TITLE')?></div>
<div id="salescenter-offline-cashbox" class="salescenter-cashbox"></div>

<script>
	BX.ready(function()
	{
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);

		// online cashbox
		var cashboxParams = <?=CUtil::PhpToJSObject($arResult['cashboxPanelParams']);?>;
		cashboxParams.container = document.getElementById('salescenter-cashbox');
		cashboxParams.sizeRatio = "55%";
		cashboxParams.itemMinWidth = 200;
		cashboxParams.tileMargin = 7;
		cashboxParams.itemType = 'BX.SaleCenterCashbox.TileGrid';

		BX.SaleCenterCashbox.init({
			cashboxParams: cashboxParams,
		});

		// offline cashbox
		var offlineCashboxParams = <?=CUtil::PhpToJSObject($arResult['offlineCashboxPanelParams']);?>;
		offlineCashboxParams.container = document.getElementById('salescenter-offline-cashbox');
		offlineCashboxParams.sizeRatio = "55%";
		offlineCashboxParams.itemMinWidth = 200;
		offlineCashboxParams.tileMargin = 7;
		offlineCashboxParams.itemType = 'BX.SaleCenterCashbox.TileGrid';

		BX.SaleCenterCashbox.init({
			cashboxParams: offlineCashboxParams,
		});
	});
</script>
