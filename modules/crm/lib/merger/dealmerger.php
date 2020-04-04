<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Recovery;
class DealMerger extends EntityMerger
{
	/**
	 * @param int $userID User ID.
	 * @param bool|false $enablePermissionCheck Permission check flag.
	 * @throws Main\ArgumentException
	 */
	public function __construct($userID, $enablePermissionCheck = false)
	{
		parent::__construct(\CCrmOwnerType::Deal, $userID, $enablePermissionCheck);
	}
	/**
	 * Get Enity field infos
	 * @return array
	 */
	protected function getEntityFieldsInfo()
	{
		return \CCrmDeal::GetFieldsInfo();
	}
	/**
	 * Get entity user field infos
	 * @return array
	 */
	protected function getEntityUserFieldsInfo()
	{
		return \CCrmDeal::GetUserFields();
	}
	/**
	 * Get entity responsible ID
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return int
	 * @throws EntityMergerException
	 * @throws Main\NotImplementedException
	 */
	protected function getEntityResponsibleID($entityID, $roleID)
	{
		throw new Main\NotImplementedException('Method getEntityResponsibleID is not implemented');
	}
	/**
	 * Get entity fields
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return array
	 * @throws Main\NotImplementedException
	 */
	protected function getEntityFields($entityID, $roleID)
	{
		throw new Main\NotImplementedException('Method getEntityFields is not implemented');
	}
	/**
	 * Check entity read permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	protected function checkEntityReadPermission($entityID, $userPermissions)
	{
		return \CCrmDeal::CheckReadPermission($entityID, $userPermissions);
	}
	/**
	 * Check entity update permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	protected function checkEntityUpdatePermission($entityID, $userPermissions)
	{
		return \CCrmDeal::CheckUpdatePermission($entityID, $userPermissions);
	}
	/**
	 * Check entity delete permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	protected function checkEntityDeletePermission($entityID, $userPermissions)
	{
		return \CCrmDeal::CheckDeletePermission($entityID, $userPermissions);
	}
	/**
	 * Update entity
	 * @param int $entityID Entity ID.
	 * @param array &$fields Entity fields.
	 * @param int $roleID Entity Role ID (is not required).
	 * @param array $options Options.
	 * @return void
	 * @throws Main\NotImplementedException
	 */
	protected function updateEntity($entityID, array &$fields, $roleID, array $options = array())
	{
		throw new Main\NotImplementedException('Method updateEntity is not implemented');
	}
	/**
	 * Delete entity
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @param array $options Options.
	 * @return void
	 * @throws Main\NotImplementedException
	 */
	protected function deleteEntity($entityID, $roleID, array $options = array())
	{
		throw new Main\NotImplementedException('Method deleteEntity is not implemented');
	}
}