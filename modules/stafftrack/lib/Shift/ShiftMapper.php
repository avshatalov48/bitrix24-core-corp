<?php

namespace Bitrix\StaffTrack\Shift;

use Bitrix\StaffTrack\Model\Shift;

class ShiftMapper
{
	public function createEntityFromDto(ShiftDto $shiftDto): Shift
	{
		$shift = (new Shift(false))
			->setUserId($shiftDto->userId)
			->setStatus($shiftDto->status)
			->setLocation($shiftDto->location)
		;

		if (!empty($shiftDto->id))
		{
			$shift->setId($shiftDto->id);
		}

		if ($shiftDto->shiftDate !== null)
		{
			$shift->setShiftDate($shiftDto->shiftDate);
		}

		if ($shiftDto->dateCreate !== null)
		{
			$shift->setDateCreate($shiftDto->dateCreate);
		}

		return $shift;
	}

	public static function createDtoFromEntity(Shift $shift): ShiftDto
	{
		return (new ShiftDto())
			->setId($shift->getId())
			->setUserId($shift->getUserId())
			->setShiftDate($shift->getShiftDate())
			->setDateCreate($shift->getDateCreate())
			->setLocation($shift->getLocation())
			->setStatus($shift->getStatus())
		;
	}
}
