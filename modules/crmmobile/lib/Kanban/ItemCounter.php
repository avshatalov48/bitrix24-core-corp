<?php

namespace Bitrix\CrmMobile\Kanban;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\ArgumentException;

final class ItemCounter
{
	use Singleton;

	private const ERROR_TYPE = 'error';
	private const INCOMING_TYPE = 'incoming';
	private const EMPTY_TYPE = 'empty';

	public function getErrorCounter(int $value): array
	{
		return $this->getCounter(self::ERROR_TYPE, $value);
	}

	public function getIncomingCounter(int $value): array
	{
		return $this->getCounter(self::INCOMING_TYPE, $value);
	}

	public function getEmptyCounter(int $value): array
	{
		return $this->getCounter(self::EMPTY_TYPE, $value);
	}

	private function getCounter(string $type, int $value): array
	{
		$counters = $this->getCounters();
		if (!isset($counters[$type]))
		{
			throw new ArgumentException('Type ' . $type . ' not known');
		}

		return array_merge($counters[$type], ['value' => $value]);
	}

	private function getCounters(): array
	{
		return [
			self::ERROR_TYPE => [
				'id' => self::ERROR_TYPE,
			],
			self::INCOMING_TYPE => [
				'id' => self::INCOMING_TYPE,
			],
			self::EMPTY_TYPE => [
				'id' => self::EMPTY_TYPE,
			],
		];
	}
}
