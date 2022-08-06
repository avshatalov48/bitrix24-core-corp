<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Util;

class License extends \Bitrix\Main\Engine\Controller
{
	private const CACHE_BANNER_TIME_TO_LIVE = 8 * 60 * 60;
	private const CACHE_BANNER_ID = "intranet.license.banner";

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [
			'getLicenseData' => [
				new Main\Engine\ActionFilter\Authentication(),
				new Main\Engine\ActionFilter\Csrf(),
			]
		];
	}

	private function getVoximplantInfo()
	{
		$telephonyInfo = [];

		if (Loader::includeModule("voximplant"))
		{
			if (
				\CVoxImplantPhone::getRentedNumbersCount() > 0
				|| \CVoxImplantSip::hasConnection()
				|| (new \CVoxImplantAccount())->getAccountBalance(false) > 0
			)
			{
				$telephonyInfo['isConnected'] = true;
				$viBalance = "";
				$viBalanceFormatted = "";
				$ViAccount = new \CVoxImplantAccount();
				$viLang = $ViAccount->GetAccountLang();
				$viCurrency = $ViAccount->GetAccountCurrency();

				if ( $viLang != 'ua')
				{
					$viBalance = $ViAccount->GetAccountBalance();
					if (Loader::includeModule('currency'))
					{
						$viCurrency = ($viCurrency === 'RUR' ? 'RUB' : $viCurrency);
						$viBalanceFormatted = \CCurrencyLang::CurrencyFormat($viBalance, $viCurrency);
					}
					else
					{
						$viBalanceFormatted = Loc::getMessage("INTRANET_LICENSE_CURRENCY_".$viCurrency,
							array("#NUM#" => number_format($viBalance, 2, '.', ' '))
						);
					}
				}

				$viLimit = \CVoxImplantAccount::GetRecordLimit();

				$telephonyInfo['limit'] = $viLimit;
				$telephonyInfo['lang'] = $viLang;
				$telephonyInfo['balanceFormatted'] = $viBalanceFormatted;
			}
			else
			{
				$telephonyInfo['isConnected'] = false;
			}

			$telephonyInfo['buyPath'] = '/telephony/?analyticsLabel[headerPopup]=Y';
		}

		return $telephonyInfo;
	}

	public function getLicenseDataAction()
	{
		if ($bitrix24Controller = $this->getBitrix24Controller())
		{
			return method_exists($bitrix24Controller, 'getLicenseDataAction')
				? $bitrix24Controller->getLicenseDataAction() : [];
		}

		if (!Main\Loader::includeModule('intranet'))
		{
			return false;
		}
		$license = Main\Application::getInstance()->getLicense();
		$expireDate = $license->getExpireDate();
		$daysLeft = null;
		if ($expireDate instanceof Main\Type\Date)
		{
			$daysLeft = (new Main\Type\DateTime())->getDiff($license->getExpireDate());
			$daysLeft = ($daysLeft->invert ? (-1) : 1) *  $daysLeft->days;
		}

		$licenseData = [
			'license' => [
				'ordersPath' => "/settings/order/",
				'isDemo' => $license->isDemo(),
				'isTimeBound' => $license->isTimeBound(),
				'expireDate' => $expireDate instanceof Main\Type\Date ? $expireDate->toString() : null,
				'daysLeft' => $daysLeft,
				'canBuy' => $this->getCurrentUser()->isAdmin(),
				'allPath' => '<script>alert("path to license page")</script>',
				'demoPath' => '<script>alert("path to license page")</script>',
			],
			'market' => [
				'isMarketAvailable' => false,
			],
			'isAdmin' => $this->getCurrentUser()->isAdmin(),
			'partner' => [],
			'telephony' => $this->getVoximplantInfo()
		];

		if (Loader::includeModule('rest') && \Bitrix\Rest\Marketplace\Client::isSubscriptionAccess())
		{
			$licenseData['market'] = array_merge(
				['isMarketAvailable' => false]/*,
 				TODO: make this info
				\Bitrix\Bitrix24\License\Market::getData()*/
			);
		}

		return $licenseData;
	}


	/**
	 * Action for load actual banners
	 * @return array|false|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getBannerDataAction()
	{
		if ($bitrix24Controller = $this->getBitrix24Controller())
		{
			return method_exists($bitrix24Controller, 'getBannerDataAction')
				? $bitrix24Controller->getBannerDataAction() : [];
		}
		/* read response from cache if exists */
		$cache = Application::getInstance()->getManagedCache();
		if ($cache->read(self::CACHE_BANNER_TIME_TO_LIVE,self::CACHE_BANNER_ID))
		{
			return $cache->get(self::CACHE_BANNER_ID);
		}

		global $USER;

		$isBitrix24Cloud = Loader::includeModule('bitrix24');
		$isAdmin = ($isBitrix24Cloud && \CBitrix24::isPortalAdmin($USER->getId())) || (!$isBitrix24Cloud && $USER->isAdmin());

		$httpClient = new HttpClient();
		$result = $httpClient->post(
			Util::getHelpdeskUrl() . '/widget2/license_widget_banners.php',
			array(
				'is_admin' => $isAdmin ? 1 : 0,
				'tariff' => Option::get('main', '~controller_group_name', ''),
				'is_cloud' => $isBitrix24Cloud ? '1' : '0',
				'host'  => defined('BX24_HOST_NAME') ? BX24_HOST_NAME : '',
				'languageId' => LANGUAGE_ID,
			)
		);

		if (false !== $result)
		{
			$data = Json::decode($result);

			if (is_array($data["notifications"]) && !empty($data["notifications"]))
			{
				$cache->set(self::CACHE_BANNER_ID, $data = $data["notifications"]);

				return $data;
			}
		}

		return [];
	}

	public function analyticsLabelAction()
	{

	}

	/**
	 * @todo Remove this code in August 2022 or after
	 */
	private function getBitrix24Controller(): ?Main\Engine\Controller
	{
		if (!Main\Loader::includeModule('bitrix24'))
		{
			return null;
		}
		$controller = Main\Engine\ControllerBuilder::build(
			\Bitrix\Bitrix24\Controller\License::class, []
		);
		$controller->setScope($this->getScope());
		$controller->setCurrentUser($this->getCurrentUser());
		return $controller;
	}
}
