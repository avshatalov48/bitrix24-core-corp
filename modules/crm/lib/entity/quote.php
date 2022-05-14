<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main;

use Bitrix\Crm\QuoteTable;

class Quote extends EntityBase
{
	/** @var Quote|null  */
	protected static $instance = null;

	/**
	 * @return Quote
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new Quote();
		}
		return self::$instance;
	}

	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Quote;
	}

	protected function getDbEntity()
	{
		return QuoteTable::getEntity();
	}
	public function getDbTableAlias()
	{
		return \CCrmQuote::TABLE_ALIAS;
	}

	//region Permissions
	protected function buildPermissionSql(array $params)
	{
		return \CCrmQuote::BuildPermSql(
			isset($params['alias']) ? $params['alias'] : 'L',
			isset($params['permissionType']) ? $params['permissionType'] : 'READ',
			isset($params['options']) && is_array($params['options']) ? $params['options'] : array()
		);
	}
	public function checkReadPermission($entityID = 0, $userPermissions = null)
	{
		return \CCrmQuote::CheckReadPermission($entityID, $userPermissions);
	}
	public function checkDeletePermission($entityID = 0, $userPermissions = null)
	{
		return \CCrmQuote::CheckDeletePermission($entityID, $userPermissions);
	}
	//endregion

	protected function getTopIdsInCompatibilityMode(
		int $limit,
		array $order = [],
		array $filter = []
	): array
	{
		$dbResult = \CCrmQuote::GetList(
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
		return \CCrmQuote::GetList(array(), $filter, array());
	}

	public function delete($entityID, array $options = array())
	{
		$entity = new \CCrmQuote(false);
		if(!$entity->Delete($entityID, $options))
		{
			return array('MESSAGE' => $entity->LAST_ERROR);
		}
		return null;
	}

	public static function getResponsibleID($entityID)
	{
		$dbResult = \CCrmQuote::GetList(
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

		$dbResult = QuoteTable::getList(
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

		$dbResult = QuoteTable::getList(
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