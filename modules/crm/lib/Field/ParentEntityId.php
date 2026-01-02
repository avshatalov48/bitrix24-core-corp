<?php

namespace Bitrix\Crm\Field;

class ParentEntityId extends AbstractRelatedEntityField
{

	protected function getRelatedEntityTypeId(): int
	{
		return $this->settings['parentEntityTypeId'];
	}
}
