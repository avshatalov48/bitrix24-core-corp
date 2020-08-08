<?php

namespace Bitrix\Rpa\Filter\Type;

use Bitrix\Main\Filter\EntitySettings;
use Bitrix\Main\ORM\Entity;
use Bitrix\Rpa\Driver;

class Settings extends EntitySettings
{
	protected $type;
	protected $entity;

	public function getEntityTypeName(): string
	{
		return 'RPA';
	}

	public function getUserFieldEntityID(): string
	{
		return '';
	}

	public function getEntity(): Entity
	{
		return Driver::getInstance()->getFactory()->getTypeDataClass()::getEntity();
	}
}