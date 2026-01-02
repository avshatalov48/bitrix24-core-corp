<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Command\Type;

use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Onboarding\Command\CommandInterface;
use Bitrix\Tasks\Onboarding\Command\Result\CommandResult;
use Bitrix\Tasks\Onboarding\Command\Trait\CommentTrait;
use Bitrix\Tasks\Onboarding\Command\Trait\InvitationTrait;
use Bitrix\Tasks\Onboarding\Comment\ResponsibleInvitationNotAcceptedOneDayComment;
use Bitrix\Tasks\Onboarding\Internal\Type;

class ResponsibleInvitationNotAcceptedOneDay implements CommandInterface
{
	use CommentTrait;
	use InvitationTrait;

	protected int $id;
	protected int $taskId;
	protected int $userId;
	protected Type $type;
	protected string $code;

	protected ?TaskObject $task = null;

	public function __construct(int $id, int $taskId, int $userId, Type $type, string $code)
	{
		$this->id = $id;
		$this->taskId = $taskId;
		$this->userId = $userId;
		$this->type = $type;
		$this->code = $code;

		$this->init();
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function __invoke(): CommandResult
	{
		$result = new CommandResult();
		if ($this->task === null)
		{
			return $result;
		}

		if (!$this->isInvitedUser($this->task->getResponsibleId()))
		{
			return $result;
		}

		$this->postComment(
			$this->task,
			new ResponsibleInvitationNotAcceptedOneDayComment($this->task)
		);

		return $result;
	}


	protected function init(): void
	{
		$this->task = TaskRegistry::getInstance()->getObject($this->taskId);
	}
}