<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Contract\NodeMemberData;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Main\Type\DateTime;

class NodeMember implements Item
{
	public const DEFAULT_ROLE_XML_ID = [
		'HEAD' => 'MEMBER_HEAD',
		'EMPLOYEE' => 'MEMBER_EMPLOYEE',
		'DEPUTY_HEAD' => 'MEMBER_DEPUTY_HEAD',
	];
	public const TEAM_ROLE_XML_ID = [
		'TEAM_HEAD' => 'MEMBER_TEAM_HEAD',
		'TEAM_EMPLOYEE' => 'MEMBER_TEAM_EMPLOYEE',
		'TEAM_DEPUTY_HEAD' => 'MEMBER_TEAM_DEPUTY_HEAD',
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
		private ?Node $node = null,
	) {}

	public function __get(string $fieldName)
	{
		// lazy loading for node field
		if ($fieldName === 'node' && $this->nodeId !== null)
		{
			if ($this->node !== null)
			{
				return $this->node;
			}

			$this->node = Container::getNodeRepository()->getById($this->nodeId, true);

			return $this->node;
		}

		if (!isset($this->$fieldName))
		{
			throw new \InvalidArgumentException("Invalid property: $fieldName");
		}

		return $this->$fieldName;
	}

	public function __set(string $fieldName, mixed $value)
	{
		if ($fieldName === 'node')
		{
			$this->node = $value;
		}
		else
		{
			throw new \InvalidArgumentException("Invalid property: $fieldName");
		}
	}
}