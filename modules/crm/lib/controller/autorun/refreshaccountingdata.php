<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Result;

final class RefreshAccountingData extends Base
{
	protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
	{
		if (!Container::getInstance()->getUserPermissions()->canUpdateItem($item))
		{
			return (new Result())->addError(ErrorCode::getAccessDeniedError());
		}

		return $this->refreshAccountingData($item);
	}

	private function refreshAccountingData(Item $item): Result
	{
		$result = new Result();

		if ($item->getEntityTypeId() === \CCrmOwnerType::Lead)
		{
			\CCrmLead::RefreshAccountingData([$item->getId()]);
		}
		elseif ($item->getEntityTypeId() === \CCrmOwnerType::Deal)
		{
			\CCrmDeal::RefreshAccountingData([$item->getId()]);
		}
		else
		{
			$result->addError(ErrorCode::getEntityTypeNotSupportedError($item->getEntityTypeId()));
		}

		return $result;
	}
}
