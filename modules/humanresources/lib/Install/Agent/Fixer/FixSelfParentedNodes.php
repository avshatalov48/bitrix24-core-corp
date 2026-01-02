<?php

namespace Bitrix\HumanResources\Install\Agent\Fixer;

use Bitrix\HumanResources\Exception\DeleteFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Install\Agent\BaseAgent;
use Bitrix\HumanResources\Model\NodeTable;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\StructureHelper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Psr\Log\LogLevel;
use Throwable;

final class FixSelfParentedNodes extends BaseAgent
{
	private const LIMIT = 100;

	public static function run(int $offset = 0): string
	{
		try
		{
			$rootNode = StructureHelper::getRootStructureDepartment();
		}
		catch (\Exception $exception)
		{
			Container::getStructureLogger()->logMessage(
				LogLevel::ERROR,
				'FixSelfParentedNodes: Error while removing self-parented nodes: ' . $exception->getMessage(),
			);

			return self::next($offset + self::LIMIT);
		}

		if (!$rootNode?->id)
		{
			return self::finish();
		}

		$nodes = NodeTable::query()
			->setSelect(['ID'])
			->whereColumn('ID', 'PARENT_ID')
			->setOffset($offset)
			->setLimit(self::LIMIT)
			->exec()
			->fetchAll()
		;

		$ids = array_column($nodes, 'ID');
		if (empty($ids))
		{
			return self::finish();
		}

		try
		{
			self::updateSelfParentedNodes($ids, $rootNode->id);
		}
		catch (\Exception $exception)
		{
			Container::getStructureLogger()->logMessage(
				LogLevel::ERROR,
				'FixSelfParentedNodes: Error while removing self-parented nodes: ' . $exception->getMessage(),
			);
		}
		finally
		{
			return self::next($offset + self::LIMIT);
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function updateSelfParentedNodes(array $ids, int $rootNodeId): void
	{
		$nodeService = Container::getNodeService();
		$nodeRepository = Container::getNodeRepository();
		foreach ($ids as $id)
		{
			$node = $nodeRepository->getById($id);
			if ($node)
			{
				$node->parentId = $rootNodeId;
				$nodeService->updateNode($node);
			}
		}
	}
}
