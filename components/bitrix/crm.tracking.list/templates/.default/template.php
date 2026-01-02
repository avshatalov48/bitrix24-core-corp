<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");
Extension::load(["ui.icons", "ui.buttons", "ui.feedback.form"]);

foreach ($arResult['ERRORS'] as $error)
{
	ShowError($error);
}
if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
{
	Toolbar::deleteFavoriteStar();
}

$sourceTileManagerId = 'crm-analytics-sources';
$channelTileManagerId = 'crm-analytics-channels';
?>
<div class="crm-analytics-list-wrapper">
	<script>
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

    <br>
    <br>
    <br>

    <div class="crm-analytics-list-title"><?=Loc::getMessage('CRM_TRACKING_START_CONFIGURATION_HELP')?></div>
    <div class="ui-tile-list-block">
        <div class="ui-tile-list-wrap">
            <div data-role="tile/items" class="ui-tile-list-list">
				<div
					class="ui-tile-list-item crm-tracking-ui-tile-custom-list-item"
					data-forms='<?= \Bitrix\Main\Web\Json::encode($arResult['FEEDBACK_FORMS_DATA']) ?>'
					style=""
					onclick="BX.UI.Feedback.Form.open(
						{
						title:'<?= htmlspecialcharsbx(CUtil::JSescape(
						Loc::getMessage('CRM_TRACKING_START_CONFIGURATION_NEED_HELP')
						)) ?>',
						forms: JSON.parse(this.dataset.forms),
						id:'crm-tracking-configuration-help',
						portalUri: '<?= htmlspecialcharsbx(CUtil::JSescape($arResult['FEEDBACK_FORM_URI'])) ?>',
						presets: {
						source: 'crm',
						},
						},
						);"
				>
                    <span class="crm-tracking-ui-tile-custom-list-item-subtitle">
                        <?=Loc::getMessage('CRM_TRACKING_START_CONFIGURATION_HELP_ORDER')?>
                    </span>
                    <button class="ui-btn ui-btn-primary"><?=Loc::getMessage('CRM_TRACKING_START_CONFIGURATION_ORDER')?></button>
                </div>
            </div>
        </div>
    </div>

	<script>
		BX.ready(function () {
			BX.Crm.Tracking.Grid.init(<?=Json::encode(array(
				'actionUri' => $arResult['ACTION_URI'] ?? '',
				"gridId" => $arParams['GRID_ID'] ?? '',
				"pathToEdit" => $arParams['PATH_TO_EDIT'] ?? '',
				"pathToAdd" => $arParams['PATH_TO_ADD'] ?? '',
				"sourceTileManagerId" => $sourceTileManagerId,
				"channelTileManagerId" => $channelTileManagerId,
				'mess' => array()
			))?>);
		});
	</script>
</div>
