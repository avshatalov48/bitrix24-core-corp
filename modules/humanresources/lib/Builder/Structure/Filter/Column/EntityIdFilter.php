<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure\Filter\Column;

use Bitrix\HumanResources\Type\IntegerCollection;

final class EntityIdFilter extends BaseColumnFilter
{
	public function __construct(
		public ?IntegerCollection $entityIds = null,
	)
	{}

	protected function getFieldName(): string
	{
		return 'ENTITY_ID';
	}

	protected function getItems(): array
	{
		return $this->entityIds->getItems();
	}

	public static function fromEntityId(int $entityId): self
	{
		return new self(
			new IntegerCollection($entityId)
		);
	}

	/**
	 * @param int[] $entityId
	 *
	 * @return self
	 */
	public static function fromEntityIds(array $entityId): self
	{
		if (empty($entityId))
		{
			return new self();
		}

		if (count($entityId) === 1)
		{
			return self::fromEntityId((int)$entityId[0]);
		}

		return new self(...$entityId);
	}
}