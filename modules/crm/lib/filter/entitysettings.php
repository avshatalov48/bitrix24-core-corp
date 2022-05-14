<?php
namespace Bitrix\Crm\Filter;

abstract class EntitySettings extends \Bitrix\Main\Filter\EntitySettings
{
	public function getEntityTypeName()
	{
		return $this->getEntityTypeID();
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Undefined;
	}
}
