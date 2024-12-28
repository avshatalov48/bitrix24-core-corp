<?php

namespace Bitrix\HumanResources\Install\Agent\HcmLink;

use Bitrix\HumanResources\Service\Container;

class ExpiredFieldValueCleaner
{
	private const LIMIT = 100;

	public static function run(): string
	{
		$fieldValueRepository = Container::getHcmLinkFieldValueRepository();
		$expiredIds = $fieldValueRepository->listExpiredIds(self::LIMIT);

		$fieldValueRepository->removeByIds($expiredIds);


		return sprintf('%s::%s();', self::class, __FUNCTION__);
	}
}