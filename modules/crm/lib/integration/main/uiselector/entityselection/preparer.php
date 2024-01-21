<?php

namespace Bitrix\Crm\Integration\Main\UISelector\EntitySelection;

use Bitrix\Main\ArgumentException;

class Preparer
{
	public const SELECTED_ITEMS_STRATEGY = 1;
	public const LAST_ITEMS_STRATEGY = 2;
	public const HIDDEN_ITEMS_STRATEGY = 3;
	public const SELECTED_ITEMS_FOR_LEAD_STRATEGY = 4;

	protected Entity $entity;

	public function __construct(Entity $entity)
	{
		$this->entity = $entity;
	}

	/**
	 * @param array $items
	 * @param int $strategyType
	 * @return Result
	 * @throws ArgumentException
	 */
	public function prepare(array $items, int $strategyType): Result
	{
		$strategy = $this->getStrategy($strategyType);
		if ($strategy === null)
		{
			throw new ArgumentException('Undefined strategy type');
		}

		return (new Result())
			->setEntities($strategy->getEntities($items))
			->setEntitiesIDs($strategy->getEntitiesIDs($items))
		;
	}

	private function getStrategy(int $strategyType): ?Strategy
	{
		return match($strategyType)
		{
			self::SELECTED_ITEMS_STRATEGY => new Strategy\SelectedEntities($this->entity),
			self::LAST_ITEMS_STRATEGY => new Strategy\LastEntities($this->entity),
			self::HIDDEN_ITEMS_STRATEGY => new Strategy\HiddenEntities($this->entity),
			self::SELECTED_ITEMS_FOR_LEAD_STRATEGY => new Strategy\SelectedEntitiesForLead($this->entity),
			default => null,
		};
	}
}
