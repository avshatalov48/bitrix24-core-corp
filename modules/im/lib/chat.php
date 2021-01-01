<?php
namespace Bitrix\Im;

use Bitrix\Im\Model\BlockUserTable;
use Bitrix\Main\Application,
	Bitrix\Main\Loader;

class Chat
{
	const TYPE_SYSTEM = 'S';
	const TYPE_PRIVATE = 'P';
	const TYPE_OPEN = 'O';
	const TYPE_THREAD = 'T';
	const TYPE_GROUP = 'C';
	const TYPE_OPEN_LINE = 'L';

	const STATUS_UNREAD = 0;
	const STATUS_NOTIFY = 1;
	const STATUS_READ = 2;

	const LIMIT_SEND_EVENT = 30;

	const FILTER_LIMIT = 50;

	public static function getTypes()
	{
		return Array(self::TYPE_GROUP, self::TYPE_OPEN_LINE, self::TYPE_OPEN, self::TYPE_THREAD);
	}

	public static function getType($chatData)
	{
		$messageType = isset($chatData["TYPE"])? $chatData["TYPE"]: $chatData["CHAT_TYPE"];
		$entityType = isset($chatData["ENTITY_TYPE"])? $chatData["ENTITY_TYPE"]: $chatData["CHAT_ENTITY_TYPE"];

		$messageType = trim($messageType);
		$entityType = trim($entityType);

		if ($messageType == IM_MESSAGE_PRIVATE)
		{
			$result = 'private';
		}
		else if (!empty($entityType))
		{
			// convert to camelCase
			$result = str_replace('_', '', lcfirst(ucwords(mb_strtolower($entityType), '_')));
		}
		else
		{
			$result = $messageType == IM_MESSAGE_OPEN? 'open': 'chat';
		}

		return $result;
	}

	public static function getRelation($chatId, $params = [])
	{
		$chatId = intval($chatId);
		if ($chatId <= 0)
		{
			return false;
		}

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();

		$selectFields = '';
		if (isset($params['SELECT']))
		{
			$params['SELECT'][] = 'ID';
			$params['SELECT'][] = 'USER_ID';
			$map = \Bitrix\Im\Model\RelationTable::getMap();
			foreach ($params['SELECT'] as $key => $value)
			{
				if (is_int($key) && isset($map[$value]))
				{
					$selectFields .= "R.{$value}, ";
					unset($map[$value]);
				}
				else if (!is_int($key) && isset($map[$key]))
				{
					$value = (string)$value;
					$selectFields .= "R.{$key} `{$connection->getSqlHelper()->forSql($value)}`, ";
					unset($map[$value]);
				}
			}
		}
		if (!$selectFields)
		{
			$selectFields = 'R.*, ';
		}

		$withUserFields = false;
		if (isset($params['USER_DATA']) && $params['USER_DATA'] == 'Y')
		{
			$withUserFields = true;
			$list = Array('ACTIVE', 'EXTERNAL_AUTH_ID');
			foreach ($list as $key)
			{
				$selectFields .= "U.{$key} USER_DATA_{$key}, ";
			}
		}

		$skipConnectorRelation = $params['SKIP_CONNECTOR'] == 'Y';

		$whereFields = '';
		if (isset($params['FILTER']))
		{
			$map = \Bitrix\Im\Model\RelationTable::getMap();
			foreach ($params['FILTER'] as $key => $value)
			{
				if (!isset($map[$key]))
				{
					continue;
				}

				if (is_int($value))
				{
				}
				else if (is_bool($value))
				{
					$value = $value? "'Y'": "'N'";
				}
				else if (is_string($value))
				{
					$value = "'{$connection->getSqlHelper()->forSql($value)}'";
				}
				else
				{
					continue;
				}

				$whereFields .= " AND R.{$key} = {$value}";
			}
		}

		$skipUnmodifiedRecords = false;
		if (isset($params['SKIP_RELATION_WITH_UNMODIFIED_COUNTERS']) && $params['SKIP_RELATION_WITH_UNMODIFIED_COUNTERS'] == 'Y')
		{
			$skipUnmodifiedRecords = true;
		}

		$sqlSelectCounter = 'R.LAST_ID, R.COUNTER, R.COUNTER PREVIOUS_COUNTER';

		$customCounter = false;
		$customMaxId = 0;
		$customMinId = 0;
		$counters = [];

		if (isset($params['REAL_COUNTERS']) && $params['REAL_COUNTERS'] != 'N' || $skipUnmodifiedRecords)
		{
			if (is_array($params['REAL_COUNTERS']) && isset($params['REAL_COUNTERS']['LAST_ID']))
			{
				$sqlSelectCounter = "R.COUNTER PREVIOUS_COUNTER, (
					SELECT COUNT(1) FROM b_im_message M WHERE M.CHAT_ID = R.CHAT_ID AND M.ID > ".intval($params['REAL_COUNTERS']['LAST_ID'])."
				) COUNTER";
			}
			else
			{
				$customCounter = true;
				$query = \Bitrix\Main\Application::getInstance()->getConnection()->query("
					SELECT ID FROM b_im_message M WHERE M.CHAT_ID = {$chatId} ORDER BY ID DESC LIMIT 100
				");
				while ($row = $query->fetch())
				{
					if (!$customMaxId)
					{
						$customMaxId = $row['ID'];
					}
					$counters[$row['ID']] = count($counters);
					$customMinId = $row['ID'];
				}
			}
		}

		$sql = "
			SELECT {$selectFields} {$sqlSelectCounter}
			FROM b_im_relation R
			".($withUserFields && !$skipConnectorRelation? "LEFT JOIN b_user U ON R.USER_ID = U.ID": "")."
			".($skipConnectorRelation? "INNER JOIN b_user U ON R.USER_ID = U.ID AND (EXTERNAL_AUTH_ID != 'imconnector' OR EXTERNAL_AUTH_ID IS NULL)": "")."
			WHERE R.CHAT_ID = {$chatId} {$whereFields}
		";
		$relations = array();
		$query = \Bitrix\Main\Application::getInstance()->getConnection()->query($sql);
		while ($row = $query->fetch())
		{
			if ($customCounter)
			{
				if (isset($counters[$row['LAST_ID']]))
				{
					$row['COUNTER'] = $counters[$row['LAST_ID']];
				}
				else if ($row['LAST_ID'] < $customMinId)
				{
					$row['COUNTER'] = count($counters);
				}
				else if ($row['LAST_ID'] > $customMaxId)
				{
					$row['COUNTER'] = 0;
				}
			}
			else
			{
				$row['COUNTER'] = $row['COUNTER'] > 99? 100: intval($row['COUNTER']);
			}

			$row['PREVIOUS_COUNTER'] = intval($row['PREVIOUS_COUNTER']);

			if ($skipUnmodifiedRecords && $row['COUNTER'] == $row['PREVIOUS_COUNTER'])
			{
				continue;
			}

			foreach ($row as $key => $value)
			{
				if (mb_strpos($key, 'USER_DATA_') === 0)
				{
					$row['USER_DATA'][mb_substr($key, 10)] = $value;
					unset($row[$key]);
				}
			}

			$relations[$row['USER_ID']] = $row;
		}

		return $relations;
	}

	public static function mute($chatId, $action, $userId = null)
	{
		$userId = \Bitrix\Im\Common::getUserId($userId);
		if (!$userId)
		{
			return false;
		}

		$chatId = intval($chatId);
		if (!$chatId)
		{
			return false;
		}

		$action = $action === true? 'Y': 'N';

		$relation = self::getRelation($chatId, Array(
			'SELECT' => Array('ID', 'NOTIFY_BLOCK'),
			'FILTER' => Array(
				'USER_ID' => $userId
			),
		));
		if (!$relation)
		{
			return false;
		}

		if ($relation[$userId]['NOTIFY_BLOCK'] == $action)
		{
			return true;
		}

		\Bitrix\Im\Model\RelationTable::update($relation[$userId]['ID'], array('NOTIFY_BLOCK' => $action));

		Recent::clearCache($userId);

		if (\Bitrix\Main\Loader::includeModule('pull'))
		{
			\Bitrix\Pull\Event::add($userId, Array(
				'module_id' => 'im',
				'command' => 'chatMuteNotify',
				'params' => Array(
					'dialogId' => 'chat'.$chatId,
					'mute' => $action == 'Y'
				),
				'extra' => \Bitrix\Im\Common::getPullExtra()
			));
		}

		return true;
	}

	public static function getMessageCount($chatId, $userId = null)
	{
		$chatId = intval($chatId);
		if (!$chatId)
		{
			return false;
		}

		$userId = \Bitrix\Im\Common::getUserId($userId);
		if (!$userId)
		{
			return false;
		}

		$relationData = \Bitrix\Im\Model\RelationTable::getList(Array(
			'select' => Array('START_ID'),
			'filter' => Array('=CHAT_ID' => $chatId, '=USER_ID' => $userId)
		))->fetch();

		if (!$relationData || $relationData['START_ID'] == 0)
		{
			$counter = \Bitrix\Im\Model\MessageTable::getList(array(
				'filter' => Array('CHAT_ID' => $chatId),
				'select' => array("CNT" => new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
			))->fetch();
		}
		else
		{
			$counter = \Bitrix\Im\Model\MessageTable::getList(array(
				'filter' => Array('CHAT_ID' => $chatId, '>=ID' => $relationData['START_ID']),
				'select' => array("CNT" => new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
			))->fetch();
		}

		return $counter && $counter['CNT'] > 0? intval($counter['CNT']): 0;
	}

	public static function hasAccess($chatId)
	{
		$chatId = intval($chatId);
		if (!$chatId)
		{
			return false;
		}

		return \Bitrix\Im\Dialog::hasAccess('chat'.$chatId);
	}

	/**
	 * @param $chatId
	 * @param null $userId
	 * @param array $options
	 * @return array|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMessages($chatId, $userId = null, $options = Array())
	{
		$userId = \Bitrix\Im\Common::getUserId($userId);
		if (!$userId)
		{
			return false;
		}

		$chatData = \Bitrix\Im\Model\ChatTable::getList(Array(
			'select' => Array(
				'CHAT_ID' => 'ID',
				'CHAT_TYPE' => 'TYPE',
				'CHAT_ENTITY_TYPE' => 'ENTITY_TYPE',
				'CHAT_ENTITY_ID' => 'ENTITY_ID',
				'RELATION_USER_ID' => 'RELATION.USER_ID',
				'RELATION_START_ID' => 'RELATION.START_ID',
				'RELATION_LAST_ID' => 'RELATION.LAST_ID',
				'RELATION_STATUS' => 'RELATION.STATUS',
				'RELATION_COUNTER' => 'RELATION.COUNTER'
			),
			'filter' => Array('=ID' => $chatId),
			'runtime' => Array(
				new \Bitrix\Main\Entity\ReferenceField(
					'RELATION',
					'\Bitrix\Im\Model\RelationTable',
					array(
						"=ref.CHAT_ID" => "this.ID",
						"=ref.USER_ID" => new \Bitrix\Main\DB\SqlExpression('?', $userId)
					),
					array("join_type"=>"LEFT")
				)
			)
		))->fetch();
		if (!$chatData)
		{
			return false;
		}

		$chatData['RELATION_START_ID'] = intval($chatData['RELATION_START_ID']);
		$chatData['RELATION_LAST_ID'] = intval($chatData['RELATION_LAST_ID']);

		if (isset($options['LIMIT']))
		{
			$options['LIMIT'] = intval($options['LIMIT']);
			$limit = $options['LIMIT'] >= 50? 50: $options['LIMIT'];
		}
		else
		{
			$limit = 50;
		}

		$filter = Array(
			'=CHAT_ID' => $chatId
		);

		$fileSort = 'ASC';
		$startFromUnread = false;
		if (
			!isset($options['LAST_ID']) && !isset($options['FIRST_ID'])
			&& $chatData['RELATION_STATUS'] != \Bitrix\Im\Chat::STATUS_READ
			&& $chatData['RELATION_COUNTER'] > $limit
		)
		{
			$startFromUnread = true;
			$options['FIRST_ID'] = $chatData['RELATION_LAST_ID'];
		}

		if (isset($options['FIRST_ID']))
		{
			$order = array();

			if ($chatData['RELATION_START_ID'] > 0 && intval($options['FIRST_ID']) < $chatData['RELATION_START_ID'])
			{
				$filter['>=ID'] = $chatData['RELATION_START_ID'];
			}
			else
			{
				if (intval($options['FIRST_ID']) > 0)
				{
					$filter['>ID'] = $options['FIRST_ID'];
				}
			}
		}
		else
		{
			$fileSort = 'DESC';
			$order = Array('CHAT_ID' => 'ASC', 'ID' => 'DESC');

			if ($chatData['RELATION_START_ID'] > 0)
			{
				$filter['>=ID'] = $chatData['RELATION_START_ID'];
			}

			if (isset($options['LAST_ID']) && intval($options['LAST_ID']) > 0)
			{
				$filter['<ID'] = intval($options['LAST_ID']);
			}
		}

		$orm = \Bitrix\Im\Model\MessageTable::getList(array(
			'filter' => $filter,
			'select' => Array('ID', 'AUTHOR_ID', 'DATE_CREATE', 'NOTIFY_EVENT', 'MESSAGE'),
			'order' => $order,
			'limit' => $limit
		));

		$users = Array();

		$userOptions = Array();
		if ($options['JSON'] == 'Y')
		{
			$userOptions['JSON'] = 'Y';
		}
		if ($chatData['CHAT_ENTITY_TYPE'] == 'LIVECHAT')
		{
			[$lineId] = explode('|', $chatData['CHAT_ENTITY_ID']);
			$userOptions['LIVECHAT'] = $lineId;
			$userOptions['USER_CODE'] = 'livechat|' . $lineId . '|' . $chatData['CHAT_ID'] . '|' . $userId;
		}

		$messages = Array();
		while($message = $orm->fetch())
		{
			if ($message['NOTIFY_EVENT'] == 'private_system')
			{
				$message['AUTHOR_ID'] = 0;
			}

			$messages[$message['ID']] = Array(
				'ID' => (int)$message['ID'],
				'CHAT_ID' => (int)$chatId,
				'AUTHOR_ID' => (int)$message['AUTHOR_ID'],
				'DATE' => $message['DATE_CREATE'],
				'TEXT' => (string)$message['MESSAGE'],
				'UNREAD' => $chatData['RELATION_USER_ID'] > 0 && $chatData['RELATION_LAST_ID'] < $message['ID']
			);
			if ($message['AUTHOR_ID'] && !isset($users[$message['AUTHOR_ID']]))
			{
				$users[$message['AUTHOR_ID']] = User::getInstance($message['AUTHOR_ID'])->getArray($userOptions);
			}
			if ($options['CONVERT_TEXT'] == 'Y')
			{
				$messages[$message['ID']]['TEXT_CONVERTED'] = \Bitrix\Im\Text::parse($message['MESSAGE']);
			}
		}

		$params = \CIMMessageParam::Get(array_keys($messages));

		$fileIds = Array();
		foreach ($params as $messageId => $param)
		{
			$messages[$messageId]['PARAMS'] = empty($param)? null: $param;

			if (isset($param['FILE_ID']))
			{
				foreach ($param['FILE_ID'] as $fileId)
				{
					$fileIds[$fileId] = $fileId;
				}
			}

			if (is_array($param['CHAT_USER']) > 0)
			{
				foreach ($param['CHAT_USER'] as $paramsUserId)
				{
					$users[$paramsUserId] = User::getInstance($paramsUserId)->getArray($userOptions);
				}
			}
		}

		$messages = \CIMMessageLink::prepareShow($messages, $params);

		$files = \CIMDisk::GetFiles($chatId, $fileIds);

		$result = Array(
			'CHAT_ID' => (int)$chatId,
			'MESSAGES' => $messages,
			'USERS' => array_values($users),
			'FILES' => array_values($files),
		);

		if (count($files) && $fileSort == 'DESC')
		{
			$result['FILES'] = array_reverse($result['FILES']);
		}

		if ($startFromUnread)
		{
			$result['MESSAGES'] = array_reverse($result['MESSAGES']);
		}

		if ($options['JSON'])
		{
			foreach ($result['MESSAGES'] as $key => $value)
			{
				if ($value['DATE'] instanceof \Bitrix\Main\Type\DateTime)
				{
					$result['MESSAGES'][$key]['DATE'] = date('c', $value['DATE']->getTimestamp());
				}

				if (isset($value['PARAMS']['CHAT_LAST_DATE']) && $value['PARAMS']['CHAT_LAST_DATE'] instanceof \Bitrix\Main\Type\DateTime)
				{
					$result['MESSAGES'][$key]['PARAMS']['CHAT_LAST_DATE'] = date('c', $value['PARAMS']['CHAT_LAST_DATE']->getTimestamp());
				}

				$result['MESSAGES'][$key] = array_change_key_case($result['MESSAGES'][$key], CASE_LOWER);
			}
			$result['MESSAGES'] = array_values($result['MESSAGES']);

			foreach ($result['FILES'] as $key => $value)
			{
				if ($value['date'] instanceof \Bitrix\Main\Type\DateTime)
				{
					$result['FILES'][$key]['date'] = date('c', $value['date']->getTimestamp());
				}

				foreach (['urlPreview', 'urlShow', 'urlDownload'] as $field)
				{
					$url = $result['FILES'][$key][$field];
					if (is_string($url) && $url && mb_strpos($url, 'http') !== 0)
					{
						$result['FILES'][$key][$field] = \Bitrix\Im\Common::getPublicDomain().$url;
					}
				}

			}

			$result = array_change_key_case($result, CASE_LOWER);
		}

		return $result;
	}

	public static function getUsers($chatId, $options = [])
	{
		$users = [];
		$externalTypes = \Bitrix\Main\UserTable::getExternalUserTypes();

		$relations = self::getRelation($chatId, ['SELECT' => ['ID', 'USER_ID']]);
		foreach ($relations as $user)
		{
			$instance = \Bitrix\Im\User::getInstance($user['USER_ID']);
			if (!$instance->isExists() || !$instance->isActive())
			{
				continue;
			}

			if (
				$options['SKIP_EXTERNAL'] &&
				in_array($instance->getExternalAuthId(), $externalTypes, true)
			)
			{
				continue;
			}

			$users[] = $instance->getArray(Array('JSON' => $options['JSON'] === 'Y'? 'Y': 'N'));
		}

		return $users;
	}

	/**
	 * @param $id
	 * @param array $params
	 * @return array|bool|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getById($id, $params = array())
	{
		$userId = \Bitrix\Im\Common::getUserId();
		if (!$userId)
		{
			return false;
		}

		$chats = self::getList(Array(
			'FILTER' => Array('ID' => $id),
			'SKIP_ACCESS_CHECK' => $params['CHECK_ACCESS'] === 'Y'? 'N': 'Y',
 		));
		if ($chats)
		{
			$chat = $chats[0];
		}
		else
		{
			return false;
		}

		if (isset($params['LOAD_READED']) && $params['LOAD_READED'])
		{
			$userOptions = [];
			if ($chat['ENTITY_TYPE'] == 'LIVECHAT')
			{
				[$lineId] = explode('|', $chat['CHAT_ENTITY_ID']);
				$userOptions['LIVECHAT'] = $lineId;
				$userOptions['USER_CODE'] = 'livechat|' . $lineId . '|' . $id . '|' . $userId;
			}

			$relations = self::getRelation($id);

			$chat['READED_LIST'] = [];
			$chat['MANAGER_LIST'] = [];
			foreach ($relations as $relation)
			{
				if (
					$relation['USER_ID'] != $userId
					&& $relation['STATUS'] == self::STATUS_READ
					&& \Bitrix\Im\User::getInstance($relation['USER_ID'])->isActive()
				)
				{
					$user = \Bitrix\Im\User::getInstance($relation['USER_ID'])->getArray($userOptions);
					$chat['READED_LIST'][] = [
						'USER_ID' => (int)$relation['USER_ID'],
						'USER_NAME' => $user['NAME'],
						'MESSAGE_ID' => (int)$relation['LAST_ID'],
						'DATE' => $relation['LAST_READ'],
					];
				}

				if ($relation['MANAGER'] === 'Y')
				{
					$chat['MANAGER_LIST'][] = (int)$relation['USER_ID'];
				}
			}
		}

		if ($params['JSON'])
		{
			$chat = self::toJson($chat);
		}

		return $chat;
	}

	public static function getList($params = array())
	{
		$params = is_array($params)? $params: Array();

		if (!isset($params['CURRENT_USER']) && is_object($GLOBALS['USER']))
		{
			$params['CURRENT_USER'] = $GLOBALS['USER']->GetID();
		}

		$params['CURRENT_USER'] = intval($params['CURRENT_USER']);

		$params['SKIP_ACCESS_CHECK'] = $params['SKIP_ACCESS_CHECK'] === 'Y'? 'Y': 'N';

		$userId = $params['CURRENT_USER'];
		if ($userId <= 0)
		{
			return false;
		}

		$enableLimit = false;
		if (isset($params['OFFSET']))
		{
			$filterLimit = intval($params['LIMIT']);
			$filterLimit = $filterLimit <= 0? self::FILTER_LIMIT: $filterLimit;

			$filterOffset = intval($params['OFFSET']);

			$enableLimit = true;
		}
		else
		{
			$filterLimit = false;
			$filterOffset = false;
		}

		$ormParams = self::getListParams($params);
		if (!$ormParams)
		{
			return false;
		}
		if ($enableLimit)
		{
			$ormParams['offset'] = $filterOffset;
			$ormParams['limit'] = $filterLimit;
		}
		if (isset($params['ORDER']))
		{
			$ormParams['order'] = $params['ORDER'];
		}

		$generalChatId = \CIMChat::GetGeneralChatId();

		$orm = \Bitrix\Im\Model\ChatTable::getList($ormParams);
		$chats = array();
		while ($row = $orm->fetch())
		{
			$avatar = \CIMChat::GetAvatarImage($row['AVATAR'], 200, false);
			$color = $row['COLOR'] <> ''? Color::getColor($row['COLOR']): Color::getColorByNumber($row['ID']);

			$chatType = \Bitrix\Im\Chat::getType($row);

			if ($generalChatId == $row['ID'])
			{
				$row["ENTITY_TYPE"] = 'GENERAL';
			}

			$muteList = Array();
			if ($row['RELATION_NOTIFY_BLOCK'] == 'Y')
			{
				$muteList[] = (int)$row['RELATION_USER_ID'];
			}

			$counter = (int)$row['RELATION_COUNTER'];
			$userCounter = (int)$row['USER_COUNT'];
			$unreadId = (int)$row['RELATION_UNREAD_ID'];
			$unreadLastId = (int)$row['LAST_MESSAGE_ID'];

			$publicOption = '';
			if ($row['ALIAS_NAME'])
			{
				$publicOption = [
					'code' => $row['ALIAS_NAME'],
					'link' => Alias::getPublicLink($row['ENTITY_TYPE'], $row['ALIAS_NAME'])
				];
			}

			$chats[] = Array(
				'ID' => (int)$row['ID'],
				'NAME' => $row['TITLE'],
				'OWNER' => (int)$row['AUTHOR_ID'],
				'EXTRANET' => $row['EXTRANET'] == 'Y',
				'AVATAR' => $avatar,
				'COLOR' => $color,
				'TYPE' => $chatType,
				'COUNTER' => $counter,
				'USER_COUNTER' => $userCounter,
				'UNREAD_ID' => $unreadId,
				'UNREAD_LAST_ID' => $unreadLastId,
				'DISK_FOLDER_ID' => (int)$row['DISK_FOLDER_ID'],
				'ENTITY_TYPE' => (string)$row['ENTITY_TYPE'],
				'ENTITY_ID' => (string)$row['ENTITY_ID'],
				'ENTITY_DATA_1' => (string)$row['ENTITY_DATA_1'],
				'ENTITY_DATA_2' => (string)$row['ENTITY_DATA_2'],
				'ENTITY_DATA_3' => (string)$row['ENTITY_DATA_3'],
				'MUTE_LIST' => $muteList,
				'DATE_CREATE' => $row['DATE_CREATE'],
				'MESSAGE_TYPE' => $row["TYPE"],
				'PUBLIC' => $publicOption,
			);
		}

		if ($params['JSON'])
		{
			$chats = self::toJson($chats);
		}

		return $chats;
	}

	public static function getListParams($params)
	{
		if (!isset($params['CURRENT_USER']) && is_object($GLOBALS['USER']))
		{
			$params['CURRENT_USER'] = $GLOBALS['USER']->GetID();
		}

		$params['CURRENT_USER'] = intval($params['CURRENT_USER']);

		$userId = $params['CURRENT_USER'];
		if ($userId <= 0)
		{
			return null;
		}

		$filter = [];
		$runtime = [];

		if (isset($params['FILTER']['ID']))
		{
			$filter['=ID'] = $params['FILTER']['ID'];
		}
		else if (isset($params['FILTER']['SEARCH']))
		{
			$find = (string)$params['FILTER']['SEARCH'];

			$helper = Application::getConnection()->getSqlHelper();
			if (Model\ChatIndexTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT'))
			{
				$find = trim($find);
				$find = \Bitrix\Main\Search\Content::prepareStringToken($find);

				if (\Bitrix\Main\Search\Content::canUseFulltextSearch($find, \Bitrix\Main\Search\Content::TYPE_MIXED))
				{
					$filter['*INDEX.SEARCH_CONTENT'] = $find;
				}
				else
				{
					return null;
				}
			}
			else
			{
				if (mb_strlen($find) < 3)
				{
					return null;
				}

				$filter['%=INDEX.SEARCH_TITLE'] = $helper->forSql($find).'%';
			}
		}

		if ($params['SKIP_ACCESS_CHECK'] === 'Y')
		{
			// do nothing
		}
		else if (
			User::getInstance($params['CURRENT_USER'])->isExtranet()
			|| User::getInstance($params['CURRENT_USER'])->isBot()
		)
		{
			$filter['=TYPE'] = [
				self::TYPE_OPEN,
				self::TYPE_GROUP,
				self::TYPE_THREAD,
				self::TYPE_PRIVATE
			];
			if (User::getInstance($params['CURRENT_USER'])->isBot())
			{
				$filter['=TYPE'][] = self::TYPE_OPEN_LINE;
			}
			$filter['=RELATION.USER_ID'] = $params['CURRENT_USER'];
		}
		else
		{
			$filter[] = [
				'LOGIC' => 'OR',
				[
					'=TYPE' => self::TYPE_OPEN,
				],
				[
					'=TYPE' => self::TYPE_GROUP,
					'=RELATION.USER_ID' => $params['CURRENT_USER']
				],
				[
					'=TYPE' => self::TYPE_THREAD,
					'=RELATION.USER_ID' => $params['CURRENT_USER']
				],
				[
					'=TYPE' => self::TYPE_PRIVATE,
					'=RELATION.USER_ID' => $params['CURRENT_USER']
				],
				[
					'=TYPE' => self::TYPE_OPEN_LINE,
					'=RELATION.USER_ID' => $params['CURRENT_USER']
				],
			];
		}

		$runtime[] = new \Bitrix\Main\Entity\ReferenceField(
			'RELATION',
			'Bitrix\Im\Model\RelationTable',
			array(
				"=ref.CHAT_ID" => "this.ID",
				"=ref.USER_ID" => new \Bitrix\Main\DB\SqlExpression('?', $params['CURRENT_USER']),
			),
			array("join_type"=>"LEFT")
		);

		return [
			'select' => [
				'*',
				'RELATION_USER_ID' => 'RELATION.USER_ID',
				'RELATION_NOTIFY_BLOCK' => 'RELATION.NOTIFY_BLOCK',
				'RELATION_COUNTER' => 'RELATION.COUNTER',
				'RELATION_LAST_ID' => 'RELATION.LAST_ID',
				'RELATION_STATUS' => 'RELATION.STATUS',
				'RELATION_UNREAD_ID' => 'RELATION.UNREAD_ID',
				'ALIAS_NAME' => 'ALIAS.ALIAS',
			],
			'filter' => $filter,
			'runtime' => $runtime
		];
	}

	/**
	 * Makes user dialog relation visible in left bar and return this new relation
	 *
	 * @param $dialogId
	 * @param $userId
	 *
	 * @return array|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function makeRelationShow($dialogId, $userId)
	{
		$relationList = self::getRelation($dialogId);
		$currentRelation = $relationList[$userId];
		unset($relationList[$userId]);
		$chat = \Bitrix\Im\Model\ChatTable::getById($dialogId)->fetch();
		//for compatibility with CIMChat::AddUser logic we make this checks
		if (intval($currentRelation['UNREAD_ID']) <= 0 || empty($currentRelation))
		{
			$relation = [
				'CHAT_ID' => $dialogId,
				'MESSAGE_TYPE' => $chat['TYPE'],
				'USER_ID' => $userId,
				'LAST_ID' => $chat['LAST_MESSAGE_ID'],
				'LAST_SEND_ID' => $chat['LAST_MESSAGE_ID'],
				'STATUS' => IM_CALL_STATUS_WAIT,
				'MESSAGE_STATUS' => IM_MESSAGE_STATUS_RECEIVED,
				'UNREAD_ID' => $chat['LAST_MESSAGE_ID'],
				'COUNTER' => 1
			];

			foreach ($relationList as $relationItem)
			{
				if ($relationItem['LAST_ID'] < $relation['LAST_ID'] && intval($relationItem['LAST_ID']) > 0)
				{
					$relation['LAST_ID'] = $relationItem['LAST_ID'];
					$relation['UNREAD_ID'] = $relation['LAST_ID'];
					break;
				}
			}

			//Condition for case when empty relation last id
			if ($relation['LAST_ID'] == $chat['LAST_MESSAGE_ID'])
			{
				$message = \Bitrix\Im\Model\MessageTable::getList(
					array(
						'select' => array('ID'),
						'order' => array('ID' => 'DESC'),
						'filter' => array('CHAT_ID' => $relation['CHAT_ID'], '<ID' => $relation['LAST_ID']),
						'limit' => 1
					)
				)->fetch();

				if (intval($message['ID']) > 0)
				{
					$relation['LAST_ID'] = $message['ID'];
					$relation['UNREAD_ID'] = $message['ID'];
				}
			}

			if (empty($currentRelation['ID']))
			{
				$orm = RelationTable::add($relation);
				$relationId = intval($orm->getId());
				if ($relationId > 0)
				{
					$relation['ID'] = $relationId;
				}
			}
			else
			{
				$orm = RelationTable::update($currentRelation['ID'], $relation);
				if ($orm->isSuccess())
				{
					$relation['ID'] = $currentRelation['ID'];
				}
			}
		}
		else
		{
			$relation = $currentRelation;
		}

		if (!empty($relation['ID']) &&
			$relation['MESSAGE_TYPE'] == self::TYPE_OPEN_LINE &&
			$chat['ENTITY_TYPE'] == 'LINES' &&
			Loader::includeModule('imopenlines')
		)
		{
			$session = new \Bitrix\ImOpenLines\Session();
			$resultLoad =$session->load(
				array(
					'USER_CODE' => $chat['ENTITY_ID'],
					'SKIP_CREATE' => 'Y'
				)
			);

			if ($resultLoad)
			{
				$relation['PARAMS']['SESSION_ID'] = $session->getData('ID');
				//$relation['LAST_ID'] = $session->getData('START_ID');
			}
		}

		return $relation;
	}

	public static function toJson($array)
	{
		return \Bitrix\Im\Common::toJson($array, false);
	}

	public static function isUserInChat($chatId, $userId = 0) : bool
	{
		if ($userId === 0)
		{
			$userId = \Bitrix\Im\Common::getUserId();
		}

		if (!$userId)
		{
			return false;
		}

		$result = RelationTable::getList(
			[
				'select' => ["ID"],
				'filter' => [
					'=USER_ID' => $userId,
					'=CHAT_ID' => $chatId
				]
			]
		)->fetch();

		return (bool)$result['ID'];
	}

	public static function isUserKickedFromChat($chatId, $userId = 0) : bool
	{
		if ($userId === 0)
		{
			$userId = \Bitrix\Im\Common::getUserId();
		}

		if (!$userId)
		{
			return false;
		}

		$result = BlockUserTable::getList(
			[
				'select' => ["ID"],
				'filter' => [
					'=USER_ID' => $userId,
					'=CHAT_ID' => $chatId
				]
			]
		)->fetch();

		return (bool)$result['ID'];
	}
}