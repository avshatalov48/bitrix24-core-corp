<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\ExpressionField;

use Bitrix\Pull;

use Bitrix\Im;
use Bitrix\Im\Model\RecentTable;

use Bitrix\ImOpenLines\Queue\Cache;
use Bitrix\ImOpenLines\Model\QueueTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Model\SessionCheckTable;

Loc::loadMessages(__FILE__);

class Queue
{
	public const UNDISTRIBUTED_QUEUE_TIME = 3600;
	public const MAX_CHAT = 1000;

	//Session check reason return values
	//if you add a new return reason, you need to add a list of possible values here: \Bitrix\ImOpenLines\Model\SessionCheckTable::getMap
	public const REASON_DEFAULT = 'DEFAULT';
	public const REASON_OPERATOR_ABSENT = 'VACATION';
	public const REASON_OPERATOR_DAY_PAUSE = 'NONWORKING';
	public const REASON_OPERATOR_DAY_END = 'NONWORKING';
	public const REASON_OPERATOR_DAY_END_SILENT = 'NONWORKING_SILENT';
	public const REASON_OPERATOR_DELETED = 'DISMISSAL';
	public const REASON_REMOVED_FROM_QUEUE = 'REMOVING';
	public const REASON_OPERATOR_NOT_AVAILABLE = 'NOT_AVAILABLE';
	public const REASON_OPERATOR_OFFLINE = 'OFFLINE';
	public const REASON_QUEUE_TYPE_CHANGED = 'DEFAULT';

	public static $type = [
		Config::QUEUE_TYPE_EVENLY => Config::QUEUE_TYPE_EVENLY,
		Config::QUEUE_TYPE_STRICTLY => Config::QUEUE_TYPE_STRICTLY,
		Config::QUEUE_TYPE_ALL => Config::QUEUE_TYPE_ALL,
	];

	/**
	 * @param $session
	 * @return Queue\Evenly|Queue\All|Queue\Strictly|null
	 */
	public static function initialization(Session $session): ?Queue\Queue
	{
		$queue = null;

		$config = $session->getConfig();
		$chat = $session->getChat();

		if (
			!empty($config)
			&& !empty($config['QUEUE_TYPE'])
			&& !empty(self::$type[$config['QUEUE_TYPE']])
			&& !empty($chat)
		)
		{
			$queueClass = "Bitrix\\ImOpenLines\\Queue\\" . ucfirst(mb_strtolower($config['QUEUE_TYPE']));

			/** @var Queue\Evenly|Queue\All|Queue\Strictly $queue */
			$queue = new $queueClass($session);
		}

		return $queue;
	}

	/**
	 * @param int $limitTime
	 * @param int $limit
	 * @param int $lineId
	 */
	public static function transferToNextSession($limitTime = 60, $limit = 0, $lineId = 0)
	{
		$time = new Tools\Time;

		$configs = [];
		$chats = [];
		$configManager = new Config();
		$runSessionIds = [];

		$count = 0;
		$countIterationPull = 0;
		while ($time->getElapsedTime() <= $limitTime && (empty($limit) || $count < $limit))
		{
			$reasonReturn = Queue::REASON_DEFAULT;

			if ($countIterationPull > 10 && Loader::includeModule('pull'))
			{
				$countIterationPull = 0;

				Pull\Event::send();
			}

			$filter = [
				'<=DATE_QUEUE' => new DateTime()
			];

			if (!empty($lineId) && is_numeric($lineId) && $lineId > 0)
			{
				$filter['=SESSION.CONFIG_ID'] = $lineId;
			}

			if (!empty($runSessionIds))
			{
				$filter['!=SESSION_ID'] = $runSessionIds;
			}

			$select = SessionTable::getSelectFieldsPerformance('SESSION');

			$select[] = 'REASON_RETURN';

			$res = SessionCheckTable::getList([
				'select' => $select,
				'filter' => $filter,
				'order' => [
					'DATE_QUEUE' => 'ASC',
					'SESSION_ID' => 'ASC',
				],
				'limit' => 1
			]);

			if ($row = $res->fetch())
			{
				$fields = [];

				if (!empty($row['REASON_RETURN']))
				{
					$reasonReturn = $row['REASON_RETURN'];
				}
				unset($row['REASON_RETURN']);

				foreach ($row as $key=>$value)
				{
					$key = str_replace('IMOPENLINES_MODEL_SESSION_CHECK_SESSION_', '', $key);
					$fields[$key] = $value;
				}

				$runSessionIds[$fields['ID']] = $fields['ID'];

				if (!isset($configs[$fields['CONFIG_ID']]))
				{
					$configs[$fields['CONFIG_ID']] = $configManager->get($fields['CONFIG_ID']);
				}
				if (!isset($chats[$fields['CHAT_ID']]))
				{
					$chats[$fields['CHAT_ID']] = new Chat($fields['CHAT_ID']);
				}

				self::sendMessageReturnedSession($reasonReturn, $fields);

				$session = new Session();
				$session->loadByArray($fields, $configs[$fields['CONFIG_ID']], $chats[$fields['CHAT_ID']]);

				$resultTransfer = $session->transferToNextInQueue(false);

				if ($resultTransfer == true)
				{
					$countIterationPull++;
				}
				$count++;
			}
			else
			{
				break;
			}
		}

		if (Loader::includeModule('pull') && $countIterationPull > 0)
		{
			Pull\Event::send();
		}
	}

	/**
	 * Defines whether sessions that require distribution.
	 *
	 * @param int $line
	 * @return bool
	 */
	public static function isThereSessionTransfer($line = 0)
	{
		$result = false;
		$line = intval($line);

		if($line > 0)
		{
			$filter = [
				'!=DATE_QUEUE' => null,
				'SESSION.CONFIG_ID' => $line
			];
		}
		else
		{
			$filter = ['!=DATE_QUEUE' => null];
		}

		$count = SessionCheckTable::getCount($filter);

		if(!empty($count) && $count > 0)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $params
	 * @return \Bitrix\Main\ORM\Query\Result
	 */
	public static function getList($params)
	{
		$lastActivityDate = self::getTimeLastActivityOperator();
		$timeHelper = Application::getConnection()->getSqlHelper()->addSecondsToDateTime(-1 * $lastActivityDate);

		$query = new Query(QueueTable::getEntity());
		if (Loader::includeModule('im'))
		{
			$query->registerRuntimeField('', new ReferenceField(
				'IM_STATUS',
				'\Bitrix\Im\Model\StatusTable',
				['=ref.USER_ID' => 'this.USER_ID'],
				['join_type'=>'left']
			));

			$query->registerRuntimeField(new ExpressionField(
				'IS_ONLINE_CUSTOM',
				'CASE WHEN %1$s > '.$timeHelper.' AND (%2$s IS NULL OR %1$s > %2$s) THEN \'Y\' ELSE \'N\' END',
				['USER.LAST_ACTIVITY_DATE', 'IM_STATUS.IDLE']
			));
		}
		else
		{
			$query->registerRuntimeField(new ExpressionField(
				'IS_ONLINE_CUSTOM',
				'CASE WHEN %s > '.$timeHelper.' THEN \'Y\' ELSE \'N\' END',
				['USER.LAST_ACTIVITY_DATE']
			));
		}

		if(isset($params['select']))
		{
			$query->setSelect($params['select']);
		}
		else
		{
			$query->addSelect('ID')->addSelect('IS_ONLINE_CUSTOM');
		}

		if (isset($params['filter']))
		{
			$query->setFilter($params['filter']);
		}

		if (isset($params['order']))
		{
			$query->setOrder($params['order']);
		}

		if(
			!empty($params['cache']) &&
			isset($params['cache']['ttl'])
		)
		{
			$query->setCacheTtl($params['cache']['ttl']);

			if(
				isset($params['cache']['cacheJoins']) &&
				$params['cache']['cacheJoins'] === true
			)
			{
				$query->cacheJoins(true);
			}
		}

		return $query->exec();
	}

	/**
	 * Return array of user data for current line.
	 *
	 * @param $userId
	 * @param $lineId
	 * @return array|bool|false
	 */
	public static function getQueueOperatorData($userId, $lineId)
	{
		$lineId = (int)$lineId;
		$userId = (int)$userId;
		$queue = false;

		$cache = new Cache();

		if ($lineId > 0 && $userId > 0)
		{
			$params = [
				'select' => ['USER_NAME', 'USER_WORK_POSITION', 'USER_AVATAR', 'USER_AVATAR_ID'],
				'filter' => [
					'CONFIG_ID' => $lineId,
					'USER_ID' => $userId
				],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC'
				],
			];

			$cache->setLineId($lineId);
			$cache->setUserId($userId);

			if ($cache->initCacheQueueOperatorData())
			{
				$queue = $cache->getVarsQueueOperatorData();
			}
			else
			{
				$cache->startCacheQueueOperatorData();

				$queue = self::getList($params)->fetch();

				if (empty($queue['USER_AVATAR']))
				{
					$queue['USER_AVATAR'] = Im\User::getInstance($userId)->getAvatar();
				}

				$cache->endCacheQueueOperatorData($queue);
			}
		}

		return $queue;
	}

	/**
	 * Set user data for openlines using line config and queue data
	 *
	 * @param $lineId
	 * @param $userArray
	 * @return array|false|mixed
	 */
	public static function setQueueUserData($lineId, $userArray)
	{
		$result = false;

		$operatorDataType = Config::operatorDataConfig($lineId);

		if ($operatorDataType == Config::OPERATOR_DATA_QUEUE)
		{
			$userData = self::getQueueOperatorData($userArray['ID'], $lineId);

			if(
				!Tools::isEmpty($userData['USER_NAME'])
				|| !Tools::isEmpty($userData['USER_AVATAR'])
				|| !Tools::isEmpty($userData['USER_AVATAR_ID'])
				|| !Tools::isEmpty($userData['USER_WORK_POSITION'])
			)
			{
				if(!Tools::isEmpty($userData['USER_NAME']))
				{
					$userArray['NAME'] = (string)$userData['USER_NAME'];

					$nameElements = explode(' ', $userArray['NAME']);
					if (count($nameElements) > 1)
					{
						$userArray['LAST_NAME'] = array_pop($nameElements);
						$userArray['FIRST_NAME'] = join(" ", $nameElements);
					}
					else
					{
						$userArray['FIRST_NAME'] = $userArray['NAME'];
						$userArray['LAST_NAME'] = '';
					}
				}

				if (!Tools::isEmpty($userData['USER_WORK_POSITION']))
				{
					$userArray['WORK_POSITION'] = (string)$userData['USER_WORK_POSITION'];
				}

				if (!Tools::isEmpty($userData['USER_AVATAR']))
				{
					$userArray['AVATAR'] = (string)$userData['USER_AVATAR'];
				}

				if (!Tools::isEmpty($userData['USER_AVATAR_ID']))
				{
					$userArray['AVATAR_ID'] = (int)$userData['USER_AVATAR_ID'];
				}

				$result = $userArray;
			}
		}
		elseif ($operatorDataType == Config::OPERATOR_DATA_HIDE)
		{
			$defaultOperatorData = self::getDefaultOperatorData($lineId);
			$result = array_merge($userArray, $defaultOperatorData);
		}

		return $result;
	}

	/**
	 * Return default operator data using line data and default present
	 *
	 * @param $lineId
	 *
	 * @return mixed
	 */
	public static function getDefaultOperatorData($lineId)
	{
		$operatorData = Config::getDefaultOperatorData($lineId);
		$result['NAME'] = !empty($operatorData['NAME']) ? $operatorData['NAME'] : Loc::getMessage("QUEUE_OPERATOR_DEFAULT_NAME");
		$result['FIRST_NAME'] = (string)$result['NAME'];
		$result['LAST_NAME'] = '';
		$result['AVATAR'] = !empty($operatorData['AVATAR']) ? $operatorData['AVATAR'] : '';
		$result['AVATAR_ID'] = !empty($operatorData['AVATAR_ID']) ? $operatorData['AVATAR_ID'] : 0;
		$result['WORK_POSITION'] = '';

		return $result;
	}

	/**
	 * Set user data for openlines using custom function and line config and queue data
	 *
	 * @param $lineId
	 * @param $userId
	 * @param bool $nullForUnprocessed
	 * @return array|null
	 */
	public static function getUserData($lineId, $userId, $nullForUnprocessed = false)
	{
		$result = null;

		if ($userId > 0)
		{
			$user = Im\User::getInstance($userId);

			if($user->isExists())
			{
				$currentUserData = [
					'ID' => (int)$userId,
					'NAME' => $user->getFullName(false),
					'FIRST_NAME' => $user->getName(false),
					'LAST_NAME' => $user->getLastName(false),
					'WORK_POSITION' => '',
					'GENDER' => $user->getGender(),
					'AVATAR' => $user->getAvatar(),
					'AVATAR_ID' => $user->getAvatarId(),
					'ONLINE' => $user->isOnline()
				];

				if ($user->isExtranet())
				{
					$result = $nullForUnprocessed? null: $currentUserData;
				}
				else
				{
					//TODO: Forced replacement of aliases.
					if (function_exists('customImopenlinesOperatorNames'))
					{
						$customData = customImopenlinesOperatorNames($lineId, [
							'ID' => (int)$currentUserData['ID'],
							'NAME' => $currentUserData['FIRST_NAME'],
							'FIRST_NAME' => '',
							'LAST_NAME' => $currentUserData['LAST_NAME'],
							'WORK_POSITION' => '',
							'GENDER' => $currentUserData['GENDER'],
							'AVATAR' => $currentUserData['AVATAR'],
							'AVATAR_ID' => $currentUserData['AVATAR_ID'],
							'EXTERNAL_AUTH_ID' => $user->getExternalAuthId(),
							'ONLINE' => $currentUserData['ONLINE']
						]);
						if (!$customData)
						{
							$result = $nullForUnprocessed? null: $currentUserData;
						}
						else
						{
							$result['ID'] = (int)$customData['ID'];
							$result['NAME'] = (string)Im\User::formatFullNameFromDatabase($customData);
							$result['FIRST_NAME'] = (string)Im\User::formatNameFromDatabase($customData);
							$result['LAST_NAME'] = (string)$customData['LAST_NAME'];
							$result['WORK_POSITION'] = (string)$customData['WORK_POSITION'];
							$result['AVATAR'] = (string)$customData['AVATAR'];
							$result['AVATAR_ID'] = (int)$customData['AVATAR_ID'];
							$result['ONLINE'] = (bool)$customData['ONLINE'];
						}
					}
					else
					{
						$result = self::setQueueUserData($lineId, $currentUserData);
						if (!$result)
						{
							$result = $nullForUnprocessed? null: $currentUserData;
						}
					}
				}

				if (!empty($result['AVATAR']))
				{
					$uri = new Uri($result['AVATAR']);
					if(empty($uri->getHost()))
					{
						$result['AVATAR'] = Common::getServerAddress() . $result['AVATAR'];
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the current ID of the open line for the specified session.
	 *
	 * @param $params
	 * @return int
	 */
	public static function getActualLineId($params)
	{
		$lineId = 0;

		if(!empty($params['LINE_ID']))
		{
			$lineId = $params['LINE_ID'];
		}

		if(!empty($params['USER_CODE']))
		{
			$entityIdParts = \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId($params['USER_CODE']);
			$lineId = $entityIdParts['lineId'];

			$raw = SessionTable::getList([
				'select' => ['CONFIG_ID'],
				'filter' => [
					'=USER_CODE' => $params['USER_CODE'],
					'=CLOSED' => 'N'
				],
				'order' => [
					'ID' => 'DESC'
				],
				"cache" => ["ttl" => 3600]
			]);
			if ($session = $raw->fetch())
			{
				if(!empty($session['CONFIG_ID']))
				{
					$lineId = $session['CONFIG_ID'];
				}
			}
		}

		return (int)$lineId;
	}

	/**
	 * Set session queue date to return it to distribution queue.
	 *
	 * @param $sessionId
	 * @param string $reasonReturn
	 */
	public static function returnSessionToQueue($sessionId, string $reasonReturn = self::REASON_DEFAULT): void
	{
		SessionCheckTable::update(
			$sessionId,
			[
				'DATE_QUEUE' => new DateTime(),
				'REASON_RETURN' => $reasonReturn,
			]
		);
	}

	/**
	 * Task allocation dates of sessions that are in the closing state.
	 *
	 * @param $sessionId
	 * @param DateTime $dateQueue
	 * @param string $reasonReturn
	 */
	public static function returnSessionWaitClientToQueue($sessionId, DateTime $dateQueue, $reasonReturn = self::REASON_DEFAULT)
	{
		SessionCheckTable::update(
			$sessionId,
			[
				'DATE_QUEUE' => $dateQueue,
				'REASON_RETURN' => $reasonReturn,
			]
		);
	}

	/**
	 * @param string $reasonReturn
	 * @param $session
	 * @return bool|int
	 */
	public static function sendMessageReturnedSession($reasonReturn = Queue::REASON_DEFAULT, $session)
	{
		$message = '';
		$result = false;

		if(
			$session['OPERATOR_ID'] > 0 &&
			$session['STATUS'] >= Session::STATUS_ANSWER
		)
		{
			switch ($reasonReturn) {
				case self::REASON_OPERATOR_ABSENT:
					$message = Loc::getMessage('IMOL_QUEUE_OPERATOR_VACATION');
					break;
				case self::REASON_OPERATOR_DAY_PAUSE:
				case self::REASON_OPERATOR_DAY_END:
					$message = Loc::getMessage('IMOL_QUEUE_OPERATOR_NONWORKING');
					break;
				case self::REASON_OPERATOR_DELETED:
					$message = Loc::getMessage('IMOL_QUEUE_OPERATOR_DISMISSAL');
					break;
				case self::REASON_REMOVED_FROM_QUEUE:
					$message = Loc::getMessage('IMOL_QUEUE_OPERATOR_REMOVING');
					break;
				case self::REASON_OPERATOR_NOT_AVAILABLE:
					$message = Loc::getMessage('IMOL_QUEUE_OPERATOR_NOT_AVAILABLE');
					break;
				case self::REASON_OPERATOR_OFFLINE:
					$message = Loc::getMessage('IMOL_QUEUE_OPERATOR_OFFLINE');
					break;
			}

			if(!empty($message))
			{
				$messageFields = [
					'TO_CHAT_ID' => $session['CHAT_ID'],
					'MESSAGE' => $message,
					'SYSTEM' => 'Y',
					'RECENT_ADD' => 'N'
				];
				$result = \Bitrix\ImOpenLines\Im::addMessage($messageFields);
			}
		}

		return $result;
	}

	//Operators queue
	/**
	 * This real operator online? That's not a bot, not a user of the connector.
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public static function isRealOperator(int $id): bool
	{
		$result = true;

		if (Loader::includeModule('im'))
		{
			$userIm = Im\User::getInstance($id);

			if(
				$userIm->isConnector() ||
				$userIm->isBot()
			)
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Basic check that the operator is active.
	 *
	 * @param $userId
	 * @param string $isCheckAvailable
	 * @param bool $ignorePause
	 * @return bool|string
	 */
	public static function isOperatorActive($userId, $isCheckAvailable = 'Y', bool $ignorePause = false)
	{
		$result = true;

		if (
			Loader::includeModule('im') &&
			self::isRealOperator($userId)
		)
		{
			if (
				$result === true &&
				!Im\User::getInstance($userId)->isActive()
			)
			{
				$result = self::REASON_OPERATOR_DELETED;
			}

			if(
				$result === true &&
				(string)$isCheckAvailable === 'Y'
			)
			{
				if (
					$result === true &&
					Im\User::getInstance($userId)->isAbsent()
				)
				{
					$result = self::REASON_OPERATOR_ABSENT;
				}

				if($result === true)
				{
					if ((new \Bitrix\ImOpenLines\UserPause((int)$userId))->getStatus())
					{
						$result = self::REASON_OPERATOR_DAY_PAUSE;
					}
					elseif(Config::isTimeManActive())
					{
						$result = self::getActiveStatusByTimeman($userId, $ignorePause);
					}
					else
					{
						$result = self::isOperatorOnline($userId);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Are there any available operators in the line.
	 *
	 * @param $idLine
	 * @param string $isCheckAvailable
	 * @param bool $ignorePause
	 * @return bool
	 */
	public static function isOperatorsActiveLine($idLine, $isCheckAvailable = 'Y', bool $ignorePause = false): bool
	{
		$result = false;

		$res = self::getList([
			'select' => [
				'ID',
				'USER_ID'
			],
			'filter' => [
				'=CONFIG_ID' => $idLine
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			],
		]);

		while($queueUser = $res->fetch())
		{
			if(self::isOperatorActive($queueUser['USER_ID'], $isCheckAvailable, $ignorePause) === true)
			{
				$result = true;

				break;
			}
		}

		return $result;
	}

	/**
	 * Returns whether the operator works according to the working time accounting.
	 *
	 * @param $userId
	 * @param bool $ignorePause
	 * @return bool|string
	 */
	public static function getActiveStatusByTimeman(int $userId, bool $ignorePause = false)
	{
		$result = self::REASON_OPERATOR_DAY_END;

		if ($userId > 0)
		{
			if (Config::isTimeManActive())
			{
				$tmUser = new \CTimeManUser($userId);
				$tmSettings = $tmUser->GetSettings(['UF_TIMEMAN']);
				if (!$tmSettings['UF_TIMEMAN'])
				{
					$result = true;
				}
				else
				{
					$tmUser->GetCurrentInfo(true); // need for reload cache
					if ((string)$tmUser->State() === 'OPENED')
					{
						$result = true;
					}
					elseif((string)$tmUser->State() === 'PAUSED')
					{
						if($ignorePause === true)
						{
							$result = true;
						}
						else
						{
							$result = self::REASON_OPERATOR_DAY_PAUSE;
						}
					}
				}
			}
			else
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Returns the time in a second after which the operator is considered offline.
	 *
	 * @return int
	 */
	public static function getTimeLastActivityOperator()
	{
		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			return 1440;
		}

		return \CUser::getSecondsForLimitOnline();
	}

	/**
	 * This operator online?
	 *
	 * @param $id int The user ID of the operator.
	 * @return bool|string
	 */
	public static function isOperatorOnline($id)
	{
		$result = true;

		if(self::isRealOperator($id))
		{
			if(Loader::includeModule('im'))
			{
				if(!\Bitrix\ImOpenLines\Im::userIsOnline($id))
				{
					$result = self::REASON_OPERATOR_OFFLINE;
				}
			}
			elseif(!\CUser::IsOnLine($id, self::getTimeLastActivityOperator()))
			{
				$result = self::REASON_OPERATOR_OFFLINE;
			}
		}

		return $result;
	}


	/**
	 * Returns true if OpenLine has only one operator.
	 *
	 * @param int $lineId OpenLine(config) ID.
	 * @param int $operatorId The operator ID which we want to check.
	 * @return bool
	 */
	public static function isOperatorSingleInLine(int $lineId, int $operatorId): bool
	{
		if ($lineId > 0)
		{
			$query = new Query(QueueTable::getEntity());
			$query->setSelect(['USER_ID']);
			$query->setFilter(['CONFIG_ID' => $lineId]);
			$query->countTotal(true);
			$count = $query->exec()->getCount();

			if ($count === 1)
			{
				$queue = $query->exec()->fetch();
				if ((int)$queue['USER_ID'] === $operatorId)
				{
					return true;
				}

			}
		}
		return false;
	}

	/**
	 * How many chats can accept this statement.
	 *
	 * @param $userId
	 * @param int $lineId
	 * @param int $maxChat
	 * @param null $typeMaxChat
	 * @return int|mixed
	 */
	public static function getCountFreeSlotOperator($userId, $lineId = 0, $maxChat = 0, $typeMaxChat = null)
	{
		$result = self::getCountFreeSlotsOperators([$userId], $lineId, $maxChat, $typeMaxChat);

		return $result[$userId] ?? Queue::MAX_CHAT;
	}

	public static function getCountFreeSlotsOperators(array $userIds, $lineId = 0, $maxChat = 0, $typeMaxChat = null)
	{
		$results = array_fill_keys($userIds, Queue::MAX_CHAT);

		if (empty($maxChat) || $maxChat > Queue::MAX_CHAT)
		{
			$maxChat = Queue::MAX_CHAT;
		}

		if (Loader::includeModule('im'))
		{
			$recentCounts = RecentTable::getList([
				'select' => ['USER_ID', 'CNT'],
				'filter' => [
					'=USER_ID' => $userIds,
					'=ITEM_TYPE' => IM_MESSAGE_OPEN_LINE
				],
				'runtime' => [
					new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
				],
				'group' => ['USER_ID']
			])->fetchAll();

			foreach ($recentCounts as $recentCount)
			{
				$userId = $recentCount['USER_ID'];
				$countNotCloseGlobal = $recentCount['CNT'];
				$results[$userId] = Queue::MAX_CHAT - $countNotCloseGlobal;
			}
		}

		if (
			!empty($lineId)
			&& is_numeric($lineId)
			&& $lineId > 0
			&& !empty($typeMaxChat)
			&& in_array($typeMaxChat, [Config::TYPE_MAX_CHAT_ANSWERED, Config::TYPE_MAX_CHAT_ANSWERED_NEW, Config::TYPE_MAX_CHAT_CLOSED], true)
		)
		{
			$stopStatus = null;

			if ($typeMaxChat == Config::TYPE_MAX_CHAT_ANSWERED_NEW)
			{
				$stopStatus = Session::STATUS_CLIENT_AFTER_OPERATOR;
			}
			elseif ($typeMaxChat == Config::TYPE_MAX_CHAT_ANSWERED)
			{
				$stopStatus = Session::STATUS_OPERATOR;
			}
			elseif ($typeMaxChat == Config::TYPE_MAX_CHAT_CLOSED)
			{
				$stopStatus = Session::STATUS_WAIT_CLIENT;
			}

			if (!empty($stopStatus))
			{
				$busyCounts = SessionCheckTable::getList([
					'select' => ['OPERATOR_ID' => 'SESSION.OPERATOR_ID', 'CNT'],
					'filter' => [
						'=SESSION.OPERATOR_ID' => $userIds,
						'=SESSION.CONFIG_ID' => $lineId,
						'<SESSION.STATUS' => $stopStatus
					],
					'runtime' => [
						new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
					],
					'group' => ['SESSION.OPERATOR_ID']
				])->fetchAll();

				foreach ($busyCounts as $busyCount)
				{
					$freeSlotRestrictions = $maxChat - $busyCount['CNT'];
					$results[$busyCount['OPERATOR_ID']] = min($freeSlotRestrictions, (int)$results[$busyCount['OPERATOR_ID']]);
				}
			}
		}

		return $results;
	}
}