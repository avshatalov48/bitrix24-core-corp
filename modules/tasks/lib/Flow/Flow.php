<?php

namespace Bitrix\Tasks\Flow;

use Bitrix\Main\Type\Contract\Arrayable;
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

class Flow implements Arrayable
{
	public const DEFAULT_DISTRIBUTION_TYPE = FlowDistributionType::QUEUE;

	private int $id = 0;
	private int $ownerId = 0;
	private int $creatorId = 0;
	private int $groupId = 0;
	private int $templateId = 0;
	private int $efficiency = 100;
	private bool $active = false;
	private int $plannedCompletionTime = 0;
	private ?DateTime $activity;
	private string $name = '';
	private string $description = '';
	private FlowDistributionType $distributionType = self::DEFAULT_DISTRIBUTION_TYPE;

	/**
	 * @see Converter::convertFromFinderCodes
	 */
	private array $responsibleList = [];
	private bool $demo = false;

	private bool $responsibleCanChangeDeadline = false;
	private bool $matchWorkTime = true;
	private bool $matchSchedule = false;
	private bool $taskControl = false;

	private bool $notifyAtHalfTime = true;
	private ?int $notifyOnQueueOverflow = null;
	private ?int $notifyOnTasksInProgressOverflow = null;
	private ?int $notifyWhenEfficiencyDecreases = null;

	/**
	 * @see Converter::convertFromFinderCodes
	 */
	private array $taskCreators = [];
	/**
	 * @see Converter::convertFromFinderCodes
	 */
	private array $team = [];
	private bool $trialFeatureEnabled = false;

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
			$this->groupId = (int) $data['GROUP_ID'];
		}

		if (!empty($data['TEMPLATE_ID']))
		{
			$this->templateId = (int)$data['TEMPLATE_ID'];
		}

		if (isset($data['EFFICIENCY']))
		{
			$this->efficiency = (int)$data['EFFICIENCY'];
		}

		if (!empty($data['ACTIVE']))
		{
			$this->active = (bool)$data['ACTIVE'];
		}

		if (!empty($data['PLANNED_COMPLETION_TIME']))
		{
			$this->plannedCompletionTime = (int)$data['PLANNED_COMPLETION_TIME'];
		}

		$this->activity = (($data['ACTIVITY'] ?? null) instanceof DateTime) ? $data['ACTIVITY'] : new DateTime();

		$this->name = (string) ($data['NAME'] ?? '');

		if (!empty($data['DESCRIPTION']))
		{
			$this->description = (string)$data['DESCRIPTION'];
		}

		if (!empty($data['DISTRIBUTION_TYPE']))
		{
			$this->distributionType = FlowDistributionType::from((string)$data['DISTRIBUTION_TYPE']);
		}

		if (!empty($data['DEMO']))
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
		return $this->name;
	}

	public function getPlannedCompletionTime(): int
	{
		return $this->plannedCompletionTime;
	}

	public function getGroupId(): int
	{
		return $this->groupId;
	}

	public function getTemplateId(): int
	{
		return $this->templateId;
	}

	public function getEfficiency(): int
	{
		return $this->efficiency;
	}

	public function getDistributionType(): FlowDistributionType
	{
		return $this->distributionType;
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
		return $this->active;
	}

	public function isDemo(): bool
	{
		return $this->demo;
	}

	public function isManually(): bool
	{
		return $this->distributionType === FlowDistributionType::MANUALLY;
	}

	public function isQueue(): bool
	{
		return $this->distributionType === FlowDistributionType::QUEUE;
	}

	public function isHimself(): bool
	{
		return $this->distributionType === FlowDistributionType::HIMSELF;
	}

	public function getTargetEfficiency(): int
	{
		return $this->notifyWhenEfficiencyDecreases ?? 100;
	}

	public function getMatchWorkTime(): bool
	{
		return $this->matchWorkTime;
	}

	public function getMatchSchedule(): bool
	{
		return $this->matchSchedule;
	}

	public function getTaskControl(): bool
	{
		return $this->taskControl;
	}

	public function canResponsibleChangeDeadline(): bool
	{
		return $this->responsibleCanChangeDeadline;
	}

	public function setTrialFeatureEnabled(bool $trialFeatureEnabled): void
	{
		$this->trialFeatureEnabled = $trialFeatureEnabled;
	}

	public function getResponsibleCanChangeDeadline(): bool
	{
		return $this->responsibleCanChangeDeadline;
	}

	public function getActivityDate(): DateTime
	{
		return $this->activity;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'creatorId' => $this->creatorId,
			'ownerId' => $this->ownerId,
			'groupId' => $this->groupId,
			'templateId' => $this->templateId,
			'efficiency' => $this->efficiency,
			'active' => $this->active,
			'plannedCompletionTime' => $this->plannedCompletionTime,
			'activity' => $this->activity,
			'name' => $this->name,
			'description' => $this->description,
			'distributionType' => $this->distributionType?->value,
			'responsibleList' => $this->responsibleList,
			'demo' => $this->demo,

			'responsibleCanChangeDeadline' => $this->responsibleCanChangeDeadline,
			'matchWorkTime' => $this->matchWorkTime,
			'matchSchedule' => $this->matchSchedule,
			'taskControl' => $this->taskControl,

			'notifyAtHalfTime' => $this->notifyAtHalfTime,
			'notifyOnQueueOverflow' => $this->notifyOnQueueOverflow,
			'notifyOnTasksInProgressOverflow' => $this->notifyOnTasksInProgressOverflow,
			'notifyWhenEfficiencyDecreases' => $this->notifyWhenEfficiencyDecreases,
			'taskCreators' => $this->taskCreators,
			'team' => $this->team,

			'trialFeatureEnabled' => $this->trialFeatureEnabled,
		];
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
