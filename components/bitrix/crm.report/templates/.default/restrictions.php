<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

?>

<div class="reports-restrictions-placeholder"></div>

<script>
	BX.ready(function() {
		<?=\Bitrix\Crm\Restriction\RestrictionManager::getReportRestriction()->prepareInfoHelperScript();?>;
		var slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);
		if(slider)
		{
			slider.close();
		}
		else
		{
			BX.addCustomEvent("SidePanel.Slider:onCloseComplete", function(event) {
				location.href = "/crm/deal/";
			});
		}
	});
</script>