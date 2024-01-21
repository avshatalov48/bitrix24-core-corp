<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\ORM\Entity;

class TypeSettings extends EntitySettings
{
	private bool $isExternalDynamicalTypes;

	public function __construct(array $params)
	{
		parent::__construct($params);
		$this->isExternalDynamicalTypes = $params['IS_EXTERNAL_DYNAMICAL_TYPES'] ?? false;
	}

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

	/**
	 * @return bool
	 */
	public function getIsExternalDynamicalTypes(): bool
	{
		return $this->isExternalDynamicalTypes;
	}

	/**
	 * @param bool $isExternalDynamicalTypes
	 */
	public function setIsExternalDynamicalTypes(bool $isExternalDynamicalTypes): void
	{
		$this->isExternalDynamicalTypes = $isExternalDynamicalTypes;
	}
}
