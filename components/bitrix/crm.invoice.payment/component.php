<?
use Bitrix\Sale;
use Bitrix\Crm\Invoice\Invoice;
use Bitrix\Crm\Invoice\Compatible;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

if (isset($arParams['HASH']))
{
	$invoice = Invoice::loadByAccountNumber($arParams['ORDER_ID']);
	if (!$invoice)
	{
		return;
	}
	$arParams['ORDER_ID'] = $invoice->getId();
	$paymentCollection = $invoice->loadPaymentCollection();
	/**	@var Sale\Payment $payment*/
	$payment = $paymentCollection->current();
	if ($arParams['HASH'] != $payment->getHash())
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}
}
else
{
	$CCrmInvoice = new CCrmInvoice();
	if ($CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}
}

global $APPLICATION;

$APPLICATION->RestartBuffer();

$isPublicLinkMode = (isset($arParams["PUBLIC_LINK_MODE"]) && $arParams["PUBLIC_LINK_MODE"] === 'Y');

$ORDER_ID = intval($arParams["ORDER_ID"]);

$dbOrder = Compatible\Helper::getList(
	array("DATE_UPDATE" => "DESC"),
	array(
		"LID" => SITE_ID,
		"ID" => $ORDER_ID
	),
	false,
	false,
	array('*', 'UF_DEAL_ID', 'UF_QUOTE_ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID', 'UF_MYCOMPANY_ID')
);

$arOrder = $dbOrder->GetNext();
$paymentData = is_array($arOrder) ?
	CCrmInvoice::PrepareSalePaymentData($arOrder, array('PUBLIC_LINK_MODE' => $isPublicLinkMode ? 'Y' : 'N')) : null;
if ($arOrder)
{
	if ($arOrder["SUM_PAID"] <> '')
		$arOrder["PRICE"] -= $arOrder["SUM_PAID"];

	$dbPaySysAction = CSalePaySystemAction::GetList(
		array(),
		array(
			"PAY_SYSTEM_ID" => $arOrder["PAY_SYSTEM_ID"],
			"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"]
		),
		false,
		false,
		array("ACTION_FILE", "PARAMS", "ENCODING")
	);

	if ($arPaySysAction = $dbPaySysAction->Fetch())
	{
		if ($arPaySysAction["ACTION_FILE"] <> '')
		{
			CSalePaySystemAction::InitParamArrays(
				$arOrder,
				$ID,
				$arPaySysAction["PARAMS"],
				[
					'REQUISITE' => is_array($paymentData['REQUISITE']) ? $paymentData['REQUISITE'] : null,
					'BANK_DETAIL' => is_array($paymentData['BANK_DETAIL']) ? $paymentData['BANK_DETAIL'] : null,
					'CRM_COMPANY' => is_array($paymentData['CRM_COMPANY']) ? $paymentData['CRM_COMPANY'] : null,
					'CRM_CONTACT' => is_array($paymentData['CRM_CONTACT']) ? $paymentData['CRM_CONTACT'] : null,
					'MC_REQUISITE' => is_array($paymentData['MC_REQUISITE']) ? $paymentData['MC_REQUISITE'] : null,
					'MC_BANK_DETAIL' => is_array($paymentData['MC_BANK_DETAIL']) ? $paymentData['MC_BANK_DETAIL'] : null,
					'CRM_MYCOMPANY' => is_array($paymentData['CRM_MYCOMPANY']) ? $paymentData['CRM_MYCOMPANY'] : null
				],
				[],
				array(),
				REGISTRY_TYPE_CRM_INVOICE
			);

			// USER_ID hack (0050242)
			$arInvoice = array();
			$dbInvoice = CCrmInvoice::GetList(
				array('ID' => 'DESC'),
				array('ID' => $ORDER_ID, 'PERMISSION' => 'READ'),
				false,
				false,
				array('ID', 'UF_CONTACT_ID', 'UF_COMPANY_ID')
			);
			if (is_object($dbInvoice))
				$arInvoice = $dbInvoice->Fetch();
			unset($dbInvoice);
			if (is_array($arInvoice) && isset($arInvoice['UF_CONTACT_ID']) && isset($arInvoice['UF_COMPANY_ID']))
			{
				$companyId = intval($arInvoice['UF_COMPANY_ID']);
				$contactId = intval($arInvoice['UF_CONTACT_ID']);
				$clientId = '';
				if ($companyId > 0)
					$clientId = 'C'.$companyId;
				else
					$clientId = 'P'.$contactId;
				$GLOBALS['SALE_INPUT_PARAMS']['ORDER']['USER_ID'] = $clientId;
				\Bitrix\Sale\BusinessValue::redefineProviderField(
					array(
						'ORDER' => array('USER_ID' => $clientId),
						'PROPERTY' => $paymentData['USER_FIELDS']
					)
				);
				unset($companyId, $contactId, $clientId);
			}
			unset($arInvoice);

			$invoice = Invoice::load($ORDER_ID);
			if ($invoice)
			{
				/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
				$paymentCollection = $invoice->getPaymentCollection();
				if ($paymentCollection)
				{
					/** @var \Bitrix\Sale\Payment $payment */
					foreach ($paymentCollection as $payment)
					{
						if (!$payment->isInner())
						{
							$context = \Bitrix\Main\Application::getInstance()->getContext();
							$service = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
							$result = $service->initiatePay($payment, $context->getRequest());
							break;
						}
					}
				}
			}
		}
	}
}

$r = $APPLICATION->EndBufferContentMan();
echo $r;
die();