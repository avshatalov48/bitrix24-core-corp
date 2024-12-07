<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Item\Workgroup;
use CTaskListState;

abstract class Common
{
	protected static ?array $instance = null;

	private ?bool $isGantt = null;
	protected string $context = FilterRegistry::FILTER_GRID;
	protected string $scope = '';

	private function __construct(protected ?string $id, protected int $userId, protected ?int $groupId)
	{
	}

	public static function getInstance(int $userId = 0, ?int $groupId = 0, ?string $id = null): static
	{
		if (is_null($id))
		{
			$id = static::getDefaultId($groupId);
		}

		if (is_null(static::$instance) || !array_key_exists($id, static::$instance))
		{
			static::$instance[$id] = new static($id, $userId, $groupId);
		}

		return static::$instance[$id];
	}

	protected static function getDefaultId(
		?int $groupId,
		string $scope = '',
		string $name = FilterRegistry::FILTER_GRID
	): string
	{
		return FilterRegistry::getId($name, $groupId, $scope);
	}

	public function getId(): ?string
	{
		return $this->id;
	}

	public function getListStateInstance(): ?CTaskListState
	{
		static $instance = null;

		if (!$instance)
		{
			$instance = CTaskListState::getInstance($this->getUserId());
		}

		return $instance;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getContext(): string
	{
		return $this->context;
	}

	public function setGanttMode(bool $isGantt): static
	{
		$this->isGantt = $isGantt;
		return $this;
	}

	public function isScrumProject(): bool
	{
		if (Loader::includeModule('socialnetwork'))
		{
			$group = Workgroup::getById($this->getGroupId());

			return ($group && $group->isScrumProject());
		}

		return false;
	}

	protected function isGantt(): bool
	{
		return $this->isGantt === true;
	}

	protected function isGrid(): bool
	{
		return $this->isGantt === false;
	}

	protected function getGroupId(): ?int
	{
		return $this->groupId;
	}

	public function setContext(string $context): static
	{
		if (in_array($context, FilterRegistry::getList(), true))
		{
			$this->context = $context;
			$this->resolveChangedContext();
		}

		return $this;
	}

	protected function resolveChangedContext(): void
	{
		unset(static::$instance[$this->id]);
		$this->id = static::getDefaultId($this->groupId, $this->scope, $this->context);
		$this->setGanttMode($this->context === FilterRegistry::FILTER_GANTT);
		static::$instance[$this->id] = $this;
	}
}