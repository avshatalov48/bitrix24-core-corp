<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;

Extension::load([
	'ui.vue',
	'salescenter.payment-pay.payment-method',
]);

/**
 * @var array $arParams
 * @var array $arResult
 */

if (is_array($arResult['PAYSYSTEMS_LIST']) && count($arResult['PAYSYSTEMS_LIST']) > 0)
{
	$items = [];
	foreach ($arResult['PAYSYSTEMS_LIST'] as $item)
	{
		$items[] = array_merge($item, ['SHOW_DESCRIPTION'=>'N']);
	}
	$items = CUtil::PhpToJSObject($items);
?>

	<div class="<?= $arResult['COMPONENT_THEME'] ?>">
		<div id="payment_method-list"></div>
	</div>
	<script>
		var items = <?=$items?>;
		BX.Vue.create({
			el: '#payment_method-list',
			data: () => {return {items}},
			template: `<salescenter-payment_pay-payment_method-list :items='items'/>`,
		});
	</script>
<?php
}
