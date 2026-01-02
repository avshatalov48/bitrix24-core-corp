<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure\Filter\Column\Node;

use Bitrix\HumanResources\Builder\Structure\Filter\Column\BaseColumnFilter;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Type\NodeEntityTypeCollection;

final class NodeTypeFilter extends BaseColumnFilter
{
	public function __construct(
		public ?NodeEntityTypeCollection $entityTypes = null,
	)
	{}

	public static function createForDepartment(): NodeTypeFilter
	{
		return new self(
			new NodeEntityTypeCollection(NodeEntityType::DEPARTMENT),
		);
	}

	protected function getFieldName(): string
	{
		return 'TYPE';
	}

	protected function getItems(): array
	{
		return array_column($this->entityTypes->getItems(), 'value');
	}

	public static function fromNodeType(NodeEntityType $nodeType): self
	{
		return new self(
			new NodeEntityTypeCollection($nodeType),
		);
	}

	public static function createForTeam(): self
	{
		return self::fromNodeType(NodeEntityType::TEAM);
	}

	/**
	 * @param NodeEntityType[] $nodeTypes
	 *
	 * @return self
	 */
	public static function fromNodeTypes(array $nodeTypes): self
	{
		if (empty($nodeTypes))
		{
			return new self();
		}

		if (count($nodeTypes) === 1)
		{
			return self::fromNodeType($nodeTypes[0]);
		}

		return new self(new NodeEntityTypeCollection(...$nodeTypes));
	}
}