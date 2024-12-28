<?php
namespace Bitrix\BIConnector\Configuration;

use Bitrix\BIConnector\Superset\MarketDashboardManager;

/**
 * Class DashboardTariffConfigurator
 * Check dashboards by B24 tariff restrictions.
 *
 * @package Bitrix\BIConnector\Configuration
 */
final class DashboardTariffConfigurator
{
	static ?array $restrictedDashboardCodes;
	/**
	 * @param string $appId
	 *
	 * @return string|null
	 */
	public static function getSliderRestrictionCodeByAppId(string $appId): ?string
	{
		return match ($appId)
		{
			'bitrix.bic_source_expenses' => 'limit_BI_analyst_workplace',
			default => null
		};
	}

	/**
	 * Check dashboard tariff restrictions by APP_ID
	 *
	 * @param string $appId
	 *
	 * @return bool
	 */
	public static function isAvailableDashboard(string $appId): bool
	{
		if (!MarketDashboardManager::isSystemAppByAppCode($appId))
		{
			return true;
		}

		return !in_array($appId, self::getRestrictedSystemDashboards(), true);
	}

	/**
	 * @return string[]
	 */
	private static function getRestrictedSystemDashboards(): array
	{
		if (isset(self::$restrictedDashboardCodes))
		{
			return self::$restrictedDashboardCodes;
		}

		$restrictedDashboards = [];

		if (!Feature::isSourceExpensesEnabled())
		{
			$restrictedDashboards[] = [
				'bitrix.bic_source_expenses',
			];
		}

		self::$restrictedDashboardCodes = array_merge(...$restrictedDashboards);
		
		return self::$restrictedDashboardCodes;
	}
}
