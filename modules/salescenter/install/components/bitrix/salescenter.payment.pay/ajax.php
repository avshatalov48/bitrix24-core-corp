<?php
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);
define("BX_SECURITY_SHOW_MESSAGE", true);
define('NOT_CHECK_PERMISSIONS', true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

if (!check_bitrix_sessid() && !$request->isPost())
{
	die();
}

$params = \Bitrix\Main\Component\ParameterSigner::unsignParameters("bitrix:salescenter.payment.pay", $request->get('signedParameters'));
$params['PAY_SYSTEM_ID'] = (int)$request->get('paysystemId');

CBitrixComponent::includeComponentClass("bitrix:salescenter.payment.pay");

$salesCenterPaymentObject = new SalesCenterPaymentPay();
$salesCenterPaymentObject->initComponent('bitrix:salescenter.payment.pay');
$result = [];
$result['html'] = $salesCenterPaymentObject->initiatePayAction($params);
$errorCollection = $salesCenterPaymentObject->getErrorCollection();
if (!$errorCollection->isEmpty())
{
	$result['status'] = 'error';
	$result['errors'] = [];
	/** @var \Bitrix\Main\Error $error */
	foreach ($errorCollection as $error)
	{
		$result['errors'][] = $error->getMessage();
	}
}
else
{
	$result['status'] = 'success';
	if (empty($result['html']))
	{
		$payment = $salesCenterPaymentObject->getPayment();
		if ($payment)
		{
			$result['fields'] = [
				'SUM_WITH_CURRENCY' => SaleFormatCurrency($payment->getSum(), $payment->getField('CURRENCY')),
				'PAY_SYSTEM_NAME' => htmlspecialcharsbx($payment->getPaymentSystemName()),
			];
		}
	}
}

echo \Bitrix\Main\Web\Json::encode($result);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>
