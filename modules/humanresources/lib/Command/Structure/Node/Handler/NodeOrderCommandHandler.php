<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Command\Structure\Node\Handler;

use Bitrix\HumanResources\Command\Structure\Node\NodeOrderCommand;
use Bitrix\HumanResources\Contract\Repository\NodeRepository;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Application;
use Bitrix\HumanResources\Internals;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;

class NodeOrderCommandHandler
{
	private NodeRepository $nodeRepository;
	private Internals\Service\Structure\NodeSettingsService $nodeSettingsService;

	public function __construct()
	{
		$this->nodeRepository = Container::getNodeRepository();
		$this->nodeSettingsService = Internals\Service\Container::getNodeSettingsService();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws \Exception
	 */
	public function __invoke(NodeOrderCommand $command): Result
	{
		$parent = $this->nodeRepository->getById($command->node->parentId);
		$result = new Result();

		if (!$parent)
		{
			return $result;
		}

		$siblings = $this->nodeRepository->getChildOf($parent);

		if ($siblings->empty())
		{
			return $result;
		}

		$direction = $command->direction < 0 ? -1 : ($command->direction >= 1 ? 1 : 0);
		if ($direction === 0)
		{
			return $result;
		}
		$collectionForUpdate = [];

		foreach ($siblings as $sibling)
		{
			if ($sibling->id === $command->node->id)
			{
				$collectionForUpdate = $siblings->getValues();
				$currentIndex = array_search($sibling, $collectionForUpdate, true);

				if ($currentIndex === false)
				{
					return $result;
				}

				$this->move($collectionForUpdate, $currentIndex, $direction, $command->count);
			}
		}

		if (!empty($collectionForUpdate))
		{
			$this->updateBatch($collectionForUpdate);
		}

		return $result;
	}

	/**
	 * @param array<Node> $siblings
	 * @param int $currentIndex
	 * @param int $direction
	 * @param int $count
	 *
	 * @return void
	 */
	private function move(array &$siblings, int $currentIndex, int $direction, int $count): void
	{
		$newIndex = $currentIndex + ($direction * $count);
		$newIndex = max(0, min(count($siblings) - 1, $newIndex));

		$element = array_splice($siblings, $currentIndex, 1)[0];
		array_splice($siblings, $newIndex, 0, [$element]);

		foreach ($siblings as $index => $sibling)
		{
			$sibling->sort = $index * NodeOrderCommand::ORDER_STEP;
		}
	}

	/**
	 * @param array<Node> $collectionForUpdate
	 *
	 * @return void
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws SqlQueryException
	 */
	private function updateBatch(array $collectionForUpdate): void
	{
		Application::getConnection()->startTransaction();

		try
		{
			foreach ($collectionForUpdate as $node)
			{
				$this->nodeRepository->update($node);
			}

			Application::getConnection()->commitTransaction();
		}
		catch (\Exception $e)
		{
			Application::getConnection()->rollbackTransaction();

			throw $e;
		}
	}
}