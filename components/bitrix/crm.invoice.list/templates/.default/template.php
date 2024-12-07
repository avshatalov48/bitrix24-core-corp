<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

use Bitrix\Crm\Component\EntityList\ActionManager;
use Bitrix\Crm\UI\NavigationBarPanel;
use Bitrix\Main\Web\Uri;

CJSCore::Init(array('crm_activity_planner', 'ui.fonts.opensans', 'crm.autorun'));
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/utils.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');

?><div id="rebuildMessageWrapper"><?

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):
	?><div id="rebuildInvoiceSearchWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_INVOICE_ATTRS']):
	?><div id="rebuildInvoiceAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_INVOICE_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildInvoiceAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;

if($arResult['NEED_FOR_TRANSFER_PS_REQUISITES']):
	?><div id="transferPSRequisitesMsg" class="crm-view-message">
	<?=Bitrix\Crm\Requisite\Conversion\PSRequisiteConverter::getIntroMessage(
		array(
			'EXEC_ID' => 'transferPSRequisitesLink', 'EXEC_URL' => '#',
			'SKIP_ID' => 'skipTransferPSRequisitesLink', 'SKIP_URL' => '#'
		)
	)?>
	</div><?
endif;

if($arResult['NEED_FOR_REFRESH_ACCOUNTING']):
	?><div id="refreshInvoiceAccountingWrapper"></div><?
endif;

?></div><?

$isRecurring = isset($arParams['IS_RECURRING']) && $arParams['IS_RECURRING'] === 'Y';

$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'];
$allowDelete = $arResult['PERMS']['DELETE'];
$currentUserID = $arResult['CURRENT_USER_ID'];
$enableToolbar = ($arResult['ENABLE_TOOLBAR'] === 'Y') ? true : false;

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'INVOICE',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
);
echo CCrmViewHelper::RenderInvoiceStatusSettings();

$prefix = $arResult['GRID_ID'];

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$now = time() + CTimeZone::GetOffset();
$arInvoiceStatusInfoValues = array();
foreach($arResult['INVOICE'] as $sKey =>  $arInvoice)
{
	$arInvoiceStatusInfoValues[$arInvoice['~ID']] = array(
		'PAY_VOUCHER_DATE' => ($arInvoice['~PAY_VOUCHER_DATE'] != '') ? FormatDate('SHORT', MakeTimeStamp($arInvoice['~PAY_VOUCHER_DATE'])) : '',
		'PAY_VOUCHER_NUM' => ($arInvoice['~PAY_VOUCHER_NUM'] != '') ? $arInvoice['~PAY_VOUCHER_NUM'] : '',
		'DATE_MARKED' => ($arInvoice['~DATE_MARKED'] != '') ? FormatDate('SHORT', MakeTimeStamp($arInvoice['~DATE_MARKED'])) : '',
		'REASON_MARKED' => ($arInvoice['~REASON_MARKED'] != '') ? $arInvoice['~REASON_MARKED'] : ''
	);

	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_INVOICE_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_INVOICE_SHOW'),
		'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arInvoice['PATH_TO_INVOICE_SHOW'])."');",
		'DEFAULT' => true
	);

	if($arInvoice['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_INVOICE_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_EDIT'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arInvoice['PATH_TO_INVOICE_EDIT'])."');"
		);

		if(!$isRecurring)
		{
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_INVOICE_COPY_TITLE'),
				'TEXT' => GetMessage('CRM_INVOICE_COPY'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arInvoice['PATH_TO_INVOICE_COPY'])."');"
			);
		}
	}

	if (!$isInternal && $arInvoice['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arInvoice['PATH_TO_INVOICE_DELETE']);
		$arActions[] =  array(
			'TITLE' => GetMessage('CRM_INVOICE_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}', 
				BX.CrmUIGridMenuCommand.remove, 
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	$arActions[] = array('SEPARATOR' => true);

	if (!$isRecurring)
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_INVOICE_PAYMENT_HTML_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_PAYMENT_HTML'),
			'ONCLICK' => "jsUtils.OpenWindow('".CUtil::JSEscape(CHTTP::urlAddParams(
					$arInvoice['PATH_TO_INVOICE_PAYMENT'],
					array('PRINT' => 'Y', 'ncc' => '1')
				))."', 960, 600);"
		);
	}
	else
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_INVOICE_INTERNAL_ADD_BTN_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_INTERNAL_ADD_BTN_TITLE'),
			'ONCLICK' => "window.open('".CUtil::JSEscape(CHTTP::urlAddParams(
					$arInvoice['PATH_TO_INVOICE_EDIT'],
					array('expose' => 'Y')
				))."');"
		);
	}

	if (!$isRecurring && is_callable(array('CSalePdf', 'isPdfAvailable')) && CSalePdf::isPdfAvailable())
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_INVOICE_PAYMENT_PDF_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_PAYMENT_PDF'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape(CHTTP::urlAddParams(
				$arInvoice['PATH_TO_INVOICE_PAYMENT'],
				array('pdf' => 1, 'DOWNLOAD' => 'Y', 'ncc' => '1')
			))."');"
		);
	}

	$eventParam = array(
		'ID' => $arInvoice['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);
	foreach(GetModuleEvents('crm', 'onCrmInvoiceListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_INVOICE_LIST_MENU', $eventParam, &$arActions));
	}

	$resultRow = array(
		'id' => $arInvoice['ID'],
		'actions' => $arActions,
		'data' => $arInvoice,
		'editable' => !$arInvoice['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'ACCOUNT_NUMBER' => '<a target="_top" href="'.$arInvoice['PATH_TO_INVOICE_SHOW'].'">'.$arInvoice['ACCOUNT_NUMBER'].'</a>',
			'STATUS_ID' => CCrmViewHelper::RenderInvoiceStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arInvoice['~ID'],
					'CURRENT_ID' => $arInvoice['~STATUS_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.invoice.list/list.ajax.php',
					'READ_ONLY' => !(isset($arInvoice['EDIT']) && $arInvoice['EDIT'] === true)
				)
			),
			'RESPONSIBLE_ID' => $arInvoice['~RESPONSIBLE_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "QUOTE_{$arInvoice['ID']}_RESPONSIBLE",
						'USER_ID' => $arInvoice['~RESPONSIBLE_ID'],
						'USER_NAME'=> $arInvoice['RESPONSIBLE'],
						'USER_PROFILE_URL' => $arInvoice['PATH_TO_USER_PROFILE']
					)
				) : '',
			'DATE_PAY_BEFORE' => ($arInvoice['DATE_PAY_BEFORE'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_PAY_BEFORE'])).'</nobr>',
			'DATE_INSERT' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arInvoice['DATE_INSERT']), $now),
			'DATE_BILL' => ($arInvoice['DATE_BILL'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_BILL'])).'</nobr>',
			'DATE_MARKED' => ($arInvoice['DATE_MARKED'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_MARKED'])).'</nobr>',
			'DATE_STATUS' => ($arInvoice['DATE_STATUS'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_STATUS'])).'</nobr>',
			'DATE_UPDATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arInvoice['DATE_UPDATE']), $now),
			'UF_MYCOMPANY_ID' => ($arInvoice['MYCOMPANY_LINK_HTML'] == "") ? '&nbsp' : $arInvoice['MYCOMPANY_LINK_HTML'],
			'PAY_VOUCHER_DATE' => ($arInvoice['PAY_VOUCHER_DATE'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['PAY_VOUCHER_DATE'])).'</nobr>',
			'PRICE' => $arInvoice['FORMATTED_PRICE'],
			'TAX_VALUE' => $arInvoice['FORMATTED_TAX_VALUE'],
			'CURRENCY' => htmlspecialcharsbx(CCrmCurrency::GetCurrencyName($arInvoice['CURRENCY'])),
			'ENTITIES_LINKS' => $arInvoice['FORMATTED_ENTITIES_LINKS'],
			'PERSON_TYPE_ID' => trim($arResult['PERSON_TYPES'][$arInvoice['PERSON_TYPE_ID']]),
			'PAY_SYSTEM_ID' => trim($arResult['PAY_SYSTEMS_LIST'][$arInvoice['PERSON_TYPE_ID']][$arInvoice['PAY_SYSTEM_ID']]),
			'COMMENTS' => $arInvoice['~COMMENTS'],
			'USER_DESCRIPTION' => $arInvoice['~USER_DESCRIPTION']
		) + $arResult['INVOICE_UF'][$sKey]
	);
	if ($arInvoice['INVOICE_IN_COUNTER_FLAG'] === true)
	{
		if ($resultRow['columnClasses']['DATE_PAY_BEFORE'] != '')
			$resultRow['columnClasses']['DATE_PAY_BEFORE'] .= ' ';
		else
			$resultRow['columnClasses']['DATE_PAY_BEFORE'] = '';
		$resultRow['columnClasses']['DATE_PAY_BEFORE'] .= 'crm-list-invoice-today';
	}
	if ($arInvoice['INVOICE_EXPIRED_FLAG'] === true)
	{
		if ($resultRow['columnClasses']['DATE_PAY_BEFORE'] != '')
			$resultRow['columnClasses']['DATE_PAY_BEFORE'] .= ' ';
		else
			$resultRow['columnClasses']['DATE_PAY_BEFORE'] = '';
		$resultRow['columnClasses']['DATE_PAY_BEFORE'] .= 'crm-list-invoice-time-expired';
	}

	$arResult['GRID_DATA'][] = &$resultRow;
	unset($resultRow);
}
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'AJAX_ONLY' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y')
);

if($enableToolbar)
{
	$entityId = 0;
	$entityType = '';
	if (is_array($arParams['INTERNAL_FILTER']))
	{
		$internalFilter = $arParams['INTERNAL_FILTER'];
		if (isset($internalFilter['UF_QUOTE_ID']))
		{
			$entityId = (int)$internalFilter['UF_QUOTE_ID'];
			$entityType = 'quote';
		}
		elseif (isset($internalFilter['UF_DEAL_ID']))
		{
			$entityId = (int)$internalFilter['UF_DEAL_ID'];
			$entityType = 'deal';
		}
		elseif (isset($internalFilter['UF_COMPANY_ID']))
		{
			$entityId = (int)$internalFilter['UF_COMPANY_ID'];
			$entityType = 'company';
		}
		elseif (isset($internalFilter['UF_CONTACT_ID']))
		{
			$entityId = (int)$internalFilter['UF_CONTACT_ID'];
			$entityType = 'contact';
		}
		unset($internalFilter);
	}

	$toolbarButtons = array();
	if ($entityType !== '')
	{
		$addButton =array(
			'TEXT' => GetMessage('CRM_INVOICE_LIST_ADD_SHORT'),
			'TITLE' => $arResult['INTERNAL_ADD_BTN_TITLE'],
			'ICON' => 'btn-new crm-invoice-command-add-invoice'
		);

		if ($entityId > 0)
		{
			$addButton['LINK'] = CCrmUrlUtil::AddUrlParams(
				CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_INVOICE_EDIT'],
					array('invoice_id' => 0)
				),
				array($entityType => $entityId)
			);
		}

		if($arResult['ADD_EVENT_NAME'] !== '')
		{
			$addButton['ONCLICK'] = "BX.onCustomEvent(window, '{$arResult['ADD_EVENT_NAME']}')";
		}

		$toolbarButtons[] = $addButton;
	}

	if (is_string($arResult['TOOLBAR_LABEL_TEXT']) && $arResult['TOOLBAR_LABEL_TEXT'] !== '')
	{
		$toolbarButtons[] = array(
			'LABEL' => true,
			'TEXT' => $arResult['TOOLBAR_LABEL_TEXT']
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => $arResult['GRID_ID'].'_inv_tb',
			'BUTTONS' => $toolbarButtons
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

if ($arResult['IS_AJAX_CALL'])
{
	$GLOBALS['OnCrmCrmInvoiceListAfterAjaxHandlerParams']['arInvoiceStatusInfoValues'] = $arInvoiceStatusInfoValues;
	function OnCrmCrmInvoiceListAfterAjaxHandler()
	{
		?>
		<script>
			BX.ready(function(){
				if (typeof(BX.CrmInvoiceStatusManager) === 'function')
				{
					BX.CrmInvoiceStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($GLOBALS['OnCrmCrmInvoiceListAfterAjaxHandlerParams']['arInvoiceStatusInfoValues']) ?>;
				}
			});
		</script><?

		return '';
	}
	AddEventHandler('main', 'OnAfterAjaxResponse', 'OnCrmCrmInvoiceListAfterAjaxHandler');
}

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

	$actionList = array(array('NAME' => GetMessage('CRM_INVOICE_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	if($allowWrite && $arParams['IS_RECURRING'] !== "Y")
	{
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
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}
		//endregion
		$actionList[] = array(
			'NAME' => GetMessage('CRM_INVOICE_ASSIGN_TO'),
			'VALUE' => 'assign_to',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_assigned_by_search',
							'NAME' => 'ACTION_ASSIGNED_BY_SEARCH'
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_assigned_by_id',
							'NAME' => 'ACTION_ASSIGNED_BY_ID'
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array('JS' => "BX.CrmUIGridExtension.prepareAction('{$gridManagerID}', 'assign_to',  { searchInputId: 'action_assigned_by_search_control', dataInputId: 'action_assigned_by_id_control', componentName: '{$prefix}_ACTION_ASSIGNED_BY' })")
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'assign_to')"))
				)
			)
		);
		//endregion

		//region Refresh Accounting Data
		$actionList[] = array(
			'NAME' => GetMessage('CRM_INVOICE_REFRESH_ACCOUNT'),
			'VALUE' => 'refresh_account',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				)
			)

		);
		//endregion
	}

	if($allowDelete)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
	}
	if($allowWrite)
	{
		//region Edit Button
		$actionManager = new ActionManager($gridManagerID);
		$controlPanel['GROUPS'][0]['ITEMS'][] = $actionManager->getEditButton();
		//endregion
	}

	if($callListUpdateMode)
	{
		$callListContext = \CUtil::jsEscape($arResult['CALL_LIST_CONTEXT']);
		$controlPanel['GROUPS'][0]['ITEMS'][] = [
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_INVOICE_UPDATE_CALL_LIST"),
			"ID" => "update_call_list",
			"NAME" => "update_call_list",
			'ONCHANGE' => [
				[
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => [['JS' => "BX.CrmUIGridExtension.updateCallList('{$gridManagerID}', {$arResult['CALL_LIST_ID']}, '{$callListContext}')"]]
				]
			]
		];
	}
	else
	{
		//region Create & start call list
		if(IsModuleInstalled('voximplant'))
		{
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => GetMessage('CRM_INVOICE_START_CALL_LIST'),
				"VALUE" => "start_call_list",
				"ONCHANGE" => array(
					array(
						"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						"DATA" => array(array('JS' => "BX.CrmUIGridExtension.createCallList('{$gridManagerID}', true)"))
					)
				)
			);
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => GetMessage('CRM_INVOICE_CREATE_CALL_LIST'),
				"VALUE" => "create_call_list",
				"ONCHANGE" => array(
					array(
						"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						"DATA" => array(array('JS' => "BX.CrmUIGridExtension.createCallList('{$gridManagerID}', false)"))
					)
				)
			);
		}
	}

	$controlPanel['GROUPS'][0]['ITEMS'][] = array(
		"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
		"ID" => "action_button_{$prefix}",
		"NAME" => "action_button_{$prefix}",
		"ITEMS" => $actionList
	);

	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
}

$filterLazyLoadUrl = '/bitrix/components/bitrix/crm.invoice.list/filter.ajax.php?' . bitrix_sessid_get();
$filterLazyLoadParams = [
	'filter_id' => urlencode($arResult['GRID_ID']),
	'is_recurring' => $isRecurring ? 'Y' : 'N',
	'siteID' => SITE_ID,
];
$uri = new Uri($filterLazyLoadUrl);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'ENABLE_FIELDS_SEARCH' => 'Y',
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => array(
			'LAZY_LOAD' => [
				'GET_LIST' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'list']))->getUri(),
				'GET_FIELD' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'field']))->getUri(),
				'GET_FIELDS' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'fields']))->getUri(),
			],
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'CONFIG' => [
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
		),
		'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)(
			$arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? \Bitrix\Main\ModuleManager::isModuleInstalled('ui')
		),
		'LIVE_SEARCH_LIMIT_INFO' => isset($arResult['LIVE_SEARCH_LIMIT_INFO'])
			? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
		'ENABLE_LIVE_SEARCH' => true,
		'DISABLE_NAVIGATION_BAR' => $arResult['DISABLE_NAVIGATION_BAR'],
		'ACTION_PANEL' => $controlPanel,
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION']
			: [],
		'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Invoice))
			->setItems([
				NavigationBarPanel::ID_KANBAN,
				NavigationBarPanel::ID_LIST
			], NavigationBarPanel::ID_LIST)
			->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
			->get(),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::InvoiceName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => '',
				'activityServiceUrl' => '',
				'taskCreateUrl'=> '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.invoice.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_INVOICE_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_INVOICE_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_INVOICE_DELETE')
			)
		)
	),
	$component
);

if (!$arResult['IS_AJAX_CALL'])
{
	?>
	<script>
		BX.ready(function ()
		{
			if (typeof(BX.CrmInvoiceStatusManager) === 'function')
			{
				BX.CrmInvoiceStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($arInvoiceStatusInfoValues) ?>;
			}
		});
	</script>
	<?
}

?>
<script>
	BX.ready(function()
	{
		BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_INVOICE_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_INVOICE_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_INVOICE_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_INVOICE_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_INVOICE_LRP_DLG_REQUEST_ERR')?>"
			};
	});
</script><?

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
<script>
	BX.ready(
		function()
		{
			if(BX.AutorunProcessPanel.isExists("rebuildInvoiceSearch"))
			{
				return;
			}

			BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_INVOICE_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
				};
			var manager = BX.AutorunProcessManager.create(
				"rebuildInvoiceSearch",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.invoice.list/list.ajax.php?'.bitrix_sessid_get()?>",
					actionName: "REBUILD_SEARCH_CONTENT",
					container: "rebuildInvoiceSearchWrapper",
					enableLayout: true
				}
			);
			manager.runAfter(100);
		}
	);
</script><?
endif;

if($arResult['NEED_FOR_REBUILD_INVOICE_ATTRS'])
{
?>
<script>
	BX.ready(
		function()
		{
			var link = BX("rebuildInvoiceAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildInvoiceAttrsMsg");
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
<?
}
if($arResult['NEED_FOR_TRANSFER_PS_REQUISITES'])
{
?><script>
	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_PSRQ_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_PSRQ_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_PSRQ_LRP_DLG_BTN_CLOSE')?>",
				requestError: "<?=GetMessageJS('CRM_PSRQ_LRP_DLG_REQUEST_ERR')?>"
			};

			BX.CrmPSRequisiteConverter.messages =
			{
				processDialogTitle: "<?=GetMessageJS('CRM_PS_RQ_TX_PROC_DLG_TITLE')?>",
				processDialogSummary: "<?=GetMessageJS('CRM_PS_RQ_TX_PROC_DLG_DLG_SUMMARY1')?>"
			};

			var converter = BX.CrmPSRequisiteConverter.create(
				"psRqConverter",
				{
					serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.config.ps.list/list.ajax.php?&<?=bitrix_sessid_get()?>"
				}
			);

			BX.addCustomEvent(
				converter,
				'ON_PS_REQUISITE_TRANFER_COMPLETE',
				function()
				{
					var msg = BX("transferPSRequisitesMsg");
					if(msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var transferLink = BX("transferPSRequisitesLink");
			if(transferLink)
			{
				BX.bind(
					transferLink,
					"click",
					function(e)
					{
						converter.convert();
						return BX.PreventDefault(e);
					}
				);
			}

			var skipTransferLink = BX("skipTransferPSRequisitesLink");
			if(skipTransferLink)
			{
				BX.bind(
					skipTransferLink,
					"click",
					function(e)
					{
						converter.skip();

						var msg = BX("transferPSRequisitesMsg");
						if(msg)
						{
							msg.style.display = "none";
						}

						return BX.PreventDefault(e);
					}
				);
			}
		}
	);
</script>
<?
}
if($arResult['NEED_FOR_REFRESH_ACCOUNTING']):?>
	<script>
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("refreshInvoiceAccounting"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_INVOICE_REFRESH_ACCOUNTING_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_INVOICE_STEPWISE_STATE_TEMPLATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("refreshInvoiceAccounting",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.invoice.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REFRESH_ACCOUNTING",
						container: "refreshInvoiceAccountingWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
