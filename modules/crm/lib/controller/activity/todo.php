<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class ToDo extends Base
{
	public function addAction(
		int $ownerTypeId,
		int $ownerId,
		string $description = '',
		string $deadline,
		int $responsibleId = null,
		?int $parentActivityId = null
	): ?array
	{
		$todo = new \Bitrix\Crm\Activity\Entity\ToDo(
			new ItemIdentifier($ownerTypeId, $ownerId)
		);
		$todo->setDescription($description);

		$deadline = $this->prepareDatetime($deadline);
		if (!$deadline)
		{
			return null;
		}
		$todo->setDeadline($deadline);

		$todo->setParentActivityId($parentActivityId);

		if ($responsibleId)
		{
			$todo->setResponsibleId($responsibleId);
		}

		return $this->saveTodo($todo);
	}

	public function updateDeadlineAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		string $value
	): ?array
	{
		$todo = \Bitrix\Crm\Activity\Entity\ToDo::load(
			new ItemIdentifier($ownerTypeId, $ownerId),
			$id
		);
		if (!$todo)
		{
			$this->addError(ErrorCode::getNotFoundError());
			return null;
		}
		$deadline = $this->prepareDatetime($value);
		if (!$deadline)
		{
			return null;
		}
		$todo->setDeadline($deadline);

		return $this->saveTodo($todo);
	}

	public function updateDescriptionAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		string $value
	): ?array
	{
		$todo = \Bitrix\Crm\Activity\Entity\ToDo::load(
			new ItemIdentifier($ownerTypeId, $ownerId),
			$id
		);
		if (!$todo)
		{
			$this->addError(ErrorCode::getNotFoundError());
			return null;
		}
		$todo->setDescription($value);

		return $this->saveTodo($todo);
	}

	public function skipEntityDetailsNotificationAction(int $entityTypeId, string $period): bool
	{
		if (!\CCrmOwnerType::ResolveName($entityTypeId))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));
		}

		$result = (new \Bitrix\Crm\Activity\TodoCreateNotification($entityTypeId))->skipForPeriod($period);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->isSuccess();
	}

	private function saveTodo(\Bitrix\Crm\Activity\Entity\ToDo $todo): ?array
	{
		$saveResult = $todo->save();
		if ($saveResult->isSuccess())
		{
			return [
				'id' => $todo->getId(),
			];
		}

		$this->addErrors($saveResult->getErrors());

		return null;
	}
}
