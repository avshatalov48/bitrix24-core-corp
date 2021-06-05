<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;

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

	private function getPartnerOrderFormParams($licensePrefix)
	{
		$orderParams = [];

		if ($licensePrefix !== LANGUAGE_ID)
		{
			$licensePrefix = 'en';
		}

		switch ($licensePrefix)
		{
			case 'ru':
				$orderParams = [
					'id'  => '1192',
					'sec' => 'b5mzdk'
				];
				break;
			case 'de':
				$orderParams = [
					'id'  => '1195',
					'sec' => 'q1rq2q'
				];
				break;
			case 'br':
				$orderParams = [
					'id'  => '1197',
					'sec' => 'nvobax'
				];
				break;
			case 'la':
				$orderParams = [
					'id'  => '1196',
					'sec' => 'dkdhid'
				];
				break;
			case 'by':
				$orderParams = [
					'id'  => '1205',
					'sec' => '31inm5'
				];
				break;
			case 'kz':
				$orderParams = [
					'id'  => '1203',
					'sec' => '6nkdb1'
				];
				break;
			case 'ua':
				$orderParams = [
					'id'  => '1204',
					'sec' => '277p0u'
				];
				break;
			case 'pl':
				$orderParams = [
					'id'  => '1198',
					'sec' => 'h1013r'
				];
				break;
			case 'it':
				$orderParams = [
					'id'  => '1199',
					'sec' => 'xsrbsh'
				];
				break;
			case 'fr':
				$orderParams = [
					'id'  => '1200',
					'sec' => '3oupk4'
				];
				break;
			case 'tr':
				$orderParams = [
					'id'  => '1202',
					'sec' => 'k3bnjz'
				];
				break;
			case 'vn':
				$orderParams = [
					'id'  => '1201',
					'sec' => '9dxb9d'
				];
				break;
			default : //en
				$orderParams = [
					'id'  => '1194',
					'sec' => '6nivh3'
				];
		}
		$orderParams['lang'] = LANGUAGE_ID;

		return $orderParams;
	}

	public function getLicenseDataAction()
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return false;
		}

		$isCloud = true;

		$licenseType = \CBitrix24::getLicenseType();
		$licenseFamily = \CBitrix24::getLicenseFamily();
		$licensePrefix = \CBitrix24::getLicensePrefix();
		$isRusZone = in_array($licensePrefix, ['ru', 'ua', 'by', 'kz']);
		$licenseTill = Option::get('main', '~controller_group_till');
		$licenseTillMessage = '';
		$daysLeftMessage = '';
		$daysLeft = 0;
		$isLicenseDateUnlimited = \CBitrix24::isLicenseDateUnlimited();

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

		$demoStart = Option::get("bitrix24", "DEMO_START");
		$demoLicenseTillMessage = "";
		$demoDaysLeftMessage = "";
		$demoDaysLeft = 0;

		if (intval($demoStart) > 0)
		{
			$demoDaysLeft = intval(($demoStart + 30 * 24 * 60 * 60 - $currentDate) / 60 / 60 / 24);

			$demoLicenseTillConverted = ConvertTimeStamp($demoStart + 30 * 24 * 60 * 60);
			$demoLicenseTillMessage = Loc::getMessage('INTRANET_LICENSE_TILL', [
				'#LICENSE_TILL#' => $demoLicenseTillConverted
			]);
			$demoDaysLeftMessage = Loc::getMessage('INTRANET_LICENSE_DAYS_LEFT_SHORT', [
				'#NUM_DAYS#' => FormatDate("ddiff", $currentDate, $demoStart + 30 * 24 * 60 * 60)
			]);
		}

		$isAdmin = (
			Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())
			|| \Bitrix\Main\Engine\CurrentUser::get()->isAdmin()
		)
			? true : false;

		$analyticaLabel = '?analyticsLabel[headerPopup]=Y&analyticsLabel[licenseType]='.$licenseType;

		$licenseData = [
			'license' => [
				'name' => \CBitrix24::getLicenseName(),
				'type' => $licenseFamily,
				'tillMessage' => $licenseTillMessage,
				'demoPath' => \CBitrix24::PATH_LICENSE_DEMO . $analyticaLabel,
				'allPath' => \CBitrix24::PATH_LICENSE_ALL . $analyticaLabel,
				'myPath' => \CBitrix24::PATH_LICENSE_MY . $analyticaLabel,
				'ordersPath' => "/settings/order/",
				'isDemo' => \CBitrix24::IsDemoLicense(),
				'isFreeTariff' => $licenseFamily === 'project',
				'isCompanyTariff' => $licenseFamily === 'company',
				'isUnlimitedDateTariff' => $isLicenseDateUnlimited,
				'isDemoAvailable' => \Bitrix\Bitrix24\Feature::isEditionTrialable('demo'),
				'demoDaysLeftMessage' => $demoDaysLeftMessage,
				'demoTillMessage' => $demoLicenseTillMessage,
				'isDemoExpired' => $demoDaysLeft < 14,
				'isAlmostExpired' => (
					$licenseFamily !== 'project'
					&& $isRusZone
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
				'isRusZone' => $isRusZone,
			],
			'market' => [
				'isMarketAvailable' => $licensePrefix === 'ru',
			],
			'isAdmin' => $isAdmin,
			'isCloud' => ModuleManager::isModuleInstalled("bitrix24"),
			'partner' => [],
		];

		if ($licenseData['market']['isMarketAvailable'])
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
			$orderParams = $this->getPartnerOrderFormParams($licensePrefix);

			$licenseData['partner'] = [
				'isPartnerConnect' => false,
				'isPartnerOrder' => true,
				'orderPartnerJs' => $orderParams,
			];
		}

		return $licenseData;
	}
}
