<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\Type\Once;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Integration\AI\Result\CollectorResult;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\EntityType;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Type\ValueNode;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\AbstractFiller;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Psr\Container\NotFoundExceptionInterface;

class FlowFiller extends AbstractFiller
{
	private const DEFAULT_UNIT_OF_TIME = 'seconds';

	private array $unitsOfTimeByInterval = [
		self::SECONDS_IN_DAY => 'days',
		self::SECONDS_IN_HOUR => 'hours',
	];

	public function fill(CollectorResult $result): void
	{
		$this->result = $result;

		$this->fillFlowTaskCreators();
		$this->fillFlowMatchWorkTime();
		$this->fillFlowDistributionType();
		$this->fillFlowManualDistributorId();
		$this->fillFlowCreateByTemplate();
		$this->fillFlowCanResponsibleChangeDeadline();
		$this->fillFlowName();
		$this->fillFlowOwnerId();
		$this->fillFlowCreatorId();
		$this->fillFlowPlannedCompletionTime();
		$this->fillFlowEfficiency();
		$this->fillUnitOfTime();
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 * @throws ProviderException
	 * @throws LoaderException
	 * @throws SystemException
	 * @throws FlowNotFoundException
	 */
	private function fillFlowTaskCreators(): void
	{
		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$creatorsAccessCodes = $memberFacade->getTaskCreatorAccessCodes($this->flow->getId());

		$users = (new AccessCodeConverter(...$creatorsAccessCodes))->getUsers()->getAccessCodeIdList();
		$departments = (new AccessCodeConverter(...$creatorsAccessCodes))->getDepartments()->getAccessCodeIdList();
		$isUserAll = (new AccessCodeConverter(...$creatorsAccessCodes))->hasUserAll() ? 'Yes' : 'No';

		$value = [
			'user_all' => $isUserAll,
			'users' => array_map(fn (int $id): string => $this->formatUserIdForNode($id), $users),
			'departments' => $departments
		];

		$node = new ValueNode(
			EntityType::FLOW,
			'task_creators',
			$value,
		);

		$this->result->addNode($node);
	}

	private function fillFlowMatchWorkTime(): void
	{
		$node = new ValueNode(
			EntityType::FLOW,
			'match_work_time',
			$this->flow->getMatchWorkTime() ? 'Yes' : 'No',
		);

		$this->result->addNode($node);
	}

	private function fillFlowDistributionType(): void
	{
		$node = new ValueNode(
			EntityType::FLOW,
			'distribution_type',
			$this->flow->getDistributionType()->value,
		);

		$this->result->addNode($node);
	}

	private function fillFlowManualDistributorId(): void
	{
		if (!$this->flow->isManually())
		{
			return;
		}

		$node = new ValueNode(
			EntityType::FLOW,
			'manual_distributor_id',
			$this->formatUserIdForNode($this->getManualDistributorId()),
		);

		$this->result->addNode($node);
	}

	/**
	 * @throws LoaderException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 * @throws FlowNotFoundException
	 */
	private function getManualDistributorId(): int
	{
		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$manualDistributorAccessCode = $memberFacade->getResponsibleAccessCodes($this->flow->getId())[0];

		return (new AccessCodeConverter($manualDistributorAccessCode))
			->getAccessCodeIdList()[0]
		;
	}

	private function fillFlowCreateByTemplate(): void
	{
		$node = new ValueNode(
			EntityType::FLOW,
			'create_tasks_by_template',
			$this->flow->getTemplateId() > 0 ? 'Yes' : 'No',
		);

		$this->result->addNode($node);
	}

	private function fillFlowCanResponsibleChangeDeadline(): void
	{
		$node = new ValueNode(
			EntityType::FLOW,
			'employee_can_change_deadline',
			$this->flow->canResponsibleChangeDeadline() ? 'Yes' : 'No',
		);

		$this->result->addNode($node);
	}

	private function fillFlowName(): void
	{
		$node = new ValueNode(
			EntityType::FLOW,
			'flow_name',
			$this->flow->getName(),
		);

		$this->result->addNode($node);
	}

	private function fillFlowOwnerId(): void
	{
		$node = new ValueNode(
			EntityType::FLOW,
			'owner_id',
			$this->formatUserIdForNode($this->flow->getOwnerId()),
		);

		$this->result->addNode($node);
	}

	private function fillFlowCreatorId(): void
	{
		$node = new ValueNode(
			EntityType::FLOW,
			'creator_id',
			$this->formatUserIdForNode($this->flow->getCreatorId()),
		);

		$this->result->addNode($node);
	}

	private function fillFlowPlannedCompletionTime(): void
	{
		$interval = $this->getDateInterval();

		$node = new ValueNode(
			EntityType::FLOW,
			'planned_completion_time',
			$this->flow->getPlannedCompletionTime() / $interval,
		);

		$this->result->addNode($node);
	}

	private function fillFlowEfficiency(): void
	{
		$node = new ValueNode(
			EntityType::FLOW,
			'team_efficiency_percentage',
			$this->flow->getEfficiency(),
		);

		$this->result->addNode($node);
	}

	private function fillUnitOfTime(): void
	{
		$interval = $this->getDateInterval();
		$value = $this->unitsOfTimeByInterval[$interval] ?? self::DEFAULT_UNIT_OF_TIME;

		$node = new ValueNode(
			EntityType::FLOW,
			'unit_of_time',
			$value,
		);

		$this->result->addNode($node);
	}

	protected function init(): void
	{
		$this->flow = $this->registry->getFlow();
		$this->employees = $this->registry->getEmployees();
	}
}
