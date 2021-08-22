<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

Extension::load(["ui.forms", "ui.buttons", "popup", "date"]);

foreach ($arResult['ERRORS'] as $error)
{
	ShowError($error);
}

foreach ($arResult['ROWS'] as $index => $data)
{
	if (isset($arResult['SOURCES'][$data['SOURCE_ID']]))
	{
		$data['SOURCE_ID'] = $arResult['SOURCES'][$data['SOURCE_ID']];
	}
	foreach ($data as $key => $value)
	{
		if (in_array($key, ['EXPENSES']))
		{
			continue;
		}

		$data[$key] = htmlspecialcharsbx($value);
	}

	$data['PERIOD'] = '<span style="white-space: nowrap;">' . $data['DATE_FROM'] . ' - ' . $data['DATE_TO'] . '</span>';

	$actions = [];
	if ($arParams['CAN_EDIT'])
	{
		$actions[] = array(
			'TITLE' => Loc::getMessage('CRM_TRACKING_EXPENSES_BTN_REMOVE_TITLE'),
			'TEXT' => Loc::getMessage('CRM_TRACKING_EXPENSES_BTN_REMOVE'),
			'ONCLICK' => "BX.Crm.Tracking.Expenses.remove({$data['ID']});"
		);
	}

	$arResult['ROWS'][$index] = array(
		'id' => $data['ID'],
		'columns' => $data,
		'actions' => $actions
	);
}

/*
ob_start();
$APPLICATION->IncludeComponent(
	"bitrix:main.ui.filter",
	"",
	array(
		"FILTER_ID" => $arParams['FILTER_ID'],
		"GRID_ID" => $arParams['GRID_ID'],
		"FILTER" => $arResult['FILTERS'],
		'ENABLE_LIVE_SEARCH' => false,
		"ENABLE_LABEL" => true,
	)
);
$filterLayout = ob_get_clean();
*/

if ($arResult['SOURCE_NAME'])
{
	$this->SetViewTarget('pagetitle');
	?>
		<button id="crm-tracking-expenses-add" type="button" class="ui-btn ui-btn-primary">
			<?=Loc::getMessage('CRM_TRACKING_EXPENSES_BTN_ADD')?>
		</button>
	<?
	$this->EndViewTarget();
}

$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));
if ($arParams['CAN_EDIT'])
{
	$button = $snippet->getRemoveButton();
	$button['ONCHANGE'][0]['DATA'][0]['JS'] = 'BX.Crm.Tracking.Expenses.removeSelected()';
	$controlPanel['GROUPS'][0]['ITEMS'][] = $button;
}

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => $arParams['GRID_ID'],
		"COLUMNS" => $arResult['COLUMNS'],
		"ROWS" => $arResult['ROWS'],
		"NAV_OBJECT" => $arResult['NAV_OBJECT'],
		"~NAV_PARAMS" => array('SHOW_ALWAYS' => false),
		'SHOW_ROW_CHECKBOXES' => $arParams['CAN_EDIT'],
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_PAGINATION' => true,
		'SHOW_SELECTED_COUNTER' => true,
		'SHOW_TOTAL_COUNTER' => true,
		'ACTION_PANEL' => $controlPanel,
		"TOTAL_ROWS_COUNT" => $arResult['TOTAL_ROWS_COUNT'],
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "N",
		"AJAX_OPTION_HISTORY" => "N"
	)
);


?>
	<div style="display: none;">
		<div id="crm-tracking-expenses-popup">

			<div class="crm-tracking-expenses-field">
				<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_TRACKING_EXPENSES_INPUT_PERIOD')?></div>
				<div>
					<div data-role="crm/tracking/from" class="ui-ctl ui-ctl-after-icon ui-ctl-date ui-ctl-inline">
						<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
						<div data-role="crm/tracking/from/view" class="ui-ctl-element"></div>
						<input type="hidden" data-role="crm/tracking/from/data">
					</div>
					<span>-</span>
					<div data-role="crm/tracking/to" class="ui-ctl ui-ctl-after-icon ui-ctl-date ui-ctl-inline">
						<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
						<div data-role="crm/tracking/to/view" class="ui-ctl-element"></div>
						<input type="hidden" data-role="crm/tracking/to/data">
					</div>
				</div>
			</div>

			<div class="crm-tracking-expenses-field">
				<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_TRACKING_EXPENSES_INPUT_SUM')?></div>
				<div>
					<div data-role="crm/tracking/sum" class="ui-ctl ui-ctl-textbox ui-ctl-inline ui-ctl-w50">
						<input data-role="crm/tracking/sum/data" type="number" class="ui-ctl-element">
					</div>

					<div data-role="crm/tracking/currency" class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-inline ui-ctl-w33">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select data-role="crm/tracking/currency/data" class="ui-ctl-element">
							<?foreach ($arResult['CURRENCIES'] as $currencyId => $currency):?>
								<option value="<?=htmlspecialcharsbx($currencyId)?>">
									<?=htmlspecialcharsbx($currency['FULL_NAME'])?>
								</option>
							<?endforeach;?>
						</select>
					</div>
				</div>
			</div>

			<div class="crm-tracking-expenses-field">
				<div>
					<label data-role="crm/tracking/actions/checkbox" class="ui-ctl ui-ctl-checkbox">
						<input data-role="crm/tracking/actions/checkbox/data" type="checkbox" class="ui-ctl-element">
						<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_TRACKING_EXPENSES_INPUT_ACTIONS')?></div>
					</label>
					<div data-role="crm/tracking/actions/cont" class="ui-ctl ui-ctl-textbox ui-ctl-inline ui-ctl-w33">
						<input data-role="crm/tracking/actions/data" type="number" class="ui-ctl-element">
					</div>
				</div>
			</div>

			<div class="crm-tracking-expenses-field">
				<div class="ui-ctl-label-text"><?=Loc::getMessage('CRM_TRACKING_EXPENSES_INPUT_COMMENT')?></div>
				<div>
					<div class="ui-ctl ui-ctl-textarea ui-ctl-xs">
						<textarea data-role="crm/tracking/comment/data" class="ui-ctl-element"></textarea>
					</div>
				</div>
			</div>

		</div>
	</div>
	<script type="text/javascript">
		BX.ready(function () {
			BX.Crm.Tracking.Expenses.init(<?=Json::encode(array(
				"gridId" => $arParams['GRID_ID'],
				'signedParameters' => $this->getComponent()->getSignedParameters(),
				'componentName' => $this->getComponent()->getName(),
				"sourceId" => $arParams['ID'],
				"sourceName" => $arResult['SOURCE_NAME'],
				'mess' => [
					'addTitle' => Loc::getMessage('CRM_TRACKING_EXPENSES_INPUT_TITLE')
				]
			))?>);

			<?if ($arResult['IS_ADD']):?>
				BX.Crm.Tracking.Expenses.showAddPopup();
			<?endif;?>
		});
	</script>
<?