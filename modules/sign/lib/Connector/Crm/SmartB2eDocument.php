<?php

namespace Bitrix\Sign\Connector\Crm;

use Bitrix\Main\Loader;
use CCrmOwnerType;

final class SmartB2eDocument extends Base
{
	public function __construct(
		private int $entityId
	) {}

	public function getCrmEntityTypeId(): int
	{
		if (!Loader::includeModule('crm'))
		{
			return 0;
		}

		return CCrmOwnerType::SmartB2eDocument;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function getName(): string
	{
		return $this->fetchFields()->getFirstByName('TITLE')?->name ?? '';
	}
}