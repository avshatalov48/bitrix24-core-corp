<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

/**
 * Class CrmReportVcWidgetContentSalesTarget
 */
class CrmReportVcWidgetContentSalesTarget extends CBitrixComponent
{
	/**
	 * @return void
	 */
	public function executeComponent()
	{
		if (!Loader::includeModule('crm'))
		{
			ShowError('Module CRM is not installed');
			return;
		}

		$saleTargetWidget = \Bitrix\Crm\Widget\Custom\SaleTarget::getInstance();
		$curUser = CCrmSecurityHelper::GetCurrentUser();

		$data = $saleTargetWidget->getDataFor($curUser->GetID());
		list($current, $totalCurrent) = \Bitrix\Crm\Widget\Data\DealSaleTarget::getCurrentValues($data['configuration']);
		$data['current'] = $current;
		$data['totalCurrent'] = $totalCurrent;

		$this->arResult['INIT_DATA'] = [$data];
		$this->arResult['CURRENCY_FORMAT'] = CCrmCurrency::GetCurrencyFormatParams(CCrmCurrency::GetBaseCurrencyID());

		$this->includeComponentTemplate();
	}
}