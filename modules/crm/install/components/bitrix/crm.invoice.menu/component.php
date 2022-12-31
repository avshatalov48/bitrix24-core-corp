<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Recurring;;
use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponent $component
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 */

if (!CModule::IncludeModule('crm'))
	return;

\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

$CrmPerms = new CCrmPerms($USER->GetID());
if ($CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_INVOICE_LIST'] = CrmCheckPath('PATH_TO_INVOICE_LIST', $arParams['PATH_TO_INVOICE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_INVOICE_RECUR'] = CrmCheckPath('PATH_TO_INVOICE_RECUR', $arParams['PATH_TO_INVOICE_RECUR'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_INVOICE_RECUR_SHOW'] = CrmCheckPath('PATH_TO_INVOICE_RECUR_SHOW', $arParams['PATH_TO_INVOICE_RECUR_SHOW'], $arParams['PATH_TO_INVOICE_RECUR'].'?invoice_id=#invoice_id#&show');
$arParams['PATH_TO_INVOICE_RECUR_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_RECUR_EDIT', $arParams['PATH_TO_INVOICE_RECUR_EDIT'], $arParams['PATH_TO_INVOICE_RECUR'].'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_INVOICE_RECUR_EXPOSE'] = CrmCheckPath('PATH_TO_INVOICE_RECUR_EXPOSE', $arParams['PATH_TO_INVOICE_RECUR_EXPOSE'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit&recur&expose=Y');
$arParams['PATH_TO_INVOICE_SHOW'] = CrmCheckPath('PATH_TO_INVOICE_SHOW', $arParams['PATH_TO_INVOICE_SHOW'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&show');
$arParams['PATH_TO_INVOICE_PAYMENT'] = CrmCheckPath('PATH_TO_INVOICE_PAYMENT', $arParams['PATH_TO_INVOICE_PAYMENT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&payment');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_INVOICE_WIDGET'] = CrmCheckPath('PATH_TO_INVOICE_WIDGET', $arParams['PATH_TO_INVOICE_WIDGET'], $APPLICATION->GetCurPage()."?widget");
$arParams['PATH_TO_INVOICE_KANBAN'] = CrmCheckPath('PATH_TO_INVOICE_KANBAN', $arParams['PATH_TO_INVOICE_KANBAN'], $APPLICATION->GetCurPage()."?kanban");

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$arResult['TYPE'] = $arParams['TYPE'];

$arResult['BUTTONS'] = array();
$arFields = array();

$arParams['ELEMENT_ID'] = intval($arParams['ELEMENT_ID']);

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'IMPORT') && $arParams['IS_RECURRING'] !== 'Y';
	$bAdd    = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$arFields = CCrmInvoice::GetByID($arParams['ELEMENT_ID']);

	$arEntityAttr[$arParams['ELEMENT_ID']] = array();
	if ($arFields !== false)
		$arEntityAttr = $CrmPerms->GetEntityAttr('INVOICE', array($arParams['ELEMENT_ID']));

	$bRead   = $arFields !== false;
	$bExport = false;
	$bImport = false;
	$bAdd    = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = $CrmPerms->CheckEnityAccess('INVOICE', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]);
	$bDelete = $CrmPerms->CheckEnityAccess('INVOICE', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]);
}

if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] == 'Y')
{
	$bExport = false;
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$addLink = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
			array(
				'invoice_id' => 0
			)
		);
		$addButton  = [
			'TEXT' => GetMessage('CRM_COMMON_ACTION_CREATE'),
			'LINK' => $addLink,
			'HIGHLIGHT' => true
		];

		if ($arParams['IS_RECURRING'] === 'Y')
		{
			$addButton['ONCLICK'] = "top.location.href = '{$addLink}'";
		}

		$arResult['BUTTONS'][] = $addButton;
	}

		if ($bImport)
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('INVOICE_IMPORT'),
				'TITLE' => GetMessage('INVOICE_IMPORT_TITLE'),
				'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_IMPORT'], array()),
				'ICON' => 'btn-import'
			);
		}

		if ($bExport)
		{
			/*
			if($bImport)
			{
				$arResult['BUTTONS'][] = array('SEPARATOR' => true);
			}
			*/

			$entityType = \CCrmOwnerType::InvoiceName;
			$stExportId = 'EXPORT_'.$entityType;
			$componentName = 'bitrix:crm.invoice.list';

			$componentParams = array(
				'INVOICE_COUNT' => '20',
				'IS_RECURRING' => $arParams['IS_RECURRING'],
				'PATH_TO_INVOICE_SHOW' => $arResult['PATH_TO_INVOICE_SHOW'],
				'PATH_TO_INVOICE_RECUR_SHOW' => $arResult['PATH_TO_INVOICE_RECUR_SHOW'],
				'PATH_TO_INVOICE_RECUR' => $arResult['PATH_TO_INVOICE_RECUR'],
				'PATH_TO_INVOICE_RECUR_EDIT' => $arResult['PATH_TO_INVOICE_RECUR_EDIT'],
				'PATH_TO_INVOICE_EDIT' => $arResult['PATH_TO_INVOICE_EDIT'],
				'PATH_TO_INVOICE_PAYMENT' => $arResult['PATH_TO_INVOICE_PAYMENT'],
				'PATH_TO_INVOICE_WIDGET' => $arResult['PATH_TO_INVOICE_WIDGET'],
				'PATH_TO_INVOICE_KANBAN' => $arResult['PATH_TO_INVOICE_KANBAN'],
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'NAVIGATION_CONTEXT_ID' => $entityType
			);
			if (isset($_REQUEST['WG']) && mb_strtoupper($_REQUEST['WG']) === 'Y')
			{
				$widgetDataFilter = \Bitrix\Crm\Widget\Data\InvoiceDataSource::extractDetailsPageUrlParams($_REQUEST);
				if (!empty($widgetDataFilter))
				{
					$componentParams['WIDGET_DATA_FILTER'] = $widgetDataFilter;
				}
			}

			$arResult['EXPORT_CSV_PARAMS'] = [
				'id' => $stExportId. '_CSV',
				'controller' => 'bitrix:crm.api.export',
				'queue' => [
					[
						'action' => 'dispatcher',
					],
				],
				'params' => [
					'SITE_ID' => SITE_ID,
					'ENTITY_TYPE' => $entityType,
					'EXPORT_TYPE' => 'csv',
					'COMPONENT_NAME' => $componentName,
					'signedParameters' => \Bitrix\Main\Component\ParameterSigner::signParameters(
						$componentName,
						$componentParams
					),
				],
				'optionsFields' => array(
					'EXPORT_ALL_FIELDS' => array(
						'name' => 'EXPORT_ALL_FIELDS',
						'type' => 'checkbox',
						'title' => Loc::getMessage('INVOICE_STEXPORT_OPTION_EXPORT_ALL_FIELDS'),
						'value' => 'N'
					),
				),
				'messages' => array(
					'DialogTitle' => Loc::getMessage('INVOICE_EXPORT_CSV_TITLE'),
					'DialogSummary' => Loc::getMessage('INVOICE_STEXPORT_SUMMARY'),
				),
				'dialogMaxWidth' => 650,
			];

			// clone params for excel export
			$arResult['EXPORT_EXCEL_PARAMS'] = $arResult['EXPORT_CSV_PARAMS'];
			$arResult['EXPORT_EXCEL_PARAMS']['id'] = $stExportId. '_EXCEL';
			$arResult['EXPORT_EXCEL_PARAMS']['params']['EXPORT_TYPE'] = 'excel';
			$arResult['EXPORT_EXCEL_PARAMS']['messages']['DialogTitle'] = Loc::getMessage('INVOICE_EXPORT_EXCEL_TITLE');

			$arResult['BUTTONS'][] = array('SEPARATOR' => true);

			$arResult['BUTTONS'][] = array(
				'TITLE' => Loc::getMessage('INVOICE_EXPORT_CSV_TITLE'),
				'TEXT' => Loc::getMessage('INVOICE_EXPORT_CSV'),
				'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_CSV').showDialog()",
				'ICON' => 'btn-export'
			);

			$arResult['BUTTONS'][] = array(
				'TITLE' => Loc::getMessage('INVOICE_EXPORT_EXCEL_TITLE'),
				'TEXT' => Loc::getMessage('INVOICE_EXPORT_EXCEL'),
				'ONCLICK' => "BX.UI.StepProcessing.ProcessManager.get('{$stExportId}_EXCEL').showDialog()",
				'ICON' => 'btn-export'
			);

			$arResult['BUTTONS'][] = array('SEPARATOR' => true);


			unset($entityType, $stExportId, $randomSequence, $stExportManagerId);
		}

		if (
			Recurring\Manager::isAllowedExpose(Recurring\Manager::INVOICE)
			&& !($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER')
		)
		{
			if ($arParams['IS_RECURRING'] === 'Y')
			{
				$text = GetMessage('INVOICE_LIST');
				$link = $arParams['PATH_TO_INVOICE_LIST'];
			}
			else
			{
				$text = GetMessage('INVOICE_CRM_RECURRING_LIST');
				$link = $arParams['PATH_TO_INVOICE_RECUR'];
			}

			$arResult['BUTTONS'][] = array(
				'TEXT' => $text,
				'TITLE' => $text,
				'ONCLICK' => 'BX.Crm.Page.openSlider("'.$link.'");'
			);
		}

		if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			$smartInvoiceTitle = \CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::SmartInvoice);
			$arResult['BUTTONS'][] = [
				'TEXT' => $smartInvoiceTitle,
				'TITLE' => $smartInvoiceTitle,
				'LINK' => \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemListUrlInCurrentView(\CCrmOwnerType::SmartInvoice),
			];
		}

		if(count($arResult['BUTTONS']) > 1)
		{
			//Force start new bar after first button
			array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
		}

	$this->IncludeComponentTemplate();
	return;
}

if ($arParams['TYPE'] == 'show' && !empty($arParams['ELEMENT_ID']) && $arParams['IS_RECURRING'] !== 'Y')
{
	\CJSCore::init(["sidepanel", "documentpreview"]);

	if(\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
	{
		$arResult['BUTTONS'][] = [
			'CODE' => 'document',
			'TEXT' => GetMessage('INVOICE_DOCUMENT_BUTTON_TEXT'),
			'TITLE' => GetMessage('INVOICE_DOCUMENT_BUTTON_TITLE'),
			'TYPE' => 'crm-document-button',
			'PARAMS' => \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Invoice::class, $arParams['ELEMENT_ID']),
		];
	}
	$menuItems = [];
	$paySystem = \Bitrix\Sale\PaySystem\Manager::getById($arFields['PAY_SYSTEM_ID']);
	if (\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isEnabled() && $paySystem['ACTION_FILE'] === 'invoicedocument')
	{
		$componentPath = \CComponentEngine::makeComponentPath('bitrix:crm.document.view');
		$componentPath = getLocalPath('components'.$componentPath.'/slider.php');

		$params = [
			'templateId' => $paySystem['PS_MODE'],
			'providerClassName' => \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Invoice::class,
			'value' => $arParams['ELEMENT_ID']
		];

		$res = Bitrix\DocumentGenerator\Model\DocumentTable::getList([
			'select' => ['ID', 'UPDATE_TIME'],
			'filter' => [
				'=PROVIDER' => Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Invoice::class,
				'=VALUE' => $arParams['ELEMENT_ID'],
				'=TEMPLATE_ID' => $paySystem['PS_MODE'],
			],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		]);

		if ($data = $res->fetch())
		{
			$params['documentId'] = $data['ID'];
			$params['sessid'] = bitrix_sessid();
			$params['mode'] = 'change';
		}

		$uri = new \Bitrix\Main\Web\Uri($componentPath);
		$href = $uri->addParams($params)->getLocator();

		$menuItems[] = [
			'text' => GetMessage('INVOICE_PAYMENT_HTML'),
			'title' => GetMessage('INVOICE_PAYMENT_HTML_TITLE'),
			'onclick' => 'BX.DocumentGenerator.Document.onBeforeCreate(\''
				. \CUtil::JSEscape($href).'\', '
				. \CUtil::PhpToJSObject(['checkNumber' => true]) . ', '
				. 'null, '
				. '\'crm\''
				.');'
		];

		unset($componentPath, $params, $res, $data, $uri, $href);
	}
	elseif(mb_strpos($paySystem['ACTION_FILE'], 'bill') !== false)
	{
		$menuItems[] = [
			'text' => GetMessage('INVOICE_PAYMENT_HTML'),
			'title' => GetMessage('INVOICE_PAYMENT_HTML_TITLE'),
			'onclick' => "var menu = BX.PopupMenu.getCurrentMenu(); ".
				"if(menu && menu.popupWindow) { menu.popupWindow.close(); } ".
				"jsUtils.OpenWindow('".CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_INVOICE_PAYMENT'],
						array('invoice_id' => $arParams['ELEMENT_ID'])
					),
					array('PRINT' => 'Y', 'ncc' => '1'))."', 960, 600)"
		];
		$menuItems[] = [
			'text' => GetMessage('INVOICE_PAYMENT_HTML_BLANK'),
			'title' => GetMessage('INVOICE_PAYMENT_HTML_BLANK_TITLE'),
			'onclick' => "var menu = BX.PopupMenu.getCurrentMenu(); ".
				"if(menu && menu.popupWindow) { menu.popupWindow.close(); } ".
				"jsUtils.OpenWindow('".CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_INVOICE_PAYMENT'],
						array('invoice_id' => $arParams['ELEMENT_ID'])
					),
					array('PRINT' => 'Y', 'BLANK' => 'Y', 'ncc' => '1'))."', 960, 600)"
		];

		if (is_callable(array('CSalePdf', 'isPdfAvailable')) && CSalePdf::isPdfAvailable())
		{
			$menuItems[] = [
				'text' => GetMessage('INVOICE_PAYMENT_PDF'),
				'title' => GetMessage('INVOICE_PAYMENT_PDF_TITLE'),
				'onclick' => "var menu = BX.PopupMenu.getCurrentMenu(); ".
					"if(menu && menu.popupWindow) { menu.popupWindow.close(); } ".
					"jsUtils.Redirect(null, '".CHTTP::urlAddParams(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_INVOICE_PAYMENT'],
							array('invoice_id' => $arParams['ELEMENT_ID'])
						),
						array('pdf' => 1, 'DOWNLOAD' => 'Y', 'ncc' => '1'))."')"
			];
			$menuItems[] = [
				'text' => GetMessage('INVOICE_PAYMENT_PDF_BLANK'),
				'title' => GetMessage('INVOICE_PAYMENT_PDF_BLANK_TITLE'),
				'onclick' => "var menu = BX.PopupMenu.getCurrentMenu(); ".
					"if(menu && menu.popupWindow) { menu.popupWindow.close(); } ".
					"jsUtils.Redirect(null, '".CHTTP::urlAddParams(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_INVOICE_PAYMENT'],
							array('invoice_id' => $arParams['ELEMENT_ID'])
						),
						array('pdf' => 1, 'DOWNLOAD' => 'Y', 'BLANK' => 'Y', 'ncc' => '1'))."')"
			];
			$menuItems[] = [
				'text' => GetMessage('INVOICE_PAYMENT_EMAIL'),
				'title' => GetMessage('INVOICE_PAYMENT_EMAIL_TITLE'),
				'onclick' => "var menu = BX.PopupMenu.getCurrentMenu(); ".
					"if(menu && menu.popupWindow) { menu.popupWindow.close(); } ".
					"onCrmInvoiceSendEmailButtClick()"
			];
		}
	}
	$menuItems[] = [
		'text' => GetMessage('INVOICE_PAYMENT_PUBLIC_LINK'),
		'title' => GetMessage('INVOICE_PAYMENT_PUBLIC_LINK_TITLE'),
		'onclick' => 'var menu = BX.PopupMenu.getCurrentMenu(); '.
			'if(menu && menu.popupWindow) { menu.popupWindow.close(); } '.
			'generateExternalLink(BX("crm_invoice_toolbar_leftMenu"))'
	];
	if (!empty($menuItems))
	{
		$arResult['BUTTONS'][] = [
			'CODE' => 'leftMenu',
			'TEXT' => GetMessage('INVOICE_LEFT_MENU_TEXT'),
			'TITLE' => GetMessage('INVOICE_LEFT_MENU_TITLE'),
			'TYPE' => 'toolbar-menu-left',
			'ITEMS' => $menuItems,
		];
	}
	unset($menuItems);

	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('INVOICE_EDIT'),
			'TITLE' => GetMessage('INVOICE_EDIT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
				array(
					'invoice_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-edit'
		);
	}
}
elseif ($arParams['TYPE'] == 'show' && $arParams['IS_RECURRING'] === 'Y' && $bWrite)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('INVOICE_EDIT'),
		'TITLE' => GetMessage('INVOICE_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_RECUR_EDIT'],
			array(
				'invoice_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if ($arParams['TYPE'] == 'show' && $arParams['IS_RECURRING'] === 'Y' && $bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('INVOICE_EXPOSE'),
		'TITLE' => GetMessage('INVOICE_EXPOSE_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_RECUR_EXPOSE'],
			array(
				'invoice_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-copy'
	);
}


if ($arParams['TYPE'] == 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$path = $arParams['IS_RECURRING'] === 'Y' ? $arParams['PATH_TO_INVOICE_RECUR_SHOW'] : $arParams['PATH_TO_INVOICE_SHOW'];
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('INVOICE_SHOW'),
		'TITLE' => GetMessage('INVOICE_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($path,
			array(
				'invoice_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']) && $arParams['IS_RECURRING'] !== 'Y')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('INVOICE_COPY'),
		'TITLE' => GetMessage('INVOICE_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
			array(
				'invoice_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{

	$path = $arParams['PATH_TO_INVOICE_EDIT'];
	if ($arParams['IS_RECURRING'] == 'Y')
	{
		$path = $arParams['PATH_TO_INVOICE_RECUR_EDIT'];
	}
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('INVOICE_DELETE'),
		'TITLE' => GetMessage('INVOICE_DELETE_TITLE'),
		'LINK' => "javascript:invoice_delete('".GetMessage('INVOICE_DELETE_DLG_TITLE')."', '".
			GetMessage('INVOICE_DELETE_DLG_MESSAGE')."', '".GetMessage('INVOICE_DELETE_DLG_BTNTITLE').
			"', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($path,
			array(
				'invoice_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

	// if ($bAdd)
	// {
	// 	$arResult['BUTTONS'][] = array(
	// 		'TEXT' => GetMessage('INVOICE_ADD'),
	// 		'TITLE' => GetMessage('INVOICE_ADD_TITLE'),
	// 		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
	// 			array(
	// 				'invoice_id' => 0
	// 			)
	// 		),
	// 		'ICON' => 'btn-new'
	// 	);
	// }

$this->IncludeComponentTemplate();
?>
