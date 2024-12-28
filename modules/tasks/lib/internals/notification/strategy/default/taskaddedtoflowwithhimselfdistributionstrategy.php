<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;
use Psr\Container\NotFoundExceptionInterface;

class TaskAddedToFlowWithHimselfDistributionStrategy implements RecipientStrategyInterface
{
	use AddUserTrait;

	private UserRepositoryInterface $userRepository;
	private TaskObject $task;
	private Dictionary $dictionary;

	public function __construct(UserRepositoryInterface $userRepository, TaskObject $task, Dictionary $dictionary)
	{
		$this->userRepository = $userRepository;
		$this->task = $task;
		$this->dictionary = $dictionary;
	}

	/**
	 * @throws SystemException
	 * @throws FlowNotFoundException
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectPropertyException
	 * @return int[]
	 */
	public function getRecipients(): array
	{
		$flowId = $this->task->getFlowId();
		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$memberAccessCodes = $memberFacade->getResponsibleAccessCodes($flowId);

		$memberIds = (new AccessCodeConverter(...$memberAccessCodes))
			->getUserIds()
		;

		return $this->userRepository->getUsersByIds($memberIds);
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getUserById($this->task->getCreatedBy());
	}
}
