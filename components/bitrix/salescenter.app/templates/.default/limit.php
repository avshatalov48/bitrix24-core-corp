<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if ($arResult['isOrderLimitReached'])
{
	?>
	<script>
		BX.ready(function () {
			var slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);
			if(slider)
			{
				slider.close();
			}
			top.BX.UI.InfoHelper.show('limit_shop_100_orders');
		});
	</script>
	<?php
}
else
{
	global $APPLICATION;
	$APPLICATION->includeComponent('bitrix:salescenter.feature', '', ['FEATURE' => 'salescenter']);
}
