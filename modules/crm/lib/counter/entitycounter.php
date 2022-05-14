<?php
namespace Bitrix\Crm\Counter;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\UserActivityTable;
use Bitrix\Crm\Pseudoactivity\Entity\WaitTable;

class EntityCounter extends CounterBase
{
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
	/** @var string */
	protected $lastCalculateOptionName = '';
	/** @var int|null  */
	protected $lastCalculatedTime = null;

	/** @var bool */
	protected $sendPullEvent = false;

	private static $userTimes = [];

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
		$this->lastCalculateOptionName = $this->resolveLastCalculateOptionName();
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
	/**
	 * @return int
	 */
	protected function isOneDay()
	{
		return $this->typeID === EntityCounterType::PENDING
			|| $this->typeID === EntityCounterType::OVERDUE
			|| $this->typeID === EntityCounterType::ALL;
	}
	protected function isExpired()
	{
		return ($this->typeID === EntityCounterType::PENDING
			|| $this->typeID === EntityCounterType::OVERDUE
			|| $this->typeID === EntityCounterType::ALL)
			&& !$this->checkLastCalculatedTime();
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
		return isset($this->extras) ? $this->extras[$name] : $default;
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

		\CUserCounter::Set($userID, $code, -1, '**', '', false);
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
	protected function resolveLastCalculateOptionName()
	{
		return $this->code !== '' ? "{$this->code}_last_calc" : '';
	}
	public static function prepareCodes($entityTypeID, $typeIDs, array $extras = null)
	{
		return EntityCounterManager::prepareCodes($entityTypeID, $typeIDs, $extras);
	}
	public static function prepareCode($entityTypeID, $typeID, array $extras = null)
	{
		return EntityCounterManager::prepareCode($entityTypeID, $typeID, $extras);
	}
	protected function checkLastCalculatedTime()
	{
		if($this->lastCalculateOptionName === '')
		{
			return false;
		}

		$current = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
		if($this->lastCalculatedTime === null)
		{
			$this->lastCalculatedTime = (int)\CUserOptions::GetOption('crm', $this->lastCalculateOptionName, 0, $this->userID);
		}
		return $this->lastCalculatedTime >= $current;
	}
	protected function refreshLastCalculatedTime()
	{
		if($this->lastCalculateOptionName === '')
		{
			return;
		}

		$current = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
		if($this->lastCalculatedTime !== $current)
		{
			$this->lastCalculatedTime = $current;
			\CUserOptions::SetOption('crm', $this->lastCalculateOptionName, $this->lastCalculatedTime, false, $this->userID);
		}
	}

	/**
	 * @param $userID
	 * @return DateTime
	 */
	protected static function getUserTime($userID): DateTime
	{
		$currentUser = (int)\CCrmSecurityHelper::GetCurrentUserID();
		if (is_array($userID))
		{
			if (empty($userID))
			{
				$userID = $currentUser;
			}
			else
			{
				$userID = (int)array_shift($userID);
			}
		}
		else
		{
			$userID = (int)$userID;
		}

		if (empty(self::$userTimes[$userID]))
		{
			$time = new DateTime();
			$offset = (int) ($userID > 0 ? \CTimeZone::GetOffset($currentUser === $userID ? null : $userID) : 0);
			if ($offset)
			{
				$time->add(($offset < 0 ? '-' : '') . 'PT' . abs($offset) . 'S');
			}
			self::$userTimes[$userID] = $time;
		}

		return clone self::$userTimes[$userID];
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
	public static function prepareEntityQueries($entityTypeID, $entityCounterTypeID, $userID, array $options = null)
	{
		$entityTypeID = (int)$entityTypeID;
		if(!is_array($options))
		{
			$options = array();
		}

		$select = isset($options['SELECT']) ? $options['SELECT'] : '';
		if($select !== 'QTY' && $select !== 'ENTY')
		{
			$select = 'QTY';
		}

		$distinct = true;
		if($select === 'ENTY' && isset($options['DISTINCT']))
		{
			$distinct = $options['DISTINCT'];
		}

		$results = [];

		$factory = Container::getInstance()->getFactory($entityTypeID);
		if (!$factory || !$factory->isCountersEnabled())
		{
			return $results;
		}

		$countersSettings = $factory->getCountersSettings();
		foreach(EntityCounterType::splitType($entityCounterTypeID) as $typeID)
		{
			if (!$countersSettings->isCounterTypeEnabled($typeID))
			{
				continue;
			}
			$query = new Query($factory->getDataClass()::getEntity());

			if($entityTypeID === \CCrmOwnerType::Deal)
			{
				$query->addFilter('=STAGE_SEMANTIC_ID', PhaseSemantics::PROCESS);
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
				$query->addFilter('=STATUS_SEMANTIC_ID', PhaseSemantics::PROCESS);
			}
			elseif(\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeID) && isset($options['CATEGORY_ID']))
			{
				$query->where('CATEGORY_ID', $options['CATEGORY_ID']);
			}

			if($typeID === EntityCounterType::IDLE)
			{
				if($select === 'ENTY')
				{
					$query->addSelect('ID', 'ENTY');
				}
				else
				{
					$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(%s)', 'ID'));
					$query->addSelect('QTY');
				}

				$query->registerRuntimeField(
					'',
					new ReferenceField('UA',
						UserActivityTable::getEntity(),
						array(
							'=ref.OWNER_ID' => 'this.ID',
							'=ref.OWNER_TYPE_ID' => new SqlExpression($entityTypeID),
							'=ref.USER_ID' => new SqlExpression(0)
						),
						array('join_type' => 'LEFT')
					)
				);
				$query->addFilter('==UA.OWNER_ID', null);

				$query->registerRuntimeField(
					'',
					new ReferenceField('W',
						WaitTable::getEntity(),
						array(
							'=ref.OWNER_ID' => 'this.ID',
							'=ref.OWNER_TYPE_ID' => new SqlExpression($entityTypeID),
							'=ref.COMPLETED' => new SqlExpression('?s', 'N')
						),
						array('join_type' => 'LEFT')
					)
				);
				$query->addFilter('==W.OWNER_ID', null);

				if($entityTypeID !== \CCrmOwnerType::Order)
					$assignedColumn = 'ASSIGNED_BY_ID';
				else
					$assignedColumn = 'RESPONSIBLE_ID';

				if(is_array($userID))
				{
					$userCount = count($userID);
					if($userCount > 1)
					{
						$query->addFilter('@'.$assignedColumn, $userID);
					}
					elseif($userCount === 1)
					{
						$query->addFilter('='.$assignedColumn, $userID[0]);
					}
				}
				elseif($userID > 0)
				{
					//Strongly required for counter design. We manage counters in user-oriented manner.
					$query->addFilter('='.$assignedColumn, $userID);
				}

				$results[] = $query;
			}
			else if($typeID === EntityCounterType::PENDING || $typeID === EntityCounterType::OVERDUE)
			{
				$query->registerRuntimeField(
					'',
					new ReferenceField('B',
						ActivityBindingTable::getEntity(),
						array(
							'=ref.OWNER_ID' => 'this.ID',
							'=ref.OWNER_TYPE_ID' => new SqlExpression($entityTypeID)
						),
						array('join_type' => 'INNER')
					)
				);

				//region Activity (inner join with correlated query for fix issue #109347)
				$activityQuery = new Main\Entity\Query(ActivityTable::getEntity());

				if(is_array($userID))
				{
					$userCount = count($userID);
					if($userCount > 1)
					{
						$activityQuery->addFilter('@RESPONSIBLE_ID', $userID);
					}
					elseif($userCount === 1)
					{
						$activityQuery->addFilter('=RESPONSIBLE_ID', $userID[0]);
					}
				}
				elseif($userID > 0)
				{
					$activityQuery->addFilter('=RESPONSIBLE_ID', $userID);
				}

				if($typeID === EntityCounterType::PENDING)
				{
					$lowBound = self::getUserTime($userID);
					$lowBound->setTime(0, 0, 0);
					$activityQuery->addFilter('>=DEADLINE', $lowBound);

					$highBound = self::getUserTime($userID);
					$highBound->setTime(23, 59, 59);
					$activityQuery->addFilter('<=DEADLINE', $highBound);
				}
				elseif($typeID === EntityCounterType::OVERDUE)
				{
					$highBound = self::getUserTime($userID);
					$highBound->setTime(0, 0, 0);
					$activityQuery->addFilter('<DEADLINE', $highBound);
				}
				if (isset($options['PROVIDER_ID']))
				{
					if (is_array($options['PROVIDER_ID']))
					{
						$activityQuery->whereIn('PROVIDER_ID', $options['PROVIDER_ID']);
					}
					else
					{
						$activityQuery->where('PROVIDER_ID', (string)$options['PROVIDER_ID']);
					}
				}
				if (isset($options['PROVIDER_TYPE_ID']))
				{
					if (is_array($options['PROVIDER_TYPE_ID']))
					{
						$activityQuery->whereIn('PROVIDER_TYPE_ID', $options['PROVIDER_TYPE_ID']);
					}
					else
					{
						$activityQuery->where('PROVIDER_TYPE_ID', (string)$options['PROVIDER_TYPE_ID']);
					}
				}

				$activityQuery->addFilter('=COMPLETED', 'N');
				$activityQuery->addSelect('ID');

				$query->registerRuntimeField(
					'',
					new ReferenceField('A',
						Main\Entity\Base::getInstanceByQuery($activityQuery),
						array('=ref.ID' => 'this.B.ACTIVITY_ID'),
						array('join_type' => 'INNER')
					)
				);
				//endregion

				if($select === 'ENTY')
				{
					$query->addSelect('B.OWNER_ID', 'ENTY');
					if($distinct)
					{
						$query->addGroup('B.OWNER_ID');
					}
				}
				else
				{
					$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(DISTINCT %s)', 'ID'));
					$query->addSelect('QTY');
				}

				$results[] = $query;
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
		$categoryId = $this->getIntegerExtraParam('CATEGORY_ID', null);
		if (!is_null($categoryId) && $categoryId >= 0)
		{
			$options['CATEGORY_ID'] = $categoryId;
		}

		return self::prepareEntityQueries($this->entityTypeID, $this->typeID, $userIDs, $options);
	}
	/**
	 * Evaluate counter value
	 * @return int
	 */
	public function calculateValue()
	{
		if (!\Bitrix\Crm\Settings\CounterSettings::getCurrent()->isEnabled())
		{
			return 0; // counters feature is completely disabled
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
		$this->currentValue = $this->calculateValue();
		if($this->code !== '')
		{
			\CUserCounter::Set($this->userID, $this->code, $this->currentValue, '**', '', $this->sendPullEvent);
			if($this->isOneDay())
			{
				$this->refreshLastCalculatedTime();
			}
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
		if(isset($params['PROVIDER_ID']))
		{
			$queryParams['PROVIDER_ID'] = $params['PROVIDER_ID'];
		}
		if(isset($params['PROVIDER_TYPE_ID']))
		{
			$queryParams['PROVIDER_TYPE_ID'] = $params['PROVIDER_TYPE_ID'];
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
}
