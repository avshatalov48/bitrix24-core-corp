<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Counter\Repository;

use Bitrix\Tasks\Onboarding\Counter\CounterRepositoryInterface;
use Bitrix\Tasks\Onboarding\Internal\Config\JobLimit;
use Bitrix\Tasks\Onboarding\Internal\Factory\JobCodeFactory;
use Bitrix\Tasks\Onboarding\Internal\Model\JobCountTable;
use Bitrix\Tasks\Onboarding\Internal\Type;

class CounterRepository implements CounterRepositoryInterface
{
	protected const CACHE_TTL = 86400 * 30;

	public function getByCode(string $code): ?int
	{
		$row = JobCountTable::query()
			->setSelect(['JOB_COUNT'])
			->where('CODE', $code)
			->setCacheTtl(static::CACHE_TTL)
			->fetch();

		if (empty($row))
		{
			return null;
		}

		return (int)($row['JOB_COUNT']);
	}

	public function isLimitReachedByType(Type $type, int $userId): bool
	{
		$code = JobCodeFactory::createCode($type, $userId);

		$count = $this->getByCode($code->code);

		return $count >= JobLimit::get($type);
	}
}