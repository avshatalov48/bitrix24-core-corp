<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\Internals\exception\UniqueCodeGenerationException;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;

class UniqueCode
{
	public const DEFAULT_LENGTH = 20;
	public const MAX_ATTEMPTS = 10;

	public function __construct(private readonly int $length = self::DEFAULT_LENGTH)
	{}

	public function generate(): string
	{
		return Random::getString($this->length, true);
	}

	public function generateForNewFile(): string
	{
		$entity = ObjectTable::getEntity();
		$query = new Query($entity);
		$query->setSelect(['ID']);
		$query->setLimit(1);
		$attemptCount = 0;

		while ($attemptCount < self::MAX_ATTEMPTS)
		{
			$uniqueCode = $this->generate();
			$query->where('UNIQUE_CODE', $uniqueCode);

			$queryResult = $query->exec()->fetch();
			if ($queryResult === false)
			{
				return $uniqueCode;
			}

			$attemptCount++;
		}

		throw new UniqueCodeGenerationException(Loc::getMessage('DISK_UNIQUE_CODE_MAX_ATTEMPTS_EXCEEDED'));
	}
}