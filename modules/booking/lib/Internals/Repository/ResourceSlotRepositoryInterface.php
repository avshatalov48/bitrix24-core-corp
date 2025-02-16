<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Entity;

interface ResourceSlotRepositoryInterface
{
	public function save(Entity\Slot\RangeCollection $rangeCollection): void;
	public function remove(Entity\Slot\RangeCollection $rangeCollection): void;
}
