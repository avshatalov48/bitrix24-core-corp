<?php

namespace Bitrix\Tasks\Flow\Comment\Task\Add;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Comment\Task\FlowCommentInterface;
use Bitrix\Tasks\Flow\Comment\UserLinkTrait;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Psr\Container\NotFoundExceptionInterface;

class HimselfFlowTaskAdd implements FlowCommentInterface
{
	use UserLinkTrait;

	private const MAX_DISPLAY_USER = 5;

	protected int $taskId;
	private Flow $flow;

	/**
	 * @var int[]
	 */
	private array $flowMembersList = [];
	private string $messageKey;

	public function __construct(int $taskId, Flow $flow)
	{
		$this->taskId = $taskId;
		$this->flow = $flow;

		$this->setMembersList();
		$this->setMessageKey();
	}

	public function getPartName(): string
	{
		return 'flow';
	}

	public function getMessageKey(): string
	{
		return $this->messageKey;
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws FlowNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	private function setMembersList(): void
	{
		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$memberAccessCodes = $memberFacade->getResponsibleAccessCodes($this->flow->getId());

		$this->flowMembersList = (new AccessCodeConverter(...$memberAccessCodes))
			->getUserIds()
		;
	}

	private function setMessageKey(): void
	{
		if (count($this->flowMembersList) > self::MAX_DISPLAY_USER)
		{
			$this->messageKey = 'COMMENT_POSTER_COMMENT_TASK_ADD_TO_FLOW_WITH_HIMSELF_DISTRIBUTION_WITH_MORE';

			return;
		}

		$this->messageKey = 'COMMENT_POSTER_COMMENT_TASK_ADD_TO_FLOW_WITH_HIMSELF_DISTRIBUTION';
	}

	public function getReplaces(): array
	{
		$messageKey = $this->getMessageKey();

		$displayMembers = $this->flowMembersList;
		if (count($this->flowMembersList) > self::MAX_DISPLAY_USER)
		{
			$displayMembers = array_slice($this->flowMembersList, 0, self::MAX_DISPLAY_USER);
			$countExcludedMembers = count($this->flowMembersList) - self::MAX_DISPLAY_USER;

			$replace = [
				'#RESPONSIBLE_LIST#' => $this->getUserBBCodes($displayMembers),
				'#COUNT_USERS#' => $countExcludedMembers,
			];

			return [[$messageKey, $replace]];
		}

		$replace = [
			'#RESPONSIBLE_LIST#' => $this->getUserBBCodes($displayMembers),
		];

		return [[$messageKey, $replace]];
	}
}