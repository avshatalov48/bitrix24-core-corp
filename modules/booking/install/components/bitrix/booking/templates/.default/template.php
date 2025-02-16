<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Booking\Component\Booking\Toolbar;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/** @var $APPLICATION \CMain */
/** @var array $arResult */

Loader::includeModule('booking');

$APPLICATION->SetTitle(Loc::getMessage('BOOKING_BOOKING_TITLE'));

if ($arResult['IS_SLIDER'])
{
	$bodyClass = $APPLICATION->getPageProperty('BodyClass') || '';
	$bodyClasses = explode(' ', $bodyClass);
	$additionalBodyClasses = ['booking-booking-slider'];
	$newBodyClass = implode(' ', array_merge($bodyClasses, $additionalBodyClasses));

	$APPLICATION->SetPageProperty('BodyClass', $newBodyClass);
}

$toolbarAfterTitleContainerId = 'booking-toolbar-after-title-container';
$toolbarCounterPanelContainerId = 'booking-toolbar-counter-panel-container';
$toolbar = new Toolbar(
	afterTitleContainerId: $toolbarAfterTitleContainerId,
	counterPanelContainerId: $toolbarCounterPanelContainerId,
);
$toolbar->build();

Extension::load('booking.core');
Extension::load('booking.booking');

?>

<div id="booking"></div>

<script>
	BX.ready(() => {
		const container = document.getElementById('booking');
		const afterTitleContainer = document.getElementById('<?= $toolbarAfterTitleContainerId?>');
		const counterPanelContainer = document.getElementById('<?= $toolbarCounterPanelContainerId?>');
		const isSlider = <?= $arResult['IS_SLIDER'] ? 'true' : 'false'?>;
		const currentUserId = <?= (int)$arResult['currentUserId'] ?>;
		const isFeatureEnabled = <?= $arResult['isFeatureEnabled'] ? 'true' : 'false'?>;
		const canTurnOnTrial = <?= $arResult['canTurnOnTrial'] ? 'true' : 'false'?>;
		const canTurnOnDemo = <?= $arResult['canTurnOnDemo'] ? 'true' : 'false'?>;
		const timezone = '<?= $arResult['timezone'] ?>' || Intl.DateTimeFormat().resolvedOptions().timeZone;
		const filterId = '<?= $arResult['FILTER_ID'] ?>';
		const editingBookingId = <?= (int)$arResult['editingBookingId'] ?>;
		const ahaMoments = <?= Json::encode($arResult['AHA_MOMENTS']) ?>;
		const totalClients = <?= (int)$arResult['TOTAL_CLIENTS'] ?>;
		const totalClientsToday = <?= (int)$arResult['TOTAL_CLIENTS_TODAY'] ?>;
		const moneyStatistics = <?= Json::encode($arResult['MONEY_STATISTICS']) ?>;

		new BX.Booking.Booking({
			container,
			afterTitleContainer,
			counterPanelContainer,
			isSlider,
			currentUserId,
			isFeatureEnabled,
			canTurnOnTrial,
			canTurnOnDemo,
			timezone,
			filterId,
			editingBookingId,
			ahaMoments,
			totalClients,
			totalClientsToday,
			moneyStatistics,
		});
	});
</script>
