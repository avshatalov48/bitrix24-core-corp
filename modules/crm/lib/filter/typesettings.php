<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Entity;

class TypeSettings extends EntitySettings
{
	public function getUserFieldEntityID(): string
	{
		return '';
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Undefined;
	}

	public function getEntity(): Entity
	{
		return Container::getInstance()->getDynamicTypeDataClass()::getEntity();
	}
}