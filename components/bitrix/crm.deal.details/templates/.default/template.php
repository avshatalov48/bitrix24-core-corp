<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmDealDetailsComponent $component */

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";
$isRecurring = isset($arResult['ENTITY_DATA']['IS_RECURRING']) && $arResult['ENTITY_DATA']['IS_RECURRING'] === 'Y';

\Bitrix\Main\UI\Extension::load([
	'crm.scoringbutton',
	'crm.conversion',
	'ui.tour',
]);

Asset::getInstance()->addJs('/bitrix/js/crm/category.js');

//region LEGEND
if (!empty($arResult['LEGEND']))
{
	$this->SetViewTarget('crm_details_legend');
	$isConversion = isset($arResult['CONTEXT']['PARAMS']['CONVERSION_SOURCE']);
	if ($arResult['ENTITY_ID'] <= 0 && !$isConversion)
	{
		$moveToCategoryIDs = array_values(
			array_diff(
				\CCrmDeal::GetPermittedToMoveCategoryIDs(),
				[$arResult['CATEGORY_ID']]
			)
		);
		?><script>
			// beautify element
			const categorySelectorElement = document.getElementById('pagetitle_sub');
			BX.Dom.style(categorySelectorElement, {
				position: 'relative',
				padding: '10px',
				'z-index': 1000,
				'background-size': 'contain',
			});
			BX.Crm.DealCategoryChanger.create('deal_category_change_new', {
				entityId: 0,
				categoryIds: <?=\Bitrix\Main\Web\Json::encode($moveToCategoryIDs)?>,
			});

			BX.Crm.DealCategoryChanger.messages = {
				changeFunnelConfirmDialogTitle: "<?=GetMessageJS('CRM_DEAL_DETAIL_CHANGE_FUNNEL_CONFIRM_DIALOG_TITLE')?>",
				changeFunnelConfirmDialogMessage: "<?=GetMessageJS('CRM_DEAL_DETAIL_CHANGE_FUNNEL_CONFIRM_DIALOG_MESSAGE')?>",
				changeFunnelConfirmDialogOkBtn: "<?=GetMessageJS('CRM_DEAL_DETAIL_CHANGE_FUNNEL_CONFIRM_DIALOG_OK_BTN')?>",
			};
		</script><?php
	}
	?>
		<a href="#" onclick="BX.Crm.DealCategoryChanger.processEntity(<?=$arResult['ENTITY_ID']?>,{ usePopupMenu: true, anchor: this }); return false;">
			<?=htmlspecialcharsbx($arResult['LEGEND'])?>
		</a><?php
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
		'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'] ?? '',
		'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'] ?? '',
		'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'] ?? '',
		'PATH_TO_DEAL_FUNNEL' => $arResult['PATH_TO_DEAL_FUNNEL'] ?? '',
		'PATH_TO_DEAL_IMPORT' => $arResult['PATH_TO_DEAL_IMPORT'] ?? '',
		'ELEMENT_ID' => $arResult['ENTITY_ID'],
		'CATEGORY_ID' => $arResult['CATEGORY_ID'],
		'MULTIFIELD_DATA' => isset($arResult['ENTITY_DATA']['MULTIFIELD_DATA'])
			? $arResult['ENTITY_DATA']['MULTIFIELD_DATA']
			: [],
		'OWNER_INFO' => $arResult['ENTITY_INFO'] ?? [],
		'CONVERSION_PERMITTED' => $arResult['CONVERSION_PERMITTED'],
		'CONVERSION_CONTAINER_ID' => $arResult['CONVERSION_CONTAINER_ID'],
		'CONVERSION_LABEL_ID' => $arResult['CONVERSION_LABEL_ID'],
		'CONVERSION_BUTTON_ID' => $arResult['CONVERSION_BUTTON_ID'],
		'IS_RECURRING' => $isRecurring ? 'Y' : 'N' ,
		'TYPE' => 'details',
		'SCRIPTS' => array(
			'DELETE' => 'BX.Crm.EntityDetailManager.items["'.CUtil::JSEscape($guid).'"].processRemoval();',
			'EXCLUDE' => 'BX.Crm.EntityDetailManager.items["'.CUtil::JSEscape($guid).'"].processExclusion();'
		),
		'ANALYTICS' => [
			'c_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_DEAL,
			'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
		],
	),
	$component
);

$isMlAvailable = \Bitrix\Crm\Ml\Scoring::isMlAvailable();
$isScoringEnabled = \Bitrix\Crm\Ml\Scoring::isEnabled();
$isScoringAvailable = \Bitrix\Crm\Ml\Scoring::isScoringAvailable();
$isTrainingUsed = \Bitrix\Crm\Ml\Scoring::isTrainingUsed();
if ($isMlAvailable && $isScoringEnabled && $isScoringAvailable && $isTrainingUsed)
{
	echo \Bitrix\Crm\Tour\Ml\ScoringShutdownWarning::getInstance()->build();
}

if ($isScoringAvailable):
?>
	<script>
		<? if($arResult['ENTITY_ID'] > 0): ?>
			new BX.CrmScoringButton({
				mlInstalled: <?= ($isMlAvailable ? 'true' : 'false')?>,
				scoringEnabled: <?= ($isScoringEnabled ? 'true' : 'false')?>,
				scoringParameters: <?= \Bitrix\Main\Web\Json::encode($arResult['SCORING']) ?>,
				entityType: '<?= CCrmOwnerType::DealName ?>',
				entityId: <?= (int)$arResult['ENTITY_ID']?>,
				isFinal: <?= $arResult['IS_STAGE_FINAL'] ? 'true' : 'false' ?>,
			});
		<? endif; ?>
	</script><?
endif;
?>
<script>
	BX.ready(() => {
		BX.message({
			'CRM_TIMELINE_HISTORY_STUB': '<?=GetMessageJS('CRM_DEAL_DETAIL_HISTORY_STUB')?>'
		});
	});
</script>
<?php
$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	[
		'GUID' => $guid,
		'ENTITY_TYPE_ID' => $isRecurring ? \CCrmOwnerType::DealRecurring : \CCrmOwnerType::Deal,
		'ENTITY_ID' => $arResult['IS_EDIT_MODE'] ? $arResult['ENTITY_ID'] : 0,
		'ENTITY_INFO' => $arResult['ENTITY_INFO'],
		'READ_ONLY' => $arResult['READ_ONLY'],
		'TABS' => $arResult['TABS'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.details/ajax.php?' . bitrix_sessid_get(),
		'EDITOR' => $component->getEditorConfig(),
		'TIMELINE' => [
			'GUID' => "{$guid}_timeline",
			'PROGRESS_SEMANTICS' => $arResult['PROGRESS_SEMANTICS'],
			'WAIT_TARGET_DATES' => $arResult['WAIT_TARGET_DATES']
		],
		'ENABLE_PROGRESS_BAR' => true,
		'ENABLE_PROGRESS_CHANGE' => (!$isRecurring && !$arResult['READ_ONLY']),
		'ACTIVITY_EDITOR_ID' => $activityEditorID,
		'EXTRAS' => [
			'CATEGORY_ID' => $arResult['CATEGORY_ID'],
			'ANALYTICS' => $arParams['EXTRAS']['ANALYTICS'] ?? [],
		],
		'ANALYTIC_PARAMS' => ['deal_category' => $arResult['CATEGORY_ID']],
		'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'] ?? '',
		'BIZPROC_STARTER_DATA' => $arResult['BIZPROC_STARTER_DATA'] ?? [],
	]
);

/** @var \Bitrix\Crm\Conversion\EntityConversionConfig|null $conversionConfig */
$conversionConfig = $arResult['CONVERSION_CONFIG'] ?? null;

if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && $conversionConfig):
?><script>
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
							},
							analytics: {
								c_section: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_DEAL ?>',
								c_sub_section: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS ?>',
							},
						}
					}
				);

				var schemeSelector = new BX.Crm.Conversion.SchemeSelector(
					converter,
					{
						entityId: <?= (int)$arResult['ENTITY_ID'] ?>,
						containerId: '<?= CUtil::JSEscape($arResult['CONVERSION_CONTAINER_ID']) ?>',
						labelId: '<?= CUtil::JSEscape($arResult['CONVERSION_LABEL_ID']) ?>',
						buttonId: '<?= CUtil::JSEscape($arResult['CONVERSION_BUTTON_ID']) ?>',
						analytics: {
							c_element: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_CONVERT_BUTTON ?>',
						},
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

					converter.setAnalyticsElement('<?= \Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_CREATE_LINKED_ENTITY_BUTTON ?>');

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

<script>
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

<script>
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
	<script>
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

echo \CCrmComponentHelper::prepareInitReceiverRepositoryJS(\CCrmOwnerType::Deal, (int)($arResult['ENTITY_ID'] ?? 0));

include 'mango_popup.php'; // temporary notification. Will be removed soon
