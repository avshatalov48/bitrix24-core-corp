<?php

namespace Bitrix\Crm\Filter;


class ActivitySettings extends EntitySettings
{
	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::Activity;
	}

	public function getUserFieldEntityID(): string
	{
		return '';
	}
}