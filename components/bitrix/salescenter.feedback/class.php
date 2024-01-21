<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox\Internals\CashboxTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\SalesCenter\Model\PageTable;
use \Bitrix\Main\Service\GeoIp;

class SalesCenterFeedbackComponent extends CBitrixComponent
{
	private const FEEDBACK_TYPE_FEEDBACK = 'feedback';
	private const FEEDBACK_TYPE_PAY_ORDER = 'pay_order';
	private const FEEDBACK_TYPE_PAYSYSTEM_OFFER = 'paysystem_offer';
	private const FEEDBACK_TYPE_PAYSYSTEM_SBP_OFFER = 'paysystem_sbp_offer';
	private const FEEDBACK_TYPE_SMSPROVIDER_OFFER = 'smsprovider_offer';
	private const FEEDBACK_TYPE_DELIVERY_OFFER = 'delivery_offer';
	private const FEEDBACK_TYPE_TERMINAL_OFFER = 'terminal_offer';
	private const FEEDBACK_TYPE_INTEGRATION_REQUEST = 'integration_request';

	private $template = '';

	public function onPrepareComponentParams($arParams): array
	{
		if (empty($arParams['FEEDBACK_TYPE']))
		{
			$arParams['FEEDBACK_TYPE'] = self::FEEDBACK_TYPE_FEEDBACK;
		}

		if ($arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_PAYSYSTEM_SBP_OFFER)
		{
			$this->template = 'newform';
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

		if ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_FEEDBACK)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_PAYSYSTEM_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackPaySystemOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_SMSPROVIDER_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackSmsProviderOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_PAY_ORDER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackPayOrderFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_DELIVERY_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackDeliveryOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_TERMINAL_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackTerminalOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_PAYSYSTEM_SBP_OFFER)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getFeedbackPaySystemSbpOfferFormInfo(LANGUAGE_ID);
		}
		elseif ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_INTEGRATION_REQUEST)
		{
			$this->arResult = Bitrix24Manager::getInstance()->getIntegrationRequestFormInfo(Bitrix24Manager::getInstance()->getPortalZone());
		}

		$this->arResult['type'] = 'slider_inline';
		$this->arResult['fields']['values']['CONTACT_EMAIL'] = CurrentUser::get()->getEmail();
		if ($this->arParams['FEEDBACK_TYPE'] === self::FEEDBACK_TYPE_INTEGRATION_REQUEST)
		{
			$this->arResult['domain'] = 'https://bitrix24.team';
			$this->arResult['presets'] = [
				'url' => defined('BX24_HOST_NAME') ? BX24_HOST_NAME : $_SERVER['SERVER_NAME'],
				'tarif' => Bitrix24Manager::getInstance()->getLicenseType(),
				'c_email' => CurrentUser::get()->getEmail(),
				'city' => implode(' / ', $this->getUserGeoData()),
				'partner_id' => \Bitrix\Main\Config\Option::get('bitrix24', 'partner_id', 0),
				'sender_page' => $this->arParams['SENDER_PAGE'],
			];
		}
		else
		{
			$this->arResult['domain'] = 'https://product-feedback.bitrix24.com';
			$this->arResult['presets'] = [
				'from_domain' => defined('BX24_HOST_NAME') ? BX24_HOST_NAME : Option::get('main', 'server_name', ''),
				'b24_plan' => Bitrix24Manager::getInstance()->getLicenseType(),
				'b24_zone' => Bitrix24Manager::getInstance()->getPortalZone(),
				'c_name' => CurrentUser::get()->getFullName(),
				'user_status' => CurrentUser::get()->isAdmin() ? 'yes' : 'no',
				'is_created_eshop' => LandingManager::getInstance()->isSiteExists() ? 'yes' : 'no',
				'is_payment_system' => $this->hasPaymentSystemConfigured() ? 'yes' : 'no',
				'is_cashbox' => $this->hasCashboxConfigured() ? 'yes' : 'no',
				'is_own_url' => $this->hasPagesWithCustomUrl() ? 'yes' : 'no',
				'is_other_website_url' => $this->hasPagesFromAnotherSite() ? 'yes' : 'no',
				'sender_page' => $this->arParams['SENDER_PAGE'],
			];
		}

		$this->includeComponentTemplate($this->template);
	}

	private function getUserGeoData(): array
	{
		$countryName = GeoIp\Manager::getCountryName('', 'ru');
		if (!$countryName)
		{
			$countryName = GeoIp\Manager::getCountryName();
		}

		$cityName = GeoIp\Manager::getCityName('', 'ru');
		if (!$cityName)
		{
			$cityName = GeoIp\Manager::getCityName();
		}

		return [
			'country' => $countryName,
			'city' => $cityName
		];
	}

	/**
	 * @return bool
	 */
	protected function hasPaymentSystemConfigured(): bool
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
	protected function hasCashboxConfigured(): bool
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
	protected function hasPagesWithCustomUrl(): bool
	{
		return (PageTable::getCount(Driver::getInstance()->getFilterForCustomUrlPages()) > 0);
	}

	/**
	 * @return bool
	 */
	protected function hasPagesFromAnotherSite(): bool
	{
		return PageTable::getCount(Driver::getInstance()->getFilterForAnotherSitePages()) > 0;
	}
}