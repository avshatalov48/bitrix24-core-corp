<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main;

use Bitrix\Crm;
use Bitrix\Crm\CompanyTable;

class Company extends EntityBase
{
	/** @var Company|null  */
	protected static $instance = null;

	/**
	 * @return Company
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new Company();
		}
		return self::$instance;
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}

	//region Db
	protected function getDbEntity()
	{
		return CompanyTable::getEntity();
	}
	public function getDbTableAlias()
	{
		return \CCrmCompany::TABLE_ALIAS;
	}
	//endregion

	//region Permissions
	protected function buildPermissionSql(array $params)
	{
		return \CCrmCompany::BuildPermSql(
			isset($params['alias']) ? $params['alias'] : 'L',
			isset($params['permissionType']) ? $params['permissionType'] : 'READ',
			isset($params['options']) && is_array($params['options']) ? $params['options'] : array()
		);
	}
	public function checkReadPermission($entityID = 0, $userPermissions = null)
	{
		return \CCrmCompany::CheckReadPermission($entityID, $userPermissions);
	}
	public function checkDeletePermission($entityID = 0, $userPermissions = null)
	{
		return \CCrmCompany::CheckDeletePermission($entityID, $userPermissions);
	}
	//endregion

	public function create(array $fields)
	{
		$entity = new \CCrmCompany(false);
		return $entity->Add(
			$fields,
			true,
			array('DISABLE_USER_FIELD_CHECK' => true)
		);
	}

	/**
	 * Get Entity By ID.
	 * @param int $entityID Entity ID.
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getByID($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$dbResult = CompanyTable::getList(array('filter' => array('=ID' => $entityID)));

		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}

	public function update($entityID, array $fields)
	{
		$entity = new \CCrmCompany(false);
		return $entity->Update(
			$entityID,
			$fields,
			true,
			array('DISABLE_USER_FIELD_CHECK' => true)
		);
	}

	protected function getTopIdsInCompatibilityMode(
		int $limit,
		array $order = [],
		array $filter = []
	): array
	{
		$dbResult = \CCrmCompany::GetListEx(
			$order,
			$filter,
			false,
			$limit > 0 ? ['nTopCount' => $limit] : false,
			['ID']
		);

		$results = [];
		while ($fields = $dbResult->Fetch())
		{
			$results[] = (int)$fields['ID'];
		}

		return $results;
	}

	public function getCount(array $params)
	{
		$filter = isset($params['filter']) && is_array($params['filter']) ? $params['filter'] : array();
		$enablePermissionCheck = isset($params['enablePermissionCheck']) ? (bool)$params['enablePermissionCheck'] : true;
		if(!$enablePermissionCheck)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}
		return \CCrmCompany::GetListEx(array(), $filter, array());
	}

	public function delete($entityID, array $options = array())
	{
		$entity = new \CCrmCompany(false);
		if(!$entity->Delete($entityID, $options))
		{
			return array('MESSAGE' => $entity->LAST_ERROR);
		}
		return null;
	}

	/**
	 * Perform deferred cleaning of the related entities.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	public function cleanup($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			return;
		}

		$eventEntity = new \CCrmEvent();
		$eventEntity->DeleteByElement(\CCrmOwnerType::CompanyName, $entityID);
	}

	public static function getResponsibleID($entityID)
	{
		$dbResult = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		return is_array($fields) && isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
	}

	/**
	 * Check if Entity exists.
	 * @param int $entityID Entity ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function isExists($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$dbResult = CompanyTable::getList(
			array(
				'select' => array('ID'),
				'filter' => array('=ID' => $entityID)
			)
		);

		return is_array($dbResult->fetch());
	}

	/**
	 * Select only existed entity IDs.
	 * @param array $entityIDs Deal IDs to check.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function selectExisted(array $entityIDs)
	{
		if(empty($entityIDs))
		{
			return array();
		}

		$dbResult = CompanyTable::getList(
			array(
				'filter' => array('@ID' => $entityIDs),
				'select' => array('ID')
			)
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['ID'];
		}
		return $results;
	}
}