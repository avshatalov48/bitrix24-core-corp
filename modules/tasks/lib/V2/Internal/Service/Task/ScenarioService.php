<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\V2\Internal\Entity\Task\Scenario;
use Bitrix\Tasks\V2\Internal\Repository\TaskScenarioRepositoryInterface;

class ScenarioService
{
	public function __construct(
		private readonly TaskScenarioRepositoryInterface $taskScenarioRepository,
	)
	{

	}

	public function saveDefault(int $taskId): void
	{
		$this->taskScenarioRepository->save($taskId, [Scenario::Default->value]);
	}

	public function save(int $taskId, array $scenarios): void
	{
		$scenarios = $this->filterValid($scenarios);

		$this->taskScenarioRepository->save($taskId, $scenarios);
	}

	public function isValid(string $scenario): bool
	{
		return Scenario::tryFrom($scenario) !== null;
	}

	public function filterValid(array $scenarios): array
	{
		$map = [];
		foreach ($scenarios as $scenario)
		{
			if (!is_string($scenario))
			{
				continue;
			}

			$value = Scenario::tryFrom($scenario)?->value;
			if ($value)
			{
				$map[$value] = true;
			}
		}

		return array_keys($map);
	}
}