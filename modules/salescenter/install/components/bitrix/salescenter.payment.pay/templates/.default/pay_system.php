<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 */

$darkMode = '';

if (isset($arParams['TEMPLATE_MODE']) && $arParams['TEMPLATE_MODE'] === 'darkmode')
{
	$darkMode = 'bx-dark';
}

if (!empty($arResult['JS_DATA']['app']['paySystems']))
{
	$options = CUtil::PhpToJSObject($arResult['JS_DATA']);
	?>
	<div class="page-section order-payment-method-container <?= $darkMode ?>">
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
				template: '<salescenter-payment_pay-components-application-pay_system :options="options"/>',
			});
		});
	</script>
	<?php
}
