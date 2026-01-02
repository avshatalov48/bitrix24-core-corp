<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Adapter;

use Bitrix\Tasks\V2\Internal\Access\Reminder\ReminderModel;
use Bitrix\Tasks\V2\Internal\Entity;

class ReminderModelAdapter implements EntityModelAdapterInterface
{
	public function __construct(
		private readonly Entity\EntityInterface $entity
	)
	{
	}

	public function transform(): ?ReminderModel
	{
		if (!$this->entity instanceof Entity\Task\Reminder)
		{
			return null;
		}

		$data['id'] = (int)$this->entity->getId();
		$data['userId'] = (int)$this->entity->userId;
		$data['taskId'] = (int)$this->entity->taskId;

		return ReminderModel::createFromArray($data);
	}

	public function create(): ?ReminderModel
	{
		return ReminderModel::createFromId($this->entity->getId());
	}
}