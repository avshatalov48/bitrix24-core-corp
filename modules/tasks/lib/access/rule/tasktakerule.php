<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Tasks\Access\Rule;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Internals\Task\Status;
use Psr\Container\NotFoundExceptionInterface;

class TaskTakeRule extends AbstractRule
{
	public function execute(AccessibleItem $task = null, $params = null): bool
	{
		if (!FlowFeature::isFeatureEnabled() || !FlowFeature::isOn())
		{
			$this->controller->addError(static::class, 'Flow feature is not enabled.');
			return false;
		}

		if (!$this->isTaskExists($task))
		{
			$this->controller->addError(static::class, 'Incorrect task');
			return false;
		}

		if (!$this->checkTaskStatus($task))
		{
			$this->controller->addError(static::class, 'Incorrect status');
			return false;
		}

		if (!$this->isOwnerEqualsResponsible($task))
		{
			$this->controller->addError(static::class, 'Owner must be equals to responsible');
			return false;
		}

		if ($this->checkUserIsNotResponsible($task))
		{
			$this->controller->addError(static::class, 'You are already responsible');
			return false;
		}

		$flow = $this->getFlow($task);

		if (!$flow)
		{
			$this->controller->addError(static::class, 'Flow not found');
			return false;
		}

		if (
			$flow->getDistributionType() === FlowDistributionType::HIMSELF
			&& $this->checkUserInFlowTeam($flow)
		)
		{
			return true;
		}

		$this->controller->addError(static::class, 'You must be in flow team');
		return false;
	}

	private function isTaskExists(?AccessibleItem $task): bool
	{
		return $task !== null;
	}

	private function checkTaskStatus(AccessibleItem $task): bool
	{
		return in_array((int)$task->getStatus(), [Status::NEW, Status::PENDING], true);
	}

	private function isOwnerEqualsResponsible(AccessibleItem $task): bool
	{
		return $task->getMembers(RoleDictionary::ROLE_DIRECTOR) === $task->getMembers(RoleDictionary::ROLE_RESPONSIBLE);
	}

	private function checkUserIsNotResponsible(AccessibleItem $task): bool
	{
		return in_array($this->user->getUserId(), $task->getMembers(RoleDictionary::ROLE_RESPONSIBLE), true);
	}

	private function getFlow(AccessibleItem $task): ?Flow
	{
		$flowData = FlowRegistry::getInstance()->get($task->getFlowId());
		if ($flowData === null)
		{
			return null;
		}

		return new Flow($flowData->toArray());
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws FlowNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	private function checkUserInFlowTeam(Flow $flow): bool
	{
		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$teamAccessCodes = $memberFacade->getTeamAccessCodes($flow->getId());

		$teamIds = (new AccessCodeConverter(...$teamAccessCodes))
			->getUserIds()
		;

		$userId = $this->user->getUserId();

		return in_array($userId, $teamIds, true);
	}
}
