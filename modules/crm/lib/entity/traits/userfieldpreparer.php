<?php

namespace Bitrix\Crm\Entity\Traits;

/**
 * Trait UserFieldPreparer
 * @package Bitrix\Crm\Entity\Traits
 */
trait UserFieldPreparer
{
	public function fillEmptyFieldValues(array &$entityFields, array $allUserFields): void
	{
		foreach($allUserFields as $fieldName => $field)
		{
			if (!array_key_exists($fieldName, $entityFields))
			{
				$className = $field['USER_TYPE']['CLASS_NAME'];
				if(is_callable([$className, 'getDefaultValue']))
				{
					$defaultValue = call_user_func_array(
						[$className, 'getDefaultValue'],
						[$field]
					);
					$entityFields[$fieldName] = $defaultValue;
				}
			}
		}
	}
}