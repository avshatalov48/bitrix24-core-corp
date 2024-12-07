<?php

namespace Bitrix\StaffTrack\Shift\Observer\Geo;

use Bitrix\StaffTrack\Model\ShiftGeoTable;
use Bitrix\StaffTrack\Shift\Observer\ObserverInterface;
use Bitrix\StaffTrack\Shift\ShiftDto;

class Add implements ObserverInterface
{
	/**
	 * @param ShiftDto $shiftDto
	 * @return void
	 * @throws \Exception
	 */
	public function update(ShiftDto $shiftDto): void
	{
		if (!empty($shiftDto->geoImageUrl) && !empty($shiftDto->address))
		{
			ShiftGeoTable::add([
				'SHIFT_ID' => $shiftDto->id,
				'IMAGE_URL' => $shiftDto->geoImageUrl,
				'ADDRESS' => $shiftDto->address,
			]);
		}
	}
}