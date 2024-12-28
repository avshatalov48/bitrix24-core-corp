<?php

namespace Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Distribution\Trait\ChangeFlowDistributionTrait;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\InvalidCommandException;
use Psr\Container\NotFoundExceptionInterface;

abstract class AbstractRemoveResponsibleService
{
	use ChangeFlowDistributionTrait;

	protected NotificationService $notificationService;

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function __construct()
	{
		$this->notificationService = ServiceLocator::getInstance()->get('tasks.flow.notification.service');
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @return int[]
	 */
	public function getFlowIdsByUser(int $userId, int $limit = 100): array
	{
		$query = FlowMemberTable::query()
			->setSelect(['ID', 'FLOW_ID', 'ENTITY_ID', 'ROLE'])
			->where('ENTITY_ID', $userId)
			->where('ROLE', $this->getResponsibleRole()->value)
			->setLimit($limit);

		return $query->exec()->fetchCollection()->getFlowIdList();
	}

	abstract protected function getResponsibleRole(): Role;

	/**
	 * @throws SqlQueryException
	 */
	protected function removeAllResponsibleByFlowId(int $flowId): void
	{
		FlowMemberTable::deleteByFlowId($flowId);
	}

	/**
	 * @param int $deletedUserId
	 * @param int[] $flowIds
	 * @return void
	 */
	abstract public function removeUserFromFlowsResponsible(int $deletedUserId, array $flowIds): void;

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 */
	protected function saveFlowWithoutDeletedUser(int $flowId, array $memberIds): void
	{
		$flow = FlowRegistry::getInstance()->get($flowId);

		if (!$flow)
		{
			return;
		}

		$memberAccessCodes = array_map(static fn(int $userId): string => "U${userId}", $memberIds);
		$command =
			(new UpdateCommand())
				->setId($flowId)
				->setResponsibleList($memberAccessCodes)
		;

		$this->save($command);
	}
}