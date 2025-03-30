<?php

namespace Bitrix\HumanResources\Util;

use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\ORM\Fields\ExpressionField;

class NodeMemberCounterHelper
{
	private const CACHE_TTL = 86400;

	/**
	 * @param int $nodeId
	 * @param bool $withAllChildNodes
	 *
	 * @return int|null
	 */
	public function countByNodeId(int $nodeId, bool $withAllChildNodes = false): ?int
	{
		$cacheManager = Container::getCacheManager();

		$cacheId = 'node_member_count_' . $nodeId . '_' . ($withAllChildNodes ? 'Y' : 'N');
		$cacheDir = NodeMemberRepository::NODE_MEMBER_CACHE_DIR;

		$result = $cacheManager->getData($cacheId, $cacheDir);

		if ($result !== null)
		{
			return $result;
		}

		try
		{
			$countQuery =
				Model\NodeMemberTable::query()
					->setSelect(['CNT'])
					->registerRuntimeField(
						'',
						new ExpressionField(
							'CNT',
							'COUNT(*)',
						),
					)
					->where('ACTIVE', 'Y')
					->where('NODE.CHILD_NODES.PARENT_ID', $nodeId)
					->setCacheTtl(self::CACHE_TTL)
					->cacheJoins(true)
			;

			if (!$withAllChildNodes)
			{
				$countQuery->where('NODE.CHILD_NODES.DEPTH', 0);
			}

			$result = $countQuery->fetch();
		}
		catch (\Exception $e)
		{
			return 0;
		}

		$cacheManager->setData($cacheId, $cacheDir, $result['CNT'] ?? 0);

		return $result['CNT'] ?? 0;
	}
}