<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Repository\Mapper;

use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Type\MemberEntityType;

class NodeMemberMapper
{
	public function convertFromOrmArray(array $nodeMember): NodeMember
	{
		return new NodeMember(
			entityType: MemberEntityType::tryFrom($nodeMember['ENTITY_TYPE'] ?? '') ?? null,
			entityId: isset($nodeMember['ENTITY_ID']) ? (int)$nodeMember['ENTITY_ID'] : null,
			nodeId: isset($nodeMember['NODE_ID']) ? (int)$nodeMember['NODE_ID'] : null,
			active: isset($nodeMember['ACTIVE']) ? $nodeMember['ACTIVE'] === 'Y' : null,
			roles: [$nodeMember['HUMANRESOURCES_MODEL_NODE_MEMBER_ROLE_ID'] ?? null],
			id: isset($nodeMember['ID']) ? (int)$nodeMember['ID'] : null,
			addedBy: isset($nodeMember['ADDED_BY']) ? (int)$nodeMember['ADDED_BY'] : null,
			createdAt: $nodeMember['CREATED_AT'] ?? null,
			updatedAt: $nodeMember['UPDATED_AT'] ?? null,
		);
	}
}