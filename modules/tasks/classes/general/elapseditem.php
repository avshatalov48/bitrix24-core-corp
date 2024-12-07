<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Tasks\Integration\Rest\ElapsedTimeTable;
use Bitrix\Tasks\Util\User;
use \Bitrix\Tasks\Access\ActionDictionary;

/**
 * Class CTaskElapsedItem
 */
final class CTaskElapsedItem extends CTaskSubItemAbstract
{
	const ACTION_ELAPSED_TIME_ADD    = 0x01;
	const ACTION_ELAPSED_TIME_MODIFY = 0x02;
	const ACTION_ELAPSED_TIME_REMOVE = 0x03;

	const SOURCE_UNDEFINED = 0x01;	// unknown source
	const SOURCE_MANUAL    = 0x02;	// item was added by user or was modified by user
	const SOURCE_SYSTEM    = 0x03;	// item was added by system (automatically)


	/**
	 * @param CTaskItemInterface $task
	 * @param array $fields with mandatory elements MINUTES, COMMENT_TEXT
	 * @return CTaskElapsedItem
	 * @throws TasksException
	 */
	public static function add(CTaskItemInterface $task, array $fields)
	{
		if (!$task->checkAccess(ActionDictionary::ACTION_TASK_ELAPSED_TIME))
		{
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		if (!isset($fields['USER_ID']) || (int)$fields['USER_ID'] === 0)
		{
			$fields['USER_ID'] = $task->getExecutiveUserId();
		}
		$fields['TASK_ID'] = $task->getId();
		$fields['COMMENT_TEXT'] = (string)$fields['COMMENT_TEXT'];
		$fields['MINUTES'] = (isset($fields['MINUTES']) ? (int)$fields['MINUTES'] : null);
		$fields['SECONDS'] = (isset($fields['SECONDS']) ? (int)$fields['SECONDS'] : null);

		/** @noinspection PhpDeprecationInspection */
		$id = (new CTaskElapsedTime())->Add($fields);

		// Reset tagged system cache by tag 'tasks_user_' . $userId for each task member
		try
		{
			self::__resetSystemWideTasksCacheByTag($task->getData(false));
		}
		catch (TasksException $e)
		{
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}

		if ($id === false)
		{
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}

		return (new self($task, (int)$id));
	}


	public function delete()
	{
		if ( ! $this->isActionAllowed(self::ACTION_ELAPSED_TIME_REMOVE) )
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);

		/** @noinspection PhpDeprecationInspection */
		$rc = CTaskElapsedTime::delete($this->itemId, array('USER_ID' => $this->executiveUserId));

		// Reset tagged system cache by tag 'tasks_user_' . $userId for each task member
		$this->resetSystemWideTasksCacheByTag();

		// Reset cache
		$this->resetCache();

		if ( ! $rc )
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
	}

	/**
	 * @param array $fields
	 * @throws TasksException
	 */
	public function update(array $fields): void
	{
		if (empty($fields))
		{
			return;
		}

		if (!$this->isActionAllowed(self::ACTION_ELAPSED_TIME_MODIFY))
		{
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		static $allowedFields = ['MINUTES', 'SECONDS', 'COMMENT_TEXT', 'CREATED_DATE'];
		if (count(array_diff(array_keys($fields), $allowedFields)) > 0)
		{
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);
		}

		/** @noinspection PhpDeprecationInspection */
		$rc = (new CTaskElapsedTime())->Update($this->itemId, $fields, ['USER_ID' => $this->executiveUserId]);

		// Reset tagged system cache by tag 'tasks_user_' . $userId for each task member
		$this->resetSystemWideTasksCacheByTag();

		// Reset cache
		$this->resetCache();

		if (!$rc)
		{
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}
	}


	public function isActionAllowed($actionId)
	{
		$isActionAllowed = false;
		CTaskAssert::assertLaxIntegers($actionId);
		$actionId = (int) $actionId;

		$isAdmin = CTasksTools::IsAdmin($this->executiveUserId)
			|| CTasksTools::IsPortalB24Admin($this->executiveUserId);

		if ($actionId === self::ACTION_ELAPSED_TIME_ADD)
			$isActionAllowed = $this->oTaskItem->checkAccess(ActionDictionary::ACTION_TASK_ELAPSED_TIME);
		elseif (($actionId === self::ACTION_ELAPSED_TIME_MODIFY) || ($actionId === self::ACTION_ELAPSED_TIME_REMOVE))
		{
			$arItemData = $this->getData($bEscape = false);
			if ($isAdmin || ($arItemData['USER_ID'] == $this->executiveUserId))
				$isActionAllowed = true;
		}

		return ($isActionAllowed);
	}

	final protected static function fetchListFromDb($taskData, $arOrder = array('ID' => 'ASC'), $arFilter = array())
	{
		CTaskAssert::assertLaxIntegers($taskData['ID']);

		if(!isset($arOrder))
			$arOrder = array('ID' => 'ASC');

		if(!is_array($arFilter))
			$arFilter = array();

		$arFilter['TASK_ID'] = (int) $taskData['ID'];

		$arItemsData = array();
		/** @noinspection PhpDeprecationInspection */
		$rsData = CTaskElapsedTime::GetList(
			$arOrder,
			$arFilter
		);

		if ( ! is_object($rsData) )
			throw new Exception();

		while ($arData = $rsData->fetch())
			$arItemsData[] = $arData;

		return (array($arItemsData, $rsData));
	}

	final protected static function fetchDataFromDb($taskId, $itemId)
	{
		/** @noinspection PhpDeprecationInspection */
		$rsData = CTaskElapsedTime::GetList(
			array(),
			array('ID' => (int) $itemId)
		);

		if (is_object($rsData) && ($arData = $rsData->fetch()))
			return ($arData);
		else
			throw new Exception();
	}

	/**
	 * Only for rest purposes
	 *
	 * @param $order
	 * @param $filter
	 * @param $select
	 * @param $params
	 *
	 * @return array
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	public static function getList($order = [], $filter = [], $select = [], $params = []): array
	{
		$parameters = [
			'order' => (is_array($order) && !empty($order) ? $order : ['ID' => 'ASC']),
			'filter' => (is_array($filter) ? $filter : []),
			'select' => (is_array($select) && !empty($select) ? $select : ['*']),
		];

		if (is_array($params))
		{
			if (is_array($params['NAV_PARAMS']))
			{
				if (isset($params['NAV_PARAMS']['nPageSize']))
				{
					$parameters['limit'] = (int)$params['NAV_PARAMS']['nPageSize'];
				}
				if (isset($params['NAV_PARAMS']['iNumPage']))
				{
					$parameters['offset'] = (int)$params['NAV_PARAMS']['iNumPage'];
				}
			}
			$parameters['count_total'] = (isset($params['count_total']) ? (bool)$params['count_total'] : true);
		}

		$dbResult = ElapsedTimeTable::getList($parameters, [
			'USER_ID' => User::getId(),
			'ROW_LIMIT' => $parameters['limit'] ?? null,
		]);

		$result = $dbResult->fetchAll();
		$count = $dbResult->getCount();

		$navData = [
			'offset' => $parameters['offset'],
			'count' => $count,
		];

		return [$result, $navData];
	}

	private static function __resetSystemWideTasksCacheByTag($arData)
	{
		global $CACHE_MANAGER;

		$arParticipants = array_unique(array_merge(
			array($arData['CREATED_BY'], $arData['RESPONSIBLE_ID']),
			$arData['ACCOMPLICES'],
			$arData['AUDITORS']
		));

		foreach ($arParticipants as $userId)
			$CACHE_MANAGER->ClearByTag('tasks_user_' . $userId);
	}


	private function resetSystemWideTasksCacheByTag()
	{
		try
		{
			$arData = $this->oTaskItem->getData($bEscape = false);
			self::__resetSystemWideTasksCacheByTag($arData);
		}
		catch (TasksException $e)
		{

		}
	}


	public static function runRestMethod($executiveUserId, $methodName, $args, $navigation)
	{
		static $arManifest = null;
		static $arMethodsMetaInfo = null;

		if ($arManifest === null)
		{
			$arManifest = self::getManifest();
			$arMethodsMetaInfo = $arManifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($arMethodsMetaInfo[$methodName]));
		$arMethodMetaInfo = $arMethodsMetaInfo[$methodName];
		$argsParsed = CTaskRestService::_parseRestParams('ctaskelapseditem', $methodName, $args);

		$runAs = $methodName;
		if(isset($arMethodsMetaInfo[$methodName]['runAs']) && (string) $arMethodsMetaInfo[$methodName]['runAs'] != '')
		{
			$runAs = $arMethodsMetaInfo[$methodName]['runAs'];
		}

		$returnValue = null;
		$navData = null;

		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$taskId    = $argsParsed[0];
				$arFields  = $argsParsed[1];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);	// taskId in $argsParsed[0]
				$oItem     = self::add($oTaskItem, $arFields);

				$returnValue = $oItem->getId();
			}
			elseif ($methodName === 'getlist')
			{
				$taskId = $argsParsed[0];
				$order = ($argsParsed[1] ?? []);
				$filter = ($argsParsed[2] ?? []);
				$select = ($argsParsed[3] ?? []);
				$navParams = is_array($argsParsed[4]['NAV_PARAMS'] ?? null) ? $argsParsed[4]['NAV_PARAMS'] : null;

				$byTaskId = false;
				if (count($argsParsed) > 0 && $taskId !== 0)
				{
					$taskItem = CTaskItem::getInstance($taskId, $executiveUserId);
					if ($taskItem->getData())
					{
						$byTaskId = true;
						$filter = array_merge($filter, array('TASK_ID' => $taskId));
					}
				}

				if (isset($navParams))
				{
					if (isset($navParams['nPageSize']))
					{
						$navParams['nPageSize'] = min(CTaskRestService::TASKS_LIMIT_PAGE_SIZE, (int)$navParams['nPageSize']);
					}
					else
					{
						$navParams['nPageSize'] = CTaskRestService::TASKS_LIMIT_PAGE_SIZE;
					}

					if (isset($navParams['iNumPage']))
					{
						$numPage = (int)$navParams['iNumPage'];
						$numPage = ($numPage > 0? ($numPage - 1) * $navParams['nPageSize'] : 0);

						$navParams['iNumPage'] = $numPage;
					}
					else
					{
						$navParams['iNumPage'] = 0;
					}
				}
				else
				{
					$navParams = array(
						'nPageSize' => ($byTaskId? 0 : CTaskRestService::TASKS_LIMIT_PAGE_SIZE),
						'iNumPage' => 0
					);
				}

				$params = array(
					'NAV_PARAMS' => $navParams,
					'count_total' => true
				);
				[$elapsedItems, $navData] = self::getList($order, $filter, $select, $params);

				$returnValue = $elapsedItems;
			}
			else
			{
				$returnValue = call_user_func_array(array('self', $runAs), $argsParsed);
			}
		}
		else
		{
			$taskId     = array_shift($argsParsed);
			$itemId     = array_shift($argsParsed);
			$oTaskItem  = CTaskItem::getInstance($taskId, $executiveUserId);
			$obElapsed  = new self($oTaskItem, $itemId);

			if ($methodName === 'get')
			{
				$returnValue = $obElapsed->getData();
			}
			else
			{
				$returnValue = call_user_func_array(array($obElapsed, $runAs), $argsParsed);
			}
		}

		return array($returnValue, $navData);
	}

	public static function getPublicFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE, AGGREGATE
		return array(
			'ID' => 			array(1, 0, 1, 1, 0, 1),
			'TASK_ID' => 		array(1, 0, 1, 1, 0, 0),
			'USER_ID' => 		array(1, 1, 1, 1, 0, 1),
			'COMMENT_TEXT' => 	array(1, 1, 0, 0, 0, 0),
			'SECONDS' => 		array(1, 1, 1, 0, 0, 1),
			'MINUTES' => 		array(1, 0, 1, 0, 0, 1),
			'SOURCE' => 		array(1, 1, 0, 0, 0, 0),
			'CREATED_DATE' => 	array(1, 1, 1, 1, 1, 0),
			'DATE_START' => 	array(1, 1, 1, 0, 1, 0),
			'DATE_STOP' => 		array(1, 1, 1, 0, 1, 0),
		);
	}

	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 *
	 * @access private
	 */
	public static function getManifest()
	{
		$allKeys = array(
			0 => array(), // readableKeys,
			1 => array(), // writableKeys,
			2 => array(), // sortableKeys,
			3 => array(), // filterableKeys,
			4 => array(), // dateKeys
			5 => array(), // aggregatableKeys
		);

		foreach ($allKeys as $index => $keys)
		{
			foreach ($fieldMap = static::getPublicFieldMap() as $fieldName => $field)
			{
				if ($field[$index] == 1)
				{
					$allKeys[$index][] = $fieldName;
				}
			}
		}

		$readableKeys = $allKeys[0];
		$writableKeys = $allKeys[1];
		$sortableKeys = $allKeys[2];
		$filterableKeys = $allKeys[3];
		$dateKeys = $allKeys[4];
		$aggregatableKeys = $allKeys[5];
		$aggregations = array('MAX', 'MIN', 'COUNT', 'SUM', 'AVG');

		return(array(
			'Manifest version' => '1.2',
			'Manifest change date' => '22 Feb 2018',
			'Warning' => 'Don\'t rely on format of this manifest, it can be changed without any notifications!',
			'REST: shortname alias to class' => 'elapseditem',
			'REST: writable elapseditem data fields' =>  $writableKeys,
			'REST: readable elapseditem data fields' =>  $readableKeys,
			'REST: sortable elapseditem data fields' =>  $sortableKeys,
			'REST: filterable elapseditem data fields' =>  $filterableKeys,
			'REST: date fields' =>  $dateKeys,
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params' => array()
				),
				'getlist' => array(
					'staticMethod' => true,
					'params' => array(
						array(
							'description' => 'taskId',
							'type' => 'integer'
						),
						array(
							'description' => 'order',
							'type' => 'array',
							'allowedKeys' => $sortableKeys
						),
						array(
							'description' => 'filter',
							'type' => 'array',
							'allowedKeys' => $filterableKeys,
							'allowedKeyPrefixes' => array(
								'!', '<=', '<', '>=', '>'
							)
						),
						array(
							'description' => 'select',
							'type' => 'array',
							'allowedValues' => array_merge(array('', '*'), $readableKeys),
							'allowedAggregations' => $aggregations,
							'allowedValuesInAggregation' => $aggregatableKeys
						),
						array(
							'description' => 'params',
							'type' => 'array',
							'allowedKeys' => array('NAV_PARAMS')
						),
					),
					'allowedKeysInReturnValue' => $readableKeys,
					'allowedAggregations' => $aggregations,
					'collectionInReturnValue' => true
				),
				'get' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type' => 'integer'
						),
						array(
							'description' => 'itemId',
							'type' => 'integer'
						)
					),
					'allowedKeysInReturnValue' => $readableKeys
				),
				'add' => array(
					'staticMethod' => true,
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type' => 'integer'
						),
						array(
							'description' => 'fields',
							'type' => 'array',
							'allowedKeys' => $writableKeys
						)
					)
				),
				'update' => array(
					'staticMethod' => false,
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type' => 'integer'
						),
						array(
							'description' => 'itemId',
							'type' => 'integer'
						),
						array(
							'description' => 'fields',
							'type' => 'array',
							'allowedKeys' => $writableKeys
						)
					)
				),
				'delete' => array(
					'staticMethod' => false,
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type' => 'integer'
						),
						array(
							'description' => 'itemId',
							'type' => 'integer'
						)
					)
				),
				'isactionallowed' => array(
					'staticMethod' => false,
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type' => 'integer'
						),
						array(
							'description' => 'itemId',
							'type' => 'integer'
						),
						array(
							'description' => 'actionId',
							'type' => 'integer'
						)
					)
				)
			)
		));
	}
}