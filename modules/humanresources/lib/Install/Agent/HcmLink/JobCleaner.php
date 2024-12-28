<?php

namespace Bitrix\HumanResources\Install\Agent\HcmLink;

use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Type\DateTime;

class JobCleaner
{
	private const PERIOD_MONTHS = '-1 month';
	private const LIMIT = 100;

	public static function run(): string
	{
		$date = (new DateTime())->add(self::PERIOD_MONTHS);

		$jobRepository = Container::getHcmLinkJobRepository();
		$expiredIds = $jobRepository->listIdsByDate($date, self::LIMIT);

		$jobRepository->removeByIds($expiredIds);

		return sprintf('%s::%s();', self::class, __FUNCTION__);
	}
}