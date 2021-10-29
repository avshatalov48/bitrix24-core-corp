<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\Date;
use Bitrix\Bitrix24;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

class CIntranetLicenseWidgetComponent extends CBitrixComponent
{
	private function getButtonClassName()
	{
		$className = '';

		if ($this->arResult['isFreeLicense'] && !$this->arResult['isAlmostLocked'])
		{
			$className = "ui-btn-icon-tariff license-btn-orange";
		}
		else
		{
			if ($this->arResult['isLicenseAlmostExpired'])
			{
				$className = "license-btn-alert-border ui-btn-icon-low-battery";
			}
			else if ($this->arResult['isLicenseExpired'])
			{
				$className = "license-btn-alert-border ui-btn-icon-battery";
			}
			else if ($this->arResult['isAlmostLocked'])
			{
				$className = "license-btn-alert-border ui-btn-icon-low-battery";
			}
			else
			{
				$className = "ui-btn-icon-tariff license-btn-blue-border";

				if ($this->arResult['isDemoLicense'])
				{
					$className = "ui-btn-icon-demo license-btn-blue-border";
				}
			}
		}

		return $className;
	}

	private function getButtonName()
	{
		$buttonName = '';

		if ($this->arResult['isFreeLicense'])
		{
			$buttonName = Loc::getMessage('INTRANET_LICENSE_WIDGET_BUY_TARIFF');
		}
		elseif ($this->arResult['isDemoLicense'])
		{
			$buttonName = Loc::getMessage('INTRANET_LICENSE_WIDGET_DEMO');
		}
		else
		{
			$buttonName = Loc::getMessage('INTRANET_LICENSE_WIDGET_MY_TARIFF');
		}

		return $buttonName;
	}

	public function executeComponent(): void
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return;
		}

		if (Loader::includeModule('extranet') && \CExtranet::isExtranetSite())
		{
			return;
		}

		$licenseScanner = Bitrix24\LicenseScanner\Manager::getInstance();
		$this->arResult['licenseType'] = \CBitrix24::getLicenseFamily();
		$this->arResult['isFreeLicense'] = $this->arResult['licenseType'] === 'project';
		$this->arResult['isDemoLicense'] = \CBitrix24::IsDemoLicense();
		$isLicenseDateUnlimited = \CBitrix24::isLicenseDateUnlimited();
		$this->arResult['isAutoPay'] = false;

		if (\CBitrix24::IsLicensePaid())
		{
			$this->arResult['isAutoPay'] = Option::get('bitrix24', '~autopay', 'N') === 'Y';
		}

		$daysLeft = 0;
		$licenseTill = Option::get('main', '~controller_group_till');
		$scannerLockTill = $licenseScanner->getLockTill();

		$date= new Date;
		$currentDate = $date->getTimestamp();

		if (intval($licenseTill))
		{
			$daysLeft = intval(($licenseTill - $currentDate) / 60 / 60 / 24);
		}

		$this->arResult['isAlmostLocked'] = $scannerLockTill > $currentDate;

		$this->arResult['isLicenseAlmostExpired'] = (
			!$isLicenseDateUnlimited
			&& !$this->arResult['isAutoPay']
			&& $daysLeft > 0
			&& $daysLeft < 14
		);
		$this->arResult['isLicenseExpired'] = (
			!$isLicenseDateUnlimited
			&& $this->arResult['isAutoPay'] ? $daysLeft < 0: $daysLeft <= 0
		);

		$this->arResult['buttonClassName'] = 'ui-btn ui-btn-round ui-btn-themes license-btn '
			. $this->getButtonClassName()
		;

		$this->arResult['buttonName'] = $this->getButtonName();

		$this->includeComponentTemplate();
	}
}
