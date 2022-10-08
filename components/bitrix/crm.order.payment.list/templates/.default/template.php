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
if($arResult['NEED_FOR_REBUILD_ORDER_PAYMENT_ATTRS'])
{
	?><div id="rebuildOrderAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_ORDER_PAYMENT_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildOrderAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
}
?></div><?
//echo CCrmViewHelper::RenderOrderShipmentStatusSettings();
$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'];
$allowDelete = $arResult['PERMS']['DELETE'];
$currentUserID = $arResult['CURRENT_USER_ID'];

$salescenterMode = ($arParams['SALESCENTER_MODE']
	&& \Bitrix\Main\ModuleManager::isModuleInstalled('salescenter')
	&& \Bitrix\SalesCenter\Integration\LandingManager::getInstance()->isSitePublished()
);

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
			'OWNER_TYPE' => CCrmOwnerType::OrderPaymentName,
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
	'ownerType' => CCrmOwnerType::OrderPaymentName,
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

foreach($arResult['ORDER_PAYMENT'] as $sKey => $payment)
{
	$jsShowUrl = isset($payment['PATH_TO_ORDER_PAYMENT_SHOW']) ? CUtil::JSEscape($payment['PATH_TO_ORDER_PAYMENT_SHOW']) : '';

	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_ORDER_PAYMENT_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_ORDER_PAYMENT_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($payment['PATH_TO_ORDER_PAYMENT_DETAILS'])."')",
		'DEFAULT' => true
	);

	if($payment['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_ORDER_PAYMENT_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_PAYMENT_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($payment['PATH_TO_ORDER_PAYMENT_EDIT'])."')"
		);
	}

	if($payment['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($payment['PATH_TO_ORDER_PAYMENT_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_ORDER_PAYMENT_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_PAYMENT_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}',
				BX.CrmUIGridMenuCommand.remove,
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	if ($salescenterMode)
	{
		$arActions[] = array(
			'TEXT' => GetMessage("CRM_ORDER_PAYMENT_SEND_TO_CHAT"),
			'ONCLICK' => "BX.Salescenter.Payments.highlightOrder('".$payment['ID']."'); BX.Salescenter.Payments.sendGridPayments();",
		);
	}

	$eventParam = array(
		'ID' => $payment['ID'],
		'GRID_ID' => $arResult['GRID_ID']
	);

	foreach(GetModuleEvents('crm', 'onCrmOrderPaymentListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_ORDER_PAYMENT_LIST_MENU', $eventParam, &$arActions));
	}

	if ($payment['PAID'] == 'Y')
	{
		$messageCode = 'CRM_ORDER_PAYMENT_PAID';
	}
	else
	{
		$messageCode = 'CRM_ORDER_PAYMENT_UNPAID';
	}
	$paySystemFull = $payment['PAY_SYSTEM_NAME'] . ' ['.$payment['PAY_SYSTEM_ID'].'] ';
	$paymentSummaryText = Loc::getMessage('CRM_ORDER_PAYMENT_PAYMENT_SUMMARY', array(
		"#ACCOUNT_NUMBER#" => $payment['ACCOUNT_NUMBER'],
		"#DATE_BILL#" => $payment['DATE_BILL'],
	));
	$resultItem = array(
		'id' => $payment['ID'],
		'actions' => $arActions,
		'data' => $payment,
		'editable' => !$payment['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'PAYMENT_SUMMARY' => CCrmViewHelper::RenderInfo(
				$payment['PATH_TO_ORDER_PAYMENT_DETAILS'],
				htmlspecialcharsbx($paymentSummaryText),
				'', // type
				array('TARGET' => '_self')
			),

			'RESPONSIBLE' => $payment['RESPONSIBLE_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => " PAYMENT_{$payment['ID']}_RESPONSIBLE",
						'USER_ID' => $payment['RESPONSIBLE_ID'],
						'USER_NAME'=> $payment['RESPONSIBLE'],
						'USER_PROFILE_URL' => $payment['PATH_TO_USER_PROFILE']
					)
				): '',
			'DATE_BILL' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($payment['DATE_BILL']), $now),
			'DATE_PAID' => !empty($payment['DATE_PAID']) ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($payment['DATE_PAID']), $now) : '',
			'DATE_MARKED' => ($payment['DATE_MARKED'] == 'Y' ? FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($payment['DATE_MARKED']), $now) : ''),
			'DATE_RESPONSIBLE_ID' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($payment['DATE_RESPONSIBLE_ID']), $now),
			'SUM' => CCrmCurrency::MoneyToString($payment['SUM'], $payment['CURRENCY']),
			'CURRENCY' => CCrmCurrency::GetEncodedCurrencyName($payment['CURRENCY']),
			'PAY_SYSTEM_FULL' => $paySystemFull,
			'PAID' => Loc::getMessage($messageCode),
			'ACCOUNT_NUMBER' => htmlspecialcharsbx($payment['ACCOUNT_NUMBER']),
			'USER_ID' => $payment['BUYER_FORMATTED_NAME'] <> '' ? '<a href="/'.$payment['PATH_TO_BUYER'].'">'.$payment['BUYER_FORMATTED_NAME'].'</a>' : ''
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

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if(($allowWrite || $allowDelete))
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

	$actionList = array(array('NAME' => GetMessage('CRM_ORDER_PAYMENT_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	$yesnoList = array(
		array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
		array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
	);

	if($allowWrite && $arParams['IS_RECURRING'] !== "Y")
	{
		$actionList[] = array(
			'NAME' => GetMessage('CRM_ORDER_PAYMENT_ACTION_PAID'),
			'VALUE' => 'paid',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				)
			)

		);

		$actionList[] = array(
			'NAME' => GetMessage('CRM_ORDER_PAYMENT_ACTION_PAID_N'),
			'VALUE' => 'paid_n',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				)
			)

		);

		//region Assign To
		//region Render User Search control
		if(!Bitrix\Main\Grid\Context::isInternalRequest())
		{
			//action_assigned_by_search + _control
			//Prefix control will be added by main.ui.grid
			$APPLICATION->IncludeComponent(
				'bitrix:intranet.user.selector.new',
				'',
				array(
					'MULTIPLE' => 'N',
					'NAME' => "{$prefix}_ACTION_ASSIGNED_BY",
					'INPUT_NAME' => 'action_assigned_by_search_control',
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

	}

	if($allowDelete)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
		$actionList[] = $snippet->getRemoveAction();
	}

	$controlPanel['GROUPS'][0]['ITEMS'][] = array(
		"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
		"ID" => "action_button_{$prefix}",
		"NAME" => "action_button_{$prefix}",
		"ITEMS" => $actionList
	);

	if ($salescenterMode)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_ORDER_PAYMENT_SEND_TO_CHAT"),
			"ID" => "send_to_chat",
			"NAME" => "send_to_chat",
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => [['JS' => "BX.Salescenter.Payments.sendGridPayments();"]]
				)
			)
		);
	}

	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
}
//endregion

if($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => GetMessage('CRM_ORDER_PAYMENT_LIST_ADD_SHORT'),
		'TITLE' => GetMessage('CRM_ORDER_PAYMENT_LIST_ADD'),
		'LINK' => $arResult['PATH_TO_ORDER_PAYMENT_ADD'],
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
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => $controlPanel,
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'MESSAGES' => $messages,
		'NAVIGATION_BAR' => array(
			'ITEMS' => array(
				array(
					//'icon' => 'table',
					'id' => 'list',
					'name' => GetMessage('CRM_ORDER_PAYMENT_LIST_FILTER_NAV_BUTTON_LIST'),
					'active' => true,
					'url' => $arResult['PATH_TO_ORDER_PAYMENT_LIST']
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
				'deletionDialogTitle' => GetMessage('CRM_ORDER_PAYMENT_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_ORDER_PAYMENT_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_ORDER_PAYMENT_DELETE'),
				'moveToCategoryDialogTitle' => GetMessage('CRM_ORDER_PAYMENT_MOVE_TO_CATEGORY_DLG_TITLE'),
				'moveToCategoryDialogMessage' => GetMessage('CRM_ORDER_PAYMENT_MOVE_TO_CATEGORY_DLG_SUMMARY')
			)
		),
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_CHECKBOXES' => $arParams['SHOW_ROW_CHECKBOXES'],
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	$component
);

if ($isInternal && (int)$arParams['INTERNAL_FILTER']['ORDER_ID'] > 0)
{
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.namespace('BX.Crm.Order.PaymentList');
				if (typeof BX.Crm.Order.PaymentList.handlerOnUpdate === "undefined")
				{
					BX.Crm.Order.PaymentList.handlerOnUpdate = function(event){
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
					BX.removeCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.Crm.Order.PaymentList.handlerOnUpdate);
					BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.Crm.Order.PaymentList.handlerOnUpdate);
				}
			}
		);
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
				startButton: "<?=GetMessageJS('CRM_ORDER_PAYMENT_LRP_DLG_BTN_START')?>" ,
				stopButton: "<?=GetMessageJS('CRM_ORDER_PAYMENT_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_ORDER_PAYMENT_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_ORDER_PAYMENT_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_ORDER_PAYMENT_LRP_DLG_REQUEST_ERR')?>"
			};
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

<?if($arResult['NEED_FOR_BUILD_TIMELINE']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("buildOrderShipmentTimeline"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_ORDER_PAYMENT_BUILD_TIMELINE_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_ORDER_PAYMENT_BUILD_TIMELINE_STATE')?>"
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
<?if($arResult['NEED_FOR_REBUILD_ORDER_PAYMENT_ATTRS']):?>
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
