<?php

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\SalesCenter\Model\PageTable;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class SalesCenterFeedbackComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('SALESCENTER_FEEDBACK_MODULE_ERROR'));
			$this->includeComponentTemplate();
			return;
		}
		if(!Bitrix24Manager::getInstance()->isEnabled())
		{
			ShowError(Loc::getMessage('SALESCENTER_FEEDBACK_BITRIX24_ERROR'));
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult = Bitrix24Manager::getInstance()->getFeedbackFormInfo(LANGUAGE_ID);
		$this->arResult['type'] = 'slider_inline';
		$this->arResult['fields']['values']['CONTACT_EMAIL'] = CurrentUser::get()->getEmail();
		$this->arResult['presets'] = [
			'from_domain' =>  BX24_HOST_NAME,
			'b24_plan' => Bitrix24Manager::getInstance()->getLicenseType(),
			'b24_zone' => Bitrix24Manager::getInstance()->getPortalZone(),
			'c_name' => CurrentUser::get()->getFullName(),
			'user_status' => Bitrix24Manager::getInstance()->isPortalAdmin(CurrentUser::get()->getId()),
			'is_created_eshop' => $this->getBooleanPhrase(LandingManager::getInstance()->isSiteExists()),
			'is_payment_system' => $this->getBooleanPhrase($this->hasPaymentSystemConfigured()),
			'is_cashbox' => $this->getBooleanPhrase($this->hasCashboxConfigured()),
			'is_own_url' => $this->getBooleanPhrase($this->hasPagesWithCustomUrl()),
			'is_other_website_url' => $this->getBooleanPhrase($this->hasPagesFromAnotherSite()),
		];

		$this->includeComponentTemplate();
	}

	/**
	 * @param $value bool
	 * @return string
	 */
	protected function getBooleanPhrase($value)
	{
		if($value)
		{
			return 'yes';
		}

		return 'no';
	}

	/**
	 * @return bool
	 */
	protected function hasPaymentSystemConfigured()
	{
		if(SaleManager::getInstance()->isEnabled())
		{
			$filter = SaleManager::getInstance()->getPaySystemFilter();

			return PaySystemActionTable::getCount($filter) > 0;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function hasCashboxConfigured()
	{
		if(SaleManager::getInstance()->isEnabled())
		{
			$filter = SaleManager::getInstance()->getCashboxFilter();

			return CashboxTable::getCount($filter) > 0;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function hasPagesWithCustomUrl()
	{
		return (PageTable::getCount(Driver::getInstance()->getFilterForCustomUrlPages()) > 0);
	}

	/**
	 * @return bool
	 */
	protected function hasPagesFromAnotherSite()
	{
		return PageTable::getCount(Driver::getInstance()->getFilterForAnotherSitePages()) > 0;
	}
}