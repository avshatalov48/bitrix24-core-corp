<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/**
 * Bitrix vars
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');
?><div id="rebuildMessageWrapper"><?

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'])
{
	?><div id="rebuildOrderSearchWrapper"></div><?
}
if($arResult['NEED_FOR_BUILD_TIMELINE'])
{
	?><div id="buildOrderTimelineWrapper"></div><?
}
if($arResult['NEED_FOR_REFRESH_ACCOUNTING'])
{
	?><div id="refreshOrderAccountingWrapper"></div><?
}
if($arResult['NEED_FOR_REBUILD_ORDER_SHIPMENT_ATTRS'])
{
	?><div id="rebuildOrderAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_ORDER_SHIPMENT_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildOrderAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
}
?></div><?
echo CCrmViewHelper::RenderOrderShipmentStatusSettings();
$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'];
$allowDelete = $arResult['PERMS']['DELETE'];
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if(!$isInternal)
{
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => CCrmOwnerType::OrderShipmentName,
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
}

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => CCrmOwnerType::OrderShipmentName,
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array()
);
echo CCrmViewHelper::RenderOrderStatusSettings();
$prefix = $arResult['GRID_ID'];
$prefixLC = mb_strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$now = time() + CTimeZone::GetOffset();
$arOrderShipmentStatusInfoValues = array();

foreach($arResult['ORDER_SHIPMENT'] as $sKey => $arOrderShipment)
{
	$jsShowUrl = isset($arOrderShipment['PATH_TO_ORDER_SHIPMENT_SHOW']) ? CUtil::JSEscape($arOrderShipment['PATH_TO_ORDER_SHIPMENT_SHOW']) : '';

	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_ORDER_SHIPMENT_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_ORDER_SHIPMENT_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arOrderShipment['PATH_TO_ORDER_SHIPMENT_DETAILS'])."')",
		'DEFAULT' => true
	);

	if($arOrderShipment['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_ORDER_SHIPMENT_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_SHIPMENT_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arOrderShipment['PATH_TO_ORDER_SHIPMENT_EDIT'])."')"
		);
	}

	if($arOrderShipment['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arOrderShipment['PATH_TO_ORDER_SHIPMENT_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_ORDER_SHIPMENT_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_SHIPMENT_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}',
				BX.CrmUIGridMenuCommand.remove,
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	$arActions[] = array('SEPARATOR' => true);

	$eventParam = array(
		'ID' => $arOrderShipment['ID'],
		'GRID_ID' => $arResult['GRID_ID']
	);

	foreach(GetModuleEvents('crm', 'onCrmOrderShipmentListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_ORDER_SHIPMENT_LIST_MENU', $eventParam, &$arActions));
	}

	$deliveryService = '';
	$deliveryServiceName = $arOrderShipment['DELIVERY_SERVICE_NAME'] <> '' ? $arOrderShipment['DELIVERY_SERVICE_NAME'] : $arOrderShipment['DELIVERY_NAME'];
	$deliveryServiceName = htmlspecialcharsbx($deliveryServiceName);

	if($arOrderShipment['DELIVERY_SERVICE_LOGOTIP'] <> '')
	{
		$deliveryService .= '<div><img height="50" src="'.$arOrderShipment['DELIVERY_SERVICE_LOGOTIP'].'"></div>';
	}

	if($deliveryServiceName <> '')
	{
		$deliveryService .= '<div>'.$deliveryServiceName.'</div>';
	}

	$resultItem = array(
		'id' => $arOrderShipment['ID'],
		'actions' => $arActions,
		'data' => $arOrderShipment,
		'editable' => !$arOrderShipment['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'SHIPMENT_SUMMARY' => CCrmViewHelper::RenderInfo(
				$arOrderShipment['PATH_TO_ORDER_SHIPMENT_DETAILS'],
				\Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_SHIPMENT_SUMMARY',array(
					'#NUMBER#' => htmlspecialcharsbx($arOrderShipment['ACCOUNT_NUMBER']),
					'#DATE#' => $arOrderShipment['DATE_INSERT']
				)),
				'', // type
				array('TARGET' => '_self')
			),
			'STATUS_ID' => CCrmViewHelper::RenderOrderShipmentStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arOrderShipment['ID'],
					'CURRENT_ID' => $arOrderShipment['STATUS_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.order.shipment.list/list.ajax.php',
					'READ_ONLY' => !(isset($arOrderShipment['EDIT']) && $arOrderShipment['EDIT'] === true)
				)
			),
			'RESPONSIBLE' => $arOrderShipment['RESPONSIBLE_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrderShipment['ID']}_RESPONSIBLE",
						'USER_ID' => $arOrderShipment['RESPONSIBLE_ID'],
						'USER_NAME'=> $arOrderShipment['RESPONSIBLE'],
						'USER_PROFILE_URL' => $arOrderShipment['PATH_TO_USER_PROFILE']
					)
				): '',
			'EMP_ALLOW_DELIVERY' => $arOrderShipment['EMP_ALLOW_DELIVERY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrderShipment['ID']}_EMP_ALLOW_DELIVERY",
						'USER_ID' => $arOrderShipment['EMP_ALLOW_DELIVERY_ID'],
						'USER_NAME'=> $arOrderShipment['EMP_ALLOW_DELIVERY'],
						'USER_PROFILE_URL' => $arOrderShipment['PATH_TO_USER_PROFILE']
					)
				): '',
			'EMP_DEDUCTED' => $arOrderShipment['EMP_DEDUCTED_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrderShipment['ID']}_EMP_DEDUCTED",
						'USER_ID' => $arOrderShipment['EMP_DEDUCTED_ID'],
						'USER_NAME'=> $arOrderShipment['EMP_DEDUCTED'],
						'USER_PROFILE_URL' => $arOrderShipment['PATH_TO_USER_PROFILE']
					)
				): '',
			'EMP_RESPONSIBLE' => $arOrderShipment['EMP_RESPONSIBLE_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrderShipment['ID']}_EMP_RESPONSIBLE",
						'USER_ID' => $arOrderShipment['EMP_RESPONSIBLE_ID'],
						'USER_NAME'=> $arOrderShipment['EMP_RESPONSIBLE'],
						'USER_PROFILE_URL' => $arOrderShipment['PATH_TO_USER_PROFILE']
					)
				): '',
			'EMP_MARKED' => $arOrderShipment['EMP_MARKED_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrderShipment['ID']}_EMP_MARKED",
						'USER_ID' => $arOrderShipment['EMP_MARKED_ID'],
						'USER_NAME'=> $arOrderShipment['EMP_MARKED'],
						'USER_PROFILE_URL' => $arOrderShipment['PATH_TO_USER_PROFILE']
					)
				): '',
			'CUSTOM_PRICE_DELIVERY' => $arOrderShipment['CUSTOM_PRICE_DELIVERY'] == 'Y' ? Loc::getMessage('MAIN_YES') : Loc::getMessage('MAIN_NO'),
			'COMMENTS' => htmlspecialcharsbx($arOrderShipment['COMMENTS']),
			'TRACKING_NUMBER' => htmlspecialcharsbx($arOrderShipment['TRACKING_NUMBER']),
			'DELIVERY_DOC_NUM' => htmlspecialcharsbx($arOrderShipment['DELIVERY_DOC_NUM']),
			'DATE_INSERT' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrderShipment['DATE_INSERT']), $now),
			'DATE_ALLOW_DELIVERY' => !empty($arOrderShipment['DATE_ALLOW_DELIVERY']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrderShipment['DATE_ALLOW_DELIVERY']), $now) : '',
			'DATE_DEDUCTED' => !empty($arOrderShipment['DATE_DEDUCTED']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrderShipment['DATE_DEDUCTED']), $now) : '',
			'DATE_MARKED' => ($arOrderShipment['DATE_MARKED'] == 'Y' ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrderShipment['DATE_MARKED']), $now) : ''),
			'DATE_RESPONSIBLE_ID' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrderShipment['DATE_RESPONSIBLE_ID']), $now),
			'MARKED' => $arOrderShipment['MARKED'] == 'Y' ? Loc::getMessage('MAIN_YES') : Loc::getMessage('MAIN_NO'),
			'CURRENCY' => CCrmCurrency::GetEncodedCurrencyName($arOrderShipment['CURRENCY']),
			'DELIVERY_SERVICE' => $deliveryService,
			'ALLOW_DELIVERY' => $arOrderShipment['ALLOW_DELIVERY'] == 'Y' ? '<span style="color: green;">'.Loc::getMessage('MAIN_YES').'</span>' : '<span style="color: red;">'.Loc::getMessage('MAIN_NO').'</span>',
			'DEDUCTED' => $arOrderShipment['DEDUCTED'] == 'Y' ? '<span style="color: green;">'.Loc::getMessage('CRM_ORDER_DEDUCTED').'</span>' : '<span style="color: red;">'.Loc::getMessage('CRM_ORDER_NOT_DEDUCTED').'</span>'
		)
	);

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'AJAX_ONLY' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y')
);

if($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => GetMessage('CRM_ORDER_SHIPMENT_LIST_ADD_SHORT'),
		'TITLE' => GetMessage('CRM_ORDER_SHIPMENT_LIST_ADD'),
		'LINK' => $arResult['PATH_TO_ORDER_SHIPMENT_ADD'],
		'ICON' => 'btn-new'
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => mb_strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => array($addButton)
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

if ($arResult['IS_AJAX_CALL'])
{
	$GLOBALS['OnCrmCrmOrderShipmentListAfterAjaxHandlerParams']['arOrderStatusInfoValues'] = $arOrderShipmentStatusInfoValues;
	function OnCrmCrmOrderShipmentListAfterAjaxHandlerParams()
	{
		?>
		<script type="text/javascript">
			BX.ready(function(){
				if (typeof(BX.CrmOrderStatusManager) === 'function')
				{
					BX.CrmOrderStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($GLOBALS['OnCrmCrmOrderShipmentListAfterAjaxHandlerParams']['arOrderStatusInfoValues']) ?>;
				}
			});
		</script><?

		return '';
	}
	AddEventHandler('main', 'OnAfterAjaxResponse', 'OnCrmCrmOrderShipmentListAfterAjaxHandlerParams');
}

$messages = array();
if(isset($arResult['ERRORS']) && is_array($arResult['ERRORS']))
{
	foreach($arResult['ERRORS'] as $error)
	{
		$messages[] = array(
			'TYPE' => \Bitrix\Main\Grid\MessageType::ERROR,
			'TITLE' => $error['TITLE'],
			'TEXT' => $error['TEXT']
		);
	}
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'ACTION_PANEL' => array(),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'MESSAGES' => $messages,
		'NAVIGATION_BAR' => array(
			'ITEMS' => array(
				array(
					//'icon' => 'table',
					'id' => 'list',
					'name' => GetMessage('CRM_ORDER_SHIPMENT_LIST_FILTER_NAV_BUTTON_LIST'),
					'active' => true,
					'url' => $arResult['PATH_TO_ORDER_SHIPMENT_LIST']
				),
			),
			'BINDING' => array(
				'category' => 'crm.navigation',
				'name' => 'index',
				'key' => mb_strtolower($arResult['NAVIGATION_CONTEXT_ID'])
			)
		),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::OrderShipmentName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'serviceUrl' => '/bitrix/components/bitrix/crm.order.shipment.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_ORDER_SHIPMENT_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_ORDER_SHIPMENT_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_ORDER_SHIPMENT_DELETE'),
				'moveToCategoryDialogTitle' => GetMessage('CRM_ORDER_SHIPMENT_MOVE_TO_CATEGORY_DLG_TITLE'),
				'moveToCategoryDialogMessage' => GetMessage('CRM_ORDER_SHIPMENT_MOVE_TO_CATEGORY_DLG_SUMMARY')
			)
		),
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	$component
);

if (!$arResult['IS_AJAX_CALL'])
{
	?>
	<script type="text/javascript">
		BX.ready(function ()
		{
			if (typeof(BX.CrmOrderStatusManager) === 'function')
			{
				BX.CrmOrderStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($arOrderShipmentStatusInfoValues) ?>;
			}
		});
	</script>
	<?
}
if ($isInternal && (int)$arParams['INTERNAL_FILTER']['ORDER_ID'] > 0)
{
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.namespace('BX.Crm.Order.ShipmentList');
				if (typeof BX.Crm.Order.ShipmentList.handlerOnUpdate === "undefined")
				{
					BX.Crm.Order.ShipmentList.handlerOnUpdate = function(event){
						if (
							BX.type.isPlainObject(event.entityData)
							&& parseInt(event.entityData.ID) === parseInt(<?=(int)$arParams['INTERNAL_FILTER']['ORDER_ID']?>)
						)
						{
							BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
						}
					};
				}

				if (typeof BX.Crm.EntityEvent !== "undefined")
				{
					BX.removeCustomEvent(BX.Crm.EntityEvent.names.update, BX.Crm.Order.ShipmentList.handlerOnUpdate);
					BX.addCustomEvent(BX.Crm.EntityEvent.names.update, BX.Crm.Order.ShipmentList.handlerOnUpdate);
				}
			}
		);
	</script>
	<?
}