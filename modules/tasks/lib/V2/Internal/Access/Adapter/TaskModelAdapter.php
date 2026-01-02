<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Adapter;

use Bitrix\Tasks\Access\Model;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\TaskStatusMapper;

class TaskModelAdapter implements EntityModelAdapterInterface
{
	public function __construct(
		private readonly Entity\EntityInterface $entity
	)
	{
		
	}

	public function transform(?Entity\EntityInterface $current = null): ?Model\TaskModel
	{
		if (!$this->entity instanceof Entity\Task)
		{
			return null;
		}

		$data = $this->transformEntity($this->entity);
		$default = $current ? $this->transformEntity($current) : [];

		return Model\TaskModel::createFromArray($data, $default);
	}

	private function transformEntity(Entity\EntityInterface $entity): array
	{
		$data['ID'] = (int)$entity->getId();

		$status = $entity->status;
		if ($status !== null)
		{
			$data['STATUS'] = (new TaskStatusMapper())->mapFromEnum($status);
		}

		$data['GROUP_ID'] = (int)$entity->group?->getId();

		if ($entity->creator !== null)
		{
			$data['CREATED_BY'] = $entity->creator->getId();
		}

		if ($entity->responsible !== null)
		{
			$data['RESPONSIBLE_ID'] = $entity->responsible->getId();
		}

		if ($entity->accomplices !== null)
		{
			$data['ACCOMPLICES'] = $entity->accomplices->getIds();
		}

		if ($entity->auditors !== null)
		{
			$data['AUDITORS'] = $entity->auditors->getIds();
		}

		if ($entity->flow !== null)
		{
			$data['FLOW_ID'] = $entity->flow->getId();
		}

		if ($entity->parent !== null)
		{
			$data['PARENT_ID'] = $entity->parent->getId();
		}

		return $data;
	}

	public function create(): ?Model\TaskModel
	{
		return Model\TaskModel::createFromId($this->entity->getId());
	}
}