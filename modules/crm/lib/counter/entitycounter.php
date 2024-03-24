<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParamsBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilder;
use Bitrix\Crm\Counter\CounterQueryBuilder\CounterQueryBuilderFactory;
use Bitrix\Crm\Counter\CounterQueryBuilder\FactoryConfig;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\Query;

class EntityCounter extends CounterBase
{
	private const COUNTER_LIMIT = 100;
	/** @var int */
	protected $typeID = EntityCounterType::UNDEFINED;
	/** @var int */
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	/** @var array|null  */
	protected $extras = null;
	/** @var int|null */
	protected $currentValue = null;
	/** @var string  */
	protected $code = '';
	/** @var int|null  */

	/** @var bool */
	protected $sendPullEvent = false;

	private CalculatedTime $calculatedTime;

	private CounterSettings $counterSettings;

	/**
	 * @param int $entityTypeID Entity Type ID (see \CCrmOwnerType).
	 * @param int $typeID Type ID (see EntityCounterType).
	 * @param int $userID User ID.
	 * @param array|null $extras Additional Parameters.
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function __construct($entityTypeID, $typeID, $userID = 0, array $extras = null)
	{
		$this->counterSettings = CounterSettings::getInstance();

		$this->setEntityTypeID($entityTypeID);
		$this->setTypeID($typeID);
		$this->setUserID($userID > 0 ? $userID : \CCrmSecurityHelper::GetCurrentUserID());
		$this->setExtras($extras !== null ? $extras : array());
		$this->code = $this->resolveCode();

		$this->calculatedTime = new CalculatedTime($this->userID, $this->code);
	}
	/**
	 * @return int
	 */
	public function getTypeID()
	{
		return $this->typeID;
	}
	/**
	 * @return int
	 */
	public function getTypeName()
	{
		return EntityCounterType::resolveName($this->typeID);
	}
	/**
	 * @param int $typeID Type ID (see EntityCounterType).
	 * @return void
	 */
	protected function setTypeID($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		$this->typeID = $typeID;
	}
	/**
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	/**
	 * @param int $entityTypeID Entity Type ID (see \CCrmOwnerType).
	 * @return void
	 */
	protected function setEntityTypeID($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		$this->entityTypeID = $entityTypeID;
	}

	public function getExtras()
	{
		return $this->extras;
	}

	protected function setExtras(array $extras)
	{
		$this->extras = $extras;
	}
	public function getExtraParam($name, $default = null)
	{
		return isset($this->extras[$name]) ? $this->extras[$name] : $default;
	}
	/**
	 * @param string $name Extra Parameter Name.
	 * @param int $default Default Value.
	 * @return int
	 */
	public function getIntegerExtraParam($name, $default = 0)
	{
		return isset($this->extras[$name]) ? (int)$this->extras[$name] : $default;
	}
	public function getBoolExtraParam(string $name, bool $default = false): bool
	{
		return isset($this->extras[$name]) ? (bool)$this->extras[$name] : $default;
	}
	public function reset()
	{
		self::resetByCode($this->code, $this->userID);
	}
	public static function resetByCode($code, $userID = 0)
	{
		if(!(is_string($code) && $code !== ''))
		{
			return;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		// reset only really existed counters:
		$counterValues = \CUserCounter::GetValues($userID, '**');
		if (
			array_key_exists($code, $counterValues)
			&& $counterValues[$code] != -1
		)
		{
			\CUserCounter::Set($userID, $code, -1, '**', '', false);
		}
	}

	public static function resetExcludedByCode(string $code, int $userId): void
	{
		if ($code === '' || $userId <= 0)
		{
			return;
		}
		$counterValue = \CUserCounter::GetValues($userId, '**')[$code] ?? -1;
		// delete counter value for all users
		EntityCounter::resetByCodeForAll($code);
		// and restore value for current user if it was
		if ($counterValue >= 0)
		{
			\CUserCounter::Set($userId, $code, $counterValue, '**', '', false);
		}
	}

	public static function resetByCodeForAll($code)
	{
		if(is_string($code) && $code !== '')
		{
			\CUserCounter::DeleteByCode($code);
		}
	}

	public function increase(int $increment = 1): void
	{
		$code = $this->getCode();
		$userId = (int)$this->getUserID();
		if (is_string($code) && $code !== '' && $userId > 0)
		{
			\CUserCounter::Increment($userId, $code, '**', $this->sendPullEvent, $increment);
		}
	}

	public function decrease(int $decrement = 1): void
	{
		$code = $this->getCode();
		$userId = (int)$this->getUserID();
		if (is_string($code) && $code !== '' && $userId > 0)
		{
			\CUserCounter::Decrement($userId, $code, '**', $this->sendPullEvent, $decrement);
		}
	}

	public function getCode()
	{
		return $this->code;
	}
	protected function resolveCode()
	{
		return static::prepareCode($this->entityTypeID, $this->typeID, $this->extras);
	}
	public static function prepareCodes($entityTypeID, $typeIDs, array $extras = null)
	{
		return EntityCounterManager::prepareCodes($entityTypeID, $typeIDs, $extras);
	}
	public static function prepareCode($entityTypeID, $typeID, array $extras = null)
	{
		return EntityCounterManager::prepareCode($entityTypeID, $typeID, $extras);
	}

	public function getValue($recalculate = false)
	{
		if($this->currentValue !== null)
		{
			return $this->currentValue;
		}

		$this->currentValue  = -1;
		if($this->code !== '' && !$recalculate)
		{
			if($this->typeID === EntityCounterType::IDLE
				&& !\CCrmUserCounterSettings::GetValue(\CCrmUserCounterSettings::ReckonActivitylessItems, true))
			{
				$this->currentValue = 0;
			}
			else
			{
				$map = \CUserCounter::GetValues($this->userID, '**');
				if(isset($map[$this->code]))
				{
					$this->currentValue = (int)$map[$this->code];
				}
			}
		}

		if($this->currentValue < 0)
		{
			$this->synchronize();
		}

		return $this->currentValue;
	}

	/**
	 * Prepare queries for specified entity.
	 *
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityCounterTypeID Entity Counter Type ID.
	 * @param array $userIds Users id.
	 * @param array|null $options Options.
	 * @return Main\ORM\Query\Query[]
	 * @throws Main\NotSupportedException
	 */
	public function prepareEntityQueries(
		int $entityTypeID,
		int $entityCounterTypeID,
		array $userIds,
		array $options = null
	): array
	{
		$entityTypeID = (int)$entityTypeID;
		if(!is_array($options))
		{
			$options = [];
		}

		$selectType = $options['SELECT'] ?? '';
		if (!in_array($selectType, [CounterQueryBuilder::SELECT_TYPE_QUANTITY, CounterQueryBuilder::SELECT_TYPE_ENTITIES]))
		{
			$selectType = CounterQueryBuilder::SELECT_TYPE_QUANTITY;
		}

		$distinct = ($selectType === CounterQueryBuilder::SELECT_TYPE_ENTITIES); // SELECT_TYPE_QUANTITY should not use distinct
		if($selectType === CounterQueryBuilder::SELECT_TYPE_ENTITIES && isset($options['DISTINCT']))
		{
			$distinct = (bool)$options['DISTINCT'];
		}
		$needExcludeUsers = (bool)($options['EXCLUDE_USERS'] ?? false);
		$hasAnyIncomingChannel = $this->getExtraParam('HAS_ANY_INCOMING_CHANEL', null); // option can be used in select entities only
		if ($selectType !== CounterQueryBuilder::SELECT_TYPE_ENTITIES)
		{
			$hasAnyIncomingChannel = null;
		}

		$results = [];

		$factory = Container::getInstance()->getFactory($entityTypeID);
		if (!$factory || !$factory->isCountersEnabled())
		{
			return $results;
		}

		$typeIDs = EntityCounterType::splitType($entityCounterTypeID);
		$isCurrentCounter = in_array(EntityCounterType::READY_TODO, $typeIDs) && in_array(EntityCounterType::OVERDUE, $typeIDs);
		if ($isCurrentCounter)
		{
			$typeIDs = array_diff($typeIDs, [EntityCounterType::OVERDUE, EntityCounterType::READY_TODO]);
			$typeIDs[] = EntityCounterType::CURRENT;
		}

		$countersSettings = $factory->getCountersSettings();
		$qbBuilderFactory = new CounterQueryBuilderFactory();

		foreach($typeIDs as $typeID)
		{
			$queryParams = null;
			if ($selectType === CounterQueryBuilder::SELECT_TYPE_QUANTITY && !$countersSettings->isCounterTypeEnabled($typeID))
			{
				continue;
			}

			if ($entityTypeID === \CCrmOwnerType::Order && !Main\Loader::includeModule('sale'))
			{
				continue;
			}

			$useUncompletedActivityTable = ($selectType === CounterQueryBuilder::SELECT_TYPE_ENTITIES);

			$useActivityResponsible = $this->counterSettings->useActivityResponsible();

			$queryBuilderParams = (new QueryParamsBuilder($entityTypeID, $userIds, $selectType, $useActivityResponsible))
				->setUseDistinct($distinct)
				->setExcludeUsers($needExcludeUsers)
				->setCounterLimit($needExcludeUsers ? self::COUNTER_LIMIT : null)
				->setHasAnyIncomingChannel($hasAnyIncomingChannel)
				->setOptions($options)
				->setRestrictedFrom($this->extras['ACT_VIEW_RESTRICT_DEADLINE_FROM'] ?? null);

			if($typeID === EntityCounterType::IDLE)
			{
				$config = FactoryConfig::create(true)
					->setEntityTypeId($this->entityTypeID)
				;
				$queryBuilder = $qbBuilderFactory->make(EntityCounterType::IDLE, $config);
				$queryParams = $queryBuilderParams->build();

				$results[] = $queryBuilder->build($factory, $queryParams);
			}
			elseif ($typeID === EntityCounterType::PENDING)
			{
				if ($useUncompletedActivityTable)
				{
					// do not use UncompletedActivityTable by default for PENDING counter
					$useUncompletedActivityTable = $this->getExtraParam('ONLY_MIN_DEADLINE', false);
				}

				$config = FactoryConfig::create($useUncompletedActivityTable)
					->setEntityTypeId($this->entityTypeID)
				;

				$periodFrom = \CCrmDateTimeHelper::getUserDate(new Main\Type\DateTime(), $this->userID);
				$periodTo = \CCrmDateTimeHelper::getUserDate(new Main\Type\DateTime(), $this->userID);
				if (array_key_exists('PERIOD_FROM', $this->extras))
				{
					$periodFrom = $this->extras['PERIOD_FROM'];
				}
				if (array_key_exists('PERIOD_TO', $this->extras))
				{
					$periodTo = $this->extras['PERIOD_TO'];
				}

				$queryBuilder = $qbBuilderFactory->make($typeID, $config);
				$queryParams = $queryBuilderParams
					->setPeriodFrom($periodFrom) // today (time 00:00:00 will applied by counter itself)
					->setPeriodTo($periodTo) // today (time 23:59:59 will applied by counter itself)
					->build();

				$results[] = $queryBuilder->build($factory, $queryParams);
			}
			elseif ($typeID === EntityCounterType::READY_TODO)
			{
				$config = FactoryConfig::create($useUncompletedActivityTable)
					->setEntityTypeId($this->entityTypeID)
				;

				$queryBuilder = $qbBuilderFactory->make($typeID, $config);
				$queryParams = $queryBuilderParams
					->setPeriodFrom($this->extras['PERIOD_FROM'] ?? null)
					->setPeriodTo($this->extras['PERIOD_TO'] ?? null)
					->build();

				$results[] = $queryBuilder->build($factory, $queryParams);
			}
			elseif ($typeID === EntityCounterType::CURRENT)
			{
				$config = FactoryConfig::create($useUncompletedActivityTable)
					->setEntityTypeId($this->entityTypeID)
				;

				$queryParams = $queryBuilderParams
					->setPeriodFrom($this->extras['PERIOD_FROM'] ?? null)
					->setPeriodTo($this->extras['PERIOD_TO'] ?? null)
					->build();
				$queryBuilder = $qbBuilderFactory->make(EntityCounterType::CURRENT, $config);

				$results[] = $queryBuilder->build($factory, $queryParams);
			}
			elseif ($typeID === EntityCounterType::OVERDUE)
			{
				$config = FactoryConfig::create(
					$useUncompletedActivityTable,
					false
				)->setEntityTypeId($this->entityTypeID);

				$queryBuilder = $qbBuilderFactory->make(EntityCounterType::OVERDUE, $config);
				$queryParams = $queryBuilderParams->build();

				$results[] = $queryBuilder->build($factory, $queryParams);
			}
			else if($typeID === EntityCounterType::INCOMING_CHANNEL)
			{
				$config = FactoryConfig::create(
					$useUncompletedActivityTable,
					$this->getExtraParam('ONLY_MIN_INCOMING_CHANNEL', false)
				)->setEntityTypeId($this->entityTypeID);

				$queryBuilder = $qbBuilderFactory->make(EntityCounterType::INCOMING_CHANNEL, $config);

				$queryParams = $queryBuilderParams
					->setPeriodFrom($this->extras['INCOMING_CHANNEL_PERIOD_FROM'] ?? null)
					->setPeriodTo($this->extras['INCOMING_CHANNEL_PERIOD_TO'] ?? null)
					->setHasAnyIncomingChannel(true)
					->build();

				$results[] = $queryBuilder->build($factory, $queryParams);
			}
			else
			{
				$typeName = EntityCounterType::resolveName($typeID);
				throw new Main\NotSupportedException("The '{$typeName}' is not supported in current context");
			}
		}
		return $results;
	}

	/**
	 * Prepare queries
	 * @param array|null $options Options.
	 * @return Query[]
	 */
	private function prepareQueries(array $options = null)
	{
		if(is_array($options) && isset($options['USER_IDS']) && is_array($options['USER_IDS']))
		{
			$userIDs = $options['USER_IDS'];
		}
		else
		{
			$userIDs = [$this->userID];
		}
		$categoryId = $this->getIntegerExtraParam(
			'DEAL_CATEGORY_ID',
			$this->getIntegerExtraParam('CATEGORY_ID', null)
		);
		if (!is_null($categoryId) && $categoryId >= 0)
		{
			$options['CATEGORY_ID'] = $categoryId;
		}
		if ($this->getBoolExtraParam('EXCLUDE_USERS'))
		{
			$options['EXCLUDE_USERS'] = true;
		}

		return $this->prepareEntityQueries($this->entityTypeID, $this->typeID, $userIDs, $options);
	}
	/**
	 * Evaluate counter value
	 * @return int
	 */
	public function calculateValue(): int
	{
		if (
			!$this->counterSettings->isEnabled()
			|| !$this->counterSettings->canBeCounted()
		)
		{
			return 0; // counters feature is completely disabled
		}
		if ($this->typeID === \Bitrix\Crm\Counter\EntityCounterType::ALL)
		{
			return $this->calculateAggregatedValue();
		}

		$result = 0;
		$queries = $this->prepareQueries(['SELECT' => 'QTY']);

		foreach($queries as $query)
		{
			$dbResult = $query->exec();
			$fields = $dbResult->fetch();
			if(is_array($fields))
			{
				$result += (int)$fields['QTY'];
			}
		}
		return $result;
	}

	public function synchronize()
	{
		if($this->code !== '')
		{
			if ($this->calculatedTime->tryStartCalculation())
			{
				$this->currentValue = $this->calculateValue();
				\CUserCounter::Set($this->userID, $this->code, $this->currentValue, '**', '', $this->sendPullEvent);
				$this->calculatedTime->finishCalculation();
			}
			else
			{
				$this->currentValue = 0;
			}
		}
		else
		{
			$this->currentValue = 0;
		}
	}

	public function synchronizePostponed()
	{
		static $addedJobs = [];

		$code = $this->getCode();
		$userId = $this->getUserID();

		if ($code === '' || $userId <= 0)
		{
			return;
		}
		$jobKey = $code.'_'.$userId;
		if (isset($addedJobs[$jobKey]))
		{
			return;
		}
		$addedJobs[$jobKey] = true;

		\Bitrix\Main\Application::getInstance()->addBackgroundJob(
			function ()
			{
				$this->synchronize();
			}
		);
	}
	/**
	 * Get details page URL.
	 * @param string $url Base URL.
	 * @return string
	 */
	public function prepareDetailsPageUrl($url = '')
	{
		$urlParams = array('counter' => mb_strtolower($this->getTypeName()), 'clear_nav' => 'Y');
		self::externalizeExtras($this->extras, $urlParams);

		if($url === '')
		{
			$url = self::getEntityListPath();
		}
		return \CHTTP::urlAddParams($url, $urlParams);
	}
	public static function externalizeExtras(array $extras, array &$params)
	{
		if(!empty($extras))
		{
			foreach($extras as $k => $v)
			{
				$params["extras[{$k}]"] = $v;
			}
		}
	}
	public static function internalizeExtras(array $params)
	{
		return isset($params['extras']) && is_array($params['extras']) ? $params['extras'] : array();
	}

	/**
	 * Get entity list path.
	 * @static
	 * @return string
	 */
	protected function getEntityListPath()
	{
		return \CCrmOwnerType::GetListUrl($this->entityTypeID, false);
	}
	/**
	 * @param array|null $params List Params (MASTER_ALIAS, MASTER_IDENTITY and etc).
	 * @return array
	 */
	public function prepareEntityListFilter(array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$sql = $this->getEntityListSqlExpression($params);
		if(empty($sql))
		{
			return array();
		}
		$masterAlias = isset($params['MASTER_ALIAS']) ? $params['MASTER_ALIAS'] : 'L';
		$masterIdentity = isset($params['MASTER_IDENTITY']) ? $params['MASTER_IDENTITY'] : 'ID';
		return array('__CONDITIONS' => array(array('SQL' => "{$masterAlias}.{$masterIdentity} IN ({$sql})")));
	}

	public function getActivityFilterParam(array $params = null): array
	{
		if ($params === null)
		{
			$params = [];
		}

		$params['GET_QUERY_OBJECTS'] = true;

		return $this->getEntityListSqlExpression($params);
	}

	/**
	 * @param array $params
	 *
	 * @return string|array
	 */
	public function getEntityListSqlExpression(array $params = [])
	{
		$queryParams = array('SELECT' => 'ENTY', 'DISTINCT' => false);
		if(isset($params['USER_IDS']))
		{
			$queryParams['USER_IDS'] = $params['USER_IDS'];
		}
		if(isset($params['STAGE_SEMANTIC_ID']))
		{
			$queryParams['STAGE_SEMANTIC_ID'] = $params['STAGE_SEMANTIC_ID'];
		}
		if(isset($params['EXCLUDE_USERS']))
		{
			$queryParams['EXCLUDE_USERS'] = (bool)$params['EXCLUDE_USERS'];
		}

		$isGetQueryObjects = (bool)($params['GET_QUERY_OBJECTS'] ?? false);

		$queries = $this->prepareQueries($queryParams);
		$union = [];
		foreach($queries as $query)
		{
			$union[] = $isGetQueryObjects ? $query : $query->getQuery();
		}

		if ($isGetQueryObjects)
		{
			return $union;
		}

		if(empty($union))
		{
			return '';
		}

		return implode(' UNION ALL ', $union);
	}

	private function calculateAggregatedValue(): int
	{
		$result = 0;
		$factory = Container::getInstance()->getFactory($this->entityTypeID);
		if (!$factory || !$factory->isCountersEnabled())
		{
			return $result;
		}
		$counterComponents = $factory->getCountersSettings()->getComponentsOfAllCounter();
		foreach ($counterComponents as $typeId)
		{
			$counter = new self($this->entityTypeID, $typeId, $this->userID, $this->extras);
			$result += $counter->getValue();
		}

		return $result;
	}

	public static function resetAllCrmCountersForAllUsers(): void
	{
		global $CACHE_MANAGER;
		Application::getConnection()
			->query("UPDATE b_user_counter set CNT=-1 WHERE CODE LIKE 'crm%' AND NOT CODE LIKE 'CRM\_**' and CNT > -1");
		$CACHE_MANAGER->CleanDir("user_counter");
	}
}
