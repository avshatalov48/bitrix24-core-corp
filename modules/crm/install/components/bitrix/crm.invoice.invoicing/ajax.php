<?php

use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('sale'))
	die();

global $APPLICATION;

$instance = Application::getInstance();
$context = $instance->getContext();
$request = $context->getRequest();

CBitrixComponent::includeComponentClass("bitrix:crm.invoice.invoicing");
$orderPayment = new CrmInvoiceInvoicingComponent();

$result = array();
if (check_bitrix_sessid() && $request->isPost() && $request->isAjaxRequest())
{
	$service = null;

	if ($request->get('PAY_SYSTEM_ID') === null)
	{
		$paySystemList = $orderPayment->getPaySystemList();
		if ($paySystemList)
		{
			$paySystemId = key($paySystemList);
			if ($paySystemId > 0)
				$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($paySystemId);
		}
	}
	else
	{
		$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($request->get('PAY_SYSTEM_ID'));
	}

	if ($service !== null)
	{
		$serviceResult = $service->processAccountMovementList($request);
		if ($serviceResult->isSuccess())
		{
			$data = $serviceResult->getData();

			if ($data['requestId'])
				$result['REQUEST_ID'] = $data['requestId'];

			if (array_key_exists('timeSleep', $data))
			{
				$result['TIME'] = $data['timeSleep'];
			}
			else
			{
				$rows = array();
				if ($data['PAYMENT_LIST'])
				{
					foreach ($data['PAYMENT_LIST'] as $item)
					{
						if ($request->get('MODE') !== 'F')
						{
							if ($request->get('MODE') === 'M' && ($item['PAID_BEFORE'] === 'Y' || $item['ACCOUNT_NUMBER'] === ''))
								continue;
							if ($request->get('MODE') === 'MN' && ($item['PAID_BEFORE'] === 'N' || $item['ACCOUNT_NUMBER'] === ''))
								continue;
							if ($request->get('MODE') === 'U' && $item['ACCOUNT_NUMBER'] !== '')
								continue;
						}
						$rows[] = array(
							'id' => $item['ID'],
							'actions' => array(),
							'data' => $item,
							'columns' => array(
								'ACCOUNT_NUMBER' => ($item['ACCOUNT_NUMBER'] !== '') ? '<a href="/crm/invoice/show/'.$item['ORDER_ID'].'/">'.$item['ACCOUNT_NUMBER'].'</a>' : '',
								'PRICE' => number_format($item['PRICE'], 2, '.', ' '),
								'CONTRACTOR_INN' => $item['CONTRACTOR_INN'],
								'CONTRACTOR_KPP' => $item['CONTRACTOR_KPP'],
								'DOC_NUMBER' => $item['DOC_NUMBER'],
								'CHARGE_DATE' => $item['CHARGE_DATE']
							)
						);
					}

					ob_start();
					$APPLICATION->IncludeComponent(
						'bitrix:crm.interface.grid',
						'',
						array(
							'GRID_ID' => 'invoicing_grid	',
							'HEADERS' => $orderPayment->getHeaders(),
							'ROWS' => $rows,
							'FORM_ID' => 'invoicing_list'
						)
					);
					$result['GRID'] = ob_get_contents();
					ob_end_clean();
				}
			}
		}
		else
		{
			$result['ERRORS'] = implode("\n", $serviceResult->getErrorMessages());
		}
	}
}

echo CUtil::PhpToJSObject($result);
$APPLICATION::FinalActions();
die();