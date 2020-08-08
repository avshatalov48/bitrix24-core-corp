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

	abstract public function getTopIDs(array $params);
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

		$query = new Main\Entity\Query($this->getDbEntity());
		if (!$enablePermissionCheck)
		{
			$query->addSelect('ID');
			$query->addOrder('ID', 'DESC');
			$query->setLimit(1);
		}
		else
		{
			$permissionSql = $this->buildPermissionSql([
				'alias' => 'L',
				'permissionType' => 'READ',
				'options' => ['PERMS' => $userPermissions]
			]);

			$query->addSelect('ID');
			if (!is_string($permissionSql))
			{
				return 0;
			}
			elseif ($permissionSql === '')
			{
				$query->addOrder('ID', 'DESC');
				$query->setLimit(1);
			}
			else
			{
				$permissionSql = mb_substr($permissionSql, 7);
				$query->addOrder('ID', 'DESC');
				$query->setLimit(1);
				$query->where('ID', 'in', new \Bitrix\Main\DB\SqlExpression($permissionSql));
			}
		}

		$dbResult = $query->exec();
		$field = $dbResult->fetch();
		return is_array($field) ? (int)$field['ID'] : 0;
	}

	public function getNewIDs($offsetID, $order = 'DESC', $limit = 100, $userID = 0, $enablePermissionCheck = true)
	{
		if($userID <= 0)
		{
			$userID = EntityAuthorization::getCurrentUserID();
		}
		$userPermissions = EntityAuthorization::getUserPermissions($userID);

		if($enablePermissionCheck && EntityAuthorization::isAdmin($userID))
		{
			$enablePermissionCheck = false;
		}

		$query = new Main\Entity\Query($this->getDbEntity());
		$query->addSelect('ID');

		if($offsetID > 0)
		{
			$query->addFilter('>ID', $offsetID);
		}
		$query->addOrder('ID', $order);

		$query->setLimit($limit);

		$results = array();
		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$ID = (int)$fields['ID'];
			if($enablePermissionCheck && !$this->checkReadPermission($ID, $userPermissions))
			{
				continue;
			}

			$results[] = $ID;
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
}