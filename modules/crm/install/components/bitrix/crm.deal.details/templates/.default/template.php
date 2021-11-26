<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Attribute\FieldAttributeManager;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityProgressBarComponent $component */

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";

\Bitrix\Main\UI\Extension::load(["crm.scoringbutton"]);

//region LEGEND
if(isset($arResult['LEGEND']))
{
	$this->SetViewTarget('crm_details_legend');
	?><a href="#" onclick="BX.Crm.DealCategoryChanger.processEntity(<?=$arResult['ENTITY_ID']?>,{ usePopupMenu: true, anchor: this }); return false;">
		<?=htmlspecialcharsbx($arResult['LEGEND'])?>
	</a><?
	$this->EndViewTarget();
}
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
		'PATH_TO_DEAL_LIST' => $arResult['PATH_TO_DEAL_LIST'],
		'PATH_TO_DEAL_SHOW' => $arResult['PATH_TO_DEAL_SHOW'],
		'PATH_TO_DEAL_EDIT' => $arResult['PATH_TO_DEAL_EDIT'],
		'PATH_TO_DEAL_FUNNEL' => $arResult['PATH_TO_DEAL_FUNNEL'],
		'PATH_TO_DEAL_IMPORT' => $arResult['PATH_TO_DEAL_IMPORT'],
		'ELEMENT_ID' => $arResult['ENTITY_ID'],
		'CATEGORY_ID' => $arResult['CATEGORY_ID'],
		'MULTIFIELD_DATA' => isset($arResult['ENTITY_DATA']['MULTIFIELD_DATA'])
			? $arResult['ENTITY_DATA']['MULTIFIELD_DATA'] : array(),
		'OWNER_INFO' => $arResult['ENTITY_INFO'],
		'CONVERSION_PERMITTED' => $arResult['CONVERSION_PERMITTED'],
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

$editorContext = array('PARAMS' => $arResult['CONTEXT_PARAMS']);
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

if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):
?><script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmDealConversionScheme.messages =
					<?=CUtil::PhpToJSObject(\Bitrix\Crm\Conversion\DealConversionScheme::getJavaScriptDescriptions(false))?>;

				BX.CrmDealConverter.messages =
				{
					accessDenied: "<?=GetMessageJS("CRM_DEAL_CONV_ACCESS_DENIED")?>",
					generalError: "<?=GetMessageJS("CRM_DEAL_CONV_GENERAL_ERROR")?>",
					dialogTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_TITLE")?>",
					syncEditorLegend: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_LEGEND")?>",
					syncEditorFieldListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
					syncEditorEntityListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
					continueButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CONTINUE_BTN")?>",
					cancelButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CANCEL_BTN")?>"
				};
				BX.CrmDealConverter.permissions =
				{
					invoice: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_INVOICE'])?>,
					quote: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_QUOTE'])?>
				};
				BX.CrmDealConverter.settings =
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.details/ajax.php?action=convert&'.bitrix_sessid_get()?>",
					config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
				};
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
