<?php

namespace Bitrix\Crm\Counter\ProblemDetector;

class Problem
{
	public function __construct(
		private string $type,
		private int $problemCount,
		private array $records,
		private array $activities,
		private array $extra = []
	)
	{
	}

	public function type(): string
	{
		return $this->type;
	}

	public function problemCount(): int
	{
		return $this->problemCount;
	}

	public function records(): array
	{
		return $this->records;
	}

	public function activities(): array
	{
		return $this->activities;
	}

	public function extra(): array
	{
		return $this->extra;
	}

	public function hasProblem(): bool
	{
		return $this->problemCount() > 0;
	}

	public static function makeEmptyProblem(string $type): self
	{
		return new self($type, 0, [], []);
	}
}