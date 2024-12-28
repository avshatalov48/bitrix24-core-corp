<?php

namespace Bitrix\HumanResources\Type\HcmLink;

use Bitrix\HumanResources\Trait\ValuesTrait;

enum JobStatus: int
{
	use ValuesTrait;

	case UNKNOWN = 0;
	case STARTED = 1;
	case IN_PROGRESS = 2;
	case DONE = 3;
	case CANCELED = 4;
	case EXPIRED = 5;

	public function isFinished(): bool
	{
		return in_array($this, self::getFinished(), true);
	}

	public function isActual(): bool
	{
		return in_array($this, [JobStatus::IN_PROGRESS, JobStatus::DONE, JobStatus::CANCELED], true);
	}

	/**
	 * @return list<self::*>
	 */
	public static function getFinished(): array
	{
		return [self::DONE, self::CANCELED, self::EXPIRED];
	}

	/**
	 * @return list<self::*>
	 */
	public static function getNotFinished(): array
	{
		$all = self::cases();

		return array_filter(
			$all,
			static fn($case) => !in_array($case, self::getFinished(), true)
		);
	}
}
