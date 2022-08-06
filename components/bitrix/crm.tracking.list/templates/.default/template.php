<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

/** @var \CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");
Extension::load(["ui.icons", "ui.buttons", "ui.feedback.form"]);

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

    <br>
    <br>
    <br>

    <div class="crm-analytics-list-title"><?=Loc::getMessage('CRM_TRACKING_START_CONFIGURATION_HELP')?></div>
    <div class="ui-tile-list-block">
        <div class="ui-tile-list-wrap">
            <div data-role="tile/items" class="ui-tile-list-list">
                <div
                        class="ui-tile-list-item crm-tracking-ui-tile-custom-list-item"
                        style=""
                        onclick="BX.UI.Feedback.Form.open(
                                {
                                title:'<?= CUtil::addslashes(Loc::getMessage('CRM_TRACKING_START_CONFIGURATION_NEED_HELP'))?>',
                                forms: [
                                {zones: ['en', 'eu', 'in', 'uk'], id: 986, lang: 'en', sec: 'bb83fq'},
                                {zones: ['de'], id: 988, lang: 'de', sec: 'c59qtl'},
                                {zones: ['la', 'co', 'mx'], id: 990, lang: 'es', sec: 'kqcqnn'},
                                {zones: ['com.br'], id: 992, lang: 'br', sec: '74yrxg'},
                                {zones: ['pl'], id: 994, lang: 'pl', sec: 'qtxmku'},
                                {zones: ['ua'], id: 978, lang: 'ua', sec: 'u509o5'},
                                {zones: ['by'], id: 979, lang: 'by', sec: '0k2fke'},
                                {zones: ['kz'], id: 976, lang: 'kz', sec: 'ht2w4d'},
                                {zones: ['ru'], id: 973, lang: 'ru', sec: 'w6vllg'},
                                ],
                                id:'crm-tracking-configuration-help',
                                portalUri: 'https://bitrix24.team'
                                }
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
	</div>

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