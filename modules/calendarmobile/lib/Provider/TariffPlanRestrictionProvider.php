<?php

namespace Bitrix\CalendarMobile\Provider;

use Bitrix\Calendar\Integration\Bitrix24\FeatureDictionary;
use Bitrix\Calendar\Integration\Bitrix24Manager;
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
					FeatureDictionary::CALENDAR_SHARING => [
						'code' => FeatureDictionary::CALENDAR_SHARING,
						'title' => Loc::getMessage('CALENDARMOBILE_TARIFF_PLAN_RESTRICTION_SHARING'),
						'isRestricted' => !Bitrix24Manager::isFeatureEnabled(FeatureDictionary::CALENDAR_SHARING),
						'isPromo' => Bitrix24Manager::isPromoFeatureEnabled(FeatureDictionary::CALENDAR_SHARING),
					],
					FeatureDictionary::CALENDAR_LOCATION => [
						'code' => FeatureDictionary::CALENDAR_LOCATION,
						'title' => Loc::getMessage('CALENDARMOBILE_TARIFF_PLAN_RESTRICTION_LOCATION'),
						'isRestricted' => !Bitrix24Manager::isFeatureEnabled(FeatureDictionary::CALENDAR_LOCATION),
						'isPromo' => Bitrix24Manager::isPromoFeatureEnabled(FeatureDictionary::CALENDAR_LOCATION),
					],
					FeatureDictionary::CALENDAR_EVENTS_WITH_PLANNER => [
						'code' => FeatureDictionary::CALENDAR_EVENTS_WITH_PLANNER,
						'title' => Loc::getMessage('CALENDARMOBILE_TARIFF_PLAN_RESTRICTION_PLANNER'),
						'isRestricted' => !Bitrix24Manager::isFeatureEnabled(FeatureDictionary::CALENDAR_EVENTS_WITH_PLANNER),
						'isPromo' => Bitrix24Manager::isPromoFeatureEnabled(FeatureDictionary::CALENDAR_EVENTS_WITH_PLANNER),
					],
					FeatureDictionary::CRM_EVENT_SHARING => [
						'code' => FeatureDictionary::CRM_EVENT_SHARING,
						'title' => Loc::getMessage('CALENDARMOBILE_TARIFF_PLAN_RESTRICTION_SHARING'),
						'isRestricted' => !Bitrix24Manager::isFeatureEnabled(FeatureDictionary::CRM_EVENT_SHARING),
						'isPromo' => Bitrix24Manager::isPromoFeatureEnabled(FeatureDictionary::CRM_EVENT_SHARING),
					],
				],
			],
			'calendarmobile',
		);
	}
}
