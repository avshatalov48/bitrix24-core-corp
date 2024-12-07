<?php

namespace Bitrix\Tasks\Flow\Internal\Entity;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Flow\Internal\EO_FlowMember_Collection;
use Bitrix\Tasks\Internals\InsertIgnoreTrait;
use Bitrix\Tasks\Internals\UniqueTrait;

class FlowMemberCollection extends EO_FlowMember_Collection implements Arrayable
{
	use InsertIgnoreTrait;
	use UniqueTrait;

	public function getTaskCreators(): static
	{
		$collection = new static();
		foreach ($this as  $object)
		{
			if ($object->getRole() === Role::TASK_CREATOR->value)
			{
				$collection->add($object);
			}
		}

		return $collection;
	}

	public function getInsertFields(): array
	{
		return ['FLOW_ID', 'ACCESS_CODE', 'ENTITY_ID', 'ENTITY_TYPE', 'ROLE'];
	}

	public function getInsertValues(): string
	{
		$values = [];
		foreach ($this as $object)
		{
			$values[] = "({$object->getFlowId()}, '{$object->getAccessCode()}', {$object->getEntityId()}, '{$object->getEntityType()}', '{$object->getRole()}')";
		}

		return implode(',', $values);
	}

	public function setFlowId(int $flowId): static
	{
		foreach ($this as $object)
		{
			$object->setFlowId($flowId);
		}

		return $this;
	}

	public function toArray(): array
	{
		return array_map(static fn (FlowMember $member): array => $member->collectValues(), iterator_to_array($this));
	}
}