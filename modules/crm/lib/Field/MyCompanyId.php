<?php

namespace Bitrix\Crm\Field;


use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class MyCompanyId extends Field
{
	public function processWithPermissions(Item $item, UserPermissions $userPermissions): Result
	{
		$result = new Result();
		if (!$item->isChanged($this->getName()))
		{
			return $result;
		}

		$value = (int)$item->get($this->getName());
		if ($value > 0)
		{
			if (!\CCrmCompany::isMyCompany($value))
			{
				return $result->addError($this->getValueNotValidError());
			}

			if (!$userPermissions->myCompany()->canReadBaseFields($value))
			{
				$result->addError(
					new Error(
						sprintf(
							'[%s #%s] %s',
							\CCrmOwnerType::GetDescription(\CCrmOwnerType::Company),
							$value,
							Loc::getMessage('CRM_COMMON_READ_ACCESS_DENIED')
						)
					)
				);
			}
		}
		elseif ($value < 0)
		{
			return $result->addError($this->getValueNotValidError());
		}

		return $result;
	}
}
