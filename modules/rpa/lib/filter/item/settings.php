<?php

namespace Bitrix\Rpa\Filter\Item;

use Bitrix\Main\Filter\EntitySettings;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\Type;

class Settings extends EntitySettings
{
	protected $type;

	public function __construct(array $params, Type $type)
	{
		parent::__construct($params);
		$this->type = $type;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function getEntityTypeName(): string
	{
		return Driver::getInstance()->getFactory()->getUserFieldEntityId($this->type->getId());
	}

	public function getUserFieldEntityID(): string
	{
		return $this->getEntityTypeName();
	}
}