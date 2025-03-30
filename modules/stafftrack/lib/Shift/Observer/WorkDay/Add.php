<?php

namespace Bitrix\StaffTrack\Shift\Observer\WorkDay;

use Bitrix\Main\LoaderException;
use Bitrix\StaffTrack\Dictionary\Status;
use Bitrix\StaffTrack\Integration\Timeman\WorkDayService;
use Bitrix\StaffTrack\Shift\Observer\ObserverInterface;
use Bitrix\StaffTrack\Shift\ShiftDto;

class Add implements ObserverInterface
{
	/**
	 * @param ShiftDto $shiftDto
	 *
	 * @return void
	 * @throws LoaderException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update(ShiftDto $shiftDto): void
	{
		if ($shiftDto->skipTm === true)
		{
			return;
		}

		if ($shiftDto->status === Status::WORKING->value)
		{
			(new WorkDayService())->handleWorkDayAfterShiftStart();
		}
	}
}
