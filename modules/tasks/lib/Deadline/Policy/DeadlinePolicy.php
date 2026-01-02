<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Policy;

use Bitrix\Main\Type\DateTime;

/**
 * DeadlinePolicy is responsible for checking if a user is allowed to update a task's deadline
 * according to project configuration and business rules.
 */
class DeadlinePolicy
{
	/**
	 * @param bool $canChangeDeadline                Global flag: can the executor change the deadline at all?
	 * @param DateTime|null $dateTime The latest allowed deadline date (null if not limited)
	 * @param int|null $maxDeadlineChanges           Maximum number of allowed changes (null if unlimited)
	 * @param bool $requireDeadlineChangeReason      Is user required to provide a reason for change?
	 */
	public function __construct(
		private readonly bool $canChangeDeadline,
		private readonly ?DateTime $dateTime = null,
		private readonly ?int $maxDeadlineChanges = null,
		private readonly bool $requireDeadlineChangeReason = false
	)
	{
	}

	/**
	 * Main access point: Checks if the user may update the deadline.
	 * @param DateTime $dateTime New deadline user wishes to set
	 * @param int $userChangesCount How many times the user has already changed the deadline
	 * @param null|string $reason Optional reason for the change
	 * @return array [bool $result, array $violations]
	 *
	 * Violations:
	 *  - 'forbidden'
	 *  - 'date_exceeds_limit'
	 *  - 'exceeded_change_count'
	 *  - 'reason_required'
	 */
	public function canUpdateDeadline(
		DateTime $dateTime,
		int $userChangesCount,
		?string $reason = null
	): array
	{
		$violations = [];

		if (!$this->canChangeDeadline)
		{
			$violations[] = 'forbidden';
		}

		if ($this->dateTime instanceof \Bitrix\Main\Type\DateTime && $dateTime > $this->dateTime)
		{
			$violations[] = 'date_exceeds_limit';
		}

		if ($this->maxDeadlineChanges !== null && $userChangesCount >= $this->maxDeadlineChanges)
		{
			$violations[] = 'exceeded_change_count';
		}

		if ($this->requireDeadlineChangeReason && (empty($reason) || trim($reason) === ''))
		{
			$violations[] = 'reason_required';
		}

		return [empty($violations), $violations];
	}

	public function isReasonRequired(): bool
	{
		return $this->requireDeadlineChangeReason;
	}

	// Accessors for synchronization with frontend (optional)
	public function getPolicyOptions(): array
	{
		return [
			'canChangeDeadline' => $this->canChangeDeadline,
			'maxDeadlineChangeDate' => $this->dateTime?->format(DateTime::getFormat()),
			'maxDeadlineChanges' => $this->maxDeadlineChanges,
			'requireDeadlineChangeReason' => $this->requireDeadlineChangeReason
		];
	}
}
