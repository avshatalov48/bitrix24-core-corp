<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 */

$containerClasses = $arResult['PAYMENT']['PAID'] === 'Y'
	? ['order-payment-container']
	: ['order-payment-container', 'order-payment-sibling-container', 'mb-4'];

if (!empty($arResult['COMPONENT_THEME']))
{
	$containerClasses[] = $arResult['COMPONENT_THEME'];
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
			BX.Vue.create({
				el: '#salescenter-payment-pay-app',
				data: () => {
					return {options: <?=$options?>}
				},
				template: '<salescenter-payment_pay-components-application-payment :options="options"/>',
			});
		});
	</script>
	<?php
}
