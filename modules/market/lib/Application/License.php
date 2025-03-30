<?php

namespace Bitrix\Market\Application;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CBitrix24;

class License
{
	public static function getInfo($appItem): array
	{
		$licenseInfo = [];

		$licenseInfo['PRIVACY_LINK'] = !empty($appItem['PRIVACY_LINK']) ? $appItem['PRIVACY_LINK'] : Loc::getMessage('MARKET_INSTALL_PRIVACY_LINK');
		$licenseInfo['PRIVACY_TEXT'] = Loc::getMessage('MARKET_INSTALL_PRIVACY_TEXT', ['#LINK#' => $licenseInfo['PRIVACY_LINK']]);


		if (LANGUAGE_ID === 'ru' || LANGUAGE_ID === 'ua' || !empty($appItem['EULA_LINK'])) {
			$licenseInfo['EULA_LINK'] = !empty($appItem['EULA_LINK']) ? $appItem['EULA_LINK'] : Loc::getMessage('MARKET_INSTALL_EULA_LINK', ['#CODE#' => urlencode($appItem['CODE'])]);
			$licenseInfo['EULA_TEXT'] = Loc::getMessage('MARKET_INSTALL_EULA_TEXT', ['#LINK#' => $licenseInfo['EULA_LINK']]);
		}

		if (
			Loader::IncludeModule('bitrix24') &&
			!in_array(CBitrix24::getLicensePrefix(), ['ua', 'kz', 'by'])
		) {
			$licenseInfo['TERMS_OF_SERVICE_LINK'] = Loc::getMessage('MARKET_INSTALL_TERMS_OF_SERVICE_LINK');
			$licenseInfo['TERMS_OF_SERVICE_TEXT'] = Loc::getMessage('MARKET_INSTALL_TERMS_OF_SERVICE_TEXT', ['#LINK#' => $licenseInfo['TERMS_OF_SERVICE_LINK']]);
		}
		elseif (
			Loader::IncludeModule('bitrix24')
			&& CBitrix24::getLicensePrefix() === 'by'
		)
		{
			$licenseInfo['TERMS_OF_SERVICE_LINK'] = Loc::getMessage('MARKET_INSTALL_TERMS_OF_SERVICE_LINK_BY');
			$licenseInfo['TERMS_OF_SERVICE_TEXT'] = Loc::getMessage('MARKET_INSTALL_TERMS_OF_SERVICE_TEXT', ['#LINK#' => $licenseInfo['TERMS_OF_SERVICE_LINK']]);
		}

		return $licenseInfo;
	}
}