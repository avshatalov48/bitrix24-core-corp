<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params\Resource;

use Bitrix\Booking\Provider\Params\SelectInterface;

class ResourceSelect implements SelectInterface
{
	protected array $select;

	public function __construct(array $select = [])
	{
		$this->select = $select;
	}

	public function prepareSelect(): array
	{
		if (empty($this->select))
		{
			//@todo get rid of this behaviour!
			return $this->getDefaultSelect();
		}

		$fields = ['*'];

		if (in_array('TYPE', $this->select, true))
		{
			$fields[] = 'TYPE';
			$fields[] = 'TYPE.NOTIFICATION_SETTINGS';
		}

		if (in_array('DATA', $this->select, true))
		{
			$fields[] = 'DATA';
		}

		if (in_array('SETTINGS', $this->select, true))
		{
			$fields[] = 'SETTINGS';
		}

		if (in_array('NOTIFICATION_SETTINGS', $this->select, true))
		{
			$fields[] = 'NOTIFICATION_SETTINGS';
		}

		if (in_array('ENTITIES', $this->select, true))
		{
			$fields[] = 'ENTITIES';
		}

		return $fields;
	}

	private function getDefaultSelect(): array
	{
		return [
			'*',
			'TYPE',
			'TYPE.NOTIFICATION_SETTINGS',
			'DATA',
			'SETTINGS',
			'NOTIFICATION_SETTINGS',
			'ENTITIES',
		];
	}
}
