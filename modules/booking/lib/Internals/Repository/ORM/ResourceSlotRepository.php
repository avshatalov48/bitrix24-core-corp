<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Exception\Resource\ResourceSlotException;
use Bitrix\Booking\Internals\Model\ResourceSettingsTable;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceSlotMapper;
use Bitrix\Booking\Internals\Repository\ResourceSlotRepositoryInterface;

class ResourceSlotRepository implements ResourceSlotRepositoryInterface
{
	private ResourceSlotMapper $mapper;

	public function __construct(ResourceSlotMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	public function save(Entity\Slot\RangeCollection $rangeCollection): void
	{
		foreach ($rangeCollection as $range)
		{
			$result = $this->mapper->convertToOrm($range)->save();
			if (!$result->isSuccess())
			{
				throw new ResourceSlotException($result->getErrors()[0]->getMessage());
			}
		}
	}

	public function remove(Entity\Slot\RangeCollection $rangeCollection): void
	{
		foreach ($rangeCollection as $range)
		{
			if ($range->getId())
			{
				ResourceSettingsTable::delete($range->getId());
			}
		}
	}
}
