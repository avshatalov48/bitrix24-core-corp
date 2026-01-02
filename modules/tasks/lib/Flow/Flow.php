<?php

namespace Bitrix\Tasks\Flow;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\Contract\Jsonable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\EntitySelector\Converter;
use Bitrix\Tasks\Flow\Access\FlowModel;
use Bitrix\Tasks\Flow\Access\SimpleFlowAccessController;
use Bitrix\Tasks\Flow\Comment\CommentEvent;
use Bitrix\Tasks\Flow\Comment\Task\FlowCommentFactory;
use Bitrix\Tasks\Flow\Comment\Task\FlowCommentInterface;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionServicesFactory;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Internal\Entity\Role;

class Flow implements Arrayable, Jsonable
{
	public const DEFAULT_DISTRIBUTION_TYPE = FlowDistributionType::QUEUE;
	private const BASE_EFFICIENCY = 100;

	private ?int $id = null;
	private ?int $ownerId = null;
	private ?int $creatorId = null;
	private ?int $groupId = null;
	private ?int $templateId = null;
	private ?int $efficiency = null;
	private ?bool $active = null;
	private ?int $plannedCompletionTime = null;
	private ?DateTime $activity = null;
	private ?string $name = null;
	private ?string $description = null;
	private ?FlowDistributionType $distributionType = null;

	/**
	 * @see Converter::convertFromFinderCodes
	 */
	private ?array $responsibleList = null;
	private ?bool $demo = null;

	private ?bool $responsibleCanChangeDeadline = null;
	private ?bool $matchWorkTime = null;
	private ?bool $matchSchedule = null;
	private ?bool $taskControl = null;

	private ?bool $notifyAtHalfTime = null;
	private ?int $notifyOnQueueOverflow = null;
	private ?int $notifyOnTasksInProgressOverflow = null;
	private ?int $notifyWhenEfficiencyDecreases = null;

	/**
	 * @see Converter::convertFromFinderCodes
	 */
	private ?array $taskCreators = null;
	/**
	 * @see Converter::convertFromFinderCodes
	 */
	private ?array $team = null;
	private ?bool $trialFeatureEnabled = null;

	/**
	 * @param $data array [
	 *  'ID' => ?int,
	 *  'OWNER_ID' => int,
	 *  'GROUP_ID' => int,
	 *  'TEMPLATE_ID' => ?int,
	 *  'ACTIVE' => ?int,
	 *  'PLANNED_COMPLETION_TIME' => ?int,
	 *  'ACTIVITY' => ?\Bitrix\Main\Type\DateTime(),
	 *  'NAME' => string,
	 *  'DESCRIPTION' => ?string,
	 *  'DISTRIBUTION_TYPE' => string,
	 * ]
	 */
	public function __construct(array $data)
	{
		if (!empty($data['ID']))
		{
			$this->id = (int)$data['ID'];
		}

		if (!empty($data['CREATOR_ID']))
		{
			$this->creatorId = (int)$data['CREATOR_ID'];
		}

		if (!empty($data['OWNER_ID']))
		{
			$this->ownerId = (int)$data['OWNER_ID'];
		}

		if (isset($data['GROUP_ID']))
		{
			$this->groupId = (int)$data['GROUP_ID'];
		}

		if (isset($data['TEMPLATE_ID']))
		{
			$this->templateId = (int)$data['TEMPLATE_ID'];
		}

		if (isset($data['EFFICIENCY']))
		{
			$this->efficiency = (int)$data['EFFICIENCY'];
		}

		if (isset($data['ACTIVE']))
		{
			$this->active = (bool)$data['ACTIVE'];
		}

		if (isset($data['PLANNED_COMPLETION_TIME']))
		{
			$this->plannedCompletionTime = (int)$data['PLANNED_COMPLETION_TIME'];
		}

		if (
			isset($data['ACTIVITY'])
			&& $data['ACTIVITY'] instanceof DateTime
		)
		{
			$this->activity = $data['ACTIVITY'];
		}

		if (!empty($data['NAME']))
		{
			$this->name = (string)$data['NAME'];
		}

		if (!empty($data['DESCRIPTION']))
		{
			$this->description = (string)$data['DESCRIPTION'];
		}

		if (!empty($data['DISTRIBUTION_TYPE']))
		{
			$this->distributionType = FlowDistributionType::from((string)$data['DISTRIBUTION_TYPE']);
		}

		if (isset($data['DEMO']))
		{
			$this->demo = (bool)$data['DEMO'];
		}

		if (!empty($data['MEMBERS']) && is_array($data['MEMBERS']))
		{
			$this->mapTaskCreators($data['MEMBERS']);
			$this->mapTeam($data['MEMBERS']);
		}

		if (!empty($data['QUEUE']) && is_array($data['QUEUE']))
		{
			$this->responsibleList = $data['QUEUE'];
		}

		if (!empty($data['OPTIONS']) && is_array($data['OPTIONS']))
		{
			$this->setOptions($data['OPTIONS']);
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getName(): string
	{
		return (string)$this->name;
	}

	public function getDescription(): string
	{
		return (string)$this->description;
	}

	public function getPlannedCompletionTime(): int
	{
		return (int)$this->plannedCompletionTime;
	}

	public function getGroupId(): int
	{
		return (int)$this->groupId;
	}

	public function getTemplateId(): int
	{
		return (int)$this->templateId;
	}

	public function getEfficiency(): int
	{
		return $this->efficiency ?? self::BASE_EFFICIENCY;
	}

	public function getDistributionType(): FlowDistributionType
	{
		return $this->distributionType ?? self::DEFAULT_DISTRIBUTION_TYPE;
	}

	public function getCreatorId(): ?int
	{
		return $this->creatorId;
	}

	public function getOwnerId(): ?int
	{
		return $this->ownerId;
	}

	public function getAccessController(int $userId): SimpleFlowAccessController
	{
		return new SimpleFlowAccessController($userId, FlowModel::createFromId($this->id));
	}

	public function getResponsibleList(): array
	{
		return $this->responsibleList ?? [];
	}

	public function getTaskCreators(): array
	{
		return $this->taskCreators ?? [];
	}

	public function getTeam(): array
	{
		return $this->team ?? [];
	}

	public function isNotifyAtHalfTime(): bool
	{
		return is_null($this->notifyAtHalfTime) ? true : $this->notifyAtHalfTime;
	}

	public function isTrialFeatureEnabled(): bool
	{
		return (bool)$this->trialFeatureEnabled;
	}

	public function setResponsibleList(array $responsibleList): self
	{
		$this->responsibleList = $responsibleList;

		return $this;
	}

	public function setTeam(array $team): self
	{
		$this->team = $team;

		return $this;
	}

	/**
	 * @param array<Option\Option> $options
	 */
	public function setOptions(array $options): self
	{
		foreach ($options as $option)
		{
			/**
			 * For flows that were created or changed their type after exiting tasks 24.300.0,
			 * the option MANUAL_DISTRIBUTOR_ID exists only for manually distribution type flows.
			 *
			 * To maintain compatibility $this->getDistributionType() === FlowDistributionType::MANUALLY
			 */
			if (
				$this->distributionType === FlowDistributionType::MANUALLY
				&& $option->getName() === Option\OptionDictionary::MANUAL_DISTRIBUTOR_ID->value
			)
			{
				$manuallyDistributorId = $option->getValue();
				$this->responsibleList = [
					['user', $manuallyDistributorId]
				];
			}
			if ($option->getName() === Option\OptionDictionary::RESPONSIBLE_CAN_CHANGE_DEADLINE->value)
			{
				$this->responsibleCanChangeDeadline = (bool)$option->getValue();
			}
			if ($option->getName() === Option\OptionDictionary::MATCH_WORK_TIME->value)
			{
				$this->matchWorkTime = (bool)$option->getValue();
			}
			if ($option->getName() === Option\OptionDictionary::MATCH_SCHEDULE->value)
			{
				$this->matchSchedule = (bool)$option->getValue();
			}
			if ($option->getName() === Option\OptionDictionary::NOTIFY_AT_HALF_TIME->value)
			{
				$this->notifyAtHalfTime = (bool)$option->getValue();
			}
			if ($option->getName() === Option\OptionDictionary::NOTIFY_ON_QUEUE_OVERFLOW->value)
			{
				$this->notifyOnQueueOverflow = (int)$option->getValue();
			}
			if ($option->getName() === Option\OptionDictionary::NOTIFY_ON_TASKS_IN_PROGRESS_OVERFLOW->value)
			{
				$this->notifyOnTasksInProgressOverflow = (int)$option->getValue();
			}
			if ($option->getName() === Option\OptionDictionary::NOTIFY_WHEN_EFFICIENCY_DECREASES->value)
			{
				$this->notifyWhenEfficiencyDecreases = (int)$option->getValue();
			}
			if ($option->getName() === Option\OptionDictionary::TASK_CONTROL->value)
			{
				$this->taskControl = (bool)$option->getValue();
			}
		}

		return $this;
	}

	public function setTaskCreators(array $taskCreators): static
	{
		$this->taskCreators = $taskCreators;
		return $this;
	}

	public function isActive(): bool
	{
		return (bool)$this->active;
	}

	public function isDemo(): bool
	{
		return (bool)$this->demo;
	}

	public function isManually(): bool
	{
		return ($this->distributionType ?? self::DEFAULT_DISTRIBUTION_TYPE) === FlowDistributionType::MANUALLY;
	}

	public function isQueue(): bool
	{
		return ($this->distributionType ?? self::DEFAULT_DISTRIBUTION_TYPE) === FlowDistributionType::QUEUE;
	}

	public function isHimself(): bool
	{
		return ($this->distributionType ?? self::DEFAULT_DISTRIBUTION_TYPE) === FlowDistributionType::HIMSELF;
	}

	public function isImmutable(): bool
	{
		return ($this->distributionType ?? self::DEFAULT_DISTRIBUTION_TYPE) === FlowDistributionType::IMMUTABLE;
	}

	public function getTargetEfficiency(): int
	{
		return $this->notifyWhenEfficiencyDecreases ?? 100;
	}

	public function getMatchWorkTime(): bool
	{
		return $this->matchWorkTime ?? true;
	}

	public function getMatchSchedule(): bool
	{
		return (bool)$this->matchSchedule;
	}

	public function getTaskControl(): bool
	{
		return (bool)$this->taskControl;
	}

	public function canResponsibleChangeDeadline(): bool
	{
		return (bool)$this->responsibleCanChangeDeadline;
	}

	public function setTrialFeatureEnabled(bool $trialFeatureEnabled): void
	{
		$this->trialFeatureEnabled = $trialFeatureEnabled;
	}

	public function getResponsibleCanChangeDeadline(): bool
	{
		return (bool)$this->responsibleCanChangeDeadline;
	}

	public function getActivityDate(): DateTime
	{
		return $this->activity ?? new DateTime();
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'creatorId' => $this->getCreatorId(),
			'ownerId' => $this->getOwnerId(),
			'groupId' => $this->getGroupId(),
			'templateId' => $this->getTemplateId(),
			'efficiency' => $this->getEfficiency(),
			'active' => $this->isActive(),
			'plannedCompletionTime' => $this->getPlannedCompletionTime(),
			'activity' => $this->getActivityDate(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'distributionType' => $this->distributionType ? $this->distributionType->value : self::DEFAULT_DISTRIBUTION_TYPE->value,
			'responsibleList' => $this->getResponsibleList(),
			'demo' => $this->isDemo(),

			'responsibleCanChangeDeadline' => $this->getResponsibleCanChangeDeadline(),
			'matchWorkTime' => $this->getMatchWorkTime(),
			'matchSchedule' => $this->getMatchSchedule(),
			'taskControl' => $this->getTaskControl(),

			'notifyAtHalfTime' => $this->isNotifyAtHalfTime(),
			'notifyOnQueueOverflow' => $this->notifyOnQueueOverflow,
			'notifyOnTasksInProgressOverflow' => $this->notifyOnTasksInProgressOverflow,
			'notifyWhenEfficiencyDecreases' => $this->notifyWhenEfficiencyDecreases,
			'taskCreators' => $this->getTaskCreators(),
			'team' => $this->getTeam(),

			'trialFeatureEnabled' => $this->isTrialFeatureEnabled(),
		];
	}

	public function toJson($options = 0): array
	{
		$result = [];

		foreach ($this as $key => $value)
		{
			if (!is_null($value))
			{
				$result[$key] = $value;
			}
		}

		return $result;
	}

	public function getComment(CommentEvent $event, int $taskId): FlowCommentInterface
	{
		return FlowCommentFactory::get($this, $taskId, $event);
	}

	private function mapTaskCreators(array $members): void
	{
		$this->taskCreators = $this->filterMembersByRole($members, Role::TASK_CREATOR);
	}

	private function mapTeam(array $members): void
	{
		$responsibleRole = (new FlowDistributionServicesFactory($this->distributionType))
			->getMemberProvider()
			->getResponsibleRole()
		;

		$this->team = $this->filterMembersByRole($members, $responsibleRole);
	}

	private function filterMembersByRole(array $members, Role $role): array
	{
		$filteredAccessCodes = array_column(
			array_filter(
				$members, static fn(array $member): bool =>
				$member['ROLE'] === $role->value
			),
			'ACCESS_CODE'
		);

		return Converter::convertFromFinderCodes($filteredAccessCodes);
	}
}
