<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Crm;
use Bitrix\Crm\Cleaning\Cleaner;
use Bitrix\Main;

class Deal extends EntityBase
{
	/** @var Deal|null  */
	protected static $instance = null;

	/**
	 * @return Deal
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new Deal();
		}
		return self::$instance;
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
	}

	//region Db
	protected function getDbEntity()
	{
		return Crm\DealTable::getEntity();
	}
	public function getDbTableAlias()
	{
		return \CCrmDeal::TABLE_ALIAS;
	}
	//endregion

	//region Permissions
	public function checkReadPermission($entityID = 0, $userPermissions = null)
	{
		return \CCrmDeal::CheckReadPermission($entityID, $userPermissions);
	}
	public function checkDeletePermission($entityID = 0, $userPermissions = null)
	{
		return \CCrmDeal::CheckDeletePermission($entityID, $userPermissions);
	}
	protected function buildPermissionSql(array $params)
	{
		return \CCrmDeal::BuildPermSql(
			isset($params['alias']) ? $params['alias'] : 'L',
			isset($params['permissionType']) ? $params['permissionType'] : 'READ',
			isset($params['options']) && is_array($params['options']) ? $params['options'] : array()
		);
	}
	//endregion

	protected function getTopIdsInCompatibilityMode(
		int $limit,
		array $order = [],
		array $filter = []
	): array
	{
		$dbResult = \CCrmDeal::GetListEx(
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
		return \CCrmDeal::GetListEx(array(), $filter, array());
	}

	public function delete($entityID, array $options = array())
	{
		$entity = new \CCrmDeal(false);
		if(!$entity->Delete($entityID, $options))
		{
			return array('MESSAGE' => $entity->LAST_ERROR);
		}
		return null;
	}

	/**
	 * @deprecated Use Cleaner instead
	 * @see \Bitrix\Crm\Cleaning\CleaningManager::getCleaner()
	 *
	 * Perform deferred cleaning of the related entities.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	public function cleanup($entityID)
	{
		$entityID = (int)$entityID;

		if ($entityID <= 0)
		{
			return;
		}

		$cleaner = Crm\Cleaning\CleaningManager::getCleaner(\CCrmOwnerType::Deal, $entityID);

		// method 'cleanup' is called from agent only
		$cleaner->getOptions()->setEnvironment(Cleaner\Options::ENVIRONMENT_AGENT);

		$cleaner->cleanup();
	}

	public static function getResponsibleID($entityID)
	{
		$dbResult = \CCrmDeal::GetListEx(
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

		$dbResult = Crm\DealTable::getList(
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

		$dbResult = Crm\DealTable::getList(
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
