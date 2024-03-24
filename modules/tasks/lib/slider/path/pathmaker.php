<?php

namespace Bitrix\Tasks\Slider\Path;

abstract class PathMaker
{
	public const PERSONAL_CONTEXT = 'personal';
	public const GROUP_CONTEXT = 'group';
	public const SPACE_CONTEXT = 'space';

	public const DEFAULT_ACTION = 'view';
	public const EDIT_ACTION = 'edit';

	public const AND = '&';
	public const START = '?';

	public static array $allowedActions = [self::DEFAULT_ACTION, self::EDIT_ACTION];

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

		$this->queryParams = $queryParams[0] === static::START
			? $queryParams
			: static::START . $queryParams;

		return $this;
	}

	public function addQueryParam(string $key, string $value): self
	{
		$this->queryParams = empty($this->queryParams)
			? static::START
			: $this->queryParams . static::AND;

		$this->queryParams .= http_build_query([$key => $value]);

		return $this;
	}

	public function setOwnerId(int $ownerId): static
	{
		$this->ownerId = $ownerId;
		return $this;
	}

	public function __construct(
		int $entityId = 0,
		string $action = self::DEFAULT_ACTION,
		int $ownerId = 0,
		string $context = self::PERSONAL_CONTEXT
	)
	{
		$this->entityId = $entityId;
		$this->action = in_array($action, static::$allowedActions, true) ? $action : static::DEFAULT_ACTION;
		$this->ownerId = $ownerId;
		$this->context = $context;
	}

	abstract public function makeEntityPath(): string;

	abstract public function makeEntitiesListPath(): string;
}