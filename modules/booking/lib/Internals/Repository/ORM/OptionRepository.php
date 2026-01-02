<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Internals\Model\OptionTable;
use Bitrix\Booking\Internals\Service\OptionDictionary;
use Bitrix\Booking\Internals\Repository\OptionRepositoryInterface;

class OptionRepository implements OptionRepositoryInterface
{
	public function __construct(private array $cache = [])
	{
	}

	public function set(int $userId, OptionDictionary $option, string|null $value): void
	{
		unset($this->cache[$userId]);

		if ($value === null)
		{
			$this->removeOption($userId, $option);

			return;
		}

		$this->mergeOption($userId, $option, $value);
	}

	public function get(
		int $userId,
		OptionDictionary $option,
		string|null $default = null,
		bool $useCache = true,
	): string|null
	{
		if (!$useCache)
		{
			return $this->getRow($userId, $option)['VALUE'] ?? $default;
		}

		if (($this->cache[$userId] ?? null) === null)
		{
			$userOptions = $this->getForUser($userId);
			$this->cache[$userId] = $userOptions;
		}

		return $this->cache[$userId][$option->value] ?? $default;
	}

	private function getForUser(int $userId): array
	{
		$options = OptionDictionary::cases();
		$optionRows = $this->getRows($userId, $options);

		$optionNames = array_map(static fn (OptionDictionary $option) => $option->value, $options);
		$userOptionsMap = array_fill_keys($optionNames, null);

		foreach ($optionRows as $optionRow)
		{
			$userOptionsMap[$optionRow['NAME']] = $optionRow['VALUE'];
		}

		return $userOptionsMap;
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

	/**
	 * @param OptionDictionary[] $options
	 */
	private function getRows(int $userId, array $options): array
	{
		return OptionTable::query()
			->setSelect(['ID', 'NAME', 'VALUE'])
			->where('USER_ID', '=', $userId)
			->whereIn('NAME', array_map(static fn (OptionDictionary $option) => $option->value, $options))
			->setLimit(count($options))
			->exec()
			->fetchAll()
		;
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

	private function mergeOption(int $userId, OptionDictionary $option, string $value): void
	{
		OptionTable::merge(
			[
				'USER_ID' => $userId,
				'NAME' => $option->value,
				'VALUE' => $value,
			],
			[
				'VALUE' => $value,
			],
			[
				'USER_ID',
				'NAME',
			]
		);
	}
}
