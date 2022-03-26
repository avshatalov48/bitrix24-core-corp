<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Import extends Operation\Add
{
	public const ERROR_CODE_ITEM_IMPORT_ACCESS_DENIED = 'CRM_ITEM_IMPORT_ACCESS_DENIED';

	public function checkAccess(): Result
	{
		$result = new Result();

		$userPermissions = Container::getInstance()->getUserPermissions($this->getContext()->getUserId());
		$canImportItem = $userPermissions->canImportItem($this->item);

		if (!$canImportItem)
		{
			$result->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_ITEM_PERMISSIONS_IMPORT_DENIED'),
					static::ERROR_CODE_ITEM_IMPORT_ACCESS_DENIED,
				)
			);
		}

		return $result;
	}

	public function isAutomationEnabled(): bool
	{
		// automation is always disabled in import for performance reasons
		return false;
	}

	public function isBizProcEnabled(): bool
	{
		// bizproc is always disabled in import for performance reasons
		return false;
	}
}
