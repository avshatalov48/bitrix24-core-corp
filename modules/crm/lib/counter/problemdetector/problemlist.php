<?php

namespace Bitrix\Crm\Counter\ProblemDetector;

class ProblemList
{
	/** @var Problem[] */
	private array $problems = [];

	public function add(Problem $problem): void
	{
		$this->problems[] = $problem;
	}

	/**
	 * @return Problem[]
	 */
	public function getProblems(): array
	{
		return $this->problems;
	}

	public function hasAnyProblem(): bool
	{
		foreach ($this->problems as $problem)
		{
			if ($problem->hasProblem())
			{
				return true;
			}
		}

		return false;
	}

	public function problemCount(): int
	{
		return array_reduce($this->problems, fn(int $carry, Problem $p) => $p->problemCount() + $carry, 0);
	}
}