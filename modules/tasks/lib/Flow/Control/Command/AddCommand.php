<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Attribute\AccessCodes;
use Bitrix\Tasks\Flow\Attribute\DistributionType;
use Bitrix\Tasks\Flow\Attribute\Instantiable;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Configuration;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Internals\Attribute\Department;
use Bitrix\Tasks\Internals\Attribute\ExpectedNumeric;
use Bitrix\Tasks\Internals\Attribute\InArray;
use Bitrix\Tasks\Internals\Attribute\Length;
use Bitrix\Tasks\Internals\Attribute\Min;
use Bitrix\Tasks\Internals\Attribute\Max;
use Bitrix\Tasks\Internals\Attribute\NotEmpty;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Attribute\Nullable;
use Bitrix\Tasks\Internals\Attribute\Parse;
use Bitrix\Tasks\Internals\Attribute\Parse\UserFromAccess;
use Bitrix\Tasks\Internals\Attribute\Project;
use Bitrix\Tasks\Internals\Attribute\Template;
use Bitrix\Tasks\Internals\Attribute\User;

/**
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
 * @method false hasId()
 * @method bool hasValidGroupId()
 * @method bool hasValidOwnerId()
 * @method bool hasValidCreatorId()
 */
class AddCommand extends AbstractCommand
{
	#[Required]
	#[PositiveNumber]
	#[User]
	public int $creatorId;

	#[Required]
	#[PositiveNumber]
	#[User]
	public int $ownerId;

	#[Required]
	#[PositiveNumber]
	#[Project]
	public int $groupId;

	#[Min(0)]
	#[Template]
	public int $templateId = 0;

	#[Min(0)]
	#[Max(100)]
	public int $efficiency = 100;

	public bool $active = true;
	public bool $demo = false;

	#[Required]
	#[Min(Configuration::MIN_PLANNED_COMPLETION_TIME)]
	#[Max(2145398400)]
	public int $plannedCompletionTime;

	#[Instantiable]
	public DateTime $activity;

	#[Required]
	#[Length(1, Configuration::MAX_NAME_LENGTH)]
	public string $name;

	public string $description = '';

	#[Required]
	#[DistributionType]
	public ?string $distributionType = null;

	#[Required]
	#[AccessCodes]
	#[NotEmpty]
	public array $responsibleList;

	#[Nullable]
	#[User]
	#[Parse(new UserFromAccess(), 'responsibleList')]
	public ?array $userResponsibleList = null;

	#[Nullable]
	#[Department]
	#[Parse(new Parse\DepartmentFromAccess(), 'responsibleList')]
	public ?array $departmentResponsibleList = null;

	public bool $responsibleCanChangeDeadline = true;

	public bool $matchWorkTime = true;

	public bool $matchSchedule = false;

	public bool $notifyAtHalfTime = false;

	public bool $taskControl = false;
	public bool $notifyWhenTaskNotTaken = true;

	#[Nullable]
	#[Min(0)]
	#[Max(99999)]
	public ?int $notifyOnQueueOverflow = null;

	#[Nullable]
	#[Min(0)]
	#[Max(99999)]
	public ?int $notifyOnTasksInProgressOverflow = null;

	#[Nullable]
	#[Min(0)]
	#[Max(100)]
	public ?int $notifyWhenEfficiencyDecreases = null;

	#[Required]
	#[AccessCodes]
	public array $taskCreators;

	#[Nullable]
	#[User]
	#[Parse(new UserFromAccess(), 'taskCreators')]
	public array $userTaskCreators;

	#[Nullable]
	#[Department]
	#[Parse(new Parse\DepartmentFromAccess(), 'taskCreators')]
	public array $departmentTaskCreators;
}