<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

if (!CModule::IncludeModule('sale'))
	return;

if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->isCrmAdmin())
{
	return;
}

CUtil::InitJSCore();

if ($_SERVER['REQUEST_METHOD'] == 'POST') // process data from popup dialog
{
	if (check_bitrix_sessid())
	{
		$arResult['BACK_URL'] = isset($_POST['BACK_URL']) ? $_POST['BACK_URL'] : '';
		$arResult['TAX_TYPE'] = isset($_POST['TAX_TYPE']) ? $_POST['TAX_TYPE'] : '';

		if($arResult['TAX_TYPE'] <> '')
		{
			if($arResult['TAX_TYPE'] == 'tax')
				CCrmTax::unSetVatMode();
			elseif($arResult['TAX_TYPE'] == 'vat')
				CCrmTax::setVatMode();
		}
	}
}
else // fill popup dialog
{
	$arResult['IS_VAT_MODE'] = CCrmTax::isVatMode();
}
$this->IncludeComponentTemplate();
?>
