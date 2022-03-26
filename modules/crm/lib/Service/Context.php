<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\Engine\CurrentUser;

class Context
{
	public const SCOPE_MANUAL = 'manual';
	public const SCOPE_TASK = 'task';
	public const SCOPE_AUTOMATION = 'automation';
	public const SCOPE_REST = 'rest';

	protected $userId;
	protected $scope;

	public function __construct(array $params = [])
	{
		foreach($params as $name => $value)
		{
			if(property_exists(static::class, $name))
			{
				$this->$name = $value;
			}
		}
	}

	public function setUserId(int $userId): Context
	{
		$this->userId = $userId;

		return $this;
	}

	public function getUserId(): int
	{
		if($this->userId !== null)
		{
			return (int) $this->userId;
		}

		return $this->getCurrentUserId();
	}

	public function setScope(string $scope): Context
	{
		$this->scope = $scope;

		return $this;
	}

	public function getScope(): string
	{
		if($this->scope)
		{
			return (string) $this->scope;
		}

		return static::SCOPE_MANUAL;
	}

	protected function getCurrentUserId(): int
	{
		global $USER;
		if(is_object($USER) && $USER instanceof \CUser)
		{
			return (int) CurrentUser::get()->getId();
		}

		return 0;
	}
}