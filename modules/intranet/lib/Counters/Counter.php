<?php

namespace Bitrix\Intranet\Counters;

use Bitrix\Intranet\User;
use CUserCounter;

class Counter
{
	public function __construct(
		private string $counterId,
		private ?SyncInterface $syncStrategy = null
	)
	{
	}

	/**
	 * @return string
	 */
	public function getCounterId(): string
	{
		return $this->counterId;
	}

	public function getValue(User $user): int
	{
		return (int)\CUserCounter::GetValue($user->getId(), $this->counterId, \CUserCounter::ALL_SITES);
	}

	public function setValue(User $user, int $value): bool
	{
		return \CUserCounter::Set($user->getId(), $this->counterId, $value, \CUserCounter::ALL_SITES);
	}

	public function sync(): void
	{
		$this->syncStrategy?->sync($this);
	}
}