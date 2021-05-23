<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\Date;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

class CIntranetLicenseWidgetComponent extends CBitrixComponent
{
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

		$date= new Date;
		$currentDate = $date->getTimestamp();

		if (intval($licenseTill))
		{
			$daysLeft = intval(($licenseTill - $currentDate) / 60 / 60 / 24);
		}

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

		$this->includeComponentTemplate();
	}
}