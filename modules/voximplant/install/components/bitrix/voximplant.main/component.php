<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
$arResult['SHOW_LINES'] = $permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY);
$arResult['SHOW_STATISTICS'] = $permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL_DETAIL, \Bitrix\Voximplant\Security\Permissions::ACTION_VIEW);
$arResult['SHOW_PAY_BUTTON'] = \Bitrix\Voximplant\Security\Helper::isAdmin() && !\Bitrix\Voximplant\Limits::isRestOnly();

$ViAccount = new CVoxImplantAccount();

$arResult['LANG'] = $ViAccount->GetAccountLang();
$arResult['CURRENCY'] = $ViAccount->GetAccountCurrency();

if ( in_array($arResult['LANG'], Array('ua', 'kz', 'by')) && !isset($_GET['REFRESH']))
{
	$arResult['AMOUNT'] = 0;
}
else
{
	$arResult['AMOUNT'] = $ViAccount->GetAccountBalance(true);
}

$arResult['ERROR_MESSAGE'] = '';

if ($ViAccount->GetError()->error)
{
	$arResult['AMOUNT'] = '';
	$arResult['CURRENCY'] = '';
	if ($ViAccount->GetError()->code == 'LICENCE_ERROR')
	{
		$arResult['ERROR_MESSAGE'] = GetMessage('VI_ERROR_LICENSE');
	}
	else
	{
		$arResult['ERROR_MESSAGE'] = GetMessage('VI_ERROR');
	}
}

if (LANGUAGE_ID == "kz")
{
	$arResult['LANG'] = "kz";
}

if(\Bitrix\Voximplant\Integration\Bitrix24::getLicensePrefix() === "by")
{
	$arResult['LANG'] = "by";
}

$arResult['LINK_TO_BUY'] = CVoxImplantMain::GetBuyLink();
$arResult['RECORD_LIMIT'] = \CVoxImplantAccount::GetRecordLimit();

$arResult['STATISTICS'] = array();
if($arResult['SHOW_STATISTICS'])
{
	$account = new CVoxImplantAccount();
	$statCursor = \Bitrix\Voximplant\StatisticTable::getList(array(
		'select' => array('YEAR', 'MONTH', 'TOTAL_DURATION', 'TOTAL_COST'),
		'filter' => array(
			'>=CALL_START_DATE' => getStatisticsStartDate(),
			'=CALL_CATEGORY' => 'external',
		),
		'group' => array('YEAR', 'MONTH'),
		'runtime' => array(
			new Bitrix\Main\Entity\ExpressionField('YEAR','extract(YEAR from %s)', array('CALL_START_DATE')),
			new Bitrix\Main\Entity\ExpressionField('MONTH','extract(MONTH from %s)', array('CALL_START_DATE')),
		)
	));

	while ($row = $statCursor->fetch())
	{
		$row["DURATION_FORMATTED"] = \CVoxImplantHistory::convertDurationToText($row["TOTAL_DURATION"]);

		if (!in_array($arResult['LANG'], array('ua', 'kz')))
		{
			$row['COST_CURRENCY'] = ($account->GetAccountCurrency() == "RUR" ? "RUB" : $account->GetAccountCurrency());
			if(CModule::IncludeModule("currency"))
				$row['COST_FORMATTED'] = \CCurrencyLang::CurrencyFormat($row["TOTAL_COST"], $row['COST_CURRENCY'], true);
			else
				$row['COST_FORMATTED'] = '';
		}
		else
		{
			$row['COST_FORMATTED'] = '';
		}

		$arResult['STATISTICS'][] = $row;
	}

	if(empty($arResult['STATISTICS']))
	{
		$row = array(
			'YEAR' => date('Y'),
			'MONTH' => date('m'),
			'TOTAL_DURATION' => 0,
			'TOTAL_COST' => 0,
			'DURATION_FORMATTED' => \CVoxImplantHistory::convertDurationToText(0),
			'COST_CURRENCY' => ($account->GetAccountCurrency() == "RUR" ? "RUB" : $account->GetAccountCurrency()),
		);
		if(CModule::IncludeModule("currency"))
			$row['COST_FORMATTED'] = \CCurrencyLang::CurrencyFormat($row["TOTAL_COST"], $row['COST_CURRENCY'], true);
		else
			$row['COST_FORMATTED'] = '';

		$arResult['STATISTICS'][] = $row;
	}

	usort($arResult['STATISTICS'], function($a, $b)
	{
		if($b['YEAR'] > $a['YEAR'])
			return 1;
		else if ($b['YEAR'] < $a['YEAR'])
			return -1;
		else
			return $b['MONTH'] - $a['MONTH'];
	});
}

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

function getStatisticsStartDate()
{
	$startDate = new \DateTime();
	$startDate->modify('-3 month');
	$dateFields = getdate($startDate->getTimestamp());
	$startDate->setDate($dateFields['year'], $dateFields['mon'], 1);
	$startDate->setTime(0,0);

	return \Bitrix\Main\Type\Date::createFromPhp($startDate);
}

?>