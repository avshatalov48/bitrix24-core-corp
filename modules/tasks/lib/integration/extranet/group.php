<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\Extranet;

use Bitrix\Main\Loader;
use CExtranet;

class Group
{
	public function isExtranetGroup(int $groupId): bool
	{
		if (!Loader::includeModule('extranet'))
		{
			return false;
		}

		return CExtranet::IsExtranetSocNetGroup($groupId);
	}
}