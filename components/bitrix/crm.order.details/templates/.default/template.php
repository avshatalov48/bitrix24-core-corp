<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\EntityDetails\ComponentMode;

CJSCore::Init(['ui']);

if (\Bitrix\Main\Loader::includeModule('sale'))
{
	Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/sale/core_ui_widget.js');
	Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/sale/core_ui_etc.js');
	Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/sale/core_ui_autocomplete.js');
	Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/sale.location.selector.search/templates/.default/script.js');
	Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/sale.location.selector.search/templates/.default/style.css');
}

Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/crm.order.product.list/templates/.default/style.css');

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmQuoteDetailsComponent $component */

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";

echo CCrmViewHelper::RenderOrderShipmentStatusSettings();

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
	'bitrix:crm.order.menu',
	'',
	array(
		'PATH_TO_ORDER_LIST' => $arResult['PATH_TO_ORDER_LIST'] ?? '',
		'PATH_TO_ORDER_FUNNEL' => $arResult['PATH_TO_ORDER_FUNNEL'] ?? '',
		'PATH_TO_ORDER_IMPORT' => $arResult['PATH_TO_ORDER_IMPORT'] ?? '',
		'ELEMENT_ID' => $arResult['ENTITY_ID'],
		'CATEGORY_ID' => $arResult['CATEGORY_ID'] ?? null,
		'MULTIFIELD_DATA' => $arResult['ENTITY_DATA']['MULTIFIELD_DATA'] ?? [],
		'OWNER_INFO' => $arResult['ENTITY_INFO'],
		'CONVERSION_PERMITTED' => $arResult['CONVERSION_PERMITTED'] ?? true,
		'BIZPROC_STARTER_DATA' => $arResult['BIZPROC_STARTER_DATA'] ?? [],
		'CANCELED' => $arResult['ENTITY_DATA']['CANCELED'],
		'TYPE' => 'details',
		'SCRIPTS' => [
			'DELETE' => 'BX.Crm.EntityDetailManager.items["'.CUtil::JSEscape($guid).'"].processRemoval();',
		]
	),
	$component
);

?><script>
		BX.ready(
			function()
			{
				BX.message({ "CRM_TIMELINE_HISTORY_STUB": "<?=GetMessageJS('CRM_ORDER_DETAIL_HISTORY_STUB')?>" });
				BX.Crm.OrderDetailManager.messages = {};
			}
		);
</script><?

$editorContext = array(
	'PARAMS' => $arResult['CONTEXT_PARAMS'],
	'SITE_ID' => $arResult['SITE_ID'],
	'PRODUCT_COMPONENT_DATA' => $arResult['PRODUCT_COMPONENT_DATA']
);

if (isset($arResult['ORIGIN_ID']) && $arResult['ORIGIN_ID'] !== '')
{
	$editorContext['ORIGIN_ID'] = $arResult['ORIGIN_ID'];
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details',
	'',
	array(
		'GUID' => $guid,
		'ENTITY_TYPE_ID' => \CCrmOwnerType::Order,
		'ENTITY_ID' => ($arResult['COMPONENT_MODE'] === ComponentMode::MODIFICATION || $arResult['COMPONENT_MODE'] === ComponentMode::VIEW) ? $arResult['ENTITY_ID'] : 0,
		'ENTITY_INFO' => $arResult['ENTITY_INFO'],
		'READ_ONLY' => $arResult['COMPONENT_MODE'] === ComponentMode::VIEW,
		'TABS' => $arResult['TABS'],
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.order.details/ajax.php?action=convert&'.bitrix_sessid_get(),
		'REST_USE' => 'Y',
		'EDITOR' => array(
			'GUID' => "{$guid}_editor",
			'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
			'DUPLICATE_CONTROL' => $arResult['DUPLICATE_CONTROL'] ?? [],
			'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
			'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
			'ENTITY_DATA' => $arResult['ENTITY_DATA'],
			'ENABLE_SECTION_EDIT' => true,
			'ENABLE_SECTION_CREATION' => true,
			'ENABLE_USER_FIELD_CREATION' => $arResult['ENABLE_USER_FIELD_CREATION'],
			'USER_FIELD_ENTITY_ID' => $arResult['USER_FIELD_ENTITY_ID'],
			'USER_FIELD_CREATE_PAGE_URL' => $arResult['USER_FIELD_CREATE_PAGE_URL'],
			'USER_FIELD_CREATE_SIGNATURE' => $arResult['USER_FIELD_CREATE_SIGNATURE'],
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.order.details/ajax.php?'.bitrix_sessid_get(),
			'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $arResult['CONTEXT_ID'],
			'CONTEXT' => $editorContext,
			'ENABLE_PAGE_TITLE_CONTROLS' => $arResult['COMPONENT_MODE'] === ComponentMode::MODIFICATION,
		),
		'TIMELINE' => array(
			'GUID' => "{$guid}_timeline",
			'WAIT_TARGET_DATES' => $arResult['WAIT_TARGET_DATES'],
			'ENABLE_SALESCENTER' => false,
		),
		'ENABLE_PROGRESS_BAR' => true,
		'ENABLE_PROGRESS_CHANGE' => $arResult['ENABLE_PROGRESS_CHANGE'],
		'ACTIVITY_EDITOR_ID' => $activityEditorID,
		'PATH_TO_USER_PROFILE' => $arResult['PATH_TO_USER_PROFILE'],
		//'CAN_CONVERT' => isset($arResult['CAN_CONVERT']) ? $arResult['CAN_CONVERT'] : false,
		//'CONVERSION_SCHEME' => isset($arResult['CONVERSION_SCHEME']) ? $arResult['CONVERSION_SCHEME'] : null
	)
);

$arOrderStatusInfoValues[$arResult['ENTITY_ID']] = [
	'REASON_CANCELED' => empty($arResult['ENTITY_DATA']['REASON_CANCELED'])
		? ''
		: $arResult['ENTITY_DATA']['REASON_CANCELED']
];

if (!empty($arResult['IS_AJAX_CALL']))
{
	$GLOBALS['OnCrmCrmOrderListAfterAjaxHandlerParams']['arOrderStatusInfoValues'] = $arOrderStatusInfoValues;

	function OnCrmCrmOrderListAfterAjaxHandler()
	{
		?>
		<script>
			BX.ready(function(){
				if (typeof(BX.CrmOrderStatusManager) === 'function')
				{
					BX.CrmOrderStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($GLOBALS['OnCrmCrmOrderListAfterAjaxHandlerParams']['arOrderStatusInfoValues']) ?>;
				}
			});
		</script><?

		return '';
	}
	AddEventHandler('main', 'OnAfterAjaxResponse', 'OnCrmCrmOrderListAfterAjaxHandler');
}
else
{
	?>
	<script>
		BX.ready(function ()
		{
			if (typeof(BX.CrmOrderStatusManager) === 'function')
			{
				BX.CrmOrderStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($arOrderStatusInfoValues) ?>;
			}
		});
	</script>
	<?php if (array_key_exists('AUTOMATION_CHECK_AUTOMATION_TOUR_GUIDE_DATA', $arResult)):?>
		<script>
			BX.ready(function() {
				BX.Runtime.loadExtension('bizproc.automation.guide')
					.then((exports) => {
						const {CrmCheckAutomationGuide} = exports;
						if (CrmCheckAutomationGuide)
						{
							CrmCheckAutomationGuide.showCheckAutomation(
								'<?= CUtil::JSEscape(CCrmOwnerType::OrderName) ?>',
								'<?= CUtil::JSEscape($arResult['CATEGORY_ID'])?>',
								<?= CUtil::PhpToJSObject($arResult['AUTOMATION_CHECK_AUTOMATION_TOUR_GUIDE_DATA']['options']) ?>,
							);
						}
					})
				;
			});
		</script>
	<?php endif;
}
