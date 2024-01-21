<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;

class Dynamic extends Item
{
	public function getTitlePlaceholder(): ?string
	{
		return $this->entityObject->getDefaultTitle();
	}

	protected function getItemReferenceFieldNameInProduct(): string
	{
		return \CCrmOwnerType::ResolveName($this->getEntityTypeId());
	}

	protected function disableCheckUserFields(): void
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		if ($factory)
		{
			$dataClass = $factory->getDataClass();
			$dataClass::disableUserFieldsCheck();
		}
	}
}
