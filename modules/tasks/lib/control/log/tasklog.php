<?php

namespace Bitrix\Tasks\Control\Log;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;

class TaskLog implements Arrayable
{
	private int $id = 0;
	private int $userId = 0;
	private int $taskId = 0;
	private ?DateTime $createdDate = null;
	private string $field = '';
	private ?Change $change = null;
	private string $userName = '';
	private string $userLastName = '';
	private string $userSecondName = '';
	private string $userLogin = '';

	public function __construct(array $data)
	{
		if (!empty($data['ID']))
		{
			$this->id = (int)$data['ID'];
		}

		if (!empty($data['USER_ID']))
		{
			$this->userId = (int)$data['USER_ID'];
		}

		if (!empty($data['TASK_ID']))
		{
			$this->taskId = (int)$data['TASK_ID'];
		}

		if (isset($data['CREATED_DATE']) && $data['CREATED_DATE'] instanceof DateTime)
		{
			$this->createdDate = $data['CREATED_DATE'];
		}

		if (!empty($data['FIELD']))
		{
			$this->field = (string)$data['FIELD'];
		}

		if (isset($data['FROM_VALUE']) || isset($data['TO_VALUE']))
		{
			$this->change = new Change($data['FROM_VALUE'] ?? null, $data['TO_VALUE'] ?? null);
		}

		if (!empty($data['USER_NAME']))
		{
			$this->userName = (string)$data['USER_NAME'];
		}

		if (!empty($data['USER_LAST_NAME']))
		{
			$this->userLastName = (string)$data['USER_LAST_NAME'];
		}

		if (!empty($data['USER_SECOND_NAME']))
		{
			$this->userSecondName = (string)$data['USER_SECOND_NAME'];
		}

		if (!empty($data['USER_LOGIN']))
		{
			$this->userLogin = (string)$data['USER_LOGIN'];
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getTaskId(): int
	{
		return $this->taskId;
	}

	public function getCreatedDate(): DateTime
	{
		return $this->createdDate;
	}

	public function getField(): string
	{
		return $this->field;
	}

	public function getUserName(): string
	{
		return $this->userName;
	}

	public function toArray(): array
	{
		return [
			'ID' => $this->id,
			'USER_ID' => $this->userId,
			'TASK_ID' => $this->taskId,
			'CREATED_DATE' => $this->createdDate,
			'FIELD' => $this->field,
			'FROM_VALUE' => $this->change?->getFromValue(),
			'TO_VALUE' => $this->change?->getToValue(),
			'USER_NAME' => $this->userName,
			'USER_LAST_NAME' => $this->userLastName,
			'USER_SECOND_NAME' => $this->userSecondName,
			'USER_LOGIN' => $this->userLogin,
		];
	}
}
