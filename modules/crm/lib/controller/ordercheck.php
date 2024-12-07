<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Main;
use Bitrix\Main\Error;

class OrderCheck extends Main\Engine\Controller
{
	public function reprintAction(int $checkId): void
	{
		if (!$this->checkPermissions())
		{
			$this->addError(new Error('Access denied'));

			return;
		}

		if (!\Bitrix\Main\Loader::includeModule('sale'))
		{
			$this->addError(new Error('Module sale is not installed'));

			return;
		}

		$result = \Bitrix\Sale\Cashbox\CheckManager::reprint($checkId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	private function checkPermissions(): bool
	{
		return EntityAuthorization::checkPermission(
			EntityPermissionType::UPDATE,
			\CCrmOwnerType::Order
		);
	}
}
