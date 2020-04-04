<?

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

$CCrmQuote = new CCrmQuote();
if ($CCrmQuote->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;
$APPLICATION->RestartBuffer();
$quoteID = intval($arParams["QUOTE_ID"]);

$dbResult = CCrmQuote::GetList(array(), array('ID' => $quoteID, 'CHECK_PERMISSIONS' => 'N'), false, false, array('*', 'UF_*'));
$arQuote = is_object($dbResult) ? $dbResult->Fetch() : null;
$paymentData = is_array($arQuote) ? CCrmQuote::PrepareSalePaymentData($arQuote) : null;
$paySystemID = isset($_REQUEST['PAY_SYSTEM_ID']) ? intval($_REQUEST['PAY_SYSTEM_ID']) : 0;

if (is_array($paymentData) && $paySystemID > 0)
{
	$dbPaySysAction = CSalePaySystemAction::GetList(
		array(),
		array(
			"PAY_SYSTEM_ID" => $paySystemID,
			"PERSON_TYPE_ID" => $arQuote["PERSON_TYPE_ID"]
		),
		false,
		false,
		array("ACTION_FILE", "PARAMS", "ENCODING")
	);


	if ($arPaySysAction = $dbPaySysAction->Fetch())
	{
		if (strlen($arPaySysAction["ACTION_FILE"]) > 0)
		{
			CSalePaySystemAction::InitParamArrays(
				$paymentData['ORDER'],
				0,
				$arPaySysAction['PARAMS'],
				array(
					'PROPERTIES' => $paymentData['PROPERTIES'],
					'BASKET_ITEMS' => $paymentData['CART_ITEMS'],
					'TAX_LIST' => $paymentData['TAX_LIST'],
					'REQUISITE' => $paymentData['REQUISITE'],
					'BANK_DETAIL' => $paymentData['BANK_DETAIL'],
					'CRM_COMPANY' => $paymentData['CRM_COMPANY'],
					'CRM_CONTACT' => $paymentData['CRM_CONTACT'],
					'MC_REQUISITE' => $paymentData['MC_REQUISITE'],
					'MC_BANK_DETAIL' => $paymentData['MC_BANK_DETAIL'],
					'CRM_MYCOMPANY' => $paymentData['CRM_MYCOMPANY']
				),
				$paymentData['PAYMENT'],
				$paymentData['SHIPMENT']
			);

			$pathToAction = $_SERVER["DOCUMENT_ROOT"].$arPaySysAction["ACTION_FILE"];

			$pathToAction = str_replace("\\", "/", $pathToAction);
			while (substr($pathToAction, strlen($pathToAction) - 1, 1) == "/")
				$pathToAction = substr($pathToAction, 0, strlen($pathToAction) - 1);

			if (file_exists($pathToAction))
			{
				if (is_dir($pathToAction))
				{
					if (file_exists($pathToAction."/payment.php"))
						include($pathToAction."/payment.php");
				}
				else
				{
					include($pathToAction);
				}
			}

			if (strlen($arPaySysAction["ENCODING"]) > 0)
			{
				define("BX_SALE_ENCODING", $arPaySysAction["ENCODING"]);
				AddEventHandler("main", "OnEndBufferContent", "ChangeEncoding");
				function ChangeEncoding($content)
				{
					global $APPLICATION;
					header("Content-Type: text/html; charset=".BX_SALE_ENCODING);
					$content = $APPLICATION->ConvertCharset($content, SITE_CHARSET, BX_SALE_ENCODING);
					$content = str_replace("charset=".SITE_CHARSET, "charset=".BX_SALE_ENCODING, $content);
				}
			}
		}
	}
}

$r = $APPLICATION->EndBufferContentMan();
echo $r;
die();
