<?php

namespace Bitrix\Crm\Service\Display\Field;

class CrmEntityField extends CrmField
{
	public const TYPE = 'crm_entity';

	protected function __construct(string $id)
	{
		parent::__construct($id);

		if (strpos($id, 'PARENT_ID_') === 0)
		{
			$entityTypeId = (int)substr($id, 10);

			if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
			{
				$this->displayParams[\CCrmOwnerType::ResolveName($entityTypeId)] = 'Y';
			}
		}
	}
}
