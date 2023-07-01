<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 */

use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

?>

<div class="payment-slip-common-container">

	<div class="check-container">
		<div class="check-container__tail"></div>
		<div class="check-container__data">
			<div class="check-container-center">
				<b class="check-container-title" data-check-field="company_name"><?=$arResult['COMPANY_DATA']['COMPANY_TITLE']?></b>

				<?php if (!empty($arResult['COMPANY_DATA']['COMPANY_ADDRESS'])): ?>
					<span data-check-field="company_address"><?=$arResult['COMPANY_DATA']['COMPANY_ADDRESS']?></span>
				<?php endif; ?>

				<?php if (!empty($arResult['COMPANY_DATA']['COMPANY_NUMBER'])): ?>
					<span data-check-field="company_number"><?=Loc::getMessage('SALE_PS_SLIP_NUMBER', ['#NUMBER#' => $arResult['COMPANY_DATA']['COMPANY_NUMBER']])?></span>
				<?php endif; ?>
			</div>
			<div class="check-container-separator"></div>
			<div class="check-container-center">
				<span><?=Loc::getMessage('SALE_PS_SLIP_PAYMENT_TITLE')?></span>
				<b class="check-container-title"><?=Loc::getMessage('SALE_PS_SLIP_PAYMENT_RESULT_SUCCESS')?></b>
			</div>
			<div class="check-container-separator"></div>
			<div class="check-container-fields">
				<div class="check-container-field" data-check-field="sum">
					<b><?=Loc::getMessage('SALE_PS_SLIP_FIELD_SUM')?>:</b>
					<b><?=$arResult['PAYMENT_DATA']['SUM']?></b>
				</div>
				<div class="check-container-field" data-check-field="ps_name">
					<span><?=Loc::getMessage('SALE_PS_SLIP_FIELD_METHOD')?>:</span>
					<span><?=$arResult['PAYMENT_DATA']['PS_NAME']?></span>
				</div>
				<div class="check-container-field" data-check-field="transaction_id">
					<span><?=Loc::getMessage('SALE_PS_SLIP_FIELD_TRANSACTION_ID')?>:</span>
					<span><?=$arResult['PAYMENT_DATA']['TRANSACTION_ID']?></span>
				</div>
				<div class="check-container-field" data-check-field="ps_date">
					<span><?=Loc::getMessage('SALE_PS_SLIP_FIELD_TERMINAL_DATE')?>:</span>
					<span><?=$arResult['PAYMENT_DATA']['DATE']?></span>
				</div>
			</div>
			<?php if (isset($arResult['PAYMENT_SLIP_WARNING'])): ?>
				<div class="check-container-separator"></div>
				<div class="check-container-center" data-check-field="slip_warning">
					<span><?=$arResult['PAYMENT_SLIP_WARNING']['TITLE']?></span>
					<b><?=$arResult['PAYMENT_SLIP_WARNING']['SUBTITLE']?></b>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="payment-slip-footer">
		<a class="payment-slip-footer__logo" href="<?=Loc::getMessage('SALESCENTER_PS_SLIP_B24_LINK')?>"></a>
		<div class="payment-slip-footer__desc"><?=\Bitrix\Main\Localization\Loc::getMessage('SALESCENTER_PS_SLIP_B24_SLOGAN')?></div>
	</div>
</div>
