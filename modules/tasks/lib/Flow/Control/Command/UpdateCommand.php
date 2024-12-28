<?php

namespace Bitrix\Tasks\Flow\Control\Command;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Attribute\AccessCodes;
use Bitrix\Tasks\Flow\Attribute\DistributionType;
use Bitrix\Tasks\Flow\Attribute\Instantiable;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Configuration;
use Bitrix\Tasks\Internals\Attribute\NotEmpty;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Internals\Attribute\Department;
use Bitrix\Tasks\Internals\Attribute\ExpectedNumeric;
use Bitrix\Tasks\Internals\Attribute\InArray;
use Bitrix\Tasks\Internals\Attribute\Length;
use Bitrix\Tasks\Internals\Attribute\Min;
use Bitrix\Tasks\Internals\Attribute\Max;
use Bitrix\Tasks\Internals\Attribute\Nullable;
use Bitrix\Tasks\Internals\Attribute\Parse;
use Bitrix\Tasks\Internals\Attribute\Parse\UserFromAccess;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;
use Bitrix\Tasks\Internals\Attribute\Primary;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Attribute\User;

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
 * @method bool hasId()
 * @method bool hasOwnerId()
 * @method bool hasValidOwnerId()
 * @method bool hasValidCreatorId()
 * @method bool isOwnerIdFilled()
 */
final class UpdateCommand extends AbstractCommand
{
	#[Required]
	#[Primary]
	#[PositiveNumber]
	public int $id;

	#[Nullable]
	#[PositiveNumber]
	#[User]
	public ?int $creatorId = null;

	#[Nullable]
	#[PositiveNumber]
	#[User]
	public ?int $ownerId = null;

	#[Nullable]
	#[PositiveNumber]
	public ?int $groupId = null;

	#[Nullable]
	#[Min(0)]
	public ?int $templateId = null;

	#[Nullable]
	#[Min(0)]
	#[Max(100)]
	public ?int $efficiency = null;

	#[Nullable]
	public ?bool $active = null;
	public ?bool $demo = null;

	#[Nullable]
	#[Min(Configuration::MIN_PLANNED_COMPLETION_TIME)]
	#[Max(2145398400)]
	public ?int $plannedCompletionTime = null;

	#[Nullable]
	#[Instantiable]
	public ?DateTime $activity = null;

	#[Nullable]
	#[Length(1, Configuration::MAX_NAME_LENGTH)]
	public ?string $name = null;

	#[Nullable]
	public ?string $description = null;

	#[Nullable]
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

	#[Nullable]
	public ?bool $responsibleCanChangeDeadline = null;

	#[Nullable]
	public ?bool $matchWorkTime = null;

	#[Nullable]
	public ?bool $matchSchedule = null;

	#[Nullable]
	public ?bool $notifyAtHalfTime = null;

	#[Nullable]
	public ?bool $notifyWhenTaskNotTaken = true;

	#[Nullable]
	public ?bool $taskControl = null;

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

	#[Nullable]
	#[AccessCodes]
	public ?array $taskCreators = null;

	#[Nullable]
	#[User]
	#[Parse(new UserFromAccess(), 'taskCreators')]
	public ?array $userTaskCreators = null;

	#[Nullable]
	#[Department]
	#[Parse(new Parse\DepartmentFromAccess(), 'taskCreators')]
	public ?array $departmentTaskCreators = null;

	public function hasValidGroupId(): bool
	{
		try
		{
			$this->validateProperty('groupId');
			return true;
		}
		catch (InvalidCommandException)
		{
			return false;
		}
	}
}
