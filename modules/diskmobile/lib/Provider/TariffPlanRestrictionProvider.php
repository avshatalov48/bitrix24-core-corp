<?php

namespace Bitrix\DiskMobile\Provider;

use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

final class TariffPlanRestrictionProvider
{
	/**
	 * Handler for mobile event onTariffRestrictionsCollect
	 *
	 * @return EventResult
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getTariffPlanRestrictions(): EventResult
	{
		return new EventResult(
			EventResult::SUCCESS,
			[
				'restrictions' => [
					'disk_common_storage' => [
						'code' => 'disk_common_storage',
						'title' => Loc::getMessage('M_DISK_TARIFF_PLAN_RESTRICTION_COMMON_STORAGE'),
						'isRestricted' => !Bitrix24Manager::isFeatureEnabled('disk_common_storage'),
						'isPromo' => false,
					],
				],
			],
			'diskmobile',
		);
	}
}
