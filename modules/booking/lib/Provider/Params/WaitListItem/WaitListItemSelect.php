<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\WaitListItem;

use Bitrix\Booking\Provider\Params\SelectInterface;

class WaitListItemSelect implements SelectInterface
{
	private array $select;

	public function __construct(array $select = [])
	{
		$this->select = $select;
	}

	public function prepareSelect(): array
	{
		$result = [];

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
