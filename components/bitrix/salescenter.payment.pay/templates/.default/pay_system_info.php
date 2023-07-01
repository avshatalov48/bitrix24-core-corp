<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 */

if (!empty($arResult['JS_DATA']['app']['paySystems']))
{
	$paySystems = CUtil::PhpToJSObject($arResult['JS_DATA']['app']['paySystems']);
	?>
	<div class="page-section order-payment-method-container <?= $arResult['COMPONENT_THEME'] ?>">
		<div id="salescenter-payment-pay-app"></div>
	</div>
	<script>
		BX.ready(function ()
		{
			BX.Vue.create({
				el: '#salescenter-payment-pay-app',
				data: () => {
					return {paySystems: <?=$paySystems?>}
				},
				template: '<salescenter-payment_pay-components-application-pay_system_info :paySystems="paySystems"/>',
			});
		});
	</script>
	<?php
}
