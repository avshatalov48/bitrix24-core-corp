<?php

namespace Bitrix\Crm\Component\EntityDetails\Traits;

use Bitrix\Crm\Attribute\FieldAttributeManager;

trait InitializeAttributeScope
{
	abstract protected function getEntityId();

	abstract protected function getCategoryId();

	private function initializeAttributeScope(): void
	{
		$options = [];

		if ($this->factory->isCategoriesSupported())
		{
			$options['CATEGORY_ID'] = $this->getCategoryId();
		}

		$this->arResult['ENTITY_ATTRIBUTE_SCOPE'] = FieldAttributeManager::resolveEntityScope(
			$this->factory->getEntityTypeId(),
			$this->getEntityId(),
			$options
		);
	}
}
