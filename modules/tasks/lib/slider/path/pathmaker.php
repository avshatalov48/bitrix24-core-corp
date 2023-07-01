<?php

namespace Bitrix\Tasks\Slider\Path;

abstract class PathMaker
{
	public const PERSONAL_CONTEXT = 'personal';
	public const GROUP_CONTEXT = 'group';

	public const DEFAULT_ACTION = 'view';
	public const EDIT_ACTION = 'edit';

	public static array $allowedActions = ['view', 'edit'];

	public string $queryParams = '';

	public int $entityId;
	public int $ownerId;
	public string $context;
	public string $action;

	public function setQueryParams(string $queryParams): self
	{
		if (empty($queryParams))
		{
			return $this;
		}
		if ($queryParams[0] === '?')
		{
			$this->queryParams = $queryParams;
		}
		else
		{
			$this->queryParams = '?' . $queryParams;
		}

		return $this;
	}

	public function __construct(int $entityId, string $action, int $ownerId, string $context)
	{
		$this->entityId = $entityId;
		$this->action = in_array($action, static::$allowedActions, true) ? $action : static::DEFAULT_ACTION;
		$this->ownerId = $ownerId;
		$this->context = $context;
	}

	abstract public function makeEntityPath(): string;
	abstract public function makeEntitiesListPath(): string;
}