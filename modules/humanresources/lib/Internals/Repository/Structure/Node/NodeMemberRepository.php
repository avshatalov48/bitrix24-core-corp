<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Repository\Structure\Node;

use Bitrix\HumanResources\Model\NodeMemberTable;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;

final class NodeMemberRepository
{
	private const CACHE_TTL = 86400;

	/**
	 * @param NodeEntityType $nodeType
	 * @param bool $active
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getMultipleNodeMembers(
		NodeEntityType $nodeType,
		bool $active = true
	): array
	{
		$subQuery = NodeMemberTable::query()
			->setSelect(['ENTITY_ID'])
			->where('ENTITY_TYPE', MemberEntityType::USER->value)
			->where('NODE.TYPE', $nodeType->name)
			->where('ACTIVE', $active ? 'Y' : 'N')
			->registerRuntimeField(
				new ExpressionField('MEMBER_CNT', 'COUNT(DISTINCT %s)', ['NODE_ID'])
			)
			->setGroup(['ENTITY_ID'])
			->where('MEMBER_CNT', '>=', 2)
		;

		$nodeMemberQuery = NodeMemberTable::query()
			->setSelect(['ENTITY_ID', 'NODE_ID'])
			->whereIn('ENTITY_ID', $subQuery)
			->cacheJoins(true)
			->setCacheTtl(self::CACHE_TTL)
		;

		$nodeMemberArray = [];
		foreach ($nodeMemberQuery->fetchAll() as $nodeMember)
		{
			if (
				$nodeMember['ENTITY_ID'] ?? null
				&& $nodeMember['NODE_ID'] ?? null
			)
			{
				$nodeMemberArray[(int)$nodeMember['ENTITY_ID']][] = (int)$nodeMember['NODE_ID'];
			}
		}

		return $nodeMemberArray;
	}
}