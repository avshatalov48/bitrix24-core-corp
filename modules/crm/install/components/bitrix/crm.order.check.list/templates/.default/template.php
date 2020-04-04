<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/** @var \CBitrixComponent $component */

global $APPLICATION;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');

$prefix = $arResult['GRID_ID'];
$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$prefixLC = strtolower($arResult['GRID_ID']);

$jsData = [
	'AJAX_URL' => '/bitrix/components/bitrix/crm.order.check.list/list.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
	'GRID_ID' => $arResult['GRID_ID'],
];

$arResult['GRID_DATA'] = array();

if ($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => GetMessage('CRM_CHECK_LIST_ADD'),
		'TITLE' => GetMessage('CRM_CHECK_LIST_ADD'),
		'ICON' => 'btn-new order-payment-check-add',
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => array($addButton)
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}
$columns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$columns[$arHead['id']] = false;
}

foreach($arResult['ROWS'] as $key => $check)
{
	$check['CASHBOX_NAME'] = htmlspecialcharsbx($check['CASHBOX_NAME']);

	$check['PATH_TO_ORDER_CHECK_SHOW'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_ORDER_CHECK_SHOW'],
		array('check_id' => $check['ID'])
	);
	$check['PATH_TO_ORDER_CHECK_EDIT'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_ORDER_CHECK_EDIT'],
		array('check_id' => $check['ID'])
	);

	$check['PATH_TO_ORDER_CHECK_CHECK_STATUS'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_ORDER_CHECK_CHECK_STATUS'],
		array('check_id' => $check['ID'])
	);

	$check['PATH_TO_ORDER_CHECK_DELETE'] = CComponentEngine::makePathFromTemplate(
		$arParams['PATH_TO_ORDER_CHECK_DELETE'],
		array('check_id' => $check['ID'])
	);

	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$actions = array();

	$actions[] = array(
		'TITLE' => GetMessage('CRM_ORDER_CHECK_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_ORDER_CHECK_SHOW'),
		'ONCLICK' => 'BX.Crm.Page.openSlider("'.$check['PATH_TO_ORDER_CHECK_SHOW'].'", { width: 500 });',
		'DEFAULT' => true
	);

	if (isset($check['CASHBOX_IS_CHECKABLE']) && $check['STATUS'] == 'P')
	{
		$actions[] = [
			'TITLE' => GetMessage('CRM_ORDER_CHECK_CHECK_STATUS_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_CHECK_CHECK_STATUS'),
			"ONCLICK" => 'BX.Crm.OrderPaymentCheckList.refreshCheck('.$check['ID'].', ' . CUtil::PhpToJSObject($jsData) .');'
		];

	}

	if ($check['STATUS'] == 'E' || $check['STATUS'] == 'N')
	{
		$actions[] = array(
			'TITLE' => GetMessage('CRM_ORDER_CHECK_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_ORDER_CHECK_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'". $gridManagerID. "',
				BX.CrmUIGridMenuCommand.remove,
				{ pathToRemove: '" . CUtil::JSEscape($check['PATH_TO_ORDER_CHECK_DELETE']) . "' }
			)"
		);
	}

	$check['DATE_CREATE'] = FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($check['DATE_CREATE']));
	$checkDate = FormatDate($arResult['DATE_FORMAT'], MakeTimeStamp($check['DATE_CREATE']));

	$paymentList = array();
	$paymentValue = "";
	if (!empty($check['PAYMENT']))
	{
		$paymentList = $check['PAYMENT'];
	}
	elseif ($check['PAYMENT_ID'] > 0)
	{
		$paymentList[] = $check['PAYMENT_ID'];
	}
	foreach ($paymentList as $paymentId)
	{
		$paymentData = $arResult['PAYMENT_LIST'][$paymentId];
		$paymentTitle = Loc::getMessage("CRM_ORDER_PAYMENT_TITLE",array(
			"#ACCOUNT_NUMBER#" => $paymentData['ACCOUNT_NUMBER'],
			"#DATE_BILL#" =>   FormatDate($arResult['DATE_FORMAT'], MakeTimeStamp($paymentData['DATE_BILL'])),
		));
		$link = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_ORDER_PAYMENT_DETAILS'],
			array('payment_id' => $paymentId)
		);
		$paymentValue .= CCrmViewHelper::RenderInfo(
			$link,
			$paymentTitle,
			htmlspecialcharsbx($paymentData['PAY_SYSTEM_NAME']),
			array('TARGET' => '_self')
		);
	}

	$shipmentList = array();
	$shipmentValue = "";
	if (!empty($check['SHIPMENT']))
	{
		$shipmentList = $check['SHIPMENT'];
	}
	elseif ($check['SHIPMENT_ID'] > 0)
	{
		$shipmentList[] = $check['SHIPMENT_ID'];
	}
	foreach ($shipmentList as $shipmentId)
	{
		$shipmentData = $arResult['SHIPMENT_LIST'][$shipmentId];
		$shipmentTitle = Loc::getMessage("CRM_ORDER_SHIPMENT_TITLE",array(
			"#ACCOUNT_NUMBER#" => $shipmentData['ACCOUNT_NUMBER'],
			"#DATE_INSERT#" =>  FormatDate($arResult['DATE_FORMAT'], MakeTimeStamp($shipmentData['DATE_INSERT'])),
		));
		$link = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_ORDER_SHIPMENT_DETAILS'],
			array('shipment_id' => $shipmentId)
		);
		$shipmentValue .= CCrmViewHelper::RenderInfo(
			$link,
			$shipmentTitle,
			htmlspecialcharsbx($shipmentData['DELIVERY_NAME']),
			array('TARGET' => '_self')
		);
	}

	$checkTitle = Loc::getMessage("CRM_ORDER_CHECK_TITLE",array(
		"#ID#" => $check['ID'],
		"#DATE_CREATE#" => $checkDate,
	));

	$columnsData = [
		'TITLE' => CCrmViewHelper::RenderInfo(
			'#',
			$checkTitle,
			'',
			array('TARGET' => '_self',
				  'ONCLICK' => 'BX.Crm.Page.openSlider("'.$check['PATH_TO_ORDER_CHECK_SHOW'].'", { width: 500 });')
		),
		'CHECK_TYPE' => $check['CHECK_TYPE'],
		'DATE_CREATE' => $check['DATE_CREATE'],
		'FORMATTED_SUM' => $check['FORMATTED_SUM'],
		'PAYMENT' => $paymentValue,
		'LINK_PARAMS' => '',
		'SHIPMENT' => $shipmentValue
	];

	if ($check['URL'])
	{
		$columnsData['LINK_PARAMS'] = CCrmViewHelper::RenderInfo(
			$check['URL'],
			Loc::getMessage('CRM_ORDER_CHECK_URL'),
			'',
			array('TARGET' => '_blank')
		);
	}

	$resultItem = array(
		'id' => $check['ID'],
		'actions' => $actions,
		'data' => $check,
		'editable' => !$check['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $columns) : 'Y',
		'columns' => $columnsData
	);

	$userActivityID = isset($check['USER_ACTIVITY_ID']) ? intval($check['USER_ACTIVITY_ID']) : 0;
	$commonActivityID = isset($check['C_USER_ACTIVITY_ID']) ? intval($check['C_USER_ACTIVITY_ID']) : 0;

	$arResult['GRID_DATA'][] = $resultItem;
	unset($resultItem);
}

$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

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
		'AJAX_MODE' => $arParams['AJAX_MODE'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'ENABLE_LIVE_SEARCH' => false,
		'HIDE_FILTER' => true,
		'ACTION_PANEL' => $controlPanel,
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::OrderName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => '',
				'activityServiceUrl' => '',
				'taskCreateUrl'=> '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.order.check.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_ORDER_CHECK_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_ORDER_CHECK_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_ORDER_CHECK_DELETE'),
			)
		),
		'SHOW_TOTAL_COUNTER' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ACTION_PANEL' => false,
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'STYLES_LOADED' => 'Y',
	),
	$component
);
?>

<?
$jsData['ADD_CHECK_URL'] = $arResult['PATH_TO_ORDER_CHECK_ADD'];
?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.OrderPaymentCheckList.create('<?=CUtil::JSEscape($arResult['GRID_ID'])?>', <?=CUtil::PhpToJSObject($jsData)?>);
		}
	);
</script>
