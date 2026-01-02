<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Repository\Structure;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable;
use Bitrix\HumanResources\Type\AccessCodeType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

final class NodeAccessCodeRepository
{
	public function createByNode(Node $node): ?string
	{
		$existed =
			NodeBackwardAccessCodeTable::query()
				->addSelect('ACCESS_CODE')
				->where('NODE_ID', $node->id)
				->setLimit(1)
				->fetch()
		;

		if ($existed)
		{
			return $existed['ACCESS_CODE'];
		}
		$node->accessCode = AccessCodeType::HrStructureNodeType->value . $node->id;

		return $node->accessCode;
	}

	/**
	 * @param array<string> $accessCodes
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getNodeIdsByAccessCodes(array $accessCodes): array
	{
		$result = [];
		if (empty($accessCodes))
		{
			return $result;
		}

		$processedAccessCodes = [];
		foreach ($accessCodes as $accessCode)
		{
			$processedAccessCodes[] = str_replace(
				AccessCodeType::IntranetDepartmentRecursiveType->value,
				AccessCodeType::IntranetDepartmentType->value,
				$accessCode,
			);
		}

		$nodeAccessCodeQuery =
			NodeBackwardAccessCodeTable::query()
				->addSelect('NODE_ID')
				->whereIn('ACCESS_CODE', $processedAccessCodes)
		;

		$nodeAccessCodeEntities = $nodeAccessCodeQuery->fetchAll();
		foreach ($nodeAccessCodeEntities as $nodeAccessEntity)
		{
			$result[] = (int)$nodeAccessEntity['NODE_ID'];
		}

		return $result;
	}
}