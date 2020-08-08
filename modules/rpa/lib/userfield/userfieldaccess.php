<?php

namespace Bitrix\Rpa\UserField;

use Bitrix\Main\UserField\Internal\UserFieldHelper;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\TypeTable;

class UserFieldAccess extends \Bitrix\Main\UserField\UserFieldAccess
{
	protected function getAvailableEntityIds(): array
	{
		$entityIds = [];

		$userPermissions = Driver::getInstance()->getUserPermissions($this->getUserId());
		$types = TypeTable::getList([
			'filter' => $userPermissions->getFilterForEditableTypes(),
		])->fetchCollection();

		foreach($types as $type)
		{
			$entityIds[] = $type->getItemUserFieldsEntityId();
		}

		return $entityIds;
	}

	public function getUserFieldDetailUrl(array $field): ?\Bitrix\Main\Web\Uri
	{
		$fieldId = (int) ($field['ID'] ?: 0);
		$entityId = $field['ENTITY_ID'] ?: null;
		if($entityId)
		{
			$typeId = $this->getTypeIdByEntityId($entityId);
			if($typeId)
			{
				return Driver::getInstance()->getUrlManager()->getFieldDetailUrl($typeId, $fieldId);
			}
		}

		return null;
	}

	protected function getTypeIdByEntityId(string $entityId): ?int
	{
		$fieldInfo = UserFieldHelper::getInstance()->parseUserFieldEntityId($entityId);
		if($fieldInfo && $fieldInfo[0] === Driver::getInstance()->getFactory())
		{
			return (int) $fieldInfo[1];
		}

		return null;
	}

	public function getRestrictedTypes(): array
	{
		return array_merge(parent::getRestrictedTypes(), [
			'video',
			'vote',
			'url_preview',
			'string_formatted',
			'disk_file',
			'disk_version',
		]);
	}
}