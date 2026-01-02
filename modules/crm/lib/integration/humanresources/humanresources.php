<?php

namespace Bitrix\Crm\Integration\HumanResources;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Type\AccessCodeType;
use Bitrix\Main\Loader;

final class HumanResources
{
	use Singleton;

	public function buildAccessCode(string $value, int $nodeId): ?string
	{
		if ($this->isUsed())
		{
			return AccessCodeType::tryFrom($value)?->buildAccessCode($nodeId);
		}

		return null;
	}

	public function isUsed(): bool
	{
		return Loader::includeModule('humanresources')
			&& Storage::instance()->isCompanyStructureConverted(false)
		;
	}
}
