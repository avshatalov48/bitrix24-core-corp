<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Restriction\OrderRestriction;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Tracking;
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
if($arResult['NEED_FOR_REBUILD_ORDER_ATTRS'])
{
	?><div id="rebuildOrderAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_ORDER_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildOrderAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
}
?></div><?

$isRecurring = isset($arParams['IS_RECURRING']) && $arParams['IS_RECURRING'] === 'Y';
$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$salescenterMode = ($arResult['SALESCENTER_MODE']
	&& \Bitrix\Main\ModuleManager::isModuleInstalled('salescenter')
	&& \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSitePublished()
);
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
			'OWNER_TYPE' => 'ORDER',
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
	'ownerType' => 'ORDER',
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
$arOrderStatusInfoValues = array();

foreach($arResult['ORDER'] as $sKey => $arOrder)
{
	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arOrderStatusInfoValues[$arOrder['ID']] = array(
		'REASON_CANCELED' => ($arOrder['REASON_CANCELED'] != '') ? $arOrder['REASON_CANCELED'] : ''
	);

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_ORDER_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_ORDER_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arOrder['PATH_TO_ORDER_DETAILS'])."')",
		'DEFAULT' => true
	);

	if($arOrder['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_ORDER_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arOrder['PATH_TO_ORDER_EDIT'])."')"
		);

		$copyButtonOnClickHandler = "BX.Crm.Page.open('".CUtil::JSEscape($arOrder['PATH_TO_ORDER_COPY'])."')";

		$arActions[] = array(
			'TITLE' => GetMessage('CRM_ORDER_COPY_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_COPY'),
			'ONCLICK' => $copyButtonOnClickHandler,
		);
	}

	if($salescenterMode)
	{
		$arActions[] = array(
			'TEXT' => GetMessage("CRM_ORDER_SEND_TO_CHAT"),
			'ONCLICK' => "BX.Salescenter.Orders.highlightOrder('".$arOrder['ID']."'); BX.Salescenter.Orders.sendGridOrders();",
		);
	}

	if(!$isInternal && $arOrder['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arOrder['PATH_TO_ORDER_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_ORDER_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}',
				BX.CrmUIGridMenuCommand.remove,
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	$arActions[] = array('SEPARATOR' => true);

	if(!$isInternal && $arParams['IS_RECURRING'] !== 'Y')
	{
		if($arOrder['EDIT'])
		{
			if(IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_ORDER_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_ORDER_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arOrder['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_ORDER_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_ORDER_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arOrder['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_ORDER_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_ORDER_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arOrder['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_ORDER_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_ORDER_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arOrder['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_ORDER_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_ORDER_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arOrder['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_ORDER_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_ORDER_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arOrder['ID']} } }
					)"
				);
			}

			if(!empty($arActivitySubMenuItems))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_ORDER_ADD_ACTIVITY_TITLE'),
					'TEXT' => GetMessage('CRM_ORDER_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				);
			}

			if($arResult['IS_BIZPROC_AVAILABLE'])
			{
				$arActions[] = array('SEPARATOR' => true);
				if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_ORDER_BIZPROC_TITLE'),
						'TEXT' => GetMessage('CRM_ORDER_BIZPROC'),
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arOrder['PATH_TO_BIZPROC_LIST'])."');"
					);
				if(!empty($arOrder['BIZPROC_LIST']))
				{
					$arBizprocList = array();
					foreach($arOrder['BIZPROC_LIST'] as $arBizproc)
					{
						$arBizprocList[] = array(
							'TITLE' => $arBizproc['DESCRIPTION'],
							'TEXT' => $arBizproc['NAME'],
							'ONCLICK' => isset($arBizproc['ONCLICK']) ?
								$arBizproc['ONCLICK']
								: "jsUtils.Redirect([], '".CUtil::JSEscape($arBizproc['PATH_TO_BIZPROC_START'])."');"
						);
					}
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_ORDER_BIZPROC_LIST_TITLE'),
						'TEXT' => GetMessage('CRM_ORDER_BIZPROC_LIST'),
						'MENU' => $arBizprocList
					);
				}
			}
		}
	}

	$eventParam = array(
		'ID' => $arOrder['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);
	foreach(GetModuleEvents('crm', 'onCrmOrderListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_ORDER_LIST_MENU', $eventParam, &$arActions));
	}

	$basket = '';

	if(!empty($arOrder['BASKET']))
	{
		foreach ($arOrder['BASKET'] as $item)
		{
			$basketItem = $item['NAME'];

			if($item['EDIT_PAGE_URL'] <> '')
			{
				$basketItem = '<a href="'.$item['EDIT_PAGE_URL'].'">'.$basketItem.'</a>';
			}

			$basketItem = '<span>['.$item['PRODUCT_ID'].'] '.$basketItem.'</span> ';

			$basketItem .= '<span>'.$item['QUANTITY'].'</span> ';

			if($item['PRICE'] <> '')
			{
				$basketItem .= '<span>'.$item["PRICE"].'</span> ';
			}

			if((float)$item['WEIGHT'] > 0)
			{
				$basketItem .= '<span>'.$item["WEIGHT"].'</span> ';
			}

			$basketItem = "<div>{$basketItem}</div>";
			if (!empty($item['PROPS']) && is_array($item['PROPS']))
			{
				$propsRow = "";
				foreach ($item['PROPS'] as $property)
				{
					$propertyString = htmlspecialcharsbx("{$property['NAME']}: {$property['VALUE']}");
					$propsRow .= "<div>{$propertyString}</div>";
				}
				if ($propsRow <> '')
				{
					$basketItem .= "<div class='crm-order-list-basket-item-props'>$propsRow</div>";
				}
			}

			$basket .= '<div class="crm-order-list-basket-item">'.$basketItem.'</div>';
		}
	}

	$shipment = '';

	if(is_array($arOrder['SHIPMENT']))
	{
		foreach ($arOrder['SHIPMENT'] as $item)
		{
			$shipmentItem = '<div>'
					.Loc::getMessage('CRM_ORDER_LIST_NUMBER')
					.': <a href="'.htmlspecialcharsbx($item['URL']).'">'
					.htmlspecialcharsbx($item['ACCOUNT_NUMBER'])
				.'</a></div> '
				.'<div>'
					.Loc::getMessage('CRM_ORDER_LIST_DELIVERY_NAME').': '
					.'<strong>'.htmlspecialcharsbx($item['DELIVERY_NAME']).'</strong>'
				.'</div>'
				.'<div>'
					.Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_PRICE').': '
					.'<strong>'.$item['PRICE_DELIVERY'].'</strong>'
				.'</div>'
				.'<div>'.Loc::getMessage('CRM_ORDER_LIST_STATUS').': '.htmlspecialcharsbx($item['STATUS']).'</div>'
				.'<div>'
					.($item['ALLOW_DELIVERY'] === 'Y'
						? Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_ALLOWED')
						: Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_NOT_ALLOWED'))
				.'</div>';

			$shipmentItem .= '<div>'
					.($item['DEDUCTED'] === 'Y'
						? Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_DEDUCTED')
						: Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_NOT_DEDUCTED'))
				.'</div>';

			if($item['TRACKING_NUMBER'] <> '')
			{
				$shipmentItem .= '<div>'
						.Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_TRACK_NUMBER').': '
						.'<strong>'.htmlspecialcharsbx($item['TRACKING_NUMBER']).'</strong>'
					.'</div>';
			}

			if($item['CANCELED'] === 'Y')
			{
				$shipmentItem .= '<div>'.Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_CANCELLED').'</div>';
			}

			if($item['MARKED'] === 'Y')
			{
				$shipmentItem .= '<div class="marked">'.Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_MARKED').'</div>';
			}

			if($item['WEIGHT'] <> '')
			{
				$shipmentItem .= '<div>'.Loc::getMessage('CRM_ORDER_LIST_SHIPMENT_WEIGHT').': '.$item["WEIGHT"].'</div> ';
			}

			$shipment .= '<div class="crm-order-list-shipment">'.$shipmentItem.'</div>';
		}
	}

	$payment = '';

	if(is_array($arOrder['PAYMENT']))
	{
		foreach ($arOrder['PAYMENT'] as $item)
		{
			$paymentItem = '<div>'
					.Loc::getMessage('CRM_ORDER_LIST_NUMBER')
					.': <a href="'.htmlspecialcharsbx($item['URL']).'">'
					.htmlspecialcharsbx($item['ACCOUNT_NUMBER'])
				.'</a></div> '
				.'<div>'
					.Loc::getMessage('CRM_ORDER_LIST_PAYSYSTEM_NAME').': '
					.'<strong>'.htmlspecialcharsbx($item['PAY_SYSTEM_NAME']).'</strong>'
				.'</div>'
				.'<div>'
					.Loc::getMessage('CRM_ORDER_LIST_PAYSYSTEM_SUM').': '
					.'<strong>'.$item['SUM'].'</strong>'
				.'</div>'
				.'<div><strong>'
				.($item['PAID'] === 'Y'
					? Loc::getMessage('CRM_ORDER_LIST_PAYSYSTEM_PAID')
					: Loc::getMessage('CRM_ORDER_LIST_PAYSYSTEM_NOT_PAID'))
				.'</strong></div>';

			$payment .= '<div class="crm-order-list-payment">'.$paymentItem.'</div>';
		}
	}

	$properties = '';

	if(is_array($arOrder['PROPS']))
	{
		foreach ($arOrder['PROPS'] as $group)
		{
			$items = '';

			foreach ($group['ITEMS'] as $property)
			{
				$items .= "<div>{$property["NAME"]}: {$property["VALUE"]}</div>";
			}

 			$properties .= "<div class=\"crm-order-list-props-group\">"
				."<div class=\"crm-order-list-props-group-name\">{$group['NAME']}:</div>"
				."<div class=\"crm-order-list-props-group-items\">{$items}</div>"
			."</div>";
		}

		$properties = "<div class=\"crm-order-list-props\">{$properties}</div>";
	}


	$resultItem = array(
		'id' => $arOrder['ID'],
		'actions' => $arActions,
		'data' => $arOrder,
		'editable' => !$arOrder['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'ORDER_SUMMARY' => CCrmViewHelper::RenderInfo(
				$arOrder['PATH_TO_ORDER_DETAILS'],
				Loc::getMessage(
					'CRM_ORDER_SUMMARY',
					array(
						'#ORDER_NUMBER#' => isset($arOrder['ACCOUNT_NUMBER']) ? htmlspecialcharsbx($arOrder['ACCOUNT_NUMBER']) : $arOrder['ID']
				)),
				!empty($arOrder['ORDER_TOPIC']) ? htmlspecialcharsbx($arOrder['ORDER_TOPIC']) : '', // type
				array('TARGET' => '_self')
			),
			'STATUS_ID' => CCrmViewHelper::RenderOrderStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arOrder['ID'],
					'CURRENT_ID' => $arOrder['STATUS_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.order.list/list.ajax.php',
					'READ_ONLY' => !(isset($arOrder['EDIT']) && $arOrder['EDIT'] === true)
				)
			),
			'ACCOUNT_NUMBER' => isset($arOrder['ACCOUNT_NUMBER']) ? htmlspecialcharsbx($arOrder['ACCOUNT_NUMBER']) : '',
			'ORDER_TOPIC' => isset($arOrder['ORDER_TOPIC']) ? htmlspecialcharsbx($arOrder['ORDER_TOPIC']) : '',
			'CLIENT' => isset($arOrder['CLIENT']) ? CCrmViewHelper::PrepareClientInfo($arOrder['CLIENT']) : '',
			'COMPANY' => isset($arOrder['COMPANY']) ? CCrmViewHelper::PrepareClientInfo($arOrder['COMPANY']) : '',
			'CONTACT' => isset($arOrder['CONTACT']) ? CCrmViewHelper::PrepareClientInfo($arOrder['CONTACT']) : '',
			'BASKET' => $basket,
			'SHIPMENT' => $shipment,
			'PAYMENT' => $payment,
			'PROPS' => $properties,
			'TITLE' => '<a target="_self" href="'.$arOrder['PATH_TO_ORDER_SHOW'].'"
				class="'.($arOrder['BIZPROC_STATUS'] != '' ? 'bizproc bizproc_status_'.$arOrder['BIZPROC_STATUS'] : '').'"
				'.($arOrder['BIZPROC_STATUS_HINT'] != '' ? 'onmouseover="BX.hint(this, \''.CUtil::JSEscape($arOrder['BIZPROC_STATUS_HINT']).'\');"' : '').'>'.$arOrder['TITLE'].'</a>',
			'PAYED' => $arOrder['PAYED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'RESERVED' => $arOrder['RESERVED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'CANCELED' => $arOrder['CANCELED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'DEDUCTED' => $arOrder['DEDUCTED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'ALLOW_DELIVERY' => $arOrder['ALLOW_DELIVERY'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'MARKED' => $arOrder['MARKED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'DATE_INSERT' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrder['DATE_INSERT']), $now),
			'DATE_PAYED' => !empty($arOrder['DATE_PAYED']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrder['DATE_PAYED']), $now) : '',
			'DATE_CANCELED' => !empty($arOrder['DATE_CANCELED']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrder['DATE_CANCELED']), $now) : '',
			'DATE_STATUS' => !empty($arOrder['DATE_STATUS']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrder['DATE_STATUS']), $now) : '',
			'DATE_ALLOW_DELIVERY' => !empty($arOrder['DATE_ALLOW_DELIVERY']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrder['DATE_ALLOW_DELIVERY']), $now) : '',
			'DATE_DEDUCTED' => !empty($arOrder['DATE_DEDUCTED']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrder['DATE_DEDUCTED']), $now) : '',
			'DATE_UPDATE' => !empty($arOrder['DATE_UPDATE']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrder['DATE_UPDATE']), $now) : '',
			'RESPONSIBLE_BY' => $arOrder['RESPONSIBLE_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrder['ID']}_RESPONSIBLE",
						'USER_ID' => $arOrder['RESPONSIBLE_ID'],
						'USER_NAME'=> $arOrder['RESPONSIBLE_BY'],
						'USER_PROFILE_URL' => $arOrder['PATH_TO_RESPONSIBLE_PROFILE']
					)
				) : '',
			'USER' => $arOrder['USER_ID'] > 0 ? CCrmViewHelper::PrepareUserBaloonHtml(
				array(
					'PREFIX' => "ORDER_{$arOrder['USER_ID']}_USER",
					'USER_ID' => $arOrder['USER_ID'],
					'USER_NAME'=> $arOrder['USER_FORMATTED_NAME'],
					'USER_PROFILE_URL' => $arOrder['PATH_TO_USER_PROFILE']
				)
			) : '',
			'COMMENTS' => htmlspecialcharsback($arOrder['COMMENTS']),
			'SUM' => $arOrder['SUM'],
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arOrder['DATE_MODIFY']), $now),
			'ORIGINATOR_ID' => isset($arOrder['ORIGINATOR_NAME']) ? $arOrder['ORIGINATOR_NAME'] : '',
			'CREATED_BY' => $arOrder['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrder['~ID']}_CREATOR",
						'USER_ID' => $arOrder['~CREATED_BY'],
						'USER_NAME'=> $arOrder['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arOrder['PATH_TO_USER_CREATOR']
					)
				) : '',
			'EMP_PAYED_ID' => $arOrder['~EMP_PAYED_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrder['~ID']}_EMP_PAYED_ID",
						'USER_ID' => $arOrder['~EMP_PAYED_ID'],
						'USER_NAME'=> $arOrder['EMP_PAYED_ID_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arOrder['PATH_TO_EMP_PAYED_ID']
					)
				) : '',
			'EMP_CANCELED_ID' => $arOrder['~EMP_CANCELED_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrder['~ID']}_EMP_CANCELED_ID",
						'USER_ID' => $arOrder['~EMP_CANCELED_ID'],
						'USER_NAME'=> $arOrder['EMP_CANCELED_ID_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arOrder['PATH_TO_EMP_CANCELED_ID']
					)
				) : '',
			'EMP_STATUS_ID' => $arOrder['~EMP_STATUS_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrder['~ID']}_EMP_STATUS_ID",
						'USER_ID' => $arOrder['~EMP_STATUS_ID'],
						'USER_NAME'=> $arOrder['EMP_STATUS_ID_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arOrder['PATH_TO_EMP_STATUS_ID']
					)
				) : '',
			'EMP_ALLOW_DELIVERY_ID' => $arOrder['~EMP_ALLOW_DELIVERY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrder['~ID']}_EMP_ALLOW_DELIVERY_ID",
						'USER_ID' => $arOrder['~EMP_ALLOW_DELIVERY_ID'],
						'USER_NAME'=> $arOrder['EMP_ALLOW_DELIVERY_ID_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arOrder['PATH_TO_EMP_ALLOW_DELIVERY_ID']
					)
				) : '',
			'EMP_DEDUCTED_ID' => $arOrder['~EMP_DEDUCTED_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "ORDER_{$arOrder['~ID']}_EMP_DEDUCTED_ID",
						'USER_ID' => $arOrder['~EMP_DEDUCTED_ID'],
						'USER_NAME'=> $arOrder['EMP_DEDUCTED_ID_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arOrder['PATH_TO_EMP_DEDUCTED_ID']
					)
				) : ''
		) + $arResult['ORDER_UF'][$sKey]
	);

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Order,
		$arOrder['ID'],
		$resultItem['columns']
	);

	$userActivityID = isset($arOrder['USER_ACTIVITY_ID']) ? intval($arOrder['USER_ACTIVITY_ID']) : 0;
	$commonActivityID = isset($arOrder['C_ACTIVITY_ID']) ? intval($arOrder['C_ACTIVITY_ID']) : 0;
	if($userActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Order),
				'ENTITY_ID' => $arOrder['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arOrder['RESPONSIBLE_ID'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $userActivityID,
				'ACTIVITY_SUBJECT' => isset($arOrder['C_ACTIVITY_SUBJECT']) ? $arOrder['C_ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arOrder['C_ACTIVITY_TIME']) ? $arOrder['C_ACTIVITY_TIME'] : '',
				'ACTIVITY_EXPIRED' => isset($arOrder['ACTIVITY_EXPIRED']) ? $arOrder['ACTIVITY_EXPIRED'] : '',
				'ALLOW_EDIT' => $arOrder['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arOrder,
			'ACTIVITY' => array(
				'RESPONSIBLE_ID' => $currentUserID,
				'TIME' => isset($arOrder['~ACTIVITY_TIME']) ? $arOrder['~ACTIVITY_TIME'] : '',
				'IS_CURRENT_DAY' => isset($arOrder['~ACTIVITY_IS_CURRENT_DAY']) ? $arOrder['~ACTIVITY_IS_CURRENT_DAY'] : false
			)
		);

		if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentOrderActivies, $counterData))
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-order-today');
		}
	}
	elseif($commonActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Order),
				'ENTITY_ID' => $arOrder['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arOrder['RESPONSIBLE_ID'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $commonActivityID,
				'ACTIVITY_SUBJECT' => isset($arOrder['C_ACTIVITY_SUBJECT']) ? $arOrder['C_ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arOrder['C_ACTIVITY_TIME']) ? $arOrder['C_ACTIVITY_TIME'] : '',
				'ACTIVITY_RESPONSIBLE_ID' => isset($arOrder['C_ACTIVITY_RESP_ID']) ? intval($arOrder['C_ACTIVITY_RESP_ID']) : 0,
				'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arOrder['C_ACTIVITY_RESP_LOGIN']) ? $arOrder['C_ACTIVITY_RESP_LOGIN'] : '',
				'ACTIVITY_RESPONSIBLE_NAME' => isset($arOrder['C_ACTIVITY_RESP_NAME']) ? $arOrder['C_ACTIVITY_RESP_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arOrder['C_ACTIVITY_RESP_LAST_NAME']) ? $arOrder['C_ACTIVITY_RESP_LAST_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arOrder['C_ACTIVITY_RESP_SECOND_NAME']) ? $arOrder['C_ACTIVITY_RESP_SECOND_NAME'] : '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
				'ALLOW_EDIT' => $arOrder['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);
	}
	else
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Order),
				'ENTITY_ID' => $arOrder['ID'],
				'ENTITY_RESPONSIBLE_ID' => $arOrder['RESPONSIBLE_ID'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ALLOW_EDIT' => $arOrder['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'HINT_TEXT' => isset($arOrder['WAITING_TITLE']) ? $arOrder['WAITING_TITLE'] : '',
				'USE_GRID_EXTENSION' => true
			)
		);
	}

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

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if(!$isInternal
	&& ($allowWrite || $allowDelete))
{
	$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
	$applyButton = $snippet->getApplyButton(
		array(
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processApplyButtonClick('{$gridManagerID}')"))
				)
			)
		)
	);

	$actionList = array(array('NAME' => GetMessage('CRM_ORDER_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	$yesnoList = array(
		array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
		array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
	);

	if($allowWrite && $arParams['IS_RECURRING'] !== "Y")
	{
		//region Add Task
		if (IsModuleInstalled('tasks'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_ORDER_TASK'),
				'VALUE' => 'tasks',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array($applyButton)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'tasks')"))
					)
				)
			);
		}
		//endregion

		$statusList = array();

		foreach($arResult['STATUS_LIST'] as $id => $name)
			$statusList[] = array('NAME' => $name, 'VALUE' => $id);

		$actionList[] = array(
			'NAME' => GetMessage('CRM_ORDER_SET_STATUS'),
			'VALUE' => 'set_status',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_status_id',
							'NAME' => 'ACTION_STATUS_ID',
							'ITEMS' => $statusList
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'set_status')"))
				)
			)
		);

		//region Assign To
		//region Render User Search control
		if(!Bitrix\Main\Grid\Context::isInternalRequest())
		{
			//action_responsible_by_search + _control
			//Prefix control will be added by main.ui.grid
			$APPLICATION->IncludeComponent(
				'bitrix:intranet.user.selector.new',
				'',
				array(
					'MULTIPLE' => 'N',
					'NAME' => "{$prefix}_ACTION_RESPONSIBLE_BY",
					'INPUT_NAME' => 'action_responsible_by_search_control',
					'SHOW_EXTRANET_USERS' => 'NONE',
					'POPUP' => 'Y',
					'SITE_ID' => SITE_ID,
					'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE']
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}
		//endregion
		$actionList[] = array(
			'NAME' => GetMessage('CRM_ORDER_ASSIGN_TO'),
			'VALUE' => 'assign_to',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_responsible_by_search',
							'NAME' => 'ACTION_RESPONSIBLE_BY_SEARCH'
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_responsible_by_id',
							'NAME' => 'ACTION_RESPONSIBLE_BY_ID'
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array('JS' => "BX.CrmUIGridExtension.prepareAction('{$gridManagerID}', 'assign_to',  { searchInputId: 'action_responsible_by_search_control', dataInputId: 'action_responsible_by_id_control', componentName: '{$prefix}_ACTION_RESPONSIBLE_BY' })")
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'assign_to')"))
				)
			)
		);
		//endregion
		//region Create call list
		if(IsModuleInstalled('voximplant'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_ORDER_CREATE_CALL_LIST'),
				'VALUE' => 'create_call_list',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array(
							$applyButton
						)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'create_call_list')"))
					)
				)
			);
		}
		//endregion
	}

	if($allowDelete)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
		$actionList[] = $snippet->getRemoveAction();
	}

	if($salescenterMode)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_ORDER_SEND_TO_CHAT"),
			"ID" => "send_to_chat",
			"NAME" => "send_to_chat",
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => [['JS' => "BX.Salescenter.Orders.sendGridOrders();"]]
				)
			)
		);
	}
	elseif($callListUpdateMode)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_ORDER_UPDATE_CALL_LIST"),
			"ID" => "update_call_list",
			"NAME" => "update_call_list",
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.updateCallList('{$gridManagerID}', {$arResult['CALL_LIST_ID']}, '{$arResult['CALL_LIST_CONTEXT']}')"))
				)
			)
		);
	}
	else
	{
		//region Create & start call list
		if(IsModuleInstalled('voximplant'))
		{
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => GetMessage('CRM_ORDER_START_CALL_LIST'),
				"VALUE" => "start_call_list",
				"ONCHANGE" => array(
					array(
						"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						"DATA" => array(array('JS' => "BX.CrmUIGridExtension.createCallList('{$gridManagerID}', true)"))
					)
				)
			);
		}
		//endregion
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
			"ID" => "action_button_{$prefix}",
			"NAME" => "action_button_{$prefix}",
			"ITEMS" => $actionList
		);
	}

	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
}
//endregion

if($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => GetMessage('CRM_ORDER_LIST_ADD_SHORT'),
		'TITLE' => GetMessage('CRM_ORDER_LIST_ADD'),
		'LINK' => $arResult['PATH_TO_ORDER_ADD'],
		'ICON' => 'btn-new'
	);

	if($arResult['ADD_EVENT_NAME'] !== '')
	{
		$addButton['ONCLICK'] = "BX.onCustomEvent(window, '{$arResult['ADD_EVENT_NAME']}')";
	}

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
	$GLOBALS['OnCrmCrmOrderListAfterAjaxHandlerParams']['arOrderStatusInfoValues'] = $arOrderStatusInfoValues;
	function OnCrmCrmOrderListAfterAjaxHandler()
	{
		?>
		<script type="text/javascript">
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

$messages = array();
if(isset($arResult['ERRORS']) && is_array($arResult['ERRORS']))
{
	foreach($arResult['ERRORS'] as $error)
	{
		$messages[] = array(
			'TYPE' => \Bitrix\Main\Grid\MessageType::ERROR,
//			'TITLE' => $error['TITLE'],
			'TEXT' => $error
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
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => array(
			'LAZY_LOAD' => array(
				'GET_LIST' => '/bitrix/components/bitrix/crm.order.list/filter.ajax.php?action=list&filter_id='.urlencode($arResult['GRID_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'GET_FIELD' => '/bitrix/components/bitrix/crm.order.list/filter.ajax.php?action=field&filter_id='.urlencode($arResult['GRID_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			)
		),
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => $controlPanel,
		'SHOW_ACTION_PANEL' => !empty($controlPanel),
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'MESSAGES' => $messages,
		'NAVIGATION_BAR' => array(
			'ITEMS' => array_merge(
				\Bitrix\Crm\Automation\Helper::getNavigationBarItems(\CCrmOwnerType::Order),
				array(
					array(
						//'icon' => 'kanban',
						'id' => 'kanban',
						'name' => GetMessage('CRM_ORDER_LIST_FILTER_NAV_BUTTON_KANBAN'),
						'active' => false,
						'url' => $arParams['PATH_TO_ORDER_KANBAN']
					),
					array(
						//'icon' => 'table',
						'id' => 'list',
						'name' => GetMessage('CRM_ORDER_LIST_FILTER_NAV_BUTTON_LIST'),
						'active' => true,
						'url' => $arResult['PATH_TO_ORDER_LIST']
					),
					/*
					array(
						//'icon' => 'chart',
						'id' => 'widget',
						'name' => GetMessage('CRM_ORDER_LIST_FILTER_NAV_BUTTON_WIDGET'),
						'active' => false,
						'url' => $arResult['PATH_TO_ORDER_WIDGET']
					)
					*/
				)
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
				'ownerTypeName' => CCrmOwnerType::OrderName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.order.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_ORDER_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_ORDER_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_ORDER_DELETE')
			)
		),
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'LIVE_SEARCH_LIMIT_INFO' => isset($arResult['LIVE_SEARCH_LIMIT_INFO']) ? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
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
				BX.CrmOrderStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($arOrderStatusInfoValues) ?>;
			}
		});
	</script>
	<?
}

?>
<script type="text/javascript">

	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_ORDER_LRP_DLG_BTN_START')?>" ,
				stopButton: "<?=GetMessageJS('CRM_ORDER_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_ORDER_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_ORDER_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_ORDER_LRP_DLG_REQUEST_ERR')?>"
			};

			BX.addCustomEvent("CrmProgressControlAfterSaveSucces", function(progressControl, result)
			{
				if(progressControl.getEntityType() !== "ORDER")
					return;

				if (BX.type.isNotEmptyString(result['ERROR']))
				{
					var grid = BX.Main.gridManager.getInstanceById('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					grid.arParams.MESSAGES = [{
						'TYPE': 'ERROR',
						'TITLE': BX.type.isNotEmptyString(result['ERROR_TITLE']) ? result['ERROR_TITLE'] :null,
						'TEXT':	result['ERROR']
					}];
					BX.onCustomEvent(window, 'BX.Main.grid:paramsUpdated', []);
				}
			});
		}
	);
</script><?
if(!$isInternal):
?><script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmActivityEditor.items['<?= CUtil::JSEscape($activityEditorID)?>'].addActivityChangeHandler(
						function()
						{
							BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
						}
				);
				BX.namespace('BX.Crm.Activity');
				if(typeof BX.Crm.Activity.Planner !== 'undefined')
				{
					BX.Crm.Activity.Planner.Manager.setCallback('onAfterActivitySave', function()
					{
						BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					});
				}
			}
	);
</script>
<?endif;?>
<?if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmOrderConversionScheme.messages =
					<?=CUtil::PhpToJSObject(\Bitrix\Crm\Conversion\OrderConversionScheme::getJavaScriptDescriptions(false))?>;

				BX.CrmOrderConverter.messages =
				{
					accessDenied: "<?=GetMessageJS("CRM_ORDER_CONV_ACCESS_DENIED")?>",
					generalError: "<?=GetMessageJS("CRM_ORDER_CONV_GENERAL_ERROR")?>",
					dialogTitle: "<?=GetMessageJS("CRM_ORDER_CONV_DIALOG_TITLE")?>",
					syncEditorLegend: "<?=GetMessageJS("CRM_ORDER_CONV_DIALOG_SYNC_LEGEND")?>",
					syncEditorFieldListTitle: "<?=GetMessageJS("CRM_ORDER_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
					syncEditorEntityListTitle: "<?=GetMessageJS("CRM_ORDER_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
					continueButton: "<?=GetMessageJS("CRM_ORDER_CONV_DIALOG_CONTINUE_BTN")?>",
					cancelButton: "<?=GetMessageJS("CRM_ORDER_CONV_DIALOG_CANCEL_BTN")?>"
				};
				BX.CrmOrderConverter.permissions =
				{
					invoice: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_INVOICE'])?>,
					quote: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_QUOTE'])?>
				};
				BX.CrmOrderConverter.settings =
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.order.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
					config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
				};
				BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildOrderSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_ORDER_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildOrderSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.order.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildOrderSearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_BUILD_TIMELINE']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("buildOrderTimeline"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_ORDER_BUILD_TIMELINE_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_ORDER_BUILD_TIMELINE_STATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("buildOrderTimeline",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.order.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "<?=$isRecurring ? 'BUILD_RECURRING_TIMELINE' : 'BUILD_TIMELINE'?>",
						container: "buildOrderTimelineWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REFRESH_ACCOUNTING']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("refreshOrderAccounting"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_ORDER_REFRESH_ACCOUNTING_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_ORDER_STEPWISE_STATE_TEMPLATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("refreshOrderAccounting",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.order.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REFRESH_ACCOUNTING",
						container: "refreshOrderAccountingWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_ORDER_SEMANTICS']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				var builderPanel = BX.CrmLongRunningProcessPanel.create(
					"rebuildOrderSemantics",
					{
						"containerId": "rebuildMessageWrapper",
						"prefix": "",
						"active": true,
						"message": "<?=GetMessageJS('CRM_ORDER_REBUILD_SEMANTICS')?>",
						"manager":
						{
							dialogTitle: "<?=GetMessageJS("CRM_ORDER_REBUILD_SEMANTICS_DLG_TITLE")?>",
							dialogSummary: "<?=GetMessageJS("CRM_ORDER_REBUILD_SEMANTICS_DLG_SUMMARY")?>",
							actionName: "REBUILD_SEMANTICS",
							serviceUrl: "<?='/bitrix/components/bitrix/crm.order.list/list.ajax.php?'.bitrix_sessid_get()?>"
						}
					}
				);
				builderPanel.layout();
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_ORDER_ATTRS']):?>
<script type="text/javascript">

	BX.ready(
		function()
		{
			var link = BX("rebuildOrderAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildOrderAttrsMsg");
						if(msg)
						{
							msg.style.display = "none";
						}
					}
				);
			}
		}
	);
</script>
<?endif;?>
<?\Bitrix\Crm\Integration\NotificationsManager::showSignUpFormOnCrmShopCreated()?>

