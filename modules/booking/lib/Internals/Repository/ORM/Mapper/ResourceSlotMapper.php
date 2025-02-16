<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Slot\Range;
use Bitrix\Booking\Entity\Slot\RangeCollection;
use Bitrix\Booking\Internals\Model\EO_ResourceSettings;
use Bitrix\Booking\Internals\Model\EO_ResourceSettings_Collection;
use Bitrix\Booking\Internals\Model\ResourceSettingsTable;
use Bitrix\Main\Web\Json;

class ResourceSlotMapper
{
	public function convertFromOrm(EO_ResourceSettings_Collection|null $collection): RangeCollection
	{
		$slotRanges = [];

		if ($collection)
		{
			foreach ($collection as $ormSlotSetting)
			{
				$slotRanges[] =
					(new Range())
						->setId($ormSlotSetting->getId())
						->setFrom($ormSlotSetting->getTimeFrom())
						->setTo($ormSlotSetting->getTimeTo())
						->setTimezone($ormSlotSetting->getTimezone())
						->setWeekDays(Json::decode($ormSlotSetting->getWeekdays()))
						->setSlotSize($ormSlotSetting->getSlotSize())
						->setResourceId($ormSlotSetting->getResourceId())
				;
			}
		}

		return new RangeCollection(...$slotRanges);
	}

	public function convertToOrm(Range $range): EO_ResourceSettings
	{
		// do not include ::wakeup call here since range is immutable
		$ormSlotRange = ResourceSettingsTable::createObject();

		$ormSlotRange
			->setResourceId($range->getResourceId())
			->setWeekdays(Json::encode($range->getWeekDays()))
			->setTimeFrom($range->getFrom())
			->setTimeTo($range->getTo())
			->setTimezone($range->getTimezone())
			->setSlotSize($range->getSlotSize())
		;

		return $ormSlotRange;
	}
}
