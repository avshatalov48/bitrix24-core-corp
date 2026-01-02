<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Reminder;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;

final class ReminderModel implements AccessibleItem
{
	private int $id = 0;
	private ?int $userId = null;
	private ?int $taskId = null;

	public static function createFromArray(array|Arrayable $data): self
	{
		if ($data instanceof Arrayable)
		{
			$data = $data->toArray();
		}

		$model = new self();
		if (isset($data['id']))
		{
			$model->id = (int)$data['id'];
		}

		if (isset($data['userId']))
		{
			$model->userId = (int)$data['userId'];
		}

		if (isset($data['taskId']))
		{
			$model->taskId = (int)$data['taskId'];
		}

		return $model;
	}

	public static function createFromId(int $itemId): self
	{
		$model = new self();
		$model->id = $itemId;

		return $model;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getUserId(): int
	{
		$this->userId ??= (int)$this->getEntity()?->userId;

		return $this->userId;
	}

	public function getTaskId(): int
	{
		$this->taskId ??= (int)$this->getEntity()?->taskId;

		return $this->taskId;
	}

	private function getEntity(): ?Reminder
	{
		return Container::getInstance()->getReminderReadRepository()->getById($this->id);
	}
}