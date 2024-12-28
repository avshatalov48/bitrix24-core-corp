<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;
use Bitrix\TasksMobile\UserField\Dto\UserFieldDto;

final class TaskDto extends Dto
{
	public int $id;
	public string $name;
	public string $description;
	public string $parsedDescription;
	public int $groupId;
	public int $flowId = 0;
	public int $timeElapsed;
	public int $timeEstimate;
	public int $commentsCount;
	public int $serviceCommentsCount;
	public int $newCommentsCount;
	public int $viewsCount = 0;
	public int $resultsCount = 0;
	public int $status;
	public int $subStatus;
	public int $priority;
	public int $parentId = 0;
	public ?string $mark;

	public int $creator;
	public int $responsible;

	/** @var int[] */
	public array $accomplices = [];

	/** @var int[] */
	public array $auditors = [];

	//	public array $relatedTasks = [];
	//	public array $subTasks = [];

	/** @var TaskTagDto[] */
	public array $tags = [];

	/** @var DiskFileDto[] */
	public array $files = [];

	public bool $isMuted;
	public bool $isPinned;
	public bool $isInFavorites;
	public bool $isResultRequired;
	public bool $isResultExists;
	public bool $isDodNecessary;
	public bool $isOpenResultExists;
	public bool $isMatchWorkTime;
	public bool $allowChangeDeadline;
	public bool $allowTimeTracking;
	public bool $allowTaskControl;
	public bool $isTimerRunningForCurrentUser;

	public ?int $deadline = null;
	public ?int $activityDate = null;
	public ?int $startDatePlan = null;
	public ?int $endDatePlan = null;
	public ?int $startDate = null;
	public ?int $endDate = null;
	public ?int $activeDodTypeId = null;

	public ChecklistSummaryDto $checklist;

	/** @var ChecklistDetailsDto[] */
	public array $checklistDetails;

	public TaskCounterDto $counter;

	/** @var RelatedCrmItemDto[] */
	public array $crm = [];

	/** @var DodTypesDto[] */
	public array $dodTypes = [];

	/** @var array<string, boolean> */
	public array $actions = [];
	/** @var array<string, boolean> */
	public array $actionsOld = [];

	public bool $areUserFieldsLoaded;
	/** @var UserFieldDto[] */
	public array $userFields;
	public array $userFieldNames;

	public function getCasts(): array
	{
		return [
			'tags' => Type::collection(TaskTagDto::class),
			'files' => Type::collection(DiskFileDto::class),
			'crm' => Type::collection(RelatedCrmItemDto::class),
			'dodTypes' => Type::collection(DodTypesDto::class),
			'checklistDetails' => Type::collection(ChecklistDetailsDto::class),
			'userFields' => Type::collection(UserFieldDto::class),
		];
	}

	protected function getDecoders(): array
	{
		return [
			function (array $fields) {
				if (!empty($fields['actions']))
				{
					$converter = new Converter(Converter::KEYS | Converter::TO_CAMEL | Converter::LC_FIRST);
					$fields['actions'] = $converter->process($fields['actions']);
				}

				if (!empty($fields['actionsOld']))
				{
					$converter = new Converter(Converter::KEYS | Converter::TO_CAMEL | Converter::LC_FIRST);
					$fields['actionsOld'] = $converter->process($fields['actionsOld']);
				}

				return $fields;
			},
		];
	}
}
