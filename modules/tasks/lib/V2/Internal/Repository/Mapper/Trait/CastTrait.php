<?php

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper\Trait;

use Bitrix\Main\Type\DateTime;
use Exception;

trait CastTrait
{
	protected function castMember(int $userId): array
	{
		return ['id' => $userId];
	}

	protected function castMembers(array $userIds): array
	{
		return array_map(static fn (mixed $id): array => ['id' => (int)$id], $userIds);
	}

	protected function castTimestamp(?int $timestamp, bool $defaultNow = true): ?DateTime
	{
		if ((int)$timestamp === 0)
		{
			return $defaultNow ? new DateTime() : null;
		}

		return DateTime::createFromTimestamp($timestamp);
	}

	protected function castDateTime(mixed $dateTime): ?int
	{
		if ($dateTime === 0 || $dateTime === '0' || $dateTime === '')
		{
			return 0;
		}

		if (is_numeric($dateTime))
		{
			return (int)$dateTime;
		}

		if ($dateTime instanceof DateTime)
		{
			return $dateTime->getTimestamp();
		}

		if ($dateTime instanceof \DateTime)
		{
			return $dateTime->getTimestamp();
		}

		if (is_string($dateTime) && $dateTime !== '')
		{
			try
			{
				return DateTime::createFromUserTime($dateTime)->getTimestamp();
			}
			catch (Exception)
			{
				return null;
			}
		}

		return null;
	}
}