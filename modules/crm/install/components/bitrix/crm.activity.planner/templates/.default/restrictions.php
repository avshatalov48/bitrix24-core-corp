<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var \CBitrixComponentTemplate $this */
/** @var array $arResult */

/** @see \Bitrix\Crm\Component\Base::addJsRouter */
$this->getComponent()->addJsRouter($this);

// todo debug why is it not working as expected

?>
<script>
	BX.ready(function() {
		<?= $arResult['RESTRICTIONS_SCRIPT']; ?>;

		var slider = top.BX && top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);

		if (slider)
		{
			slider.close();
		}
		else
		{
			BX.addCustomEvent("SidePanel.Slider:onCloseComplete", function(event) {
				var entityTypeId = <?= (int)$arResult['ENTITY_TYPE_ID'] ?>;
				location.href = BX.Crm.Router.getItemListUrl(entityTypeId);
			});
		}
	});
</script>
