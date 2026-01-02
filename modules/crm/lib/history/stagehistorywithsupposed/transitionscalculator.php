<?php

namespace Bitrix\Crm\History\StageHistoryWithSupposed;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\InvalidOperationException;
use Psr\Log\LoggerInterface;

final class TransitionsCalculator
{
	private LoggerInterface $logger;

	public function __construct(
		private readonly Factory $factory,
	)
	{
		$this->logger = Container::getInstance()->getLogger('History.StageHistoryWithSupposed');
	}

	/**
	 * @return array{
	 *     0: TransitionDto[],
	 *     1: CloseDateDirective,
	 * }
	 */
	public function calculateOnItemAdd(?int $categoryId, string $stageId): array
	{
		$start = $this->getFirstProcessStage($categoryId);
		if ($start === $stageId)
		{
			// regular add on first stage

			return [
				[$this->buildTransition($categoryId, $stageId, PhaseSemantics::PROCESS)],
				$this->calculateDirective(PhaseSemantics::PROCESS, PhaseSemantics::PROCESS),
			];
		}

		[$transitions, $directive] = $this->calculateOnStageChange($categoryId, $start, $stageId);
		// start stage is not registered in normal calculations
		array_unshift($transitions, $this->buildTransition($categoryId, $start, PhaseSemantics::PROCESS, true));

		return [$transitions, $directive];
	}

	/**
	 * @return array{
	 *     0: TransitionDto[],
	 *     1: CloseDateDirective,
	 * }
	 */
	public function calculateOnCategoryChange(int $finishCategoryId, string $startStageId, string $finishStageId): array
	{
		$startSemantics = $this->getStageSemantics($startStageId);
		$finishSemantics = $this->getStageSemantics($finishStageId);

		$dto = $this->buildTransition($finishCategoryId, $finishStageId, $finishSemantics);
		$directive = $this->calculateDirective($startSemantics, $finishSemantics);

		return [[$dto], $directive];
	}

	/**
	 * @return array{
	 *     0: TransitionDto[],
	 *     1: CloseDateDirective,
	 * }
	 */
	public function calculateOnStageChange(
		?int $categoryId,
		string $startStageId,
		string $finishStageId,
	): array
	{
		$startSemantics = $this->getStageSemantics($startStageId);
		$finishSemantics = $this->getStageSemantics($finishStageId);

		// P -> P
		if (!PhaseSemantics::isFinal($startSemantics) && !PhaseSemantics::isFinal($finishSemantics))
		{
			$transitions = $this->calculateTransitionsBetweenProcessStages($categoryId, $startStageId, $finishStageId);
		}
		// P -> S
		elseif (!PhaseSemantics::isFinal($startSemantics) && PhaseSemantics::isSuccess($finishSemantics))
		{
			$lastProcessStageId = $this->getLastProcessStage($categoryId);

			$transitions = $this->calculateTransitionsBetweenProcessStages(
				$categoryId,
				$startStageId,
				$lastProcessStageId,
			);
			if (!empty($transitions))
			{
				end($transitions)->isSupposed = true;
			}

			$successDto = $this->buildTransition($categoryId, $finishStageId, $finishSemantics);
			$transitions[] = $successDto;
		}
		// P -> F
		// S or F -> P
		// S -> F
		// F -> S
		// F -> F
		else
		{
			$transitions = [
				$this->buildTransition($categoryId, $finishStageId, $finishSemantics),
			];
		}


		return [$transitions, $this->calculateDirective($startSemantics, $finishSemantics)];
	}

	private function calculateDirective(string $startSemantics, string $finishSemantics): CloseDateDirective
	{
		if (!PhaseSemantics::isFinal($startSemantics) && PhaseSemantics::isFinal($finishSemantics))
		{
			// just closed
			return CloseDateDirective::SetNow;
		}
		elseif (PhaseSemantics::isFinal($startSemantics) && !PhaseSemantics::isFinal($finishSemantics))
		{
			// was opened again
			return CloseDateDirective::Reset;
		}
		elseif (PhaseSemantics::isFinal($startSemantics) && PhaseSemantics::isFinal($finishSemantics))
		{
			// move between closed
			return CloseDateDirective::SetLastKnownInNew;
		}
		else
		{
			// process
			return CloseDateDirective::DoNothing;
		}
	}

	/**
	 * @return TransitionDto[]
	 * @throws InvalidOperationException
	 */
	private function calculateTransitionsBetweenProcessStages(
		?int $categoryId,
		string $startStageId,
		string $finishStageId,
	): array
	{
		$processStageIds = $this->getProcessStageIds($categoryId);

		$startStageIndex = array_search($startStageId, $processStageIds, true);
		if ($startStageIndex === false)
		{
			throw new InvalidOperationException('Start stage is not a process stage');
		}
		$finishStageIndex = array_search($finishStageId, $processStageIds, true);
		if ($finishStageIndex === false)
		{
			throw new InvalidOperationException('Finish stage is not a process stage');
		}

		$transitions = [];

		if ($finishStageIndex > $startStageIndex)
		{
			// moving forward

			$between = array_slice($processStageIds, $startStageIndex + 1, $finishStageIndex - $startStageIndex - 1);
		}
		elseif ($finishStageIndex < $startStageIndex)
		{
			// moving backwards

			$between = array_slice($processStageIds, $finishStageIndex + 1, $startStageIndex - $finishStageIndex - 1);
			$between = array_reverse($between);
		}
		else
		{
			$this->logger->error(
				'{method}: no transition between process stages',
				['method' => __METHOD__, ...compact('categoryId', 'startStageId', 'finishStageId')],
			);

			return [];
		}

		foreach ($between as $stageId)
		{
			$dto = $this->buildTransition($categoryId, $stageId, PhaseSemantics::PROCESS, true);
			$transitions[] = $dto;
		}

		$finishDto = $this->buildTransition($categoryId, $finishStageId, PhaseSemantics::PROCESS);
		$transitions[] = $finishDto;

		return $transitions;
	}

	private function getFirstProcessStage(?int $categoryId): string
	{
		$allProcesses = $this->getProcessStageIds($categoryId);
		if (empty($allProcesses))
		{
			throw new InvalidOperationException(
				"No process stages found for {$this->factory->getEntityTypeId()} and category {$categoryId}"
			);
		}

		return reset($allProcesses);
	}

	private function getLastProcessStage(?int $categoryId): string
	{
		$allProcesses = $this->getProcessStageIds($categoryId);
		if (empty($allProcesses))
		{
			throw new InvalidOperationException(
				"No process stages found for {$this->factory->getEntityTypeId()} and category {$categoryId}",
			);
		}

		return end($allProcesses);
	}

	/**
	 * @param int|null $categoryId
	 *
	 * @return string[]
	 */
	private function getProcessStageIds(?int $categoryId): array
	{
		$ids = [];
		foreach ($this->factory->getStages($categoryId) as $stage)
		{
			if (!PhaseSemantics::isFinal($stage->getSemantics()))
			{
				$ids[] = $stage->getStatusId();
			}
		}

		return $ids;
	}

	private function buildTransition(
		?int $categoryId,
		string $stageId,
		string $semantics,
		bool $isSupposed = false,
	): TransitionDto
	{
		$dto = new TransitionDto([
			'categoryId' => $categoryId,
			'stageId' => $stageId,
			'semantics' => $semantics,
			'isSupposed' => $isSupposed,
		]);

		// sanity check
		if ($dto->hasValidationErrors())
		{
			$this->logger->critical(
				'Invalid transition DTO data',
				[
					'dto' => $dto,
					'errors' => $dto->getValidationErrors(),
				],
			);
		}

		return $dto;
	}

	private function getStageSemantics(string $stageId): string
	{
		return $this->factory->getStageSemantics($stageId);
	}
}
