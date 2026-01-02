<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

abstract class AbstractRelatedEntityField extends Field
{
	public function processWithPermissions(Item $item, UserPermissions $userPermissions): Result
	{
		$result = new Result();
		if (!$item->isChanged($this->getName()))
		{
			return $result;
		}
		if ($this->canSkipPermissionsCheck($item))
		{
			return $result;
		}

		$values = (array)$item->get($this->getName());

		foreach ($values as $value)
		{
			$value = (int)$value;

			if ($value < 0)
			{
				return $result->addError($this->getValueNotValidError());
			}

			if ($value > 0 && !$userPermissions->item()->canRead($this->getRelatedEntityTypeId(), $value))
			{
				$result->addError(
					new Error(
						sprintf(
							'[%s #%s] %s',
							\CCrmOwnerType::GetDescription($this->getRelatedEntityTypeId()),
							$value,
							Loc::getMessage('CRM_COMMON_READ_ACCESS_DENIED')
						)
					)
				);
			}
		}

		return $result;
	}

	protected function canSkipPermissionsCheck(Item $item): bool
	{
		$multipleImplementationFieldName = $this->getMultipleImplementationFieldName();
		if (!$multipleImplementationFieldName)
		{
			return false;
		}

		if ($item->hasField($multipleImplementationFieldName)) // to avoid duplicated errors for example in CONTACT_ID and CONTACT_IDS fields
		{
			$fieldValue = (int)$item->get($this->getName());
			if ($fieldValue > 0 && in_array($fieldValue, (array)$item->get($multipleImplementationFieldName)))
			{
				return true;
			}
		}

		return false;
	}

	protected function getMultipleImplementationFieldName(): ?string
	{
		return null;
	}

	abstract protected function getRelatedEntityTypeId(): int;
}
