<?php

namespace Bitrix\Tasks\Integration\Bitrix24;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

final class Portal
{
	private const CACHE_TTL = 60 * 60 * 24 * 30;
	private const FIRST_ADMIN_ID = 1;

	public function getCreationDateTime(): ?DateTime
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return $this->getFirstUserRegisterDate();
		}

		$createTime = (int)(\CBitrix24::getCreateTime());

		if ($createTime <= 0)
		{
			return null;
		}

		return DateTime::createFromTimestamp($createTime);
	}

	private function getFirstUserRegisterDate(): ?DateTime
	{
		$firstUser = UserTable::query()
			->setSelect(['ID', 'DATE_REGISTER'])
			->where('ID', self::FIRST_ADMIN_ID)
			->setLimit(1)
			->setCacheTtl(self::CACHE_TTL)
			->fetchObject();

		return $firstUser?->getDateRegister();

	}
}
