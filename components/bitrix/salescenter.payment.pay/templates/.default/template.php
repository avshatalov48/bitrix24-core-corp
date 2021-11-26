<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load([
	'popup',
	'loader',
	'documentpreview',
	'sidepanel',
	'ui.fonts.ruble',
	'salescenter.payment-pay.application',
	'ui.vue',
]);

/**
 * @var array $arResult
 * @var array $arParams
 * @global CMain $APPLICATION
 */

$payment = $arResult['PAYMENT'];
$currentPaySystem = $payment['PAY_SYSTEM_INFO'];
$currentPaySystemName = (mb_strlen($currentPaySystem['NAME']) > 20)
	? mb_substr($currentPaySystem['NAME'], 0, 17).'...'
	: $currentPaySystem['NAME'];

$containerClasses = [];

if ($arParams['TEMPLATE_MODE'] === 'darkmode')
{
	$containerClasses[] = 'bx-dark';
}

$messages = Loc::loadLanguageFile(__FILE__);

if (!empty($arResult['errorMessage']))
{
	foreach ($arResult['errorMessage'] as $errorMessage)
	{
		?>
		<div class="page-description"><?= $errorMessage ?></div>
		<?php
	}
}
else
{
	if ($payment['PAID'] === 'Y' || $arParams['ALLOW_SELECT_PAY_SYSTEM'] !== 'Y')
	{
		$addClasses = $payment['PAID'] === 'Y'
			? ['order-payment-container']
			: ['order-payment-container', 'order-payment-sibling-container', 'mb-4'];

		$containerClasses = array_merge($containerClasses, $addClasses);
	}
	else
	{
		$containerClasses = array_merge($containerClasses, [
			'page-section',
			'order-payment-method-container',
		]);
	}

	if (!empty($arResult['JS_DATA']['app']['paySystems']))
	{
		$options = CUtil::PhpToJSObject($arResult['JS_DATA']);
		?>
		<div class="<?= join(' ', $containerClasses) ?>">
			<div id="salescenter-payment-pay-app"></div>
		</div>
		<script>
			BX.ready(function ()
			{
				BX.message(<?=CUtil::PhpToJSObject($messages)?>);
				BX.Vue.create({
					el: '#salescenter-payment-pay-app',
					data: () => {
						return {options: <?=$options?>}
					},
					template: '<salescenter-payment-pay-app :options="options"/>',
				});
			});
		</script>
		<?php
	}
}
