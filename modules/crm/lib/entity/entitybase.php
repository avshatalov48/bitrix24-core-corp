<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Security\EntityAuthorization;

abstract class EntityBase
{
	abstract public function getEntityTypeID();
	abstract protected function getDbEntity();
	abstract protected function getDbTableAlias();

	abstract protected function buildPermissionSql(array $params);

	abstract public function checkReadPermission($entityID = 0, $userPermissions = null);
	abstract public function checkDeletePermission($entityID = 0, $userPermissions = null);

	public function create(array $fields)
	{
		throw new Main\NotImplementedException('Method "create" must be overridden');
	}

	/**
	 * Get Entity By ID.
	 * @param int $entityID Entity ID.
	 * @return array|null
	 * @throws Main\NotImplementedException
	 */
	public function getByID($entityID)
	{
		throw new Main\NotImplementedException('Method "getByID" must be overridden');
	}

	public function update($entityID, array $fields)
	{
		throw new Main\NotImplementedException('Method "update" must be overridden');
	}

	/**
	 * Get ids according to $params
	 *
	 * @param array $params
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getTopIDs(array $params)
	{
		$order = isset($params['order']) && is_array($params['order']) ? $params['order'] : ['ID' => 'ASC'];
		$filter = isset($params['filter']) && is_array($params['filter']) ? $params['filter'] : [];
		$limit = isset($params['limit']) ? (int)$params['limit'] : 0;
		$enablePermissionCheck = isset($params['enablePermissionCheck']) ? (bool)$params['enablePermissionCheck'] : true;

		static $cache;
		$cacheKey = md5(serialize($params));
		if (isset($cache[$cacheKey]))
		{
			return $cache[$cacheKey];
		}
		$cache[$cacheKey] = [];

		$permissionSql = '';
		if ($enablePermissionCheck)
		{
			/** @var \CCrmPerms $userPermissions */
			$userPermissions = $params['userPermissions'] ?? \CCrmPerms::GetCurrentUserPermissions();
			$permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::ResolveName($this->getEntityTypeID()),
				'',
				'READ',
				[
					'RAW_QUERY' => true,
					'PERMS' => $userPermissions,
				]
			);
		}
		if ($permissionSql === false) // Access denied
		{
			return $cache[$cacheKey];
		}
		elseif (
			$permissionSql !== '' // need to check permissions
			&& empty($filter)  // and has not any filters
			&& count($order) === 1 // and ordered only by ID
			&& isset($order['ID'])
		)
		{
			$cache[$cacheKey] = $this->getTopIdsFromPermissions($userPermissions, $limit, $order['ID']);
			return $cache[$cacheKey];
		}

		$query = new \Bitrix\Main\Entity\Query($this->getDbEntity());
		$query->addSelect('ID');
		$query->setOrder($order);
		$query->setFilter($filter);
		$query->setLimit($limit);

		if ($permissionSql !== '')
		{
			$query->addFilter('@ID', new Main\DB\SqlExpression($permissionSql));
		}

		$dbResult = $query->exec();
		while ($fields = $dbResult->fetch())
		{
			$cache[$cacheKey][] = (int)$fields['ID'];
		}
		return $cache[$cacheKey];
	}

	abstract public function getCount(array $params);
	abstract public function delete($entityID, array $options = array());
	public function cleanup($entityID)
	{
		throw new Main\NotImplementedException('Method cleanup must be overridden');
	}

	/**
	 * Check if Entity exists.
	 * @param int $entityID Entity ID.
	 * @return bool
	 * @throws Main\NotImplementedException
	 */
	public function isExists($entityID)
	{
		return is_array($this->getByID($entityID));
	}

	/**
	 * Get id of last created item
	 *
	 * @param int $userID
	 * @param bool $enablePermissionCheck
	 * @return int|mixed
	 */
	public function getLastID($userID = 0, $enablePermissionCheck = true)
	{
		if ($userID <= 0)
		{
			$userID = EntityAuthorization::getCurrentUserID();
		}
		$userPermissions = EntityAuthorization::getUserPermissions($userID);

		if ($enablePermissionCheck && EntityAuthorization::isAdmin($userID))
		{
			$enablePermissionCheck = false;
		}
		$topIds = $this->getTopIDs([
			'enablePermissionCheck' => $enablePermissionCheck,
			'userPermissions' => $userPermissions,
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		]);

		return (is_array($topIds) && isset($topIds[0])) ? $topIds[0] : 0;
	}

	/**
	 * Get ids of last created items
	 *
	 * @param $offsetID
	 * @param string $order
	 * @param int $limit
	 * @param int $userID
	 * @param bool $enablePermissionCheck
	 * @return array
	 */
	public function getNewIDs($offsetID, $order = 'DESC', $limit = 100, $userID = 0, $enablePermissionCheck = true)
	{
		if ($userID <= 0)
		{
			$userID = EntityAuthorization::getCurrentUserID();
		}
		$userPermissions = EntityAuthorization::getUserPermissions($userID);

		if ($enablePermissionCheck && EntityAuthorization::isAdmin($userID))
		{
			$enablePermissionCheck = false;
		}
		$order = mb_strtoupper($order) !== 'DESC' ? 'ASC' : 'DESC';

		$filter = [];
		if ($offsetID > 0)
		{
			$filter = ['>ID' => $offsetID];
		}
		$topIds = $this->getTopIDs([
			'enablePermissionCheck' => false,
			'order' => ['ID' => $order],
			'filter' => $filter,
			'limit' => $limit,
		]);

		$results = [];
		foreach ($topIds as $id)
		{
			if ($enablePermissionCheck && !$this->checkReadPermission($id, $userPermissions))
			{
				continue;
			}

			$results[] = $id;
		}

		return $results;
	}

	public function getEntityMultifields($entityID, array $options = null)
	{
		if($entityID <= 0)
		{
			return array();
		}

		$dbResult = \CCrmFieldMulti::GetListEx(
			array('ID' => 'asc'),
			array(
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($this->getEntityTypeID()),
				'=ELEMENT_ID' => $entityID
			)
		);

		if($options === null)
		{
			$options = array();
		}

		$skipEmpty = isset($options['skipEmpty']) && $options['skipEmpty'];

		$entityMultiFields = array();
		while($fields = $dbResult->Fetch())
		{
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			if ($skipEmpty && $value === '')
			{
				continue;
			}

			$typeID = $fields['TYPE_ID'];
			if(!isset($this->entityMutliFields[$typeID]))
			{
				$entityMultiFields[$typeID] = array();
			}

			$entityMultiFields[$typeID][] = array(
				'ID' => $fields['ID'],
				'VALUE' => $value,
				'VALUE_TYPE' => isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : '',
				'COMPLEX_ID' => isset($fields['COMPLEX_ID']) ? $fields['COMPLEX_ID'] : ''
			);
		}

		return $entityMultiFields;
	}
	public function setEntityMultifields($entityID, array $data)
	{
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'entityID');
		}

		$multifieldEntity = new \CCrmFieldMulti();
		$multifieldEntity->SetFields(\CCrmOwnerType::ResolveName($this->getEntityTypeID()), $entityID, $data);
	}

	public function prepareFilter(array &$filterFields, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		if(isset($filterFields['ACTIVITY_COUNTER']))
		{
			if(is_array($filterFields['ACTIVITY_COUNTER']))
			{
				$counterTypeID = Crm\Counter\EntityCounterType::joinType(
					array_filter($filterFields['ACTIVITY_COUNTER'], 'is_numeric')
				);
			}
			else
			{
				$counterTypeID = (int)$filterFields['ACTIVITY_COUNTER'];
			}
			unset($filterFields['ACTIVITY_COUNTER']);

			$counter = null;
			if($counterTypeID > 0)
			{
				$counterUserIDs = array();
				if(isset($filterFields['ASSIGNED_BY_ID']))
				{
					if(is_array($filterFields['ASSIGNED_BY_ID']))
					{
						$counterUserIDs = array_filter($filterFields['ASSIGNED_BY_ID'], 'is_numeric');
					}
					elseif($filterFields['ASSIGNED_BY_ID'] > 0)
					{
						$counterUserIDs[] = $filterFields['ASSIGNED_BY_ID'];
					}
				}

				try
				{
					$counter = Crm\Counter\EntityCounterFactory::create(
						$this->getEntityTypeID(),
						$counterTypeID,
						0,
						Crm\Counter\EntityCounter::internalizeExtras($params)
					);

					$filterFields += $counter->prepareEntityListFilter(
						array(
							'MASTER_ALIAS' => $this->getDbTableAlias(),
							'MASTER_IDENTITY' => 'ID',
							'USER_IDS' => $counterUserIDs
						)
					);
					unset($filterFields['ASSIGNED_BY_ID']);
				}
				catch(Main\NotSupportedException $e)
				{
				}
				catch(Main\ArgumentException $e)
				{
				}
			}
		}
	}

	private function getTopIdsFromPermissions(\CCrmPerms $userPermissions, $limit, $sortOrder = 'asc'): array
	{
		$result = [];

		$permissionSql = \CCrmPerms::BuildSql(
			\CCrmOwnerType::ResolveName($this->getEntityTypeID()),
			'',
			'READ',
			[
				'RAW_QUERY' => [
					'TOP' => $limit,
					'SORT_TYPE' => $sortOrder
				],
				'PERMS' => $userPermissions,
			]
		);
		if ($permissionSql === false || $permissionSql === '')
		{
			throw new \Bitrix\Main\NotSupportedException('Unable to get top ids from permissions');
		}
		$dbResult = \Bitrix\Main\Application::getConnection()->query($permissionSql);
		while ($fields = $dbResult->fetch())
		{
			$result[] = (int)$fields['ENTITY_ID'];
		}

		return $result;
	}
}