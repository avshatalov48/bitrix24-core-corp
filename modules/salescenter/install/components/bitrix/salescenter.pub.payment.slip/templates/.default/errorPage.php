<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

/**
 * @var $arParams[]
 */

?>

<div class="payment-slip-common-container">
	<div class="payment-slip-error-container">
		<div class="payment-slip-error-container__logo"></div>
		<div class="payment-slip-error-container__title"><?=$arParams['ERROR_MSG'] ?? ''?></div>
	</div>
	<div class="payment-slip-footer">
		<a class="payment-slip-footer__logo" href="<?=Loc::getMessage('SALESCENTER_PS_SLIP_B24_LINK')?>"></a>
		<div class="payment-slip-footer__desc"><?=\Bitrix\Main\Localization\Loc::getMessage('SALESCENTER_PS_SLIP_B24_SLOGAN')?></div>
	</div>
</div>