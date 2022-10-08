<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$APPLICATION->SetTitle(Loc::getMessage('CRM_SCENARIO_SELECTION_TITLE'));
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-scenario-selection--modifier');

$messages = Loc::loadLanguageFile(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
	'ui.notification',
]);
?>

<div class="crm-scenario-selection-wrapper">
	<div class="crm-scenario-selection-card crm-scenario-selection-deal <?= $arResult['selected_scenario'] ===  'deal' ? 'crm-scenario-selection--active' : '' ?>" data-scenario="deal">
		<div class="crm-scenario-selection-content">
			<div class="crm-scenario-selection-title-box"></div>
			<h3 class="crm-scenario-selection-title">
				<?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS') ?>
			</h3>
			<div class="crm-scenario-selection-desc"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_DESCRIPTION') ?></div>
			<div class="crm-scenario-selection-btn-box">
				<button class="ui-btn ui-btn-sm ui-btn-round ui-btn-light-border crm-scenario-selection-btn"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_SELECT') ?></button>
			</div>
		</div>
		<ul class="crm-scenario-selection-list">
			<li class="crm-scenario-selection-list-item"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_DESCRIPTION_ITEM_1') ?></li>
			<li class="crm-scenario-selection-list-item"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_DESCRIPTION_ITEM_2') ?></li>
			<li class="crm-scenario-selection-list-item"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_DESCRIPTION_ITEM_3') ?></li>
		</ul>
		<div class="crm-scenario-selection-info" id="active-orders-info" style="visibility: hidden;">
			<div class="crm-scenario-selection-info-title"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_ACTIVE_ORDERS', ['#ORDER_COUNT#' => $arResult['order_count']]) ?></div>
			<div class="crm-scenario-selection-info-text"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_ONLY_WARNING') ?></div>
			<div class="crm-scenario-selection-info-checkbox">
				<input type="checkbox" id="convert-active">
				<label for="convert-active"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_CONVERT_ACTIVE_ORDERS') ?></label>
			</div>
		</div>
	</div>
	<div class="crm-scenario-selection-card <?= $arResult['selected_scenario'] ===  'order_deal' ? 'crm-scenario-selection--active' : '' ?>" data-scenario="order_deal">
		<div class="crm-scenario-selection-content">
			<h3 class="crm-scenario-selection-title">
				<?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_PLUS_ORDERS') ?>
			</h3>
			<div class="crm-scenario-selection-desc"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_PLUS_ORDERS_DESCRIPTION') ?></div>
			<div class="crm-scenario-selection-btn-box">
				<button class="ui-btn ui-btn-sm ui-btn-round ui-btn-light-border crm-scenario-selection-btn"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_SELECT') ?></button>
			</div>
		</div>
		<ul class="crm-scenario-selection-list">
			<li class="crm-scenario-selection-list-item"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_PLUS_ORDERS_DESCRIPTION_ITEM_1') ?></li>
			<li class="crm-scenario-selection-list-item"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_PLUS_ORDERS_DESCRIPTION_ITEM_2') ?></li>
			<li class="crm-scenario-selection-list-item"><?= Loc::getMessage('CRM_SCENARIO_SELECTION_DEALS_PLUS_ORDERS_DESCRIPTION_ITEM_3') ?></li>
		</ul>
	</div>
</div>
<?php
$saveButton = [
	'TYPE' => 'save',
	'ONCLICK' => 'BX.CrmScenarioSelection.saveSelectedScenario(event)',
];

$APPLICATION->IncludeComponent(
	'bitrix:ui.button.panel',
	'',
	[
		'BUTTONS' => [$saveButton, 'cancel'],
		'HIDE' => 'Y',
	]
);
?>

<script>
	BX.message(<?=CUtil::PhpToJSObject($messages)?>);
	BX.ready(function() {
		var cardNodes = document.querySelectorAll('.crm-scenario-selection-card');
		var buttonNodes = document.querySelectorAll('.crm-scenario-selection-btn');
		var selectedScenario = <?= CUtil::PhpToJSObject($arResult['selected_scenario']); ?>;
		var convertActiveCheckbox = document.getElementById('convert-active');
		var ordersModeInfo = document.getElementById('active-orders-info');
		var dealListUrl = <?= CUtil::PhpToJSObject($arResult['deal_list_url']); ?>;
		var isOrdersInfoAlwaysHidden = <?= CUtil::PhpToJSObject($arResult['order_info_always_hidden']); ?>;

		BX.CrmScenarioSelection.init({
			cardNodes: cardNodes,
			buttonNodes: buttonNodes,
			selectedScenario: selectedScenario,
			convertActiveCheckbox: convertActiveCheckbox,
			ordersModeInfo: ordersModeInfo,
			isOrdersInfoAlwaysHidden: isOrdersInfoAlwaysHidden,
			dealListUrl: dealListUrl,
		});
	})
</script>
