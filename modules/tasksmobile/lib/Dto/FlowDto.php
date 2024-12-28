<?php

namespace Bitrix\Tasksmobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\TasksMobile\FlowAiAdvice\Dto\FlowAiAdviceDto;

class FlowDto extends Dto
{
	public function __construct(
		public readonly int $id,
		public readonly int $ownerId,
		public readonly int $creatorId,
		public readonly int $groupId,
		public readonly int $templateId,
		public readonly int $efficiency,
		public readonly bool $active,
		public readonly bool $demo,
		public readonly int $plannedCompletionTime,
		public readonly string $name,
		public readonly ?FlowDistributionType $distributionType,
		public readonly array $taskCreators,
		public readonly array $taskAssignees,
		public readonly array $pending,
		public readonly array $atWork,
		public readonly array $completed,
		public readonly ?int $tasksTotal = null,
		public readonly int $myTasksTotal,
		public readonly array $myTasksCounter,
		public readonly ?string $averagePendingTime,
		public readonly ?string $averageAtWorkTime,
		public readonly ?string $averageCompletedTime,
		public readonly ?string $plannedCompletionTimeText = null,
		public readonly ?string $enableFlowUrl = null,
		public readonly ?int $activity = null,
		public readonly ?string $description = null,
		public readonly ?bool $responsibleCanChangeDeadline = null,
		public readonly ?bool $matchWorkTime = null,
		public readonly ?bool $notifyAtHalfTime = null,
		public readonly ?bool $notifyOnQueueOverflow = null,
		public readonly ?bool $notifyOnTasksInProgressOverflow = null,
		public readonly ?bool $notifyWhenEfficiencyDecreases = null,
		public readonly ?FlowAiAdviceDto $aiAdvice = null,
	)
	{
		parent::__construct();
	}
}
