<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);
$messages = Loc::loadLanguageFile(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-paddings no-hidden no-background");

CJSCore::Init([
	"admin_sidepanel",
]);

Extension::load([
	'salescenter.manager',
	'ui.tilegrid',
	'ui.fonts.opensans',
	'popup',
	'ajax',
]);

\Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->addFeedbackButtonToToolbar();

?>
<div class="mp<? if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y"): ?> mp-slider<? endif; ?>">
	<div class="salescenter-container">
		<div class="salescenter-container" id="salescenter-panel"></div>
	</div>

	<?php if($arResult['deliveryParams'])
	{?>
	<div class="salescenter-container">
		<div class="salescenter-block-title"><?=Loc::getMessage('SALESCENTER_CONTROL_PANEL_DELIVERY_TITLE');?></div>
		<div class="salescenter-container" id="salescenter-delivery"></div>
	</div>
	<?php }
	if($arResult['cashboxParams'])
	{?>
	<div class="salescenter-container">
		<div class="salescenter-block-title"><?=Loc::getMessage($arResult['cashboxTitleCode']);?></div>
		<div class="salescenter-container" id="salescenter-cashbox"></div>
	</div>
	<?}?>
</div>

<script>
	BX.ready(function()
	{
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);
		BX.Salescenter.Manager.init(<?=CUtil::PhpToJSObject($arResult['managerParams'])?>);

		// common grid
		var panelParams = <?=CUtil::PhpToJSObject($arResult['panelParams']);?>;
		panelParams.container = document.getElementById('salescenter-panel');
		panelParams.sizeRatio = "55%";
		panelParams.itemMinWidth = 180;
		panelParams.tileMargin = 7;
		panelParams.itemType = 'BX.Salescenter.ControlPanel.Item';

		var panelGrid = new BX.TileGrid.Grid(panelParams);
		panelGrid.draw();

		<?php if($arResult['deliveryParams'])
		{?>
		// delivery grid
		var deliveryParams = <?=CUtil::PhpToJSObject($arResult['deliveryParams']);?>;
		deliveryParams.container = document.getElementById('salescenter-delivery');
		deliveryParams.sizeRatio = "55%";
		deliveryParams.itemMinWidth = 180;
		deliveryParams.tileMargin = 7;
		deliveryParams.itemType = 'BX.Salescenter.ControlPanel.Item';

		var deliveryGrid = new BX.TileGrid.Grid(deliveryParams);
		deliveryGrid.draw();
		<?php }
		if($arResult['cashboxParams'])
		{
		?>
		// cashbox grid
		var cashboxParams = <?=CUtil::PhpToJSObject($arResult['cashboxParams']);?>;
		cashboxParams.container = document.getElementById('salescenter-cashbox');
		cashboxParams.sizeRatio = "55%";
		cashboxParams.itemMinWidth = 180;
		cashboxParams.tileMargin = 7;
		cashboxParams.itemType = 'BX.Salescenter.ControlPanel.Item';

		var cashboxGrid = new BX.TileGrid.Grid(cashboxParams);
		cashboxGrid.draw();
		<?php }?>

		BX.Salescenter.ControlPanel.init({
			panelGrid: panelGrid,
		});
	});
</script>
<?