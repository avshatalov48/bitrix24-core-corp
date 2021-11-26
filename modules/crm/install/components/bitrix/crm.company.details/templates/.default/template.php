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
$isMyCompany = $arResult['IS_MY_COMPANY'];
$isClientCompany = !$isMyCompany;

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
	'bitrix:crm.company.menu',
	'',
	array(
		'PATH_TO_COMPANY_LIST' => $arResult['PATH_TO_COMPANY_LIST'],
		'PATH_TO_COMPANY_SHOW' => $arResult['PATH_TO_COMPANY_SHOW'],
		'PATH_TO_COMPANY_EDIT' => $arResult['PATH_TO_COMPANY_EDIT'],
		'PATH_TO_COMPANY_IMPORT' => $arResult['PATH_TO_COMPANY_IMPORT'],
		'ELEMENT_ID' => $arResult['ENTITY_ID'],
		'MYCOMPANY_MODE' => $isMyCompany ? 'Y' : 'N',
		'MULTIFIELD_DATA' => isset($arResult['ENTITY_DATA']['MULTIFIELD_DATA'])
			? $arResult['ENTITY_DATA']['MULTIFIELD_DATA'] : array(),
		'OWNER_INFO' => $arResult['ENTITY_INFO'],
		'BIZPROC_STARTER_DATA' => $arResult['BIZPROC_STARTER_DATA'],
		'TYPE' => 'details',
		'SCRIPTS' => array(
			'DELETE' => 'BX.Crm.EntityDetailManager.items["'.CUtil::JSEscape($guid).'"].processRemoval();'
		)
	),
	$component
);

?><script type="text/javascript">
		BX.ready(
			function()
			{
				BX.message({ "CRM_TIMELINE_HISTORY_STUB": "<?=GetMessageJS('CRM_COMPANY_DETAIL_HISTORY_STUB')?>" });
			}
		);
</script><?

$editorContext = array('PARAMS' => $arResult['CONTEXT_PARAMS']);
if(isset($arResult['ORIGIN_ID']) && $arResult['ORIGIN_ID'] !== '')
{
	$editorContext['ORIGIN_ID'] = $arResult['ORIGIN_ID'];
}
$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	[
		'GUID' => $guid,
		'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
		'ENTITY_ID' => $arResult['IS_EDIT_MODE'] ? $arResult['ENTITY_ID'] : 0,
		'ENTITY_INFO' => $arResult['ENTITY_INFO'],
		'READ_ONLY' => $arResult['READ_ONLY'],
		'TABS' => $arResult['TABS'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.details/ajax.php?'.bitrix_sessid_get(),
		'EDITOR' => [
			'GUID' => "{$guid}_editor",
			'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
			'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
			'DUPLICATE_CONTROL' => $arResult['DUPLICATE_CONTROL'],
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
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.details/ajax.php?'.bitrix_sessid_get(),
			'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $arResult['CONTEXT_ID'],
			'CONTEXT' => $editorContext,
			'ATTRIBUTE_CONFIG' => [
				'ENTITY_SCOPE' => $arResult['ENTITY_ATTRIBUTE_SCOPE'],
				'CAPTIONS' => FieldAttributeManager::getCaptionsForEntityWithStages(CCrmOwnerType::Company),
			],
			'COMPONENT_AJAX_DATA' => [
				'RELOAD_ACTION_NAME' => 'LOAD',
				'RELOAD_FORM_DATA' => [
					'ACTION_ENTITY_ID' => $arResult['ENTITY_ID']
				] + $editorContext
			]
		],
		'TIMELINE' => [
			'GUID' => "{$guid}_timeline",
			'ENABLE_WAIT' => false,
			'ENABLE_CALL' => $isClientCompany,
			'ENABLE_EMAIL' => $isClientCompany,
			'ENABLE_MEETING' => $isClientCompany,
			'ENABLE_TASK' => $isClientCompany,
			'ENABLE_SMS' => $isClientCompany
		],
		'ACTIVITY_EDITOR_ID' => $activityEditorID,
		'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
		'ENABLE_PROGRESS_BAR' => false
	]
);

if($arResult['ENTITY_ID'] <= 0 && !empty($arResult['FIELDS_SET_DEFAULT_VALUE']))
{?>
	<script type="text/javascript">
		BX.ready(function () {
			var fieldsSetDefaultValue = <?= CUtil::PhpToJSObject($arResult['FIELDS_SET_DEFAULT_VALUE']) ?>;
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
							field._model._data[field.getDataKey()] = field.getSchemeElement().getData().defaultValue;
							field.refreshLayout();
						}
					}
				}
			});
		});
	</script><?php
}