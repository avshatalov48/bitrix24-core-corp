<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
	return;

if(\Bitrix\Voximplant\Limits::isRestOnly())
{
	return;
}

$ViAccount = new CVoxImplantAccount();

$arResult['CURRENCY'] = $arParams['CURRENCY']? $arParams['CURRENCY']: $ViAccount->GetAccountCurrency();
$arResult['LANG'] = $arParams['LANG']? $arParams['LANG']: $ViAccount->GetAccountLang();

if (in_array($arResult['LANG'], Array('ua', 'kz')))
	return false;

$arResult['NUMBERS'] = CVoxImplantPhone::GetRentNumbers() ?: [];

$arResult['PAID_BEFORE'] = Array(
	'TS' => 0,
	'DATE' => '',
	'PRICE' => 0,
	'NOTICE' => false,
);

$arResult['VERIFY_BEFORE'] = Array(
	'TS' => 0,
	'DATE' => '',
	'NOTICE' => false,
);

foreach ($arResult['NUMBERS'] as $value)
{
	if ($arResult['PAID_BEFORE']['TS'] > $value['PAID_BEFORE_TS'] || $arResult['PAID_BEFORE']['TS'] == 0)
	{
		$arResult['PAID_BEFORE']['TS'] = $value['PAID_BEFORE_TS'];
		$arResult['PAID_BEFORE']['DATE'] = $value['PAID_BEFORE'];
		$arResult['PAID_BEFORE']['PRICE'] = $value['PRICE'];
	}
	else if ($arResult['PAID_BEFORE']['TS'] == $value['PAID_BEFORE_TS'])
	{
		$arResult['PAID_BEFORE']['PRICE'] += $value['PRICE'];
	}

	if ($arResult['VERIFY_BEFORE']['TS'] > $value['VERIFY_BEFORE_TS'] || $arResult['VERIFY_BEFORE']['TS'] == 0)
	{
		$arResult['VERIFY_BEFORE']['TS'] = $value['VERIFY_BEFORE_TS'];
		$arResult['VERIFY_BEFORE']['DATE'] = $value['VERIFY_BEFORE'];
		$arResult['VERIFY_BEFORE']['NOTICE'] = $arResult['VERIFY_BEFORE']['TS'] > 0? true: false;
	}
}

if ($arResult['PAID_BEFORE']['TS'] > 0)
{
	$data = new Bitrix\Main\Type\DateTime();
	if ($arResult['PAID_BEFORE']['TS'] <= $data->getTimestamp()+604800) // 1 week
	{
		$arResult['AMOUNT'] = $arParams['AMOUNT']? $arParams['AMOUNT']: $ViAccount->GetAccountBalance(true);
		if ($arResult['AMOUNT'] < $arResult['PAID_BEFORE']['PRICE'])
		{
			$arResult['PAID_BEFORE']['NOTICE'] = true;
		}
	}
}

if (!empty($arResult['NUMBERS']) && !(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>