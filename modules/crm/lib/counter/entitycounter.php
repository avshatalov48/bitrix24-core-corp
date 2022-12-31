<?php

namespace Bitrix\Crm\Counter;

use Bitrix\Crm\Counter\QueryBuilder\DeadlineBased;
use Bitrix\Crm\Counter\QueryBuilder\IncomingChannel;
use Bitrix\Crm\Counter\QueryBuilder\Idle;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Crm\PhaseSemantics;

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

	/**
	 * @param int $entityTypeID Entity Type ID (see \CCrmOwnerType).
	 * @param int $typeID Type ID (see EntityCounterType).
	 * @param int $userID User ID.
	 * @param array|null $extras Additional Parameters.
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function __construct($entityTypeID, $typeID, $userID = 0, array $extras = null)
	{
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

	protected function isOneDay(): bool
	{
		return $this->typeID === EntityCounterType::PENDING
			|| $this->typeID === EntityCounterType::OVERDUE
			|| $this->typeID === EntityCounterType::CURRENT
			|| $this->typeID === EntityCounterType::ALL_DEADLINE_BASED
			|| $this->typeID === EntityCounterType::ALL
		;
	}

	protected function isExpired(): bool
	{
		return $this->isOneDay() && !$this->isValidLastCalculatedTime();
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

	protected function isValidLastCalculatedTime(): bool
	{
		if($this->code === '')
		{
			return false;
		}

		return $this->calculatedTime->wasCalculatedToday();
	}

	public function getValue($recalculate = false)
	{
		if($this->currentValue !== null)
		{
			return $this->currentValue;
		}

		$this->currentValue  = -1;
		if($this->code !== '' && !$recalculate && !$this->isExpired())
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
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityCounterTypeID Entity Counter Type ID.
	 * @param array|int $userID User ID.
	 * @param array|null $options Options.
	 * @return array
	 * @throws Main\NotSupportedException
	 */
	public function prepareEntityQueries($entityTypeID, $entityCounterTypeID, $userID, array $options = null)
	{
		$entityTypeID = (int)$entityTypeID;
		if(!is_array($options))
		{
			$options = [];
		}

		$select = isset($options['SELECT']) ? $options['SELECT'] : '';
		if($select !== QueryBuilder::SELECT_TYPE_QUANTITY && $select !== QueryBuilder::SELECT_TYPE_ENTITIES)
		{
			$select = QueryBuilder::SELECT_TYPE_QUANTITY;
		}

		$distinct = ($select === QueryBuilder::SELECT_TYPE_ENTITIES); // SELECT_TYPE_QUANTITY should not use distinct
		if($select === QueryBuilder::SELECT_TYPE_ENTITIES && isset($options['DISTINCT']))
		{
			$distinct = (bool)$options['DISTINCT'];
		}
		$needExcludeUsers = (bool)($options['EXCLUDE_USERS'] ?? false);
		$hasAnyIncomingChannel = $this->getExtraParam('HAS_ANY_INCOMING_CHANEL', null); // option can be used in select entities only
		if ($select !== QueryBuilder::SELECT_TYPE_ENTITIES)
		{
			$hasAnyIncomingChannel = null;
		}

		$results = [];

		$factory = Container::getInstance()->getFactory($entityTypeID);
		if (!$factory || !$factory->isCountersEnabled())
		{
			return $results;
		}
		$countersSettings = $factory->getCountersSettings();

		$typeIDs = EntityCounterType::splitType($entityCounterTypeID);
		$isCurrentCounter = in_array(EntityCounterType::PENDING, $typeIDs) && in_array(EntityCounterType::OVERDUE, $typeIDs);
		foreach($typeIDs as $typeID)
		{
			if ($select === QueryBuilder::SELECT_TYPE_QUANTITY && !$countersSettings->isCounterTypeEnabled($typeID))
			{
				continue;
			}
			$useUncompletedActivityTable = ($select === QueryBuilder::SELECT_TYPE_ENTITIES);
			$query = $factory->getDataClass()::query();

			$stageSemanticId = isset($options['STAGE_SEMANTIC_ID']) && $options['STAGE_SEMANTIC_ID']
				? $options['STAGE_SEMANTIC_ID']
				: PhaseSemantics::PROCESS
			;
			if($entityTypeID === \CCrmOwnerType::Deal)
			{
				$query->addFilter('=STAGE_SEMANTIC_ID', $stageSemanticId);
				$query->addFilter('=IS_RECURRING', 'N');

				if(isset($options['CATEGORY_ID']) && $options['CATEGORY_ID'] >= 0)
				{
					$query->addFilter('=CATEGORY_ID', new SqlExpression('?i', $options['CATEGORY_ID']));
				}
			}
			else if($entityTypeID === \CCrmOwnerType::Contact)
			{
				if (isset($options['CATEGORY_ID']))
				{
					$query->where('CATEGORY_ID', $options['CATEGORY_ID']);
				}
			}
			else if($entityTypeID === \CCrmOwnerType::Company)
			{
				$query->addFilter('=IS_MY_COMPANY', 'N');
				if (isset($options['CATEGORY_ID']))
				{
					$query->where('CATEGORY_ID', $options['CATEGORY_ID']);
				}
			}
			elseif($entityTypeID === \CCrmOwnerType::Order)
			{
				if(!Main\Loader::includeModule('sale'))
				{
					continue;
				}
				$query->addFilter('=CANCELED', 'N');
				$query->addFilter('@STATUS_ID', OrderStatus::getSemanticProcessStatuses());
			}
			elseif($entityTypeID === \CCrmOwnerType::Lead)
			{
				$query->addFilter('=STATUS_SEMANTIC_ID', $stageSemanticId);
			}
			elseif(\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeID) && isset($options['CATEGORY_ID']))
			{
				$query->where('CATEGORY_ID', $options['CATEGORY_ID']);
			}

			if($typeID === EntityCounterType::IDLE)
			{
				$results[] = (new Idle($entityTypeID, $userID))
					->setUseDistinct($distinct)
					->setExcludeUsers($needExcludeUsers)
					->setSelectType($select)
					->setUseUncompletedActivityTable(true)
					->build($query)
				;
			}
			elseif ($typeID === EntityCounterType::PENDING)
			{
				if ($isCurrentCounter)
				{
					$periodFrom = null;
					$periodTo = \CCrmDateTimeHelper::getUserDate(new Main\Type\DateTime(), $this->userID); // today (time 23:59:59 will applied by counter itself)
					if (array_key_exists('PERIOD_FROM', $this->extras))
					{
						$periodFrom = $this->extras['PERIOD_FROM'];
					}
					if (array_key_exists('PERIOD_TO', $this->extras))
					{
						$periodTo = $this->extras['PERIOD_TO'];
					}
					$results[] = (new DeadlineBased($entityTypeID, $userID))
						->setUseDistinct($distinct)
						->setExcludeUsers($needExcludeUsers)
						->setCounterLimit($needExcludeUsers ? self::COUNTER_LIMIT : null)
						->setSelectType($select)
						->setPeriodFrom($periodFrom)
						->setPeriodTo($periodTo)
						->setUseUncompletedActivityTable($useUncompletedActivityTable)
						->setHasAnyIncomingChannel($hasAnyIncomingChannel)
						->build($query)
					;
				}
				else
				{
					if ($useUncompletedActivityTable)
					{
						// do not use UncompletedActivityTable by default for PENDING counter
						$useUncompletedActivityTable = $this->getExtraParam('ONLY_MIN_DEADLINE', false);
					}
					$results[] = (new DeadlineBased($entityTypeID, $userID))
						->setUseDistinct($distinct)
						->setExcludeUsers($needExcludeUsers)
						->setCounterLimit($needExcludeUsers ? self::COUNTER_LIMIT : null)
						->setSelectType($select)
						->setPeriodFrom(\CCrmDateTimeHelper::getUserDate(new Main\Type\DateTime(), $this->userID)) // today (time 00:00:00 will applied by counter itself)
						->setPeriodTo(\CCrmDateTimeHelper::getUserDate(new Main\Type\DateTime(), $this->userID)) // today (time 23:59:59 will applied by counter itself)
						->setUseUncompletedActivityTable($useUncompletedActivityTable)
						->setHasAnyIncomingChannel($hasAnyIncomingChannel)
						->build($query)
					;
				}
			}
			elseif ($typeID === EntityCounterType::OVERDUE)
			{
				if ($isCurrentCounter)
				{
					continue;
				}
				$results[] = (new DeadlineBased($entityTypeID, $userID))
					->setUseDistinct($distinct)
					->setExcludeUsers($needExcludeUsers)
					->setCounterLimit($needExcludeUsers ? self::COUNTER_LIMIT : null)
					->setSelectType($select)
					->setPeriodTo(\CCrmDateTimeHelper::getUserDate(new Main\Type\DateTime(), $this->userID)->add('-1 day')) // yesterday (time 23:59:59 will applied by counter itself)
					->setUseUncompletedActivityTable($useUncompletedActivityTable)
					->setHasAnyIncomingChannel($hasAnyIncomingChannel)
					->build($query)
				;
			}
			else if($typeID === EntityCounterType::INCOMING_CHANNEL)
			{
				if ($useUncompletedActivityTable && $this->getExtraParam('ONLY_MIN_INCOMING_CHANNEL', false))
				{
					$periodFrom = null;
					$periodTo = null;
					if (array_key_exists('INCOMING_CHANNEL_PERIOD_FROM', $this->extras))
					{
						$periodFrom = $this->extras['INCOMING_CHANNEL_PERIOD_FROM'];
					}
					if (array_key_exists('INCOMING_CHANNEL_PERIOD_TO', $this->extras))
					{
						$periodTo = $this->extras['INCOMING_CHANNEL_PERIOD_TO'];
					}
					$results[] = (new DeadlineBased($entityTypeID, $userID))
						->setUseDistinct($distinct)
						->setExcludeUsers($needExcludeUsers)
						->setCounterLimit($needExcludeUsers ? self::COUNTER_LIMIT : null)
						->setSelectType($select)
						->setPeriodFrom($periodFrom)
						->setPeriodTo($periodTo)
						->setUseUncompletedActivityTable(true)
						->setHasAnyIncomingChannel(true)
						->build($query)
					;
				}
				else
				{
					$results[] = (new IncomingChannel($entityTypeID, $userID))
						->setUseDistinct($distinct)
						->setExcludeUsers($needExcludeUsers)
						->setCounterLimit($needExcludeUsers ? self::COUNTER_LIMIT : null)
						->setSelectType($select)
						->build($query);
				}
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
	protected function prepareQueries(array $options = null)
	{
		if(is_array($options) && isset($options['USER_IDS']) && is_array($options['USER_IDS']))
		{
			$userIDs = $options['USER_IDS'];
		}
		else
		{
			$userIDs = array($this->userID);
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
		if (!\Bitrix\Crm\Settings\CounterSettings::getCurrent()->isEnabled())
		{
			return 0; // counters feature is completely disabled
		}
		if ($this->typeID === \Bitrix\Crm\Counter\EntityCounterType::ALL)
		{
			return $this->calculateAggregatedValue();
		}

		$result = 0;
		$queries = $this->prepareQueries(array('SELECT' => 'QTY'));

		foreach($queries as $query)
		{
			//echo '<pre>', $query->getQuery(), '</pre>';
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

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	public function getEntityListSqlExpression(array $params = [])
	{
		$union = array();
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

		$queries = $this->prepareQueries($queryParams);
		foreach($queries as $query)
		{
			$union[] = $query->getQuery();
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
}
