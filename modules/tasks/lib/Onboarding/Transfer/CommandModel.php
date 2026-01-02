<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Transfer;

use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\Onboarding\Internal\Type;

final class CommandModel
{
	public function __construct(
		public readonly Type $type,
		#[PositiveNumber]
		public readonly int $taskId,
		#[PositiveNumber]
		public readonly int $userId,
		public readonly bool $isCountable = false,
	)
	{

	}

	public function isEqual(CommandModel $commandModel): bool
	{
		return
			$this->type === $commandModel->type
			&& $this->taskId === $commandModel->taskId
			&& $this->userId === $commandModel->userId
			&& $this->isCountable === $commandModel->isCountable
		;
	}
}
