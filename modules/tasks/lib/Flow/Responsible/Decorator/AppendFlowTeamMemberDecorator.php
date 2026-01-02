<?php

namespace Bitrix\Tasks\Flow\Responsible\Decorator;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\FlowMemberFacade;
use Bitrix\Tasks\Flow\Responsible\Distributor\DistributorStrategyInterface;

class AppendFlowTeamMemberDecorator implements DistributorStrategyInterface
{

	/**
	 * @var DistributorStrategyInterface $strategy
	 * @var FlowMemberFacade $memberFacade
	 */
	public function __construct(
		private DistributorStrategyInterface $strategy
	)
	{
		$this->strategy = $this->strategy;
		$this->memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
	}

	public function distribute(Flow $flow, array $fields, array $taskData): int
	{
		$responsibleId = $this->strategy->distribute($flow, $fields, $taskData);

		$memberAccessCodes = $this->memberFacade->getTeamAccessCodes($flow->getId());
		$userIds = (new AccessCodeConverter(...$memberAccessCodes))->getUserIds();

		if (!in_array($responsibleId, $userIds, true))
		{
			$this->appendResponsibleToFlow($flow->getId(), $responsibleId, $memberAccessCodes);
		}

		return $responsibleId;
	}

	private function appendResponsibleToFlow(int $flowId, int $memberId, array $memberAccessCodes): void
	{
		$command = (new UpdateCommand())
			->setId($flowId)
			->setResponsibleList([...$memberAccessCodes, "U{$memberId}"])
		;

		$this->save($command);
	}

	private function save(UpdateCommand $command): void
	{
		$flowService = ServiceLocator::getInstance()->get('tasks.flow.service');
		$flowService->update($command);
	}
}