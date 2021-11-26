<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityProgressBarComponent $component */

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Conversion\EntityConverter;
use Bitrix\Crm\Conversion\LeadConversionType;

\Bitrix\Main\UI\Extension::load(["crm.scoringbutton"]);

//region LEGEND
if(isset($arResult['LEGEND']))
{
	$this->SetViewTarget('crm_details_legend');
	echo htmlspecialcharsbx($arResult['LEGEND']);
	$this->EndViewTarget();
}
//endregion

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";

if (\Bitrix\Crm\Restriction\RestrictionManager::getLeadsRestriction()->hasPermission())
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		[
			'CONTAINER_ID' => '',
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $prefix,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'ENABLE_EMAIL_ADD' => true,
			'ENABLE_TASK_ADD' => $arResult['ENABLE_TASK'],
			'MARK_AS_COMPLETED_ON_VIEW' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		],
		$component,
		['HIDE_ICONS' => 'Y']
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.lead.menu',
		'',
		[
			'PATH_TO_LEAD_LIST' => $arResult['PATH_TO_LEAD_LIST'],
			'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'],
			'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
			'PATH_TO_LEAD_IMPORT' => $arResult['PATH_TO_LEAD_IMPORT'],
			'ELEMENT_ID' => $arResult['ENTITY_ID'],
			'MULTIFIELD_DATA' => isset($arResult['ENTITY_DATA']['MULTIFIELD_DATA'])
				? $arResult['ENTITY_DATA']['MULTIFIELD_DATA'] : [],
			'OWNER_INFO' => $arResult['ENTITY_INFO'],
			'CONVERSION_PERMITTED' => $arResult['CONVERSION_PERMITTED'],
			'BIZPROC_STARTER_DATA' => $arResult['BIZPROC_STARTER_DATA'],
			'TYPE' => 'details',
			'SCRIPTS' => [
				'DELETE' => 'BX.Crm.EntityDetailManager.items["' . CUtil::JSEscape($guid) . '"].processRemoval();',
				'EXCLUDE' => 'BX.Crm.EntityDetailManager.items["' . CUtil::JSEscape($guid) . '"].processExclusion();'
			]
		],
		$component
	);
}
?>
	<script type="text/javascript">
		BX.message({
			"CRM_TIMELINE_HISTORY_STUB": "<?=GetMessageJS('CRM_LEAD_DETAIL_HISTORY_STUB')?>",
		});
		<? if($arResult['ENTITY_ID'] > 0): ?>
			new BX.CrmScoringButton({
				mlInstalled: <?= (\Bitrix\Crm\Ml\Scoring::isMlAvailable() ? 'true' : 'false')?>,
				scoringEnabled: <?= (\Bitrix\Crm\Ml\Scoring::isEnabled() ? 'true' : 'false')?>,
				scoringParameters: <?= \Bitrix\Main\Web\Json::encode($arResult['SCORING'])?>,
				entityType: '<?= CCrmOwnerType::LeadName?>',
				entityId: <?= (int)$arResult['ENTITY_ID']?>,
				isFinal: <?= $arResult['IS_STAGE_FINAL'] ? 'true' : 'false' ?>,
			});
		<? endif; ?>
</script><?

//$arResult['READ_ONLY'] = true;
$editorContext = array('PARAMS' => $arResult['CONTEXT_PARAMS']);
if(isset($arResult['ORIGIN_ID']) && $arResult['ORIGIN_ID'] !== '')
{
	$editorContext['ORIGIN_ID'] = $arResult['ORIGIN_ID'];
}
$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	array(
		'GUID' => $guid,
		'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
		'ENTITY_ID' => $arResult['IS_EDIT_MODE'] ? $arResult['ENTITY_ID'] : 0,
		'ENTITY_INFO' => $arResult['ENTITY_INFO'],
		'READ_ONLY' => $arResult['READ_ONLY'],
		'TABS' => $arResult['TABS'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.details/ajax.php?'.bitrix_sessid_get(),
		'EDITOR' => array(
			'GUID' => "{$guid}_editor",
			'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
			'DUPLICATE_CONTROL' => $arResult['DUPLICATE_CONTROL'],
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
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.details/ajax.php?'.bitrix_sessid_get(),
			'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $arResult['CONTEXT_ID'],
			'CONTEXT' => $editorContext,
			'ATTRIBUTE_CONFIG' => [
				'ENTITY_SCOPE' => $arResult['ENTITY_ATTRIBUTE_SCOPE'],
				'CAPTIONS' => FieldAttributeManager::getCaptionsForEntityWithStages(CCrmOwnerType::Lead),
			],
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
		'ENABLE_PROGRESS_CHANGE' => $arResult['ENABLE_PROGRESS_CHANGE'],
		'PROGRESS_BAR' => array('VERBOSE_MODE' => true),
		'ACTIVITY_EDITOR_ID' => $activityEditorID,
		'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
		'CAN_CONVERT' => isset($arResult['CAN_CONVERT']) ? $arResult['CAN_CONVERT'] : false,
		'CONVERSION_SCHEME' => isset($arResult['CONVERSION_SCHEME']) ? $arResult['CONVERSION_SCHEME'] : null,
		'CONVERSION_TYPE_ID' => isset($arResult['CONVERSION_TYPE_ID'])
			? $arResult['CONVERSION_TYPE_ID'] : LeadConversionType::GENERAL
	)
);

if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIGS'])):
?><script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmLeadConversionType.configs = <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIGS'])?>;
				<?if(isset($arResult['CONVERSION_SCRIPT_DESCRIPTIONS'])):?>
					BX.CrmLeadConversionScheme.messages = <?=CUtil::PhpToJSObject($arResult['CONVERSION_SCRIPT_DESCRIPTIONS'])?>;
				<?endif;?>

				BX.CrmLeadConverter.messages =
				{
					accessDenied: "<?=GetMessageJS("CRM_LEAD_CONV_ACCESS_DENIED")?>",
					generalError: "<?=GetMessageJS("CRM_LEAD_CONV_GENERAL_ERROR")?>",
					dialogTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_TITLE")?>",
					syncEditorLegend: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_LEGEND")?>",
					syncEditorFieldListTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
					syncEditorEntityListTitle: "<?=GetMessageJS("CRM_LEAD_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
					continueButton: "<?=GetMessageJS("CRM_LEAD_DETAIL_CONTINUE_BTN")?>",
					cancelButton: "<?=GetMessageJS("CRM_LEAD_DETAIL_CANCEL_BTN")?>",
					selectButton: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_BTN")?>",
					openEntitySelector: "<?=GetMessageJS("CRM_LEAD_CONV_OPEN_ENTITY_SEL")?>",
					entitySelectorTitle: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_TITLE")?>",
					contact: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_CONTACT")?>",
					company: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_COMPANY")?>",
					noresult: "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_SEARCH_NO_RESULT")?>",
					search : "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_SEARCH")?>",
					last : "<?=GetMessageJS("CRM_LEAD_CONV_ENTITY_SEL_LAST")?>"
				};
				BX.CrmLeadConverter.permissions =
				{
					contact: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_CONTACT'])?>,
					company: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_COMPANY'])?>,
					deal: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_DEAL'])?>
				};
				BX.CrmLeadConverter.settings =
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.lead.show/ajax.php?action=convert&'.bitrix_sessid_get()?>"
				};
				BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
					DealCategory::getJavaScriptInfos(EntityConverter::getPermittedDealCategoryIDs())
				)?>;
				BX.CrmDealCategorySelectDialog.messages =
				{
					title: "<?=GetMessageJS('CRM_LEAD_CONV_DEAL_CATEGORY_DLG_TITLE')?>",
					field: "<?=GetMessageJS('CRM_LEAD_CONV_DEAL_CATEGORY_DLG_FIELD')?>",
					saveButton: "<?=GetMessageJS('CRM_LEAD_DETAIL_BUTTON_SAVE')?>",
					cancelButton: "<?=GetMessageJS('CRM_LEAD_DETAIL_BUTTON_CANCEL')?>"
				};
				BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
				BX.CrmEntityType.setNotFoundMessages(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetNotFoundMessages())?>);
				BX.onCustomEvent(window, "BX.CrmEntityConverter:applyPermissions", [BX.CrmEntityType.names.lead]);

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
