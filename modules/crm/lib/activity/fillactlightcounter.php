<?php

namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\Activity\LightCounter\CalculateParams;
use Bitrix\Crm\Activity\LightCounter\CounterLightTime;
use Bitrix\Main\Type\DateTime;

class FillActLightCounter
{

	public function onActAdd(array $actFields, array $pingOffsets): ?DateTime
	{
		if ($actFields['COMPLETED'] === 'Y')
		{
			return null;
		}

		$lightTime = $this->calculateLightTime($actFields, $pingOffsets);
		$this->updateOrCreate($actFields['ID'], $lightTime);
		return $lightTime;
	}

	public function onActUpdate(array $actFields, array $arPrevEntity, array $pingOffsets): ?DateTime
	{
		if ($actFields['COMPLETED'] === 'Y' && $arPrevEntity['COMPLETED'] === 'N')
		{
			$this->remove($actFields['ID']);
		}
		else if ($actFields['COMPLETED'] === 'N')
		{
			$lightTime = $this->calculateLightTime($actFields, $pingOffsets);
			$this->updateOrCreate($actFields['ID'], $lightTime);
			return $lightTime;
		}
		return null;
	}

	public function onActDelete(array $actFields): void
	{
		$this->remove($actFields['ID']);
	}

	private function remove(int $actId): void
	{
		ActCounterLightTimeTable::delete($actId);
	}

	private function updateOrCreate(int $actId, DateTime $lightTime): void
	{
		$rec = ActCounterLightTimeTable::getById($actId)->fetch();

		if (
			$rec
			&& !$this->isDatesEqual($rec['LIGHT_COUNTER_AT'], $lightTime)
		)
		{
			ActCounterLightTimeTable::update($actId, [
				'LIGHT_COUNTER_AT' => $lightTime,
				'IS_LIGHT_COUNTER_NOTIFIED' => $this->isDateLessThenNow($lightTime) ? 'Y' : 'N'
			]);
			return;
		}

		if (!$rec)
		{
			ActCounterLightTimeTable::add([
				'ACTIVITY_ID' => $actId,
				'LIGHT_COUNTER_AT' => $lightTime,
				'IS_LIGHT_COUNTER_NOTIFIED' => $this->isDateLessThenNow($lightTime) ? 'Y' : 'N'
			]);
		}
	}

	private function calculateLightTime(array $actFields, array $pingOffsets): DateTime
	{
		$params = CalculateParams::createFromArrays($actFields, $pingOffsets);

		return (new CounterLightTime())->calculate($params);
	}

	private function isDateLessThenNow(DateTime $lightTime): bool
	{
		$now = new DateTime();
		return $lightTime->getTimestamp() < $now->getTimestamp();
	}

	private function isDatesEqual(?DateTime $date1, ?DateTime $date2): bool
	{
		$ts1 = $date1 ? $date1->getTimestamp() : 0;
		$ts2 = $date2 ? $date2->getTimestamp() : 0;

		return $ts1 === $ts2;
	}

}