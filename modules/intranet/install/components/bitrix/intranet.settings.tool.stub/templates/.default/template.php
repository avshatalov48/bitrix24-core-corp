<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */

global $APPLICATION;

\Bitrix\Main\Ui\Extension::load(['ui.info-helper']);
\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass', ($bodyClass ? $bodyClass . ' ' : '')
	. 'no-all-paddings semi-transparent-background'
);
?>

<script>
	BX.ready(() => {
		if (!top.BX || !top.BX.Reflection.getClass('BX.UI.InfoHelper'))
		{
			return;
		}

		const showHelperCallback = () => {
			top.BX.UI.InfoHelper.show('<?=CUtil::JSEscape($arResult['LIMIT_CODE'])?>', {
				isLimit: true,
				limitAnalyticsLabels: {
					module: '<?=CUtil::JSEscape($arResult['MODULE'])?>',
					source: '<?=CUtil::JSEscape($arResult['SOURCE'])?>',
				},
			});
		};

		const slider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (slider)
		{
			slider.close(true, showHelperCallback);
		}
		else
		{
			showHelperCallback();
		}
	});
</script>