<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

final class SystemDashboardManager
{
	public const SYSTEM_DASHBOARD_APP_ID_DEALS = 'deals';
	public const SYSTEM_DASHBOARD_APP_ID_LEADS = 'leads';
	public const SYSTEM_DASHBOARD_APP_ID_SALES = 'sales';
	public const SYSTEM_DASHBOARD_APP_ID_SALES_STRUCT = 'sales_struct';
	public const SYSTEM_DASHBOARD_APP_ID_TELEPHONY = 'telephony';

	private const RU_ZONE = 'ru';
	private const EN_ZONE = 'en';
	private const KZ_ZONE = 'kz';
	private const BY_ZONE = 'by';

	public static function resolveMarketAppId(string $appId): string
	{
		$appIdTemplate = 'bitrix.bic_#CODE#_#LANG#';
		$lang = self::getDashboardLanguageCode();

		return strtr(
			$appIdTemplate,
			[
				'#CODE#' => $appId,
				'#LANG#' => $lang,
			],
		);
	}

	public static function getDashboardTitleByAppId(string $appId): string
	{
		return match ($appId)
		{
			self::SYSTEM_DASHBOARD_APP_ID_DEALS => Loc::getMessage('BICONNECOR_SYSTEM_DASHBOARD_TITLE_DEALS'),
			self::SYSTEM_DASHBOARD_APP_ID_LEADS => Loc::getMessage('BICONNECOR_SYSTEM_DASHBOARD_TITLE_LEADS'),
			self::SYSTEM_DASHBOARD_APP_ID_TELEPHONY => Loc::getMessage('BICONNECOR_SYSTEM_DASHBOARD_TITLE_TELEPHONY'),
			self::SYSTEM_DASHBOARD_APP_ID_SALES => Loc::getMessage('BICONNECOR_SYSTEM_DASHBOARD_TITLE_SALES'),
			self::SYSTEM_DASHBOARD_APP_ID_SALES_STRUCT => Loc::getMessage('BICONNECOR_SYSTEM_DASHBOARD_TITLE_SALES_STRUCT'),
			default => '',
		};
	}

	private static function getDashboardLanguageCode(): string
	{
		$zone = null;
		if (Loader::includeModule('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
		}
		elseif (Loader::includeModule('intranet'))
		{
			$zone = \CIntranetUtils::getPortalZone();
		}

		if ($zone === self::RU_ZONE || $zone === self::BY_ZONE)
		{
			return self::RU_ZONE;
		}

		if ($zone === self::KZ_ZONE)
		{
			return self::KZ_ZONE;
		}

		return self::EN_ZONE;
	}
}
