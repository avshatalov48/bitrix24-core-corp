<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Type;

use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Onboarding\Command\CountableCommandInterface;
use Bitrix\Tasks\Onboarding\Command\Result\CommandResult;
use Bitrix\Tasks\Onboarding\Command\Trait\ContainerTrait;
use Bitrix\Tasks\Onboarding\Internal\Config\JobLimit;
use Bitrix\Tasks\Onboarding\Internal\Config\TaskCountLimit;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\Notification\NotificationController;

class TooManyTasks implements CountableCommandInterface
{
	use ContainerTrait;

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

		if (!$this->canIncreaseCounter())
		{
			return $result;
		}

		$notificationController = new NotificationController();

		$notificationController->onTooManyTasks($this->task)->push();

		return $result;
	}

	public function getExecutionLimit(): int
	{
		return JobLimit::get($this->type);
	}

	public function canIncreaseCounter(): bool
	{
		if ($this->task === null)
		{
			return false;
		}

		if ($this->task->getCreatedBy() !== $this->task->getResponsibleId())
		{
			return false;
		}

		$limit = TaskCountLimit::get($this->type);

		$taskRepository = $this->getContainer()->getTaskRepository();
		$count = $taskRepository->getOnePersonTasksCount($this->task->getCreatedBy());

		return $count >= $limit;
	}

	protected function init(): void
	{
		$this->task = TaskRegistry::getInstance()->getObject($this->taskId);
	}
}
