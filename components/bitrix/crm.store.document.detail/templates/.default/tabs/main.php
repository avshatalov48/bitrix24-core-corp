<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $component \CatalogProductDetailsComponent
 * @var $arResult array
 */

global $APPLICATION;
?>
<div class="catalog-document-card-wrapper">
	<div class="catalog-document-card-left">

	<?php
	$guid = $arResult['GUID'];
	$prefix = mb_strtolower($guid);
	$activityEditorID = "{$prefix}_editor";
	$editorContext = [
		'PARAMS' => $arResult['CONTEXT_PARAMS'],
		'SITE_ID' => $arResult['SITE_ID'],
		'ORDER_ID' => $arResult['ORDER_ID'],
		'PRODUCT_COMPONENT_DATA' => $arResult['PRODUCT_COMPONENT_DATA'],
		'ID' => $arResult['ENTITY_DATA']['ID'],
	];
	$editor = [
		'GUID' => "{$guid}_editor",
		'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
		'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
		'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
		'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
		'ENTITY_DATA' => $arResult['ENTITY_DATA'],
		'ENABLE_SECTION_EDIT' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],
		'ENABLE_SECTION_CREATION' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],

		'ENABLE_FIELDS_CONTEXT_MENU' => !$arResult['IS_READ_ONLY'],
		'ENABLE_PAGE_TITLE_CONTROLS' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],
		'ENABLE_SETTINGS_FOR_ALL' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],
		'ENABLE_SECTION_DRAG_DROP' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],

		'ENABLE_CONFIGURATION_UPDATE' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],
		'ENABLE_COMMON_CONFIGURATION_UPDATE' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],
		'ENABLE_PERSONAL_CONFIGURATION_UPDATE' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],

		'ENABLE_CONFIG_CONTROL' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],
		'ENABLE_CONFIG_SCOPE_TOGGLE' => $arResult['UI_ENTITY_CARD_SETTINGS_EDITABLE'],

		'SERVICE_URL' => '/bitrix/components/bitrix/crm.store.document.detail/ajax.php?'.bitrix_sessid_get(),
		'CONTEXT_ID' => $arResult['CONTEXT_ID'],
		'CONTEXT' => $editorContext,
	];
	$extras = $arResult['EXTRAS'];

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
			'MARK_AS_COMPLETED_ON_VIEW' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		],
		$component,
		['HIDE_ICONS' => 'Y']
	);

	$entityEditorInfo = $APPLICATION->IncludeComponent(
		'bitrix:crm.entity.editor',
		'',
		array_merge(
			$editor,
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::ShipmentDocument,
				'ENTITY_ID' => $arResult['DOCUMENT_ID'],
				'ENTITY_TYPE_TITLE' => \Bitrix\Main\Localization\Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_DOC_TYPE_SHORT_SHIPMENT'),
				'EXTRAS' => $extras,
				'READ_ONLY' => $arResult['ENTITY_DATA']['DEDUCTED'] === 'Y' || $arResult['IS_READ_ONLY'],
				'DETAIL_MANAGER_ID' => $guid,
				'MODULE_ID' => 'crm',
				'MESSAGES' => [],
				'IS_TOOL_PANEL_ALWAYS_VISIBLE' => $arResult['IS_TOOL_PANEL_ALWAYS_VISIBLE'],
			]
		)
	);
	?>
	</div>
	<div class="catalog-document-card-right">
		<?php
			$entityInfo = $arResult['ENTITY_INFO'];
			$APPLICATION->IncludeComponent(
				'bitrix:crm.timeline',
				'',
				[
					'ENTITY_TYPE_ID' => \CCrmOwnerType::ShipmentDocument,
					'ENTITY_ID' => $arResult['DOCUMENT_ID'],
					'ENTITY_INFO' => $entityInfo,
					'EXTRAS' => $arResult['EXTRAS'],
					'ACTIVITY_EDITOR_ID' => $activityEditorID,
					'READ_ONLY' => $arResult['IS_READ_ONLY'],
					'ENTITY_CONFIG_SCOPE' => $entityEditorInfo['ENTITY_CONFIG_SCOPE'],
					'USER_SCOPE_ID' => $entityEditorInfo['USER_SCOPE_ID'],
				],
				$component,
				['HIDE_ICONS' => 'Y']
			);
		?>
	</div>
</div>

<script>
	BX.addCustomEvent('Schedule:onBeforeRefreshLayout', function(event) {
		var plannedBlock = document.querySelector('.crm-entity-stream-section.crm-entity-stream-section-planned');
		if (plannedBlock)
		{
			BX.hide(plannedBlock.parentElement);
		}
	});
</script>
