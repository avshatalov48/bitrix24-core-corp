<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
/** @var array $arParams */

$urlMan = \Bitrix\Rpa\Driver::getInstance()->getUrlManager();
$editRobotUrl = $urlMan->getAutomationEditRobotUrl($arParams["typeId"]);
$stageId = $arParams["stage"];
$bodyClass = false; // $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-background no-hidden");
\Bitrix\Main\UI\Extension::load(["ui.fonts.opensans", "ui.design-tokens"]);

$icons = [
	'RpaRequestActivity' => 'rpa-add-robot-item-request',
	'RpaReviewActivity' => 'rpa-add-robot-item-know',
	'RpaMoveActivity' => 'rpa-add-robot-item-move',
	'RpaApproveActivity' => 'rpa-add-robot-item-approve',
];
?>
<div class="rpa-add-robot">
	<?php foreach ($arResult['ROBOTS'] as $robot):
		$robotUrl = $editRobotUrl->addParams(['robotType' => $robot['CLASS'], 'stage' => $stageId]);
		$icon = $icons[$robot['CLASS']] ?? '';
	?>
		<a class="rpa-add-robot-item <?=$icon?>" href="<?=htmlspecialcharsbx($robotUrl)?>" onclick="BX.Rpa.Automation.AddRobotComponent.closeSlider()">
			<div class="rpa-add-robot-title">
				<span class="rpa-add-robot-title-item"><?=htmlspecialcharsbx($robot['NAME'])?></span>
			</div>
			<span class="rpa-add-robot-icon"></span>
		</a>
	<?php endforeach;?>
</div>
<script>
	BX.ready(function()
	{
		BX.namespace('BX.Rpa.Automation.AddRobotComponent');
		BX.Rpa.Automation.AddRobotComponent = {};
		BX.Rpa.Automation.AddRobotComponent.closeSlider = function()
		{
			var sliderInstance = BX.getClass('BX.SidePanel.Instance');
			if (sliderInstance)
			{
				var thisSlider = sliderInstance.getSliderByWindow(window);
				if (thisSlider)
				{
					thisSlider.close();
				}
			}

		};
	});
</script>