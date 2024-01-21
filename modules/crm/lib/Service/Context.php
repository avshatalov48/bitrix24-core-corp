<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\Engine\CurrentUser;

class Context
{
	public const SCOPE_MANUAL = 'manual';
	public const SCOPE_TASK = 'task'; // agents, background jobs
	public const SCOPE_AUTOMATION = 'automation';
	public const SCOPE_REST = 'rest';
	public const SCOPE_AI = 'ai';

	protected $eventId;
	protected $userId;
	protected $scope;
	protected array $itemOptions = [];

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

	public function getEventId(): ?string
	{
		return $this->eventId;
	}

	public function setEventId(?string $eventId): Context
	{
		$this->eventId = $eventId;
		return $this;
	}

	/**
	 * @param string $optionName
	 * @return mixed|null
	 */
	public function getItemOption(string $optionName)
	{
		$options = $this->getItemOptions();
		return ($options[$optionName] ?? null);
	}

	public function getItemOptions(): array
	{
		return $this->itemOptions;
	}

	public function setItemOption(string $optionName, $value): Context
	{
		$this->itemOptions[$optionName] = $value;
		return $this;
	}

	public function setItemOptions(array $itemOptions): Context
	{
		$this->itemOptions = $itemOptions;
		return $this;
	}
}
