<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\CallList\Internals\CallListItemTable;
use Bitrix\Crm\CallList\Internals\CallListTable;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Rest\RestException;


class CallList extends \IRestService
{
	public const ERROR_ARGUMENT = 'ERROR_ARGUMENT';
	public const ACCESS_ERROR = 'ACCESS_ERROR';
	public const LIST_ID_ERROR = 'LIST_ID_ERROR';
	public const STATUS_ERROR = 'STATUS_ERROR';
	public const ENTITY_ERROR = 'ENTITY_ERROR';
	public const ENTITIES_ERROR = 'ENTITIES_ERROR';
	public const ENTITY_TYPE_ERROR = 'ENTITY_TYPE_ERROR';
	public const WEBFORM_ERROR = 'WEBFORM_ERROR';

	private static $methods = [
		'crm.calllist.list' => [self::class, 'getsCallList'],
		'crm.calllist.get' => [self::class, 'getCallList'],
		'crm.calllist.add' => [self::class, 'addCallList'],
		'crm.calllist.update' => [self::class, 'updateCallList'],
		'crm.calllist.items.get' => [self::class, 'getItems'],
		'crm.calllist.statuslist' => [self::class, 'getStatusList'],
	];

	/**
	 * Registering new rest methods
	 *
	 * @param $bindings
	 */
	public static function register(&$bindings): void
	{
		$bindings = array_merge($bindings, self::$methods);
	}

	/**
	 * Gets a list of all possible statuses of participants in the call list
	 *
	 * @param array $query
	 * @param int $nav
	 * @param \CRestServer $server
	 * @return array
	 */
	public static function getStatusList(array $query, int $nav, \CRestServer $server): array
	{
		$rows = \Bitrix\Crm\CallList\CallList::getStatusList();
		$result = [];

		foreach ($rows as $row)
		{
			$result[] = [
				'ID' => (int)$row['ID'],
				'NAME' => $row['NAME'],
				'SORT' => (int)$row['SORT'],
				'STATUS_ID' => $row['STATUS_ID']
			];
		}
		return $result;
	}

	/**
	 * Gets information about call lists without its participants
	 * with the ability to select fields, sort, filter, and pagination
	 *
	 * @param array{select: array, filter: array, order: array} $query
	 * @param int $nav
	 * @param \CRestServer $server
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getsCallList(array $query, int $nav, \CRestServer $server): array
	{
		$query = array_change_key_case($query, CASE_UPPER);
		$callLists = CallListTable::query();
		$callLists->setSelect($query['SELECT'] ?? ['*']);
		$callLists->setFilter($query['FILTER'] ?? []);
		$callLists->setOrder($query['ORDER'] ?? []);

		if ($nav !== -1)
		{
			$navigation = self::getNavData($nav, true);
			$navigation['offset'] = $navigation['offset'] ?? 0;
			$callLists->setLimit($navigation['limit']);
			$callLists->setOffset($navigation['offset']);
		}

		$result = [];
		foreach ($callLists->exec() as $callList)
		{
			if (isset($callList['DATE_CREATE']))
			{
				$callList['DATE_CREATE'] = $callList['DATE_CREATE']->format('Y-m-d H:i:s');
			}

			unset($callList['GRID_ID'], $callList['FILTER_PARAMS'], $callList['FILTERED']);
			if (isset($callList['UALIAS_0']))
			{
				unset($callList['UALIAS_0']);
			}

			$result[] = $callList;
		}

		return $result;
	}

	/**
	 * Gets information about the call list without its participants
	 *
	 * @param $query array{id: int}
	 * @param $nav
	 * @param \CRestServer $server
	 * @return array
	 * @throws RestException
	 * @throws ArgumentException
	 * @throws ObjectException
	 * @throws SystemException
	 */
	public static function getCallList(array $query, $nav, \CRestServer $server): array
	{
		$query = array_change_key_case($query, CASE_UPPER);
		self::checkRequiredParams(['ID'], $query, $server);

		$callListId = (int)$query['ID'];
		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId);
		}
		catch (ArgumentException | SystemException $exception)
		{
			throw new RestException(
				'Incorrect list id',
				self::LIST_ID_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}
		$result = $callList->toArray();

		$result['DATE_CREATE'] = $result['DATE_CREATE']->format('Y-m-d H:i:s');
		unset($result['ITEMS'], $result['GRID_ID'], $result['FILTER_PARAMS'], $result['FILTERED']);

		return $result;
	}

	/**
	 * Gets a list of participants in the call list with the ability to filter by participant status and pagination
	 *
	 * @param array{LIST_ID: int, FILTER: array} $query
	 * @param int $nav
	 * @param \CRestServer $server
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws RestException
	 * @throws SystemException
	 */
	public static function getItems(array $query, int $nav, \CRestServer $server): array
	{
		$query = array_change_key_case($query, CASE_UPPER);

		self::checkRequiredParams(['LIST_ID'], $query, $server);

		$callListId = (int)$query['LIST_ID'];
		if ($callListId <= 0)
		{
			throw new RestException('List id should be positive', self::LIST_ID_ERROR, $server::STATUS_WRONG_REQUEST);
		}

		$row = CallListTable::getById($callListId)->fetch();
		if (!$row)
		{
			throw new RestException('Call list is not found', self::LIST_ID_ERROR, $server::STATUS_WRONG_REQUEST);
		}

		$navigation = self::getNavData($nav,true);

		$rowItems =
			CallListItemTable::query()
				->addSelect('ELEMENT_ID', 'ID')
				->addSelect('ENTITY_TYPE_ID')
				->addSelect('STATUS_ID', 'STATUS')
				->addSelect('CALL_ID')
				->where('LIST_ID', $callListId)
				->setLimit($navigation['limit'])
				->addOrder('RANK','ASC')
		;

		if (isset($navigation['offset']))
		{
			$rowItems->setOffset($navigation['offset']);
		}

		//get by status
		if (isset($query['FILTER']['STATUS']))
		{
			$statusList = \Bitrix\Crm\CallList\CallList::getStatusList();
			$statusList = array_column($statusList, 'STATUS_ID');

			if (!in_array($query['FILTER']['STATUS'], $statusList, true))
			{
				throw new RestException(
					'Incorrect status',
					self::STATUS_ERROR,
					$server::STATUS_WRONG_REQUEST
				);
			}

			$rowItems->where('STATUS_ID', $query['FILTER']['STATUS']);
		}
		$result = [];
		$userPermissions = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions();

		foreach ($rowItems->exec() as $rowItem)
		{
			$canReadItem = $userPermissions->checkReadPermissions((int)$rowItem['ENTITY_TYPE_ID'], (int)$rowItem['ID']);
			if (!$canReadItem)
			{
				continue;
			}
			$result[] = [
				'ID' => (int)$rowItem['ID'],
				'STATUS' => $rowItem['STATUS'],
				'ENTITY_TYPE' => (int)$rowItem['ENTITY_TYPE_ID']
			];
		}
		if (empty($result))
		{
			throw new RestException(
				'You do not have access to the participants of this call list',
				self::ACCESS_ERROR,
				$server::STATUS_FORBIDDEN
			);
		}

		return $result;
	}

	/**
	 * Add new call list with the permission check for each of the participants
	 *
	 * @param array{ENTITY_TYPE: string, ENTITIES: array, WEBFORM_ID: int} $query
	 * @param int $nav
	 * @param \CRestServer $server
	 * @return int
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws RestException
	 * @throws SystemException
	 */
	public static function addCallList(array $query, int $nav, \CRestServer $server): int
	{
		$query = array_change_key_case($query, CASE_UPPER);

		self::checkRequiredParams(['ENTITY_TYPE', 'ENTITIES'], $query, $server);

		if(!is_array($query['ENTITIES']))
		{
			throw new RestException(
				'Entities is not array',
				self::ENTITIES_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}

		$entityTypeId = \CCrmOwnerType::resolveId($query['ENTITY_TYPE']);
		if (!in_array($entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company], true))
		{
			throw new RestException(
				'Incorrect entity type',
				self::ENTITY_TYPE_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}

		if (!self::isEntitiesExist($query['ENTITY_TYPE'], $query['ENTITIES'], $server))
		{
			throw new RestException('Incorrect entities id', self::ENTITY_ERROR, $server::STATUS_WRONG_REQUEST);
		}

		$query['ENTITIES'] = self::filterAllowedItems($query['ENTITIES'], $entityTypeId);
		if (empty($query['ENTITIES']))
		{
			throw new RestException(
				'You don\'t have access to these entities',
				self::ACCESS_ERROR,
				$server::STATUS_FORBIDDEN
			);
		}

		$callList = \Bitrix\Crm\CallList\CallList::createWithEntities($query['ENTITY_TYPE'], $query['ENTITIES']);

		if (isset($query['WEBFORM_ID']))
		{
			if (!self::isWebFormExist($query['WEBFORM_ID']))
			{
				throw new RestException('Incorrect webform id', self::WEBFORM_ERROR, $server::STATUS_WRONG_REQUEST);
			}
			$callList->setWebformId($query['WEBFORM_ID']);
		}

		$callList->persist()->createActivity();

		return $callList->getId();
	}

	/**
	 * Adds missing participants to the call list and deletes those who aren't in the request
	 * with the permission check for each of the participants. Update web form if exist in request
	 *
	 * @param array{LIST_ID: int, ENTITY_TYPE: string, ENTITIES: array, WEBFORM_ID: int} $query
	 * @param int $nav
	 * @param \CRestServer $server
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws RestException
	 * @throws SystemException
	 */
	public static function updateCallList(array $query, int $nav, \CRestServer $server): bool
	{
		$query = array_change_key_case($query, CASE_UPPER);

		self::checkRequiredParams(['LIST_ID', 'ENTITY_TYPE', 'ENTITIES'], $query, $server);

		$entityTypeId = \CCrmOwnerType::resolveId($query['ENTITY_TYPE']);
		if (!in_array($entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company], true))
		{
			throw new RestException(
				'EntityType is incorrect',
				self::ENTITY_TYPE_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}

		if (!is_array($query['ENTITIES']))
		{
			throw new RestException(
				'Entities is not array',
				self::ENTITIES_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}
		if (!self::isEntitiesExist($query['ENTITY_TYPE'], $query['ENTITIES'], $server))
		{
			throw new RestException(
				'Incorrect entities id',
				self::ENTITIES_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}

		$userPermissions = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions();

		if (!$userPermissions->isAdmin())
		{
			$canReadType = $userPermissions->canReadType($entityTypeId);
			if (!$canReadType)
			{
				throw new RestException(
					'Access Denied',
					self::ACCESS_ERROR,
					$server::STATUS_FORBIDDEN
				);
			}
		}
		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId((int)$query['LIST_ID'], true);
		}
		catch (SystemException $exception)
		{
			throw new RestException(
				'Incorrect list id or access denied',
				self::LIST_ID_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}
		if ((int)$callList->getEntityTypeId() !== (int)$entityTypeId)
		{
			throw new RestException(
				'Discrepancy between the type of call participants and incoming type',
				self::ENTITY_TYPE_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}

		$currentEntitiesId = [];
		foreach ($callList->getItems() as $item)
		{
			$currentEntitiesId[] = (int)$item->getElementId();
		}

		$intersect = array_intersect($currentEntitiesId, $query['ENTITIES']);
		$addEntitiesId = array_diff($query['ENTITIES'], $intersect);
		$deleteEntities = array_diff($currentEntitiesId, $intersect);

		$addEntitiesId = self::filterAllowedItems($addEntitiesId, $entityTypeId);

		$callList->addEntities($addEntitiesId);

		if (isset($query['WEBFORM_ID']))
		{
			if (!self::isWebFormExist($query['WEBFORM_ID']))
			{
				throw new RestException(
					'Incorrect webform id',
					self::WEBFORM_ERROR,
					$server::STATUS_WRONG_REQUEST
				);
			}
			$callList->setWebformId($query['WEBFORM_ID']);
		}
		else
		{
			$callList->setWebformId(null);
		}

		$callList->persist();
		$callList->deleteItems($deleteEntities);

		return true;
	}

	/**
	 * @param array $requiredParams
	 * @param array $query
	 * @param \CRestServer $server
	 * @throws RestException
	 */
	private static function checkRequiredParams(array $requiredParams, array $query, \CRestServer $server): void
	{
		foreach ($requiredParams as $requiredParam)
		{
			if (!array_key_exists($requiredParam, $query))
			{
				throw new RestException(
					$requiredParam . ' is not found',
					self::ERROR_ARGUMENT,
					$server::STATUS_WRONG_REQUEST
				);
			}
		}
	}

	/**
	 * @param string $type
	 * @param array $entitiesId
	 * @param \CRestServer $server
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws RestException
	 * @throws SystemException
	 */
	private static function isEntitiesExist(string $type, array $entitiesId, \CRestServer $server): bool
	{
		if (empty($entitiesId))
		{
			throw new RestException(
				'Incorrect entities id',
				self::ENTITIES_ERROR,
				$server::STATUS_WRONG_REQUEST
			);
		}

		if ($type === \CCrmOwnerType::ContactName)
		{
			$query =
				ContactTable::query()
					->addSelect('ID')
					->whereIn('ID', $entitiesId)
			;
		}
		elseif ($type === \CCrmOwnerType::CompanyName)
		{
			$query =
				CompanyTable::query()
					->addSelect('ID')
					->whereIn('ID', $entitiesId)
			;
		}

		$ids = [];
		foreach ($query->exec() as $entity)
		{
			$ids[] = (int)$entity['ID'];
		}

		return count($ids) === count($entitiesId);
	}

	/**
	 * @param int $webFormId
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function isWebFormExist(int $webFormId): bool
	{
		$id = FormTable::getById($webFormId)->fetch();

		return $id !== false;
	}

	/**
	 * @param array $entityIds
	 * @param int $entityTypeId
	 * @return array
	 */
	private static function filterAllowedItems(array $entityIds, int $entityTypeId): array
	{
		$userPermissions = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions();
		foreach ($entityIds as $key => $entity)
		{
			$canReadItem = $userPermissions->checkReadPermissions($entityTypeId, (int)$entity);
			if (!$canReadItem)
			{
				unset($entityIds[$key]);
			}
		}

		return $entityIds;
	}

}