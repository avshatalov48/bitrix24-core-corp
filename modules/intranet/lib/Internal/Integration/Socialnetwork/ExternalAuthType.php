<?php

namespace Bitrix\Intranet\Internal\Integration\Socialnetwork;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\ComponentHelper;

class ExternalAuthType
{
	private array $defaultTypes;
	public function __construct()
	{
		$this->defaultTypes = UserTable::getExternalUserTypes();
	}

	public function getNotUserTypeList(): array
	{
		if (Loader::includeModule('socialnetwork'))
		{
			return ComponentHelper::checkPredefinedAuthIdList(
				array_diff($this->defaultTypes, ['email', 'shop'])
			);
		}

		return [];
	}

	public function getAllTypeList(): array
	{
		if (Loader::includeModule('socialnetwork'))
		{
			return ComponentHelper::checkPredefinedAuthIdList(
				$this->defaultTypes
			);
		}

		return [];
	}
}