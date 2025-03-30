<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Booking;

use Bitrix\Booking\Provider\Params\SelectInterface;

class BookingSelect implements SelectInterface
{
	protected array $select;

	public function __construct(array $select = [])
	{
		$this->select = $select;
	}

	public function prepareSelect(): array
	{
		$result = [];

		if (in_array('RESOURCES', $this->select, true))
		{
			$result[] = 'RESOURCES';
			$result[] = 'RESOURCES.RESOURCE';
			$result[] = 'RESOURCES.RESOURCE.DATA';
			$result[] = 'RESOURCES.RESOURCE.TYPE';
			$result[] = 'RESOURCES.RESOURCE.TYPE.NOTIFICATION_SETTINGS';
			$result[] = 'RESOURCES.RESOURCE.SETTINGS';
			$result[] = 'RESOURCES.RESOURCE.NOTIFICATION_SETTINGS';
		}

		if (in_array('CLIENTS', $this->select, true))
		{
			$result[] = 'CLIENTS';
			$result[] = 'CLIENTS.IS_RETURNING';
			$result[] = 'CLIENTS.CLIENT_TYPE';
		}

		if (in_array('EXTERNAL_DATA', $this->select, true))
		{
			$result[] = 'EXTERNAL_DATA';
		}

		if (in_array('NOTE', $this->select, true))
		{
			$result[] = 'NOTE';
		}

		return $result;
	}
}
