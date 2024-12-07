<?php

namespace Bitrix\Mobile\TariffPlanRestriction\Provider;

use Bitrix\Bitrix24\License;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Mobile\TariffPlanRestriction\Dto\FeatureRestrictionDto;

class TariffPlanRestrictionProvider
{
	public function getTariffPlanRestrictions(): array
	{
		$tariffPlanRestrictions = [];

		$event = new Event('mobile', 'onTariffRestrictionsCollect');
		$event->send();

		$eventResults = $event->getResults();
		if (empty($eventResults) || !is_array($eventResults))
		{
			return $tariffPlanRestrictions;
		}

		foreach ($eventResults as $eventResult)
		{
			if ($eventResult->getType() !== EventResult::SUCCESS)
			{
				continue;
			}

			$restrictions = ($eventResult->getParameters()['restrictions'] ?? []);
			if (empty($restrictions) || !is_array($restrictions))
			{
				continue;
			}

			foreach ($restrictions as $key => $value)
			{
				$tariffPlanRestrictions[$key] = FeatureRestrictionDto::make($value);
			}
		}

		return $tariffPlanRestrictions;
	}

	public function isDemoAvailable(): bool
	{
		return (
			Loader::includeModule('bitrix24')
			&& License::getCurrent()->getDemo()->isAvailable()
		);
	}

	public function activateDemo(): bool
	{
		return (
			Loader::includeModule('bitrix24')
			&& License::getCurrent()->getDemo()->isAvailable()
			&& !License::getCurrent()->getDemo()->activate()->isSuccess()
		);
	}
}
