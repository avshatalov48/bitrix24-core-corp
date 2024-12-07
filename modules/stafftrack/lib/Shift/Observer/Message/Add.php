<?php

namespace Bitrix\StaffTrack\Shift\Observer\Message;

use Bitrix\StaffTrack\Dictionary\Status;
use Bitrix\StaffTrack\Integration\Im\MessageService;
use Bitrix\StaffTrack\Shift\Observer\ObserverInterface;
use Bitrix\StaffTrack\Shift\ShiftDto;

class Add implements ObserverInterface
{
	/**
	 * @param ShiftDto $shiftDto
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function update(ShiftDto $shiftDto): void
	{
		$service = new MessageService($shiftDto->userId);

		if (Status::isWorkingStatus($shiftDto->status))
		{
			$service->sendShiftStart($shiftDto);
		}
		else if (Status::isNotWorkingStatus($shiftDto->status))
		{
			$service->sendShiftCancel($shiftDto);
		}
	}
}