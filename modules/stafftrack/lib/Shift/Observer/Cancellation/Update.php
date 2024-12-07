<?php

namespace Bitrix\StaffTrack\Shift\Observer\Cancellation;

use Bitrix\StaffTrack\Dictionary\Status;
use Bitrix\StaffTrack\Model\ShiftCancellationTable;
use Bitrix\StaffTrack\Shift\Observer\ObserverInterface;
use Bitrix\StaffTrack\Shift\ShiftDto;
use Bitrix\StaffTrack\Shift\ShiftRegistry;

class Update implements ObserverInterface
{
	/**
	 * @param ShiftDto $shiftDto
	 * @return void
	 * @throws \Exception
	 */
	public function update(ShiftDto $shiftDto): void
	{
		$shiftModel = ShiftRegistry::getInstance()->get($shiftDto->id);

		if (
			!empty($shiftDto->cancelReason)
			&& Status::isNotWorkingStatus($shiftDto->status)
			&& !$shiftModel?->getCancellation()?->getId()
		)
		{
			ShiftCancellationTable::add([
				'SHIFT_ID' => $shiftDto->id,
				'REASON' => $shiftDto->cancelReason,
				'DATE_CANCEL' => $shiftDto->dateCancel ?? null,
			]);
		}
	}
}