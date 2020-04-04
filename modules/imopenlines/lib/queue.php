<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Application,
	\Bitrix\Main\Entity\Query,
	\Bitrix\Main\ModuleManager,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Entity\ReferenceField,
	\Bitrix\Main\Entity\ExpressionField;

use \Bitrix\Im\User;

use \Bitrix\ImOpenLines\Model\QueueTable,
	\Bitrix\ImOpenLines\Model\SessionTable;

Loc::loadMessages(__FILE__);

class Queue
{
	const USER_DATA_CACHE_TIME = 86400;

	private $error = null;
	private $id = null;
	private $session = null;
	private $config = null;
	private $chat = null;

	/**
	 * Queue constructor.
	 * @param $session
	 * @param $config
	 * @param $chat
	 */
	public function __construct($session, $config, $chat)
	{
		$this->error = new BasicError(null, '', '');
		$this->session = $session;
		$this->config = $config;
		$this->chat = $chat;
	}

	/**
	 * @param bool $manual
	 * @param int $currentOperator
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getNextUser($manual = false, $currentOperator = 0)
	{
		$result = [
			'RESULT' => false,
			'USER_ID' => 0,
			'USER_LIST' => [],
			//TODO: fix 105666
			'SECOND' => false,
		];

		$firstUserId = 0;
		$firstUpdateId = 0;
		$updateId = 0;

		if (!Loader::includeModule('im'))
		{
			return $result;
		}

		$filter = ['=CONFIG_ID' => $this->config['ID']];

		if ($this->config['QUEUE_TYPE'] == Config::QUEUE_TYPE_STRICTLY)
		{
			$order = ['ID' => 'asc'];
		}
		else
		{
			$order = [
				'LAST_ACTIVITY_DATE' => 'asc',
				'LAST_ACTIVITY_DATE_EXACT' => 'asc'
			];
		}
		$res = self::getList([
			'select' => Array('ID', 'USER_ID', 'IS_ONLINE_CUSTOM'),
			'filter' => $filter,
			'order' => $order,
		]);

		$session = null;
		while($queueUser = $res->fetch())
		{
			if (!User::getInstance($queueUser['USER_ID'])->isActive())
			{
				continue;
			}

			if (User::getInstance($queueUser['USER_ID'])->isAbsent())
			{
				continue;
			}

			if ($this->config['CHECK_ONLINE'] == 'Y' && $queueUser['IS_ONLINE_CUSTOM'] != 'Y')
			{
				continue;
			}

			if ($this->config['TIMEMAN'] == "Y" && !self::getActiveStatusByTimeman($queueUser['USER_ID']))
			{
				continue;
			}

			if($this->config["MAX_CHAT"] > 0 && (empty($currentOperator) || $currentOperator != $queueUser['USER_ID']))
			{
				$filterSession = array(
					'=OPERATOR_ID' => $queueUser['USER_ID'],
					//'!CHECK.SESSION_ID' => null,
					'CONFIG_ID' => $this->config['ID'],
				);

				if($this->config["TYPE_MAX_CHAT"] == Config::TYPE_MAX_CHAT_CLOSED)
				{
					$filterSession['<STATUS'] = 50;
				}
				elseif($this->config["TYPE_MAX_CHAT"] == Config::TYPE_MAX_CHAT_ANSWERED_NEW)
				{
					$filterSession['<STATUS'] = 25;
				}
				else
				{
					$filterSession['<STATUS'] = 40;
				}

				$cntSessions = SessionTable::getList([
					'select' => ['CNT'],
					'filter' => $filterSession,
					'runtime' => [
						new ExpressionField('CNT', 'COUNT(*)')
					]
				])->fetch();

				if($cntSessions["CNT"] >= $this->config["MAX_CHAT"])
				{
					continue;
				}
			}

			if(empty($firstUserId))
			{
				$firstUserId = $queueUser['USER_ID'];
				$firstUpdateId = $queueUser['ID'];
			}

			if($this->session['QUEUE_HISTORY'][$queueUser['USER_ID']] == true)
			{
				continue;
			}

			$result['USER_ID'] = $queueUser['USER_ID'];
			$updateId = $queueUser['ID'];
			$result['RESULT'] = true;

			break;
		}

		if(empty($result['USER_ID']) && !empty($firstUserId))
		{
			$result['USER_ID'] = $firstUserId;
			$updateId = $firstUpdateId;
			$result['RESULT'] = true;
			$result['SECOND'] = true;
		}

		if (!$this->session['JOIN_BOT'] && $updateId > 0)
		{
			QueueTable::update($updateId, ['LAST_ACTIVITY_DATE' => new DateTime(), 'LAST_ACTIVITY_DATE_EXACT' => microtime(true) * 10000]);
		}

		Log::write(['Filter' => $filter, 'Result' => $result], 'GET NEXT USER');

		return $result;
	}

	/**
	 * Check the operator responsible for CRM on the possibility of transfer of chat.
	 *
	 * @param $idUser
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isActiveCrmUser($idUser)
	{
		$result = true;

		if (!Loader::includeModule('im'))
		{
			$result = false;
		}

		if ($result != false && !User::getInstance($idUser)->isActive())
		{
			$result = false;
		}

		if ($result != false && User::getInstance($idUser)->isAbsent())
		{
			$result = false;
		}

		if ($result != false && $this->config['TIMEMAN'] == "Y" && !self::getActiveStatusByTimeman($idUser))
		{
			$result = false;
		}

		Log::write(Array('idUser' => $idUser, 'Result' => $result), 'IS ACTIVE CRM USER');

		return $result;
	}

	public function getQueue()
	{
		if (!Loader::includeModule('im'))
		{
			return null;
		}

		$filter = Array('=CONFIG_ID' => $this->config['ID']);
		$res = self::getList(Array(
			'select' => Array('ID', 'USER_ID'),
			'filter' => $filter
		));
		$result = Array(
			'RESULT' => false,
			'USER_ID' => 0,
			'USER_LIST' => Array()
		);
		$session = null;
		while($queueUser = $res->fetch())
		{
			if (!User::getInstance($queueUser['USER_ID'])->isActive())
			{
				continue;
			}

			$result['RESULT'] = true;
			$result['USER_LIST'][] = $queueUser['USER_ID'];
		}

		Log::write(Array('Filter' => $filter, 'Result' => $result), 'GET ALL QUEUE');

		return $result;
	}

	public static function getActiveStatusByTimeman($userId)
	{
		if ($userId <= 0)
			return false;

		if (\CModule::IncludeModule('timeman'))
		{
			$tmUser = new \CTimeManUser($userId);
			$tmSettings = $tmUser->GetSettings(Array('UF_TIMEMAN'));
			if (!$tmSettings['UF_TIMEMAN'])
			{
				$result = true;
			}
			else
			{
				$tmUser->GetCurrentInfo(true); // need for reload cache

				if ($tmUser->State() == 'OPENED')
				{
					$result = true;
				}
				else
				{
					$result = false;
				}
			}
		}
		else
		{
			$result = true;
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
		return ModuleManager::isModuleInstalled('bitrix24')? 1440: 180;
	}

	public static function getList($params)
	{
		$lastActivityDate = self::getTimeLastActivityOperator();
		$timeHelper = Application::getConnection()->getSqlHelper()->addSecondsToDateTime('(-'.$lastActivityDate.')');

		$query = new Query(QueueTable::getEntity());
		if(Loader::includeModule('im'))
		{
			$query->registerRuntimeField('', new ReferenceField(
				'IM_STATUS',
				'\Bitrix\Im\Model\StatusTable',
				array("=ref.USER_ID" => "this.USER_ID",),
				array("join_type"=>"left")
			));

			$query->registerRuntimeField('', new ExpressionField('IS_ONLINE_CUSTOM', 'CASE WHEN %s > '.$timeHelper.' &&  %s IS NULL THEN \'Y\' ELSE \'N\' END', Array('USER.LAST_ACTIVITY_DATE', 'IM_STATUS.IDLE')));
		}
		else
		{
			$query->registerRuntimeField('', new ExpressionField('IS_ONLINE_CUSTOM', 'CASE WHEN %s > '.$timeHelper.' THEN \'Y\' ELSE \'N\' END', Array('USER.LAST_ACTIVITY_DATE')));
		}

		if (isset($params['select']))
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

		return $query->exec();
	}

	/**
	 * This operator online?
	 *
	 * @param $id The user ID of the operator.
	 * @return bool
	 */
	public static function isOperatorOnline($id)
	{
		return \CUser::IsOnLine($id, self::getTimeLastActivityOperator());
	}

	public function getError()
	{
		return $this->error;
	}

	/**
	 * Return array of user data for current line
	 *
	 * @param $userId
	 * @param $lineId
	 *
	 * @return array|bool|false
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getQueueOperatorData($userId, $lineId)
	{
		$lineId = intval($lineId);
		$userId = intval($userId);
		$queue = false;
		$cacheId = md5(serialize(array($lineId, $userId)));
		$cacheDir = '/imopenlines/queue/';
		$cache = \Bitrix\Main\Application::getInstance()->getCache();
		$taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();

		if ($lineId > 0 && $userId > 0)
		{
			$params = array(
				'select' => array('USER_NAME', 'USER_WORK_POSITION', 'USER_AVATAR', 'USER_AVATAR_ID'),
				'filter' => array(
					'CONFIG_ID' => $lineId,
					'USER_ID' => $userId
				)
			);

			if ($cache->initCache(self::USER_DATA_CACHE_TIME, $cacheId, $cacheDir))
			{
				$queue = $cache->getVars();
			}
			else
			{
				$cache->startDataCache();
				$taggedCache->startTagCache($cacheDir);
				$taggedCache->registerTag(self::getUserCacheTag($userId, $lineId));

				$queue = self::getList($params)->fetch();

				$taggedCache->endTagCache();
				$cache->endDataCache($queue);
			}
		}

		return $queue;
	}

	public static function getUserCacheTag($userId, $lineId)
	{
		return 'QUEUE_USER_DATA_'.$userId.'_'.$lineId;
	}

	/**
	 * Set user data for openlines using line config and queue data
	 *
	 * @param $lineId
	 * @param $userArray
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function setQueueUserData($lineId, $userArray)
	{
		$operatorDataType = Config::operatorDataConfig($lineId);

		if ($operatorDataType == Config::OPERATOR_DATA_QUEUE)
		{
			$userData = self::getQueueOperatorData($userArray['ID'], $lineId);

			if (is_null($userData['USER_NAME']))
			{
				//case for users not from current line
				return false;
			}
			else
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

			if (!is_null($userData['USER_WORK_POSITION']))
			{
				$userArray['WORK_POSITION'] = (string)$userData['USER_WORK_POSITION'];
			}

			if (!is_null($userData['USER_AVATAR']))
			{
				$userArray['AVATAR'] = (string)$userData['USER_AVATAR'];
			}

			if (!is_null($userData['USER_AVATAR_ID']))
			{
				$userArray['AVATAR_ID'] = (int)$userData['USER_AVATAR_ID'];
			}
		}
		elseif ($operatorDataType == Config::OPERATOR_DATA_HIDE)
		{
			$defaultOperatorData = self::getDefaultOperatorData($lineId);
			$userArray = array_merge($userArray, $defaultOperatorData);
		}
		else
		{
			//case for show default user setting
			return false;
		}

		return $userArray;
	}

	/**
	 * Return default operator data using line data and default present
	 *
	 * @param $lineId
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
	 * @param $nullForUnprocessed
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getUserData($lineId, $userId, $nullForUnprocessed = false)
	{
		if ($userId <= 0)
		{
			return null;
		}

		$user = \Bitrix\Im\User::getInstance($userId);
		if (!$user->isExists())
		{
			return null;
		}

		$currentUserData = [
			'ID' => $userId,
			'NAME' => $user->getFullName(false),
			'FIRST_NAME' => $user->getName(false),
			'LAST_NAME' => $user->getLastName(false),
			'WORK_POSITION' => '',
			'GENDER' => $user->getGender(),
			'AVATAR' => $user->getAvatar(),
			'AVATAR_ID' => $user->getAvatarId(),
			'ONLINE' => $user->isOnline()
		];
		if (!empty($result['AVATAR']))
		{
			$currentUserData['AVATAR'] = substr($currentUserData['AVATAR'], 0, 4) == 'http'? $currentUserData['AVATAR']: \Bitrix\ImOpenLines\Common::getServerAddress().$currentUserData['AVATAR'];
		}

		if ($user->isExtranet())
		{
			return $nullForUnprocessed? null: $currentUserData;
		}

		if (function_exists('customImopenlinesOperatorNames'))
		{
			$customData = customImopenlinesOperatorNames($lineId, [
				'ID' => $currentUserData['ID'],
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
				return $nullForUnprocessed? null: $currentUserData;
			}

			$result['ID'] = $customData['ID'];
			$result['NAME'] = (string)\Bitrix\Im\User::formatFullNameFromDatabase($customData);
			$result['FIRST_NAME'] = (string)\Bitrix\Im\User::formatNameFromDatabase($customData);
			$result['LAST_NAME'] = (string)$customData['LAST_NAME'];
			$result['WORK_POSITION'] = (string)$customData['WORK_POSITION'];
			$result['AVATAR'] = (string)$customData['AVATAR'];
			$result['AVATAR_ID'] = (int)$customData['AVATAR_ID'];
			$result['ONLINE'] = (bool)$customData['ONLINE'];
		}
		else
		{
			$result = self::setQueueUserData($lineId, $currentUserData);
			if (!$result)
			{
				return $nullForUnprocessed? null: $currentUserData;
			}
		}

		if (!empty($result['AVATAR']))
		{
			$result['AVATAR'] = substr($result['AVATAR'], 0, 4) == 'http'? $result['AVATAR']: \Bitrix\ImOpenLines\Common::getServerAddress().$result['AVATAR'];
		}

		return $result;
	}
}