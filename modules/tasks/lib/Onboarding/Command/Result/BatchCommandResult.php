<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Command\Result;

use Bitrix\Main\Result;

final class BatchCommandResult extends Result
{
	private array $completedCommandIds = [];
	private array $notCompletedCommandIds = [];
	private array $duplicatedCommandCodes = [];

	public function addCompletedCommandId(int $id): void
	{
		$this->completedCommandIds[] = $id;
	}

	public function getCompletedCommandIds(): array
	{
		return $this->completedCommandIds;
	}

	public function addNotCompletedCommandId(int $id): void
	{
		$this->notCompletedCommandIds[] = $id;
	}

	public function getNotCompletedCommandIds(): array
	{
		return $this->notCompletedCommandIds;
	}

	public function addDuplicatedCommandCodes(string $code): void
	{
		$this->duplicatedCommandCodes[] = $code;
	}

	public function getDuplicatedCommandCodes(): array
	{
		return array_unique($this->duplicatedCommandCodes);
	}
}