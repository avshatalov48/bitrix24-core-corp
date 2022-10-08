<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class EnsureMyCompanyRequisitesNotEmpty extends Action
{
	public function process(Item $item): Result
	{
		$result = new Result();

		$myCompanyId = $item->getMycompanyId();
		if ($myCompanyId)
		{
			$defaultRequisite = new DefaultRequisite(new ItemIdentifier(\CCrmOwnerType::Company, $myCompanyId));
			if (!$defaultRequisite->getId())
			{
				$result->addError(new Error(
					Loc::getMessage("COMPANY_REQUISITES_EMPTY_ERROR"),
					Field::ERROR_CODE_REQUIRED_FIELD_ATTRIBUTE,
					[
						'fieldName' => Item::FIELD_NAME_MYCOMPANY_ID
					]
				));
			}
		}

		return $result;
	}
}
