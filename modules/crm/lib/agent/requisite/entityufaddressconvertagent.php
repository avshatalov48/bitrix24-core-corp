<?php
namespace Bitrix\Crm\Agent\Requisite;

use Bitrix\Main\SystemException;

abstract class EntityUfAddressConvertAgent extends EntityAddressConvertAgent
{
	protected function getOption(string $optionName, $defaultValue = null)
	{
		$result = $defaultValue;

		$progressData = $this->getProgressData();
		$options = is_array($progressData['OPTIONS']) ? $progressData['OPTIONS'] : [];
		if ($optionName !== '' && isset($options[$optionName]))
		{
			$result = $options[$optionName];
		}

		return $result;
	}
	public function getSourceEntityTypeId()
	{
		$result = \CCrmOwnerType::Undefined;

		$optionValue = $this->getOption('SOURCE_ENTITY_TYPE_ID');
		if ($optionValue > 0)
		{
			$sourceEntityTypeId = (int)$optionValue;
			if (\CCrmOwnerType::IsDefined($sourceEntityTypeId))
			{
				$result = $sourceEntityTypeId;
			}
		}

		return $result;
	}
	public function getSourceUserFieldName()
	{
		$result = '';

		$optionValue = $this->getOption('SOURCE_USER_FIELD_NAME');
		if (is_string($optionValue) && $optionValue !== '')
		{
			$result = $optionValue;
		}

		return $result;
	}
}
