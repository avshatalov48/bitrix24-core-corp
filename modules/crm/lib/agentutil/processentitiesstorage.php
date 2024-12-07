<?php

namespace Bitrix\Crm\AgentUtil;

use Bitrix\Crm\AgentUtil\ProcessEntitiesStorage\OptionEnum;
use Bitrix\Main\Config\Option;

final class ProcessEntitiesStorage
{
	private const DEFAULT_PROCESS_LIMIT = 100;
	private const DEFAULT_LAST_ENTITY_ID = -1;
	private const MODULE = 'crm';

	private const ELEMENTS_SEPARATOR = ',';

	public function __construct(
		private readonly string $agent,
	)
	{
	}

	public function getCurrentEntityTypeId(): ?int
	{
		$entityTypeIds = $this->getEntityTypeIds();

		return reset($entityTypeIds) ?: null;
	}

	public function shiftEntityTypeIds(): self
	{
		$entityTypeIds = $this->getEntityTypeIds();
		if (empty($entityTypeIds))
		{
			return $this;
		}

		array_shift($entityTypeIds);
		if (empty($entityTypeIds))
		{
			$this->clearAll();

			return $this;
		}

		return $this
			->setEntityTypeIds($entityTypeIds)
			->clear(OptionEnum::LAST_ENTITY_ID)
		;
	}

	private function setEntityTypeIds(array $entityTypeIds): self
	{
		$entityTypeIdsStr = implode(self::ELEMENTS_SEPARATOR, $entityTypeIds);

		return $this->setOption(OptionEnum::ENTITY_TYPE_IDS, $entityTypeIdsStr);
	}

	public function getLastEntityId(): int
	{
		return (int)$this->getOption(OptionEnum::LAST_ENTITY_ID, self::DEFAULT_LAST_ENTITY_ID);
	}

	public function setLastEntityId(int $lastEntityId): self
	{
		return $this->setOption(OptionEnum::LAST_ENTITY_ID, $lastEntityId);
	}

	public function getProcessLimit(): int
	{
		return (int)$this->getOption(OptionEnum::LIMIT, self::DEFAULT_PROCESS_LIMIT);
	}

	public function setProcessLimit(int $processLimit): self
	{
		return $this->setOption(OptionEnum::LIMIT, $processLimit);
	}

	/**
	 * @return int[]
	 */
	private function getEntityTypeIds(): array
	{
		$entityTypeIdsStr = $this->getOption(OptionEnum::ENTITY_TYPE_IDS, '');
		if (empty($entityTypeIdsStr))
		{
			return [];
		}

		$entityTypeIds = explode(self::ELEMENTS_SEPARATOR, $entityTypeIdsStr);

		return array_map('intval', $entityTypeIds);
	}

	private function clearAll(): void
	{
		foreach (OptionEnum::cases() as $option)
		{
			$this->clear($option);
		}
	}

	private function clear(OptionEnum $option): self
	{
		Option::delete(self::MODULE, [
			'name' => $this->getOptionName($option),
		]);

		return $this;
	}

	private function getOption(OptionEnum $option, mixed $defaultValue = ''): string
	{
		return Option::get(self::MODULE, $this->getOptionName($option), $defaultValue);
	}

	private function setOption(OptionEnum $option, mixed $value): self
	{
		Option::set(self::MODULE, $this->getOptionName($option), $value);

		return $this;
	}

	private function getOptionName(OptionEnum $optionEnum): string
	{
		return $optionEnum->getOptionName($this->agent);
	}
}
