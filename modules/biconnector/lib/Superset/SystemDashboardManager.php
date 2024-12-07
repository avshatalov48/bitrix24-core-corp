<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\Web\Json;

final class SystemDashboardManager
{
	public const SYSTEM_DASHBOARD_APP_ID_DEALS = 'deals';
	public const SYSTEM_DASHBOARD_APP_ID_LEADS = 'leads';
	public const SYSTEM_DASHBOARD_APP_ID_SALES = 'sales';
	public const SYSTEM_DASHBOARD_APP_ID_SALES_STRUCT = 'sales_struct';
	public const SYSTEM_DASHBOARD_APP_ID_TELEPHONY = 'telephony';

	public const OPTION_NEW_DASHBOARD_NOTIFICATION_LIST = 'new_dashboard_notification_list';
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

	public static function getNewDashboardNotificationUserIds(): array
	{
		$list = Option::get('biconnector', self::OPTION_NEW_DASHBOARD_NOTIFICATION_LIST, null);
		if ($list === null)
		{
			$adminIds = [];
			$users = UserGroupTable::getList([
				'filter' => [
					'=GROUP_ID' => 1,
					'=DATE_ACTIVE_TO' => null,
					'=USER.ACTIVE' => 'Y',
					'=USER.IS_REAL_USER' => 'Y',
				],
				'select' => [ 'USER_ID' ]
			]);

			while ($user = $users->Fetch())
			{
				$adminIds[] = $user['USER_ID'];
			}

			return $adminIds;
		}

		$list = Json::decode($list);

		return is_array($list) ? $list : [];
	}
	public static function notifyUserDashboardModification(SupersetDashboard $dashboard, bool $isModification): void
	{
		if (Loader::includeModule('im'))
		{
			$title = htmlspecialcharsbx($dashboard->getTitle());
			$link = "<a href='/bi/dashboard/detail/{$dashboard->getId()}/'>{$title}</a>";

			$notificationCode = $isModification
				? 'BICONNECTOR_USER_NOTIFICATION_SYSTEM_DASHBOARD_MODIFICATION'
				: 'BICONNECTOR_USER_NOTIFICATION_SYSTEM_DASHBOARD_CREATION'
			;

			$notificationCallback = fn (?string $languageId = null) => Loc::getMessage(
				$notificationCode,
				["#LINK#" => $link],
				$languageId
			);

			foreach (self::getNewDashboardNotificationUserIds() as $userId)
			{
				$notificationFields = [
					'TO_USER_ID' => $userId,
					'FROM_USER_ID' => 0,
					'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
					'NOTIFY_MODULE' => 'biconnector',
					'NOTIFY_TITLE' =>  Loc::getMessage('BICONNECTOR_USER_NOTIFICATION_SYSTEM_DASHBOARD_TITLE'),
					'NOTIFY_MESSAGE' => $notificationCallback,
				];

				\CIMNotify::Add($notificationFields);
			}
		}
	}

	/**
	 * Adds agent to set admin as dashboard's owner if the previous owner was fired.
	 * @param $fields array User fields ACTIVE (Y/N) and ID.
	 *
	 * @return void
	 */
	public static function onAfterUserUpdateHandler(array $fields): void
	{
		if (!SupersetInitializer::isSupersetReady())
		{
			return;
		}

		if (!isset($fields['ACTIVE']))
		{
			return;
		}

		if ($fields['ACTIVE'] === 'N')
		{
			$userId = (int)($fields['ID'] ?? 0);
			if ($userId)
			{
				\CAgent::addAgent(
					"\\Bitrix\\BIConnector\\Integration\\Superset\\Agent::setDefaultOwnerForDashboards({$userId});",
					'biconnector',
					'N',
					300,
					'',
					'Y',
					convertTimeStamp(time() + \CTimeZone::getOffset() + 300, 'FULL')
				);
			}
		}
	}
}
