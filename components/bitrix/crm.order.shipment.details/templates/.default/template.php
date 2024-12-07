<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\EntityDetails\ComponentMode;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;

CJSCore::Init(array('crm_entity_editor'));

if (Loader::includeModule('sale'))
{
	Extension::load('sale.address');
}

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmQuoteDetailsComponent $component */

$guid = $arResult['GUID'];
$jsGuid = CUtil::JSEscape($guid);
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";
$statusViewTemplate = '';

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
		'MARK_AS_COMPLETED_ON_VIEW' => false,
		'SKIP_VISUAL_COMPONENTS' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.order.shipment.menu',
	'',
	array(
		'PATH_TO_ORDER_SHIPMENT_LIST' => $arResult['PATH_TO_ORDER_SHIPMENT_LIST'] ?? '',
		'PATH_TO_ORDER_SHIPMENT_SHOW' => $arResult['PATH_TO_ORDER_SHIPMENT_SHOW'] ?? '',
		'PATH_TO_ORDER_SHIPMENT_EDIT' => $arResult['PATH_TO_ORDER_SHIPMENT_EDIT'] ?? '',
		'ELEMENT_ID' => $arResult['ENTITY_ID'],
		'MULTIFIELD_DATA' => $arResult['ENTITY_DATA']['MULTIFIELD_DATA'] ?? [],
		'OWNER_INFO' => $arResult['ENTITY_INFO'] ?? [],
		'TYPE' => 'details',
		'SCRIPTS' => array(
			'DELETE' => 'BX.Crm.EntityDetailManager.items["'.$jsGuid.'"].processRemoval();'
		)
	),
	$component
);

?><script>
		BX.ready(
			function()
			{
				BX.message({ "CRM_TIMELINE_HISTORY_STUB": "<?=GetMessageJS('CRM_ORDER_SHIPMENT_DETAIL_HISTORY_STUB')?>" });
			}
		);
</script><?

$editorContext = array(
	'PARAMS' => $arResult['CONTEXT_PARAMS'],
	'SITE_ID' => $arResult['SITE_ID'],
	'ORDER_ID' => $arResult['ORDER_ID'],
	'PRODUCT_COMPONENT_DATA' => $arResult['PRODUCT_COMPONENT_DATA']
);

if (!empty($arResult['ORIGIN_ID']))
{
	$editorContext['ORIGIN_ID'] = $arResult['ORIGIN_ID'];
}

if (!empty($arResult['ENTITY_DATA']['TRACKING_NUMBER']))
{
	$trackingNumber  = CUtil::JSEscape($arResult['ENTITY_DATA']['TRACKING_NUMBER']);
	$onClick = "crmOrderShipment_{$jsGuid}.trackingStatusUpdate({$arResult['ENTITY_DATA']['ID']}, \"{$trackingNumber}\"); return false;";

	$statusViewTemplate =
		'<span id="crm-order-shipment-tracking-status-name">#STATUS_NAME#</span> '
		."<button class='ui-btn ui-btn-xs ui-btn-primary ui-btn-icon-business' onclick='{$onClick}'>"
			.\Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_SHIPMENT_TRACKING_STATUS_UPDATE')
		."</button>";

	$arResult['ENTITY_DATA']['TRACKING_STATUS_VIEW'] = str_replace(
		'#STATUS_NAME#',
		$arResult['ENTITY_DATA']['TRACKING_STATUS_DATA']['TRACKING_STATUS_NAME'],
		$statusViewTemplate
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	array(
		'GUID' => $guid,
		'ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
		'ENTITY_ID' => ($arResult['COMPONENT_MODE'] === ComponentMode::MODIFICATION || $arResult['COMPONENT_MODE'] === ComponentMode::VIEW)? $arResult['ENTITY_ID'] : 0,
		'ENTITY_INFO' => $arResult['ENTITY_INFO'],
		'READ_ONLY' => $arResult['COMPONENT_MODE'] === ComponentMode::VIEW,
		'TABS' => $arResult['TABS'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.order.shipment.details/ajax.php?action=convert&'.bitrix_sessid_get(),
		'REST_USE' => 'N',
		'EDITOR' => array(
			'GUID' => "{$guid}_editor",
			'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'] ?? null,
			'DUPLICATE_CONTROL' => $arResult['DUPLICATE_CONTROL'] ?? null,
			'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
			'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
			'ENTITY_DATA' => $arResult['ENTITY_DATA'],
			'ENABLE_SECTION_EDIT' => true,
			'ENABLE_SECTION_CREATION' => true,
			'ENABLE_USER_FIELD_CREATION' => $arResult['ENABLE_USER_FIELD_CREATION'] ?? null,
			'USER_FIELD_ENTITY_ID' => $arResult['USER_FIELD_ENTITY_ID'] ?? null,
			'USER_FIELD_CREATE_PAGE_URL' => $arResult['USER_FIELD_CREATE_PAGE_URL'] ?? null,
			'USER_FIELD_CREATE_SIGNATURE' => $arResult['USER_FIELD_CREATE_SIGNATURE'] ?? null,
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.order.shipment.details/ajax.php?' . bitrix_sessid_get(),
			'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $arResult['CONTEXT_ID'],
			'CONTEXT' => $editorContext
		),
		'TIMELINE' => array(
			'ENTITY_INFO' => array(
				'ENTITY_ID' => $arResult['ENTITY_DATA']['ORDER_ID'],
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
				'ENTITY_TYPE_NAME' => CCrmOwnerType::OrderName,
				'TITLE' => GetMessage('CRM_ORDER_TITLE', array('#ID#' => $arResult['ENTITY_DATA']['ORDER_ID'])),
				'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Order, $arResult['ENTITY_DATA']['ORDER_ID'], false),
			),
			'ENTITY_ID' => $arResult['ENTITY_DATA']['ORDER_ID'],
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
			'GUID' => "{$guid}_timeline",
			'WAIT_TARGET_DATES' => $arResult['WAIT_TARGET_DATES'],
			'ENABLE_SALESCENTER' => false,
		),
		'ENABLE_PROGRESS_BAR' => true,
		'ENABLE_PROGRESS_CHANGE' => $arResult['ENABLE_PROGRESS_CHANGE'],
		'ACTIVITY_EDITOR_ID' => $activityEditorID,
		'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
	)
);
?>
<script>
	var crmOrderShipment_<?=$jsGuid?>;

	BX.ready(
		() =>
		{
			crmOrderShipment_<?=$jsGuid?> = BX.Crm.OrderShipment.create(
				'<?=$jsGuid?>',
				{
					trackingStatusNameNodeId: 'crm-order-shipment-tracking-status-name',
					editorId: '<?=CUtil::JSEscape($activityEditorID)?>',
					statusViewTemplate: '<?=CUtil::JSEscape($statusViewTemplate )?>'
				}
			);
		}
	);
</script>
