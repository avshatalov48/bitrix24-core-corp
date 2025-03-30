<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Internals\Exception\OptionSetException;
use Bitrix\Booking\Internals\Model\OptionTable;
use Bitrix\Booking\Internals\Service\OptionDictionary;
use Bitrix\Booking\Internals\Repository\OptionRepositoryInterface;

class OptionRepository implements OptionRepositoryInterface
{
	public function set(int $userId, OptionDictionary $option, string|null $value): void
	{
		if ($value === null)
		{
			$this->removeOption($userId, $option);

			return;
		}

		$row = $this->getRow($userId, $option);

		if (!$row)
		{
			$this->addOption($userId, $option, $value);

			return;
		}

		$result = OptionTable::update(
			$row['ID'],
			[
				'USER_ID' => $userId,
				'NAME' => $option->value,
				'VALUE' => $value,
			],
		);

		if (!$result->isSuccess())
		{
			throw new OptionSetException($result->getErrors()[0]->getMessage());
		}
	}

	public function get(int $userId, OptionDictionary $option, string|null $default = null): string|null
	{
		return $this->getRow($userId, $option)['VALUE'] ?? $default;
	}

	private function getRow(int $userId, OptionDictionary $option): array|null
	{
		$result = OptionTable::query()
			->setSelect(['ID', 'VALUE'])
			->where('USER_ID', '=', $userId)
			->where('NAME', '=', $option->value)
			->setLimit(1)
			->exec()
			->fetch()
		;

		if (!$result)
		{
			return null;
		}

		return $result;
	}

	private function removeOption(int $userId, OptionDictionary $option): void
	{
		$row = $this->getRow($userId, $option);

		if (!$row)
		{
			return;
		}

		OptionTable::delete($row['ID']);
	}

	private function addOption(int $userId, OptionDictionary $option, string $value): void
	{
		$result = OptionTable::add([
			'USER_ID' => $userId,
			'NAME' => $option->value,
			'VALUE' => $value,
		]);

		if (!$result->isSuccess())
		{
			throw new OptionSetException($result->getErrors()[0]->getMessage());
		}
	}
}
