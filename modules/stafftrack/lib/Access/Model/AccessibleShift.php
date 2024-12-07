<?php

namespace Bitrix\StaffTrack\Access\Model;

use Bitrix\Main\Access\AccessibleItem;

interface AccessibleShift extends AccessibleItem
{
	public function getUserId(): int;
}