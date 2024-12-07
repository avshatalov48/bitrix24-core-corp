<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Exclusion\Applicability;
use Bitrix\Crm\Exclusion\Manager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Result;

final class Exclusion extends Base
{
	protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
	{
		$result = new Result();

		if (!Manager::checkCreatePermission())
		{
			return $result->addError(ErrorCode::getAccessDeniedError());
		}


		$applicabilityResult = Applicability::checkApplicability($item->getEntityTypeId(), $item->getId());
		if (!$applicabilityResult->isSuccess())
		{
			return $result->addErrors($applicabilityResult->getErrors());
		}

		try
		{
			Manager::excludeEntity($item->getEntityTypeId(), $item->getId());
		}
		catch (ObjectException $deletionException)
		{
			$result->addError(
				Error::createFromThrowable($deletionException),
			);
		}

		return $result;
	}
}
