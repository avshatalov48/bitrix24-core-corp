<?

namespace Bitrix\Mobile\Integration\Crm;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Tab\Manager;
use Bitrix\Main\Config\Option;

class CallTracker
{
	/**
	 * Is call tracker feature available for current user
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return (
			Loader::includeModule('crm')
			&& \CCrmAuthorizationHelper::CheckReadPermission('DEAL', 0)
			&& class_exists('\Bitrix\Crm\Activity\Provider\CallTracker')
			&& Loader::includeModule('mobileapp')
			&& \Bitrix\MobileApp\Mobile::getInstance()
			&& \Bitrix\MobileApp\Mobile::getPlatform() === 'android'
			&& \Bitrix\MobileApp\Mobile::getApiVersion() >= self::getMinApiVersion()
			&& (Option::get('mobile', 'crm_call_tracker_enabled', 'N') === 'Y')
		);
	}

	/**
	 * Menu id
	 * @return string
	 */
	public static function getMenuId(): string
	{
		return 'calltracker';
	}

	/**
	 * Minimal api version which supports call tracker
	 * @return int
	 */
	public static function getMinApiVersion(): int
	{
		return 36;
	}

	/**
	 * If user should see the spotlight, return spotlight params array, or null instead
	 * @return array|null
	 */
	public static function getSpotlightParams(): ?array
	{
		if (!self::isAvailable())
		{
			return null;
		}

		if (self::hasActiveTab())
		{
			return null; // call tracker already in menu, no spotlight required
		}

		return [
			'id' => 'callTracker',
			'minApiVersion' => self::getMinApiVersion(),
			'delayCount' => 4,
			'menuId' => 'more',
			'text' => Loc::getMessage("MOBILE_MENU_SPOTLIGHT_CALL_TRACKER"),
			'icon' => 'tracker',
		];
	}

	public static function hasActiveTab(): bool
	{
		$manager = new Manager();
		$activeMenuTabs = $manager->getActiveTabs();

		return (isset($activeMenuTabs[self::getMenuId()]));
	}
}