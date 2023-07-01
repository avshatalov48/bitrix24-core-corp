<?php
namespace Bitrix\Crm\Integration\Sign\Access\Service;

use Bitrix\Crm\Security\Role\Model\RoleRelationTable;
use Bitrix\Main\Access\Exception\RoleRelationSaveException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class RoleRelationService
{
	
	public function __construct()
	{
		Loader::includeModule('crm');
	}
	
	/**
	 * @inheritDoc
	 * @throws RoleRelationSaveException
	 */
	public function saveRoleRelation(array $settings): void
	{
		$currentRelations = (new \CCrmRole())->GetRelation();
		$relation = [];
		$roles = [];
		foreach ($settings as $setting)
		{
			$roleId = $setting['id'];
			$accessCodes = $setting['accessCodes'] ?? [];
			
			if ($roleId === false || !$this->validateRoleId($roleId))
			{
				continue;
			}
			foreach ($accessCodes as $code => $value)
			{
				$relation[$code][] = $roleId;
			}
			$roles[] = $roleId;
		}
		
		while ($currentRelation = $currentRelations->Fetch())
		{
			if (!in_array($currentRelation['ROLE_ID'],  $roles))
			{
				$relation[$currentRelation['RELATION']][] = $currentRelation['ROLE_ID'];
			}
		}
		
		(new \CCrmRole())->SetRelation($relation, false);
	}
	
	/**
	 * @param array $parameters
	 * @return array|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getRelationList(array $parameters = []): ?array
	{
		return RoleRelationTable::getList($parameters)->fetchAll();
	}
	
	public function validateRoleId(int $roleId): bool
	{
		$role = \CCrmRole::getList(['ID' => 'DESC'], ['=ID' => $roleId])->Fetch();
		
		if ($role['GROUP_CODE'] !== RolePermissionService::ROLE_GROUP_CODE)
		{
			return false;
		}
		return true;
	}
}