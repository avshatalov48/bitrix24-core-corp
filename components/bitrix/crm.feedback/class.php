<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\SalesCenter\Integration\SaleManager;

class CrmFeedbackComponent extends CBitrixComponent
{
	public function onPrepareComponentParams($arParams): array
	{
		if (!is_array($arParams))
		{
			$arParams = [];
		}

		$arParams['SENDER_PAGE'] ??= '';
		if (!is_string($arParams['SENDER_PAGE']))
		{
			$arParams['SENDER_PAGE'] = '';
		}
		if (!preg_match("/^[A-Za-z_]+$/", $arParams['SENDER_PAGE']))
		{
			$arParams['SENDER_PAGE'] = '';
		}

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if(!Loader::includeModule('salescenter'))
		{
			ShowError(Loc::getMessage('CRM_FEEDBACK_MODULE_SALESCENTER_ERROR'));
			return;
		}
		if(!Loader::includeModule('sale'))
		{
			ShowError(Loc::getMessage('CRM_FEEDBACK_MODULE_SALE_ERROR'));
			return;
		}

		$this->arResult = $this->getFeedbackFormInfo($this->getPortalZone());
		$this->arResult['type'] = 'slider_inline';
		$this->arResult['domain'] = 'https://product-feedback.bitrix24.com';

		$currentUser = CurrentUser::get();
		$this->arResult['fields']['values']['CONTACT_EMAIL'] = $currentUser->getEmail();
		$this->arResult['presets'] = [
			'from_domain' => defined('BX24_HOST_NAME') ? BX24_HOST_NAME : Option::get('main', 'server_name', ''),
			'c_name' => $currentUser->getFullName(),
			'c_email' => $currentUser->getEmail(),
			'b24_plan' => $this->getLicenseType(),
			'sender_page' => $this->arParams['SENDER_PAGE'],
			'is_payment_system' => $this->hasPaymentSystemConfigured() ? 'yes' : 'no',
			'is_cashbox' => $this->hasCashboxConfigured() ? 'yes' : 'no',
		];

		$this->includeComponentTemplate();
	}

	/**
	 * @param string | null $region
	 * @return array
	 */
	private function getFeedbackFormInfo(?string $region): array
	{
		switch ($region)
		{
			case 'ru':
				return ['id' => 628, 'lang' => 'ru', 'zones' => ['ru'], 'code' => 'b5309667', 'sec' => 'rgyboj'];
			case 'en':
				return ['id' => 630, 'lang' => 'en', 'zones' => ['en'], 'code' => 'b5309667', 'sec' => 'ypq6nz'];
			case 'com.br':
				return ['id' => 632, 'lang' => 'com.br', 'zones' => ['com.br'], 'code' => 'b5309667', 'sec' => 'ama2ql'];
			default:
				return ['id' => 628, 'lang' => 'ru', 'zones' => ['ru'], 'code' => 'b5309667', 'sec' => 'rgyboj'];
		}
	}

	/**
	 * @return null|string
	 */
	private function getPortalZone(): ?string
	{
		if ($this->isEnabled())
		{
			return \CBitrix24::getPortalZone();
		}

		return null;
	}

	/**
	 * @return null|string
	 */
	private function getLicenseType(): ?string
	{
		if($this->isEnabled())
		{
			return \CBitrix24::getLicenseType();
		}

		return null;
	}

	private function isEnabled(): bool
	{
		return Loader::includeModule('bitrix24');
	}

	protected function hasCashboxConfigured(): bool
	{
		if(SaleManager::getInstance()->isEnabled())
		{
			$filter = SaleManager::getInstance()->getCashboxFilter();

			return CashboxTable::getCount($filter) > 0;
		}

		return false;
	}

	protected function hasPaymentSystemConfigured(): bool
	{
		if(SaleManager::getInstance()->isEnabled())
		{
			$filter = SaleManager::getInstance()->getPaySystemFilter();

			return PaySystemActionTable::getCount($filter) > 0;
		}

		return false;
	}
}