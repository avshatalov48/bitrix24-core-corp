<?php

namespace Bitrix\Crm\Agent\Stage;

use Bitrix\Crm\AgentUtil\ProcessEntitiesStorage;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;

abstract class RepairServiceBase
{
	protected const DONE = false;
	protected const CONTINUE = true;

	public function __construct(
		protected readonly ProcessEntitiesStorage $processStorage,
	)
	{
	}

	abstract protected function repair(int $entityTypeId): ?int;

	final public function execute(): bool
	{
		$currentEntityTypeId = $this->processStorage->getCurrentEntityTypeId();
		if ($currentEntityTypeId === null)
		{
			return self::DONE;
		}

		$lastId = $this->repair($currentEntityTypeId);
		if ($lastId === null)
		{
			$this->processStorage->shiftEntityTypeIds();

			return self::CONTINUE;
		}

		$this->processStorage->setLastEntityId($lastId);

		return self::CONTINUE;
	}

	protected function getFactory(int $entityTypeId): ?Factory
	{
		if (!\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isStagesSupported())
		{
			return null;
		}

		return $factory;
	}

	/**
	 * @return Item[]
	 */
	protected function getItems(Factory $factory): array
	{
		$select = [
			Item::FIELD_NAME_ID,
			...$this->getSelect($factory),
		];

		$filter = [
			'>ID' => $this->processStorage->getLastEntityId(),
			...$this->getFilter($factory),
		];

		return $factory->getItems(
			[
				'select' => $select,
				'filter' => $filter,
				'limit' => $this->processStorage->getProcessLimit(),
				'order' => [ Item::FIELD_NAME_ID => 'ASC' ],
			],
		);
	}

	abstract protected function getSelect(Factory $factory): array;

	protected function getFilter(Factory $factory): array
	{
		return [];
	}
}
