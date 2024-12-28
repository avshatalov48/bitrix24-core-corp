<?php

namespace Bitrix\Sign\Service;

use Bitrix\Sign\Type\CounterType;
use CAllUserCounter;
use CUserCounter;

final class CounterService
{
	private const B2E_MY_DOCUMENTS_EVENT_NAME = 'changeB2eMyDocumentsCounters';

	public function get(CounterType $counterType, int $userId): int
	{
		return ($userId > 0) ? (int)CUserCounter::GetValue($userId, $counterType->value, CAllUserCounter::ALL_SITES) : 0;
	}

	public function set(CounterType $counterType, int $value, int $userId): bool
	{
		if ($userId < 1)
		{
			return false;
		}

		return CUserCounter::Set($userId, $counterType->value, $value, CAllUserCounter::ALL_SITES);
	}

	public function clear(CounterType $counterType, int $userId): void
	{
		if ($userId < 1)
		{
			return;
		}

		CUserCounter::Clear($userId, $counterType->value, CAllUserCounter::ALL_SITES);
	}

	public function getPullEventName(CounterType $counterType): string
	{
		return match ($counterType)
		{
			CounterType::SIGN_B2E_MY_DOCUMENTS => self::B2E_MY_DOCUMENTS_EVENT_NAME,
		};
	}
}
