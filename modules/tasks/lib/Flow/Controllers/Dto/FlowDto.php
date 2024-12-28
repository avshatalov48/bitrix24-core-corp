<?php

namespace Bitrix\Tasks\Flow\Controllers\Dto;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Attribute\EntitySelector;
use Bitrix\Tasks\Flow\Attribute\Instantiable;
use Bitrix\Tasks\Internals\Attribute\Primary;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Dto\AbstractBaseDto;

/**
 * @method self setId(int $id)
 * @method self setCreatorId(int $creatorId)
 * @method self setOwnerId(int $ownerId)
 * @method self setGroupId(int $groupId)
 * @method self setTemplateId(int $templateId)
 * @method self setEfficiency(int $efficiency)
 * @method self setActive(bool $active)
 * @method self setDemo(bool $demo)
 * @method self setPlannedCompletionTime(int $plannedCompletionTime)
 * @method self setActivity(DateTime $activity = new DateTime())
 * @method self setName(string $name)
 * @method self setDescription(string $description)
 * @method self setDistributionType(string $distributionType)
 * @method self setResponsibleList(array $responsibleList)
 * @method self setTaskCreators(array $taskCreators)
 * @method self setNotifyOnQueueOverflow(int $notifyOnQueueOverflow)
 * @method self setNotifyAtHalfTime(bool $notifyAtHalfTime)
 * @method self setNotifyOnTasksInProgressOverflow(int $notifyOnTasksInProgressOverflow)
 * @method self setNotifyWhenEfficiencyDecreases(int $notifyWhenEfficiencyDecreases)
 * @method self setTaskControl(bool $taskControl)
 *
 * @method bool hasTaskCreators()
 * @method bool hasResponsibleList()
 *
 * @method void validateName(bool $required = false)
 */
final class FlowDto extends AbstractBaseDto
{
	#[Primary]
	public int $id = 0;

	public int $creatorId;

	public int $ownerId;

	public int $groupId;

	public int $templateId = 0;

	public int $efficiency = 100;

	public bool $active;

	public bool $demo;

	public int $plannedCompletionTime;

	#[Instantiable]
	public DateTime $activity;

	public string $name;

	public string $description = '';

	public ?string $distributionType = null;

	#[Required]
	#[EntitySelector]
	public array $responsibleList = [];

	public bool $responsibleCanChangeDeadline = true;

	public bool $matchWorkTime = true;

	public bool $matchSchedule = false;

	public bool $notifyAtHalfTime = false;

	public bool $taskControl = false;

	public ?int $notifyOnQueueOverflow = 0;

	public ?int $notifyOnTasksInProgressOverflow = 0;

	public ?int $notifyWhenEfficiencyDecreases = 0;

	#[Required]
	#[EntitySelector]
	public array $taskCreators = [];
}