<?php

namespace Bitrix\Crm\Kanban\EntityActCounter;

class CounterInfo
{
	public function __construct(
		private array $deadlines,
		private array $incoming,
		private array $counters,
		private array $incomingByResponsible
	)
	{
	}

	public function deadlines(): array
	{
		return $this->deadlines;
	}

	public function incoming(): array
	{
		return $this->incoming;
	}

	public function counters(): array
	{
		return $this->counters;
	}

	public function incomingByResponsible(): array
	{
		return $this->incomingByResponsible;
	}

	public static function createEmpty(): self
	{
		return new self([], [], [], []);
	}

}