<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Bitrix24;
use Bitrix\UI\Util;

class License extends \Bitrix\Main\Engine\Controller
{
	private function getVoximplantInfo($licensePrefix)
	{
		$telephonyInfo = [];

		if (in_array($licensePrefix, ['kz', 'by']))
		{
			$telephonyInfo['isConnected'] = false;
			$telephonyInfo['buyPath'] = '/marketplace/?category=telephony';
		}
		elseif (Loader::includeModule("voximplant"))
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
		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		$isCloud = true;
		$licenseScanner = Bitrix24\LicenseScanner\Manager::getInstance();

		$licenseType = \CBitrix24::getLicenseType();
		$licenseFamily = \CBitrix24::getLicenseFamily();
		$licensePrefix = \CBitrix24::getLicensePrefix();
		$licenseTill = Option::get('main', '~controller_group_till');
		$licenseTillMessage = '';
		$scannerLockTill = $licenseScanner->getLockTill();
		$scannerIsAlmostLocked = $scannerLockTill > time() && !$licenseScanner->isEditionCompatible('project');
		$scannerLockTillMessage = '';
		$daysLeftMessage = '';
		$daysLeft = 0;
		$isLicenseDateUnlimited = \CBitrix24::isLicenseDateUnlimited();
		$isAutoPay = (\CBitrix24::IsLicensePaid() && \CBitrix24::isAutoPayLicense());
		$isAdmin = (
			Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())
			|| \Bitrix\Main\Engine\CurrentUser::get()->isAdmin()
		)
			? true : false;

		$canBuyLicense = $isAdmin || \CBitrix24::canAllBuyLicense();

		$date= new Date;
		$currentDate = $date->getTimestamp();

		if (intval($licenseTill))
		{
			$daysLeft = intval(($licenseTill - $currentDate) / 60 / 60 / 24);
			$licenseTillConverted = ConvertTimeStamp($licenseTill);
			$licenseTillMessage = Loc::getMessage('INTRANET_LICENSE_TILL', ['#LICENSE_TILL#' => $licenseTillConverted]);

			$daysLeftMessage = Loc::getMessage('INTRANET_LICENSE_DAYS_LEFT_SHORT', [
				'#NUM_DAYS#' => FormatDate(
					"ddiff",
					$currentDate,
					($licenseTill > $currentDate ? $licenseTill : $currentDate)
				)
			]);
		}

		if ($scannerIsAlmostLocked)
		{
			$scannerLockTillMessage = Loc::getMessage('INTRANET_LICENSE_SCANNER_DAYS_LEFT', [
				'#NUM_DAYS#' => FormatDate("ddiff", $currentDate, $scannerLockTill)
			]);
		}

		$analyticsLabel = '?analyticsLabel[headerPopup]=Y&analyticsLabel[licenseType]='.$licenseType;
		$isMarketAvailable = Loader::includeModule('rest') && \Bitrix\Rest\Marketplace\Client::isSubscriptionAccess();

		$licenseData = [
			'license' => [
				'name' => \CBitrix24::getLicenseName(),
				'type' => $licenseType,
				'tillMessage' => $licenseTillMessage,
				'demoPath' => \CBitrix24::PATH_LICENSE_DEMO . $analyticsLabel,
				'allPath' => \CBitrix24::PATH_LICENSE_ALL . $analyticsLabel,
				'myPath' => \CBitrix24::PATH_LICENSE_MY . $analyticsLabel,
				'ordersPath' => "/settings/order/",
				'isDemo' => \CBitrix24::IsDemoLicense(),
				'isFreeTariff' => $licenseFamily === 'project',
				'isCompanyTariff' => $licenseFamily === 'company',
				'isUnlimitedDateTariff' => $isLicenseDateUnlimited,
				'isDemoAvailable' => \Bitrix\Bitrix24\Feature::isEditionTrialable('demo'),
				'isDemoExpired' => $daysLeft < 14,
				'isAlmostExpired' => (
					$licenseFamily !== 'project'
					&& !$isAutoPay
					&& $daysLeft > 0
					&& $daysLeft < 14
					&& !$isLicenseDateUnlimited
				),
				'isExpired' => (
					$licenseFamily !== 'project'
					&& ($isAutoPay ? $daysLeft < 0 : $daysLeft <= 0)
					&& !$isLicenseDateUnlimited
				),
				'daysLeft' => $daysLeft,
				'daysLeftMessage' => $daysLeftMessage,
				'isAutoPay' => $isAutoPay,
				'canBuy' => $canBuyLicense,
				'isAlmostLocked' => $scannerIsAlmostLocked,
				'scannerLockTillMessage' => $scannerLockTillMessage,
				'showScanner' => (Option::get('bitrix24', '~license_scan_visible', 'N') === 'Y')
			],
			'market' => [
				'isMarketAvailable' => $isMarketAvailable,
			],
			'isAdmin' => $isAdmin,
			'isCloud' => ModuleManager::isModuleInstalled("bitrix24"),
			'partner' => [],
		];

		if ($isMarketAvailable)
		{
			$licenseData['market'] = array_merge(
				$licenseData['market'], \Bitrix\Bitrix24\License\Market::getData()
			);
		}

		$licenseData['telephony'] = $this->getVoximplantInfo($licensePrefix);

		if ($isCloud && $partnerId = Option::get('bitrix24', 'partner_id', ''))
		{
			if ($partnerId !== "9409443") //sber
			{
				$arParamsPartner = [];
				$arParamsPartner['MESS'] = [
					'BX24_PARTNER_TITLE' => Loc::getMessage('INTRANET_LICENSE_SITE_PARTNER'),
					'BX24_CLOSE_BUTTON'  => Loc::getMessage('INTRANET_LICENSE_CLOSE_BUTTON'),
					'BX24_LOADING'       => Loc::getMessage('INTRANET_LICENSE_LOADING'),
				];

				$licenseData['partner'] = [
					'isPartnerConnect' => true,
					'isPartnerOrder' => false,
					'connectPartnerJs' => $arParamsPartner,
				];
			}
		}
		elseif ($isCloud)
		{
			$licenseData['partner'] = [
				'isPartnerConnect' => false,
				'isPartnerOrder' => true,
				'formLang' => LANGUAGE_ID,
				'formPortalUri' => \Bitrix\Intranet\Util::CP_BITRIX_PATH,
			];
		}

		return $licenseData;
	}


	private const CACHE_BANNER_TIME_TO_LIVE = 8 * 60 * 60;
	private const CACHE_BANNER_ID = "intranet.license.banner";

	/**
	 * Action for load actual banners
	 * @return array|false|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getBannerDataAction()
	{
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
}
