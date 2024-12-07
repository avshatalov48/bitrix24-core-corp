<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Contract\NodeMemberData;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\Type\DateTime;

class NodeMember implements Item
{
	public const DEFAULT_ROLE_XML_ID = [
		'HEAD' => 'MEMBER_HEAD',
		'EMPLOYEE' => 'MEMBER_EMPLOYEE',
		'DEPUTY_HEAD' => 'MEMBER_DEPUTY_HEAD',
	];

	public function __construct(
		public MemberEntityType $entityType,
		public int $entityId,
		public int $nodeId,
		public ?bool $active = null,
		/** @var array<int> $roles */
		public ?array $roles = [],
		public ?int $role = null,
		public ?string $icon = '',
		public ?int $id = null,
		public ?int $addedBy = null,
		public ?DateTime $createdAt = null,
		public ?DateTime $updatedAt = null,
	) {}
}