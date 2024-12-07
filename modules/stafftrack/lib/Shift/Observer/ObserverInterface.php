<?php

namespace Bitrix\StaffTrack\Shift\Observer;

use Bitrix\StaffTrack\Shift\ShiftDto;

interface ObserverInterface
{
	public function update(ShiftDto $shiftDto): void;
}