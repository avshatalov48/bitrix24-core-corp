<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmContactDetailsComponent $component */

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";

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
	'bitrix:crm.contact.menu',
	'',
	[
		'PATH_TO_CONTACT_LIST' => $arResult['PATH_TO_CONTACT_LIST'] ?? '',
		'PATH_TO_CONTACT_SHOW' => $arResult['PATH_TO_CONTACT_SHOW'] ?? '',
		'PATH_TO_CONTACT_EDIT' => $arResult['PATH_TO_CONTACT_EDIT'] ?? '',
		'PATH_TO_CONTACT_IMPORT' => $arResult['PATH_TO_CONTACT_IMPORT'] ?? '',
		'ELEMENT_ID' => $arResult['ENTITY_ID'],
		'MULTIFIELD_DATA' => $arResult['ENTITY_DATA']['MULTIFIELD_DATA'] ?? [],
		'OWNER_INFO' => $arResult['ENTITY_INFO'],
		'BIZPROC_STARTER_DATA' => $arResult['BIZPROC_STARTER_DATA'] ?? [],
		'TYPE' => 'details',
		'SCRIPTS' => [
			'DELETE' => 'BX.Crm.EntityDetailManager.items["' . CUtil::JSEscape($guid) . '"].processRemoval();',
		]
	],
	$component
);

?><script type="text/javascript">
		BX.ready(
			function()
			{
				BX.message({ "CRM_TIMELINE_HISTORY_STUB": "<?=GetMessageJS('CRM_CONTACT_DETAIL_HISTORY_STUB')?>" });
			}
		);
</script><?php
$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	[
		'GUID' => $guid,
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
		'ENTITY_ID' => $arResult['IS_EDIT_MODE'] ? $arResult['ENTITY_ID'] : 0,
		'ENTITY_INFO' => $arResult['ENTITY_INFO'],
		'READ_ONLY' => $arResult['READ_ONLY'],
		'TABS' => $arResult['TABS'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.details/ajax.php?' . bitrix_sessid_get(),
		'EDITOR' => $component->getEditorConfig(),
		'TIMELINE' => ['GUID' => "{$guid}_timeline"],
		'ACTIVITY_EDITOR_ID' => $activityEditorID,
		'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
		'ENABLE_PROGRESS_BAR' => false,
		'EXTRAS' => ['CATEGORY_ID' => $arResult['CATEGORY_ID']],
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

echo \CCrmComponentHelper::prepareInitReceiverRepositoryJS(\CCrmOwnerType::Contact, (int)($arResult['ENTITY_ID'] ?? 0));
