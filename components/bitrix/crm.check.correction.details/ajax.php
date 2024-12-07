<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Sale\Cashbox;
use Bitrix\Main;

if (
	!CModule::IncludeModule('crm')
	|| !CModule::IncludeModule('sale')
)
{
	return;
}

global $APPLICATION;

Main\Localization\Loc::loadMessages(__FILE__);

if (!function_exists('__CrmCheckCorrectionDetailsEndJsonResponse'))
{
	function __CrmCheckCorrectionDetailsEndJsonResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if (!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if (!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

$request = Main\Application::getInstance()->getContext()->getRequest();

$currentUser = CCrmSecurityHelper::GetCurrentUser();

if (
	!$currentUser
	|| !$currentUser->IsAuthorized()
	|| !check_bitrix_sessid()
	|| !$request->isPost()
)
{
	return;
}

$action = $request->get('ACTION');

if($action === '')
{
	__CrmCheckCorrectionDetailsEndJsonResponse(['ERROR' => 'Action is not defined!']);
}
elseif ($action === 'GET_FORMATTED_SUM')
{
	if (!\Bitrix\Main\Loader::includeModule('currency'))
	{
		return;
	}

	$sum = $_POST['SUM'] ?? 0;
	$currencyId = $_POST['CURRENCY_ID'] ?? '';
	if($currencyId === '')
	{
		$currencyId = (string)\Bitrix\Currency\CurrencyManager::getBaseCurrency();
	}

	__CrmCheckCorrectionDetailsEndJsonResponse([
		'FORMATTED_SUM' => CCurrencyLang::CurrencyFormat($sum, $currencyId, false),
		'FORMATTED_SUM_WITH_CURRENCY' => CCurrencyLang::CurrencyFormat($sum, $currencyId),
	]);
}
elseif ($action === 'SAVE')
{
	$fields = [
		'CORRECTION_TYPE' => $request->get('CORRECTION_TYPE'),
		'DOCUMENT_NUMBER' => $request->get('DOCUMENT_NUMBER'),
		'DOCUMENT_DATE' => $request->get('DOCUMENT_DATE'),
		'DESCRIPTION' => $request->get('DESCRIPTION'),
		'CORRECTION_PAYMENT' => [],
		'CORRECTION_VAT' => []
	];

	if ($request->get('CORRECTION_PAYMENT_CASHLESS'))
	{
		$fields['CORRECTION_PAYMENT'][] = [
			'TYPE' => Cashbox\Check::PAYMENT_TYPE_CASHLESS,
			'SUM' => $request->get('CORRECTION_PAYMENT_CASHLESS'),
		];
	}

	if ($request->get('CORRECTION_PAYMENT_CASH'))
	{
		$fields['CORRECTION_PAYMENT'][] = [
			'TYPE' => Cashbox\Check::PAYMENT_TYPE_CASH,
			'SUM' => $request->get('CORRECTION_PAYMENT_CASH'),
		];
	}

	CBitrixComponent::includeComponentClass("bitrix:crm.check.correction.details");
	$component = new CCrmCheckCorrectionDetailsComponent();

	foreach ($component->getVatList() as $vat)
	{
		$rate = (int)$vat['RATE'];
		if ($request->get('CORRECTION_VAT_'.$rate))
		{
			$fields['CORRECTION_VAT'][] = [
				'TYPE' => $rate,
				'SUM' => $request->get('CORRECTION_VAT_'.$rate),
			];
		}
	}

	if ($request->get('CORRECTION_VAT_NONE'))
	{
		$fields['CORRECTION_VAT'][] = [
			'TYPE' => '',
			'SUM' => $request->get('CORRECTION_VAT_NONE'),
		];
	}

	$result = Cashbox\CheckManager::addCorrection($request->get('TYPE'), $request->get('CASHBOX_ID'), $fields);
	if (!$result->isSuccess())
	{
		__CrmCheckCorrectionDetailsEndJsonResponse(['ERROR' => implode("\n", $result->getErrorMessages())]);
	}

	__CrmCheckCorrectionDetailsEndJsonResponse(['ENTITY_ID' => $result->getId()]);
}
