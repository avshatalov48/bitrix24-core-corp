<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/**
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var array $arResult
 */

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');

\Bitrix\Main\UI\Extension::load('ui.buttons');
\CJSCore::init("sidepanel");

$bodyClass = $APPLICATION->getPageProperty("BodyClass", false);
$APPLICATION->setPageProperty("BodyClass", trim(sprintf("%s %s", $bodyClass, "pagetitle-toolbar-field-view no-background")));
?>

<? $this->setViewTarget('inside_pagetitle', 10); ?>
	<div class="pagetitle-container pagetitle-align-right-container" style="padding-right: 12px;">
		<a class="webform-small-button webform-small-button-blue bx24-top-toolbar-add adm-toolbar-panel-button"
				href="<?=htmlspecialcharsbx($arResult['PATH_TO_CREATE_GROUP'])?>">
			<?=Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_BTN_ADD')?>
		</a>
	</div>
<? $this->endViewTarget(); ?>

<div class='crm-buyer-groups-edit-wrapper'>
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.grid',
		'titleflex',
		[
			'GRID_ID' => $arResult['GRID']['ID'],
			'HEADERS' => $arResult['GRID']['HEADERS'],
			'ROWS' => $arResult['GRID']['ROWS'],
			'TOTAL_ROWS_COUNT' => $arResult['GRID']['TOTAL_ROWS_COUNT'],
			//'NAV_OBJECT' => $arResult['NAV_OBJECT'],
			'~NAV_PARAMS' => ['SHOW_ALWAYS' => false],
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_GRID_SETTINGS_MENU' =>true,
			'SHOW_PAGINATION' => true,
			'SHOW_SELECTED_COUNTER' => false,
			'SHOW_TOTAL_COUNTER' => true,
			'ALLOW_COLUMNS_SORT' => false,
			'ALLOW_COLUMNS_RESIZE' => false,
			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_STYLE' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',

			'FILTER_ID' => $arResult['FILTER']['FILTER_ID'],
			'FILTER' => $arResult['FILTER']['FILTER'],
			'FILTER_PRESETS' => $arResult['FILTER']['FILTER_PRESETS'],
			'ENABLE_LIVE_SEARCH' => $arResult['FILTER']['ENABLE_LIVE_SEARCH'],
			'ENABLE_LABEL' => $arResult['FILTER']['ENABLE_LABEL'],
			'RESET_TO_DEFAULT_MODE' => $arResult['FILTER']['RESET_TO_DEFAULT_MODE'],

			'SORT' => isset($arResult['GRID_SORT']['sort']) ? $arResult['GRID_SORT']['sort'] : [],
			'SORT_VARS' => isset($arResult['GRID_SORT']['vars']) ? $arResult['GRID_SORT']['vars'] : [],

			'EXTENSION' => array(
				'ID' => $arResult['MANAGER_ID'],
				'CONFIG' => array(
					'gridId' => $arResult['GRID']['ID']
				),
				'MESSAGES' => array(
					'deletionDialogTitle' => GetMessage('CRM_ORDER_BUYER_GROUP_LIST_DELETE_GROUP_TITLE'),
					'deletionDialogMessage' => GetMessage('CRM_ORDER_BUYER_GROUP_LIST_DELETE_CONFIRM'),
					'deletionDialogButtonTitle' => GetMessage('CRM_ORDER_BUYER_GROUP_LIST_DELETE')
				)
			),
		]
	);
	?>
</div>

<script>
	BX.ready(function() {
		BX.SidePanel.Instance.bindAnchors({
			rules: [{
				condition: [
					"/shop/buyer_group/(\\d+)/edit/"
				],
				options: {
					width: 580
				}
			}]
		});

		BX.addCustomEvent('SidePanel.Slider:onMessage', function(event){
			if (event.getEventId() === 'OrderBuyerGroupEdit::onSave')
			{
				var gridObject = top.BX.Main.gridManager.getById('<?=$arResult['GRID']['ID']?>');

				if (gridObject && gridObject.hasOwnProperty('instance'))
				{
					gridObject.instance.reloadTable('POST');
				}
			}
		});
	});
</script>