<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var \CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");
Extension::load(["ui.icons"]);

foreach ($arResult['ERRORS'] as $error)
{
	ShowError($error);
}


$sourceTileManagerId = 'crm-analytics-sources';
$channelTileManagerId = 'crm-analytics-channels';
?>
<div class="crm-analytics-list-wrapper">
	<script type="text/javascript">
		BX.ready(function () {
			top.BX.addCustomEvent(
				top,
				'crm-analytics-source-edit',
				function (options)
				{
					var manager = BX.UI.TileList.Manager.getById('<?=$sourceTileManagerId?>');
					if (manager)
					{
						return;
					}

					var row = options.row;
					var enabled = options.enabled;
					var added = options.added;

					if (row.CODE)
					{
						manager.getTile(row.CODE || row.ID).changeSelection(enabled);
					}
					else if (added)
					{
						manager.addTile({
							id: row.ID,
							name: row.NAME,
							iconClass:'ui-icon',
							data: {
								url: '<?=htmlspecialcharsbx($arParams['PATH_TO_EDIT'])?>'.replace('#id#', row.ID)
							}
						})
					}
				}
			);
			top.BX.addCustomEvent(
				top,
				'crm-analytics-channel-enable',
				function (target, isEnabled)
				{
					var manager = BX.UI.TileList.Manager.getById('<?=$channelTileManagerId?>');
					if (manager)
					{
						manager.getTile(target).changeSelection(isEnabled);
					}
				}
			);
		});
	</script>
	<div class="crm-analytics-list-title"><?=Loc::getMessage('CRM_TRACKING_LIST_SOURCES')?></div>
	<?$APPLICATION->IncludeComponent("bitrix:ui.tile.list", "", [
		'ID' => $sourceTileManagerId,
		'SHOW_BUTTON_ADD' => true,
		'LIST' => $arResult['SOURCES'],
	]);?>

	<br>
	<br>
	<br>

	<div class="crm-analytics-list-title"><?=Loc::getMessage('CRM_TRACKING_LIST_CHANNELS')?></div>
	<?$APPLICATION->IncludeComponent("bitrix:ui.tile.list", "", [
		'ID' => $channelTileManagerId,
		'LIST' => $arResult['CHANNELS'],
	]);?>

	<script type="text/javascript">
		BX.ready(function () {
			BX.Crm.Tracking.Grid.init(<?=Json::encode(array(
				'actionUri' => $arResult['ACTION_URI'],
				"gridId" => $arParams['GRID_ID'],
				"pathToEdit" => $arParams['PATH_TO_EDIT'],
				"pathToAdd" => $arParams['PATH_TO_ADD'],
				"sourceTileManagerId" => $sourceTileManagerId,
				"channelTileManagerId" => $channelTileManagerId,
				'mess' => array()
			))?>);
		});
	</script>
</div>