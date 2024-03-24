<?php

namespace Bitrix\Tasks\Internals\Notification;

use Bitrix\Tasks\Internals\TaskObject;

class Metadata
{
	private string $entityCode;
	private string $entityOperation;
	private array $params;


	public function __construct(string $entityCode, string  $entityOperation, array $params = [])
	{
		$this->entityCode = $entityCode;
		$this->entityOperation = $entityOperation;
		$this->params = $params;
	}

	public function getEntityCode(): string
	{
		return $this->entityCode;
	}

	public function getEntityOperation(): string
	{
		return $this->entityOperation;
	}

	public function getTask(): ?TaskObject
	{
		return (isset($this->params['task']) && $this->params['task'] instanceof TaskObject)
			? $this->params['task']
			: null;
	}

	public function getCommentId(): ?int
	{
		return (isset($this->params['comment_id']) && is_int($this->params['comment_id']))
			? $this->params['comment_id']
			: null;
	}

	public function getChanges(): ?array
	{
		return (isset($this->params['changes']) && is_array($this->params['changes']))
			? $this->params['changes']
			: null;
	}

	public function getPreviousFields(): ?array
	{
		return (isset($this->params['previous_fields']) && is_array($this->params['previous_fields']))
			? $this->params['previous_fields']
			: null;
	}

	public function getUserRepository(): ?UserRepository
	{
		return (isset($this->params['user_repository']) && $this->params['user_repository'] instanceof UserRepository)
			? $this->params['user_repository']
			: null;
	}

	public function getMemberCode(): ?string
	{
		return (isset($this->params['member_code']) && is_string($this->params['member_code']))
			? $this->params['member_code']
			: null;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function addParams(string $key, mixed $value): void
	{
		if (!isset($this->params[$key]) || !is_array($this->params[$key]))
		{
			$this->params[$key] = $value;
		}
		elseif (is_array($this->params[$key]))
		{
			$this->params[$key] = array_unique(array_merge($this->params[$key], $value));
		}
	}
}