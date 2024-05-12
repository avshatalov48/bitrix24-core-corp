<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
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
			$users = \CUser::GetList(
				'ID',
				'ASC',
				[
					'GROUPS_ID' => 1,
					'ACTIVE' => 'Y',
				],
				[
					'FIELDS' => 'ID',
				]
			);
			while ($user = $users->Fetch())
			{
				$adminIds[] = $user['ID'];
			}

			return $adminIds;
		}

		$list = Json::decode($list);

		return is_array($list) ? $list : [];
	}
	public static function notifyUserDashboardModification(EO_SupersetDashboard $dashboard, bool $isModification): void
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
}
