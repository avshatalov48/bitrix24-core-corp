<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass.' ' : '') . 'bx-layout-inner-inner-table--empty'
);

?>
<script>
	top.BX && top.BX.ready(function() {
		<?= $arResult['sliderScript'] ?>;
		const slider = top.BX.SidePanel && top.BX.SidePanel.Instance.getSliderByWindow(window);

		if (slider)
		{
			slider.close();

			return;
		}

		top.BX.addCustomEvent('SidePanel.Slider:onCloseComplete', (event) => {
			location.href = '<?= $arResult['locationHref'] ?>';
		});
	});
</script>
