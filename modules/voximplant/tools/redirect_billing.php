<?php

define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('voximplant'))
{
	return false;
}

global $APPLICATION;
if ($APPLICATION instanceof \CMain)
{
	$APPLICATION->RestartBuffer();
}

$apiClient = new \CVoxImplantHttp();
$result = (array)$apiClient->getBillingUrl();

if (isset($result['error']))
{
	echo $result['error']['msg'];
	\CMain::FinalActions();
}
else
{
	$billingUrl = $result['billingUrl'];
	if ($billingUrl != '')
	{
		header("Location: " . $billingUrl);
	}
	else
	{
		echo "Billing is not available for your account";
		\CMain::FinalActions();
	}
}