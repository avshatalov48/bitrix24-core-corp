<?php

namespace Bitrix\Crm\ListEntity\Entity;

use Bitrix\Crm\ListEntity\Entity;

class Dynamic extends Entity
{
	protected $factory;

	public function setFactory(Dynamic $factory): Entity
	{
		$this->factory = $factory;
		return $this;
	}

	public function getTypeName(): string
	{
		return $this->factory->getEntityName();
	}
}
