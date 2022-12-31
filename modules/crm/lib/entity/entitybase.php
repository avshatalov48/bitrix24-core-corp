<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Filter\Factory;
use Bitrix\Crm\Security\QueryBuilder\Options;
use Bitrix\Crm\Security\QueryBuilder\Result;
use Bitrix\Main;
use Bitrix\Crm\Security\EntityAuthorization;

abstract class EntityBase
{
	abstract public function getEntityTypeID();
	abstract protected function getDbEntity();
	abstract public function getDbTableAlias();

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

		if (
			!$this->areFieldsCompatibleWithOrm(array_keys($order))
			|| !$this->areFieldsCompatibleWithOrm(array_keys($filter))
		)
		{
			if (!$enablePermissionCheck)
			{
				$filter['CHECK_PERMISSIONS'] = 'N';
			}

			return $this->getTopIdsInCompatibilityMode($limit, $order, $filter);
		}

		static $cache;
		$cacheKey = md5(serialize($params) . $this->getEntityTypeID());
		if (isset($cache[$cacheKey]))
		{
			return $cache[$cacheKey];
		}
		$cache[$cacheKey] = [];

		$categories = $filter['=CATEGORY_ID'] ?? $filter['@CATEGORY_ID'] ?? $filter['CATEGORY_ID'] ?? null;
		if (!is_null($categories))
		{
			$categories = array_unique(array_map('intval',  (array)$categories));
			if (empty($categories))
			{
				$categories = null;
			}
		}

		if ($enablePermissionCheck)
		{
			$userPermissions = $params['userPermissions'] ?? \CCrmPerms::GetCurrentUserPermissions();
			$builderOptions = (new Options())
				->setNeedReturnRawQuery(true)
			;
			if ($categories)
			{
				$builderOptions->setSkipCheckOtherEntityTypes(true);
			}
			$builderResult = $this->buildPermissionSqlForCategories(
				$userPermissions->GetUserID(),
				$builderOptions,
				$categories ?? [0]
			);
		}

		$isOnlyCategoryFilter = (count($filter) === 1) && is_array($categories);

		if ($enablePermissionCheck && !$builderResult->hasAccess()) // Access denied
		{
			return $cache[$cacheKey];
		}
		elseif (
			$enablePermissionCheck
			&& $builderResult->hasRestrictions() // need to check permissions
			&& (empty($filter) || $isOnlyCategoryFilter)  // filter is suitable
			&& count($order) === 1 // and ordered only by ID
			&& isset($order['ID'])
		)
		{
			$cache[$cacheKey] = $this->getTopIdsFromPermissions(
				$userPermissions,
				$limit,
				$order['ID'],
				$categories ?? [0]  // for backward compatibility, use categoryId = 0 if not defined
			);

			return $cache[$cacheKey];
		}

		$query = new \Bitrix\Main\Entity\Query($this->getDbEntity());
		$query->addSelect('ID');
		$query->setOrder($order);
		$query->setFilter($filter);
		$query->setLimit($limit);

		if ($enablePermissionCheck && $builderResult->hasRestrictions())
		{
			$query->addFilter('@ID', $builderResult->getSqlExpression());
		}

		$dbResult = $query->exec();
		while ($fields = $dbResult->fetch())
		{
			$cache[$cacheKey][] = (int)$fields['ID'];
		}
		return $cache[$cacheKey];
	}

	protected function areFieldsCompatibleWithOrm(array $fields): bool
	{
		$notCompatibleFields = [
			'SEARCH_CONTENT',
		];

		$dbEntity = $this->getDbEntity();
		$sqlWhere = new \CSQLWhere();
		try
		{
			foreach ($fields as $field)
			{
				$field = $sqlWhere->makeOperation($field)['FIELD']; // remove filter operations like >, <, % etc
				if (in_array($field, $notCompatibleFields))
				{
					return false;
				}
				\Bitrix\Main\ORM\Query\Chain::getChainByDefinition($dbEntity, $field);
			}
		}
		catch (\Bitrix\Main\SystemException $e) // Unknown field definition
		{
			return false;
		}

		return true;
	}

	protected abstract function getTopIdsInCompatibilityMode(
		int $limit,
		array $order = [],
		array $filter = []
	): array;

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
	public function getNewIDs($offsetID, $order = 'DESC', $limit = 100, $userID = 0, $enablePermissionCheck = true, ?int $categoryId = null)
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
		if (!is_null($categoryId))
		{
			$filter['@CATEGORY_ID'] = $categoryId;
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

	/**
	 * @deprecated Use \Bitrix\Main\Filter\Filter::getValue
	 * @see \Bitrix\Main\Filter\Filter::getValue
	 */
	public function prepareFilter(array &$filterFields, array $params = null)
	{
		$entityTypeId = (int)$this->getEntityTypeID();

		$filterFields = Factory::createEntityFilter(
			Factory::createEntitySettings(
				$entityTypeId,
				'',  // grid id is not valuable here
				Factory::convertSettingsParams($entityTypeId, $params)
			)
		)->getValue($filterFields);
	}

	private function getTopIdsFromPermissions(\CCrmPerms $userPermissions, $limit, $sortOrder = 'asc', array $categories = [0]): array
	{
		$builderOptions = (new Options())
			->setRawQueryOrder((string)$sortOrder)
			->setRawQueryLimit((int)$limit)
			->setNeedReturnRawQuery(true)
			->setUseRawQueryDistinct($limit > 1)
		;
		$builderResult = $this->buildPermissionSqlForCategories($userPermissions->GetUserID(), $builderOptions, $categories);

		if (!$builderResult->hasRestrictions())
		{
			throw new \Bitrix\Main\NotSupportedException('Unable to get top ids from permissions');
		}

		$result = [];
		$permissionRecords = \Bitrix\Main\Application::getConnection()->query($builderResult->getSql());
		while ($fields = $permissionRecords->fetch())
		{
			$result[] = (int)$fields['ENTITY_ID'];
		}

		return $result;
	}

	private function buildPermissionSqlForCategories(int $userId, Options $builderOptions, ?array $categoryIds = null): Result
	{
		$permEntityTypeHelper = new PermissionEntityTypeHelper($this->getEntityTypeID());


		if (is_null($categoryIds))
		{
			$permEntities = $permEntityTypeHelper->getAllPermissionEntityTypesForEntity();
		}
		else
		{
			$permEntities = [];
			foreach ($categoryIds as $categoryId)
			{
				$permEntities[] = $permEntityTypeHelper->getPermissionEntityTypeForCategory((int)$categoryId);
			}
		}

		$queryBuilder = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->createListQueryBuilder($permEntities, $builderOptions)
		;

		return $queryBuilder->build();
	}
}
