<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityProgressBarComponent $component */

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";

\Bitrix\Main\UI\Extension::load([
	'crm.scoringbutton',
	'crm.conversion',
	'ui.tour',
]);

//region LEGEND
if(isset($arResult['LEGEND']))
{
	$this->SetViewTarget('crm_details_legend');
	?><a href="#" onclick="BX.Crm.DealCategoryChanger.processEntity(<?=$arResult['ENTITY_ID']?>,{ usePopupMenu: true, anchor: this }); return false;">
		<?=htmlspecialcharsbx($arResult['LEGEND'])?>
	</a><?
	$this->EndViewTarget();
}

if (isset($arResult['IS_AUTOMATION_DEBUG_ITEM']) && $arResult['IS_AUTOMATION_DEBUG_ITEM'] === 'Y'):
	$this->SetViewTarget('crm_details_title_prefix');
	?><span class="crm-details-debug-item">
		<?= htmlspecialcharsbx(Loc::getMessage('CRM_DEAL_DETAIL_AUTOMATION_DEBUG_ITEM')) ?>
	</span><?php
	$this->EndViewTarget();
endif;
//endregion

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	array(
		'CONTAINER_ID' => '',
		'EDITOR_ID' => $activityEditorID,
		'PREFIX' => $prefix,
		'ENABLE_UI' => false,
		'ENABLE_TOOLBAR' => false,
		'ENABLE_EMAIL_ADD' => true,
		'ENABLE_TASK_ADD' => $arResult['ENABLE_TASK'],
		'MARK_AS_COMPLETED_ON_VIEW' => false,
		'SKIP_VISUAL_COMPONENTS' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.deal.menu',
	'',
	array(
		'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'] ?? null,
		'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'],
		'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
		'PATH_TO_DEAL_FUNNEL' => $arResult['PATH_TO_DEAL_FUNNEL'] ?? null,
		'PATH_TO_DEAL_IMPORT' => $arResult['PATH_TO_DEAL_IMPORT'] ?? null,
		'ELEMENT_ID' => $arResult['ENTITY_ID'],
		'CATEGORY_ID' => $arResult['CATEGORY_ID'],
		'MULTIFIELD_DATA' => isset($arResult['ENTITY_DATA']['MULTIFIELD_DATA'])
			? $arResult['ENTITY_DATA']['MULTIFIELD_DATA'] : array(),
		'OWNER_INFO' => $arResult['ENTITY_INFO'],
		'CONVERSION_PERMITTED' => $arResult['CONVERSION_PERMITTED'],
		'CONVERSION_CONTAINER_ID' => $arResult['CONVERSION_CONTAINER_ID'],
		'CONVERSION_LABEL_ID' => $arResult['CONVERSION_LABEL_ID'],
		'CONVERSION_BUTTON_ID' => $arResult['CONVERSION_BUTTON_ID'],
		'IS_RECURRING' => $arResult['ENTITY_DATA']['IS_RECURRING'],
		'BIZPROC_STARTER_DATA' => $arResult['BIZPROC_STARTER_DATA'],
		'TYPE' => 'details',
		'SCRIPTS' => array(
			'DELETE' => 'BX.Crm.EntityDetailManager.items["'.CUtil::JSEscape($guid).'"].processRemoval();',
			'EXCLUDE' => 'BX.Crm.EntityDetailManager.items["'.CUtil::JSEscape($guid).'"].processExclusion();'
		)
	),
	$component
);

?><script type="text/javascript">
	BX.message({
		"CRM_TIMELINE_HISTORY_STUB": "<?=GetMessageJS('CRM_DEAL_DETAIL_HISTORY_STUB')?>",
	});

	<? if($arResult['ENTITY_ID'] > 0): ?>
			new BX.CrmScoringButton({
				mlInstalled: <?= (\Bitrix\Crm\Ml\Scoring::isMlAvailable() ? 'true' : 'false')?>,
				scoringEnabled: <?= (\Bitrix\Crm\Ml\Scoring::isEnabled() ? 'true' : 'false')?>,
				scoringParameters: <?= \Bitrix\Main\Web\Json::encode($arResult['SCORING']) ?>,
				entityType: '<?= CCrmOwnerType::DealName ?>',
				entityId: <?= (int)$arResult['ENTITY_ID']?>,
				isFinal: <?= $arResult['IS_STAGE_FINAL'] ? 'true' : 'false' ?>,
			});
	<? endif; ?>
</script><?

$editorContext = $arResult['CONTEXT'];
if(isset($arResult['ORIGIN_ID']) && $arResult['ORIGIN_ID'] !== '')
{
	$editorContext['ORIGIN_ID'] = $arResult['ORIGIN_ID'];
}
if(isset($arResult['INITIAL_DATA']))
{
	$editorContext['INITIAL_DATA'] = $arResult['INITIAL_DATA'];
}
$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	array(
		'GUID' => $guid,
		'ENTITY_TYPE_ID' => ($arResult['ENTITY_DATA']['IS_RECURRING'] !== 'Y') ? \CCrmOwnerType::Deal : \CCrmOwnerType::DealRecurring,
		'ENTITY_ID' => $arResult['IS_EDIT_MODE'] ? $arResult['ENTITY_ID'] : 0,
		'ENTITY_INFO' => $arResult['ENTITY_INFO'],
		'READ_ONLY' => $arResult['READ_ONLY'],
		'TABS' => $arResult['TABS'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.details/ajax.php?'.bitrix_sessid_get(),
		'EDITOR' => array(
			'GUID' => "{$guid}_editor",
			'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
			'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
			'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
			'ENTITY_DATA' => $arResult['ENTITY_DATA'],
			'ENTITY_VALIDATORS' => $arResult['ENTITY_VALIDATORS'],
			'ENABLE_SECTION_EDIT' => true,
			'ENABLE_SECTION_CREATION' => true,
			'ENABLE_USER_FIELD_CREATION' => $arResult['ENABLE_USER_FIELD_CREATION'],
			'USER_FIELD_ENTITY_ID' => $arResult['USER_FIELD_ENTITY_ID'],
			'USER_FIELD_CREATE_PAGE_URL' => $arResult['USER_FIELD_CREATE_PAGE_URL'],
			'USER_FIELD_CREATE_SIGNATURE' => $arResult['USER_FIELD_CREATE_SIGNATURE'],
			'USER_FIELD_FILE_URL_TEMPLATE' => $arResult['USER_FIELD_FILE_URL_TEMPLATE'],
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.details/ajax.php?'.bitrix_sessid_get(),
			'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $arResult['CONTEXT_ID'],
			'CONTEXT' => $editorContext,
			'ATTRIBUTE_CONFIG' => array(
				'ENTITY_SCOPE' => $arResult['ENTITY_ATTRIBUTE_SCOPE'],
				'CAPTIONS' => FieldAttributeManager::getCaptionsForEntityWithStages(),
			),
			'COMPONENT_AJAX_DATA' => [
				'RELOAD_ACTION_NAME' => 'LOAD',
				'RELOAD_FORM_DATA' => [
					'ACTION_ENTITY_ID' => $arResult['ENTITY_ID']
				] + $editorContext
			]
		),
		'TIMELINE' => array(
			'GUID' => "{$guid}_timeline",
			'ENABLE_WAIT' => true,
			'PROGRESS_SEMANTICS' => $arResult['PROGRESS_SEMANTICS'],
			'WAIT_TARGET_DATES' => $arResult['WAIT_TARGET_DATES']
		),
		'ENABLE_PROGRESS_BAR' => true,
		'ENABLE_PROGRESS_CHANGE' => ($arResult['ENTITY_DATA']['IS_RECURRING'] !== 'Y' && !$arResult['READ_ONLY']),
		'ACTIVITY_EDITOR_ID' => $activityEditorID,
		'EXTRAS' => array('CATEGORY_ID' => $arResult['CATEGORY_ID']),
		'ANALYTIC_PARAMS' => array('deal_category' => $arResult['CATEGORY_ID']),
		'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE']
	)
);

/** @var \Bitrix\Crm\Conversion\EntityConversionConfig|null $conversionConfig */
$conversionConfig = $arResult['CONVERSION_CONFIG'] ?? null;

if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && $conversionConfig):
?><script type="text/javascript">
		BX.ready(
			function()
			{
				var converter = BX.Crm.Conversion.Manager.Instance.initializeConverter(
					BX.CrmEntityType.enumeration.deal,
					{
						configItems: <?= CUtil::PhpToJSObject($conversionConfig->toJson()) ?>,
						scheme: <?= CUtil::PhpToJSObject($conversionConfig->getScheme()->toJson(true)) ?>,
						params: {
							serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.details/ajax.php?action=convert&'.bitrix_sessid_get()?>",
							messages: {
								accessDenied: "<?=GetMessageJS("CRM_DEAL_CONV_ACCESS_DENIED")?>",
								generalError: "<?=GetMessageJS("CRM_DEAL_CONV_GENERAL_ERROR")?>",
								dialogTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_TITLE")?>",
								syncEditorLegend: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_LEGEND")?>",
								syncEditorFieldListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
								syncEditorEntityListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
								continueButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CONTINUE_BTN")?>",
								cancelButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CANCEL_BTN")?>"
							}
						}
					}
				);

				var schemeSelector = new BX.Crm.Conversion.SchemeSelector(
					converter,
					{
						entityId: <?= (int)$arResult['ENTITY_ID'] ?>,
						containerId: '<?= CUtil::JSEscape($arResult['CONVERSION_CONTAINER_ID']) ?>',
						labelId: '<?= CUtil::JSEscape($arResult['CONVERSION_LABEL_ID']) ?>',
						buttonId: '<?= CUtil::JSEscape($arResult['CONVERSION_BUTTON_ID']) ?>'
					}
				);

				schemeSelector.enableAutoConversion();

				var convertByEvent = function(dstEntityTypeId)
				{
					var schemeItem = converter.getConfig().getScheme().getItemForSingleEntityTypeId(dstEntityTypeId);
					if (!schemeItem)
					{
						console.error('SchemeItem with single entityTypeId ' + dstEntityTypeId  + ' is not found');
						return;
					}

					converter.getConfig().updateFromSchemeItem(schemeItem);

					converter.convert(<?= (int)$arResult['ENTITY_ID'] ?>);
				};

				BX.addCustomEvent(window,
					'CrmCreateQuoteFromDeal',
					function()
					{
						convertByEvent(BX.CrmEntityType.enumeration.quote);
					}
				);
				BX.addCustomEvent(window,
					'CrmCreateInvoiceFromDeal',
					function()
					{
						convertByEvent(BX.CrmEntityType.enumeration.invoice);
					}
				);
				BX.addCustomEvent(window,
					'BX.Crm.ItemListComponent:onAddNewItemButtonClick',
					function(event)
					{
						var dstEntityTypeId = Number(event.getData().entityTypeId);
						if (dstEntityTypeId > 0)
						{
							convertByEvent(dstEntityTypeId);
						}
					}
				);

				BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
				BX.onCustomEvent(window, "BX.CrmEntityConverter:applyPermissions", [BX.CrmEntityType.names.deal]);
				<?php
				if($arResult['ENTITY_ID'] <= 0 && !empty($arResult['FIELDS_SET_DEFAULT_VALUE']))
				{?>
                    var fieldsSetDefaultValue = <?=CUtil::PhpToJSObject($arResult['FIELDS_SET_DEFAULT_VALUE']);?>;
                    BX.addCustomEvent("onSave", function(fieldConfigurator, params) {
					var field = params.field;
					if(
						fieldConfigurator instanceof BX.Crm.EntityEditorFieldConfigurator
						&& fieldConfigurator._mandatoryConfigurator
						&& (field instanceof BX.Crm.EntityEditorField || field instanceof BX.UI.EntityEditorField)
						//&& field.isChanged()
						&& fieldsSetDefaultValue.indexOf(field._id) > -1
					)
					{
						if(fieldConfigurator._mandatoryConfigurator.isEnabled())
						{
							delete field._model._data[field.getDataKey()];
							field.refreshLayout();
						}
						else
						{
							if(field.getSchemeElement().getData().defaultValue)
							{
								field._model._data[field.getDataKey()] =
									field.getSchemeElement().getData().defaultValue
								;
								field.refreshLayout();
							}
						}
					}
                    });
				<?php
				}?>
			}
		);
	</script><?
endif;
?>

<script type="text/javascript">
	BX.ready(() => {
		BX.Crm.Deal.DealComponent = new BX.Crm.Deal.DealManager({
			guid: '<?=CUtil::JSEscape($guid)?>',
		});
		<?php if($arResult['WAREHOUSE_CRM_TOUR_DATA']['IS_TOUR_AVAILABLE']): ?>
		BX.Loc.setMessage(<?=CUtil::PhpToJSObject([
			'CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TITLE' => Loc::getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TITLE'),
			'CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TEXT' => Loc::getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TEXT'),
			'CRM_DEAL_DETAIL_WAREHOUSE_PRODUCT_STORE_GUIDE_TITLE' => Loc::getMessage('CRM_DEAL_DETAIL_WAREHOUSE_PRODUCT_STORE_GUIDE_TITLE'),
			'CRM_DEAL_DETAIL_WAREHOUSE_PRODUCT_STORE_GUIDE_TEXT' => Loc::getMessage('CRM_DEAL_DETAIL_WAREHOUSE_PRODUCT_STORE_GUIDE_TEXT'),
			'CRM_DEAL_DETAIL_WAREHOUSE_ADD_DOCUMENT_GUIDE_TITLE' => Loc::getMessage('CRM_DEAL_DETAIL_WAREHOUSE_ADD_DOCUMENT_GUIDE_TITLE'),
			'CRM_DEAL_DETAIL_WAREHOUSE_ADD_DOCUMENT_GUIDE_TEXT' => Loc::getMessage('CRM_DEAL_DETAIL_WAREHOUSE_ADD_DOCUMENT_GUIDE_TEXT'),
			'CRM_DEAL_DETAIL_WAREHOUSE_SUCCESS_DEAL_GUIDE_TITLE' => Loc::getMessage('CRM_DEAL_DETAIL_WAREHOUSE_SUCCESS_DEAL_GUIDE_TITLE'),
			'CRM_DEAL_DETAIL_WAREHOUSE_SUCCESS_DEAL_GUIDE_TEXT' => Loc::getMessage('CRM_DEAL_DETAIL_WAREHOUSE_SUCCESS_DEAL_GUIDE_TEXT'),
		])?>);

		const onboardingData = {
			chain:  Number(<?=CUtil::PhpToJSObject($arResult['WAREHOUSE_CRM_TOUR_DATA']['CHAIN_DATA']['CHAIN'])?>),
			chainStep: Number(<?=CUtil::PhpToJSObject($arResult['WAREHOUSE_CRM_TOUR_DATA']['CHAIN_DATA']['STAGE'])?>),
			successDealGuideIsOver: (<?=CUtil::PhpToJSObject($arResult['WAREHOUSE_CRM_TOUR_DATA']['CHAIN_DATA']['SUCCESS_DEAL_GUIDE_IS_OVER'])?> === 'true')
		};
		const serviceUrl = '<?='/bitrix/components/bitrix/crm.deal.details/ajax.php?'.bitrix_sessid_get()?>';
		BX.Crm.Deal.DealComponent.enableOnboardingChain(onboardingData, serviceUrl);
		<?php endif; ?>
	});
</script>

<script type="text/javascript">
	(function() {
		var listener = function(e) {
			if (BX.Main && BX.Main.gridManager)
			{
				var grid = BX.Main.gridManager.getInstanceById('CCrmEntityProductListComponent');
				if (grid)
				{
					grid.reload();
				}
			}
		};
		BX.Event.EventEmitter.subscribe('PaymentDocuments.EntityEditor:changePaymentPaidStatus', listener);
		BX.Event.EventEmitter.subscribe('PaymentDocuments.EntityEditor:changeShipmentShippedStatus', listener);
		BX.Event.EventEmitter.subscribe('PaymentDocuments.EntityEditor:changeRealizationDeductedStatus', listener);
	})();
</script>
<?php if (isset($arResult['ACTIVE_TAB']) && $arResult['ACTIVE_TAB']): ?>
<script>
	BX.ready(function () {
		BX.onCustomEvent('<?= $arResult['GUID'] ?>_click_<?= CUtil::JSEscape($arResult['ACTIVE_TAB']) ?>');
	});
</script>
<?php endif; ?>

<?php if (array_key_exists('AUTOMATION_CHECK_AUTOMATION_TOUR_GUIDE_DATA', $arResult)):?>
	<script type="text/javascript">
		BX.ready(function() {
			BX.Runtime.loadExtension('bizproc.automation.guide')
				.then((exports) => {
					const {CrmCheckAutomationGuide} = exports;
					if (CrmCheckAutomationGuide)
					{
						CrmCheckAutomationGuide.showCheckAutomation(
							'<?= CUtil::JSEscape(CCrmOwnerType::DealName) ?>',
							'<?= CUtil::JSEscape($arResult['CATEGORY_ID'])?>',
							<?= CUtil::PhpToJSObject($arResult['AUTOMATION_CHECK_AUTOMATION_TOUR_GUIDE_DATA']['options']) ?>,
						);
					}
				})
			;
		});
	</script>
<?php endif;
