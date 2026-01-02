<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Repository\Mapper;

use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Util\AccessCodeHelper;

class NodeMapper
{
	public function convertFromOrmArray(array $node): Node
	{
		$accessCode =
			$node['HUMANRESOURCES_MODEL_NODE_ACCESS_CODE_ACCESS_CODE']
			?? AccessCodeHelper::makeCodeByTypeAndId((int)($node['ID'] ?? 0))
		;

		return new Node(
			name: $node['NAME'] ?? null,
			type: NodeEntityType::tryFrom($node['TYPE'] ?? '') ?? null,
			structureId: isset($node['STRUCTURE_ID']) ? (int)$node['STRUCTURE_ID'] : null,
			accessCode: $accessCode,
			id: isset($node['ID']) ? (int)$node['ID'] : null,
			parentId: isset($node['PARENT_ID']) ? (int)$node['PARENT_ID'] : null,
			depth: isset($node['HUMANRESOURCES_MODEL_NODE_CHILD_NODES_DEPTH'])
				? (int)$node['HUMANRESOURCES_MODEL_NODE_CHILD_NODES_DEPTH']
				: null,
			createdBy: isset($node['CREATED_BY']) ? (int)$node['CREATED_BY'] : null,
			createdAt: $node['CREATED_AT'] ?? null,
			updatedAt: $node['UPDATED_AT'] ?? null,
			xmlId: $node['XML_ID'] ?? null,
			active: isset($node['ACTIVE']) ? $node['ACTIVE'] === 'Y' : null,
			globalActive: isset($node['GLOBAL_ACTIVE']) ? $node['GLOBAL_ACTIVE'] === 'Y' : null,
			sort: isset($node['SORT']) ? (int)$node['SORT'] : 0,
			description: $node['DESCRIPTION'] ?? null,
			colorName: $node['COLOR_NAME'] ?? null,
		);
	}
}