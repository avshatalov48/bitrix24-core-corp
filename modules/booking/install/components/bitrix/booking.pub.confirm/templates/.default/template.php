<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

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

$currentLang = $arResult['currentLang'] ?? null;
$hasBitrix24Link = is_string($arResult['bitrix24Link']);

?>

<div>
	<div id="booking-confirm-page"></div>
	<div class="booking-template-pub-footer">
		<div class="booking-template-pub-footer-top"><?= Loc::getMessage('BOOKING_CONFIRM_PAGE_BITRIX24_LOGO_POWERED', [
				'#CLASS#' => 'booking-template-pub-footer__logo-b24 ' . ($currentLang === 'ru' ? '--ru' : ''),
			])?> <?= Loc::getMessage('BOOKING_CONFIRM_PAGE_FREE_SITES_AND_CRM', [
				'#TAG#' => ($hasBitrix24Link ? 'a' : 'span'),
				'#CLASS#' => 'booking-template-pub-footer__link ' . (!$hasBitrix24Link ? '--no-link' : ''),
				'#HREF#' => ($hasBitrix24Link ? $arResult['bitrix24Link'] : '#'),
			])?>
		</div>
	</div>
</div>


<script>
	BX.ready(() => {
		const container = document.getElementById('booking-confirm-page');
		const booking = <?= \Bitrix\Main\Web\Json::encode($arResult['booking']) ?>;
		const hash = '<?= $arResult['hash']; ?>';
		const company = '<?= $arResult['company']; ?>';
		const context = '<?= $arResult['context']; ?>';

		new BX.Booking.ConfirmPagePublic({
			container,
			booking,
			hash,
			company,
			context,
		});
	});
</script>