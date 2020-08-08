<?php

namespace Bitrix\Rpa\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\FieldTable;

class Fields extends Base
{
	public function getSettingsAction(\Bitrix\Rpa\Model\Type $type, int $stageId = 0): ?array
	{
		if(!Driver::getInstance()->getUserPermissions()->canModifyType($type->getId()))
		{
			$this->addError(new Error(Loc::getMessage('RPA_MODIFY_TYPE_ACCESS_DENIED')));
			return null;
		}

		return [
			'fields' => FieldTable::getGroupedList($type->getId(), $stageId),
		];
	}

	public function setSettingsAction(\Bitrix\Rpa\Model\Type $type, array $fields = [], int $stageId = 0): ?array
	{
		if(!Driver::getInstance()->getUserPermissions()->canModifyType($type->getId()))
		{
			$this->addError(new Error(Loc::getMessage('RPA_MODIFY_TYPE_ACCESS_DENIED')));
			return null;
		}

		$result = FieldTable::mergeSettings($type->getId(), $stageId, $fields);
		if(!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return [
			'fields' => FieldTable::getGroupedList($type->getId(), $stageId),
		];
	}

	public function setVisibilitySettingsAction(\Bitrix\Rpa\Model\Type $type, string $visibility, array $fields = [], int $stageId = 0): ?array
	{
		if(!Driver::getInstance()->getUserPermissions()->canModifyType($type->getId()))
		{
			$this->addError(new Error(Loc::getMessage('RPA_MODIFY_TYPE_ACCESS_DENIED')));
			return null;
		}

		$data = [
			$visibility => $fields,
		];
		$result = FieldTable::mergeSettings($type->getId(), $stageId, $data, $visibility);
		if(!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return [
			'fields' => FieldTable::getGroupedList($type->getId(), $stageId),
		];
	}
}