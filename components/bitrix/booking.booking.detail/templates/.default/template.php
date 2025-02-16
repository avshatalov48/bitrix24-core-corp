<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
Loader::includeModule('booking');
global $APPLICATION;
$APPLICATION->SetTitle($arResult['title']);
Extension::load(['booking.confirm-page-public', 'ui.design-tokens']);
?>

<div>
	<div id="booking-detail-page"></div>
</div>

<script>
	BX.ready(() => {
		const container = document.getElementById('booking-detail-page');
		const hash = '';
		const booking = <?= \Bitrix\Main\Web\Json::encode($arResult['booking']) ?>;
		const company = '<?= $arResult['company']; ?>';
		const context = 'manager.view.details';
		new BX.Booking.ConfirmPagePublic({
			container,
			booking,
			hash,
			company,
			context,
		});
	});

</script>
