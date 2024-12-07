<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
?>


<script>
	BX.ready(function () {
		var slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);
		if(slider)
		{
			slider.close();
		}
		top.BX.UI.InfoHelper.show(<?= CUtil::PhpToJSObject(\Bitrix\Crm\Restriction\OrderRestriction::LIMIT_SLIDER_ID) ?>);
	});
</script>