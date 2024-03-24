<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main\Loader,
	\Bitrix\Main\UserTable,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\Application,
	\Bitrix\Main\Type\DateTime,
	\Bitrix\Im\Model\ChatTable,
	\Bitrix\Main\ORM\Query\Query,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\DB\SqlExpression,
	\Bitrix\Main\Entity\ReferenceField,
	\Bitrix\Main\ORM\Fields\ExpressionField;

Loc::loadMessages(__FILE__);

class Im
{
	/** Time to cache online operator status. */
	const CACHE_TIME_IM_USER_ONLINE = 5;
	/** Path for online operator status cache. */
	const CACHE_DIR_IM_USER_ONLINE = '/imopenlines/im_user_online/';

	/**
	 * @param $fields
	 * @return bool|int
	 */
	public static function addMessage($fields)
	{
		$result = false;

		if(Loader::includeModule('im'))
		{
			$fields['MESSAGE_TYPE'] = IM_MESSAGE_OPEN_LINE;

			$result = \CIMMessenger::Add($fields);
			if (!$result)
			{
				$errorMessage = 'Unknown error';
				if ($e = $GLOBALS['APPLICATION']->GetException())
				{
					$errorMessage = $e->GetString();
				}
				\Bitrix\ImOpenLines\Log::write([$fields, $errorMessage], 'DEBUG SESSION');
			}
		}

		return $result;
	}

	/**
	 * @param $chatId
	 * @param $message
	 * @return bool|int
	 */
	public static function addAutomaticSystemMessage($chatId, $message)
	{
		return self::addMessage([
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => $message,
			'SYSTEM' => 'Y',
			'URL_PREVIEW' => 'N',
			'IMPORTANT_CONNECTOR' => 'Y',
			'NO_SESSION_OL' => 'Y',
			'PARAMS' => [
				'CLASS' => 'bx-messenger-content-item-ol-output',
			]
		]);
	}

	/**
	 * @param $chatId
	 * @param int $timeLimitVote
	 * @return bool|int
	 */
	public static function addCloseVoteMessage($chatId, $timeLimitVote = 0, $userLang = null)
	{
		return self::addMessage([
			'TO_CHAT_ID' => $chatId,
			'MESSAGE' => Loc::getMessage('IMOL_IM_CLOSE_VOTE_MESSAGE_NO_TIME_LEFT', null, $userLang),
			'SYSTEM' => 'Y',
			'IMPORTANT_CONNECTOR' => 'Y',
			'NO_SESSION_OL' => 'Y',
			'RECENT_ADD' => 'N',
			'PARAMS' => [
				'CLASS' => 'bx-messenger-content-item-ol-output',
			]
		]);
	}

	/**
	 * @param $messages
	 * @return array
	 */
	public static function addMessagesNewsletter($messages): array
	{
		$result = array();
		$userCodes = array();

		if (is_array($messages) && Loader::includeModule('im'))
		{
			foreach ($messages as $code => $message)
			{
				$result[$code] = false;
				$userCodes[] = $code;
			}

			$rawChat = ChatTable::getList(array(
				'select' => array('ID', 'ENTITY_ID', 'RECENT_MID' => 'RECENT.ITEM_MID', 'ENTITY_DATA_1'),
				'filter' => array(
					'=ENTITY_TYPE' => 'LINES',
					'=ENTITY_ID' => $userCodes
				),
				'runtime' => array(
					new ReferenceField(
						'RECENT',
						'\Bitrix\Im\Model\RecentTable',
						array(
							'=this.ID' => 'ref.ITEM_ID',
							'=this.AUTHOR_ID' => 'ref.USER_ID',
							'ref.ITEM_TYPE' => new SqlExpression('?i', IM_MESSAGE_OPEN_LINE)
						)
					)
				)
			));

			while($rowChat = $rawChat->fetch())
			{
				//TODO: Replace with the method \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId or \Bitrix\ImOpenLines\Chat::parseLiveChatEntityId
				$chatFieldData = explode('|', $rowChat['ENTITY_DATA_1']);
				$blockTimestamp = (int)$chatFieldData[8];
				if ($blockTimestamp !== 0 && $blockTimestamp < (new DateTime())->getTimestamp())
				{
					continue;
				}
				$fields = $messages[$rowChat['ENTITY_ID']];

				$fields['MESSAGE_TYPE'] = IM_MESSAGE_OPEN_LINE;
				$fields['TO_CHAT_ID'] = $rowChat['ID'];
				$fields['FROM_USER_ID'] = 0;
				$fields['SYSTEM'] = 'Y';
				$fields['SKIP_USER_CHECK'] = 'Y';
				$fields['IMPORTANT_CONNECTOR'] = 'Y';
				$fields['INCREMENT_COUNTER'] = 'N';
				$fields['PUSH'] = 'N';
				if(empty($rowChat['RECENT_MID']))
					$fields['RECENT_ADD'] = 'N';
				else
					$fields['RECENT_ADD'] = 'Y';

				$fields['NO_SESSION_OL'] = 'Y';

				$result[$rowChat['ENTITY_ID']] = \CIMMessenger::Add($fields);
			}
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return bool|int
	 */
	public static function addMessageLiveChat($fields)
	{
		$result = false;

		if(Loader::includeModule('im'))
		{
			$fields['MESSAGE_TYPE'] = IM_MESSAGE_CHAT;

			$result = \CIMMessenger::Add($fields);
			if (!$result)
			{
				$errorMessage = 'Unknown error';
				if ($e = $GLOBALS['APPLICATION']->GetException())
				{
					$errorMessage = $e->GetString();
				}
				\Bitrix\ImOpenLines\Log::write([$fields, $errorMessage], 'DEBUG SESSION');
			}
		}

		return $result;
	}

	/**
	 * @param int $chatId
	 * @return bool
	 */
	public static function chatHide($chatId)
	{
		if (Loader::includeModule('im'))
		{
			return \CIMChat::hide($chatId);
		}
		return false;
	}

	/**
	 * Determining the status of an online operator using the messenger functionality.
	 *
	 * @param $id
	 * @return bool
	 */
	public static function userIsOnline($id): bool
	{
		$result = false;

		if(Loader::includeModule('im'))
		{
			$id = intval($id);
			if ($id > 0)
			{
				$cache = Cache::createInstance();

				if ($cache->initCache(self::CACHE_TIME_IM_USER_ONLINE, $id, self::CACHE_DIR_IM_USER_ONLINE))
				{
					$result = $cache->getVars();
				}
				elseif ($cache->startDataCache())
				{
					$lastActivityDate = Queue::getTimeLastActivityOperator();
					$timeHelper = Application::getConnection()->getSqlHelper()->addSecondsToDateTime('(-'.$lastActivityDate.')');

					$query = new Query(UserTable::getEntity());

					$query->setSelect(['IS_ONLINE_CUSTOM']);

					$query->registerRuntimeField('', new ReferenceField(
						'IM_STATUS',
						'\Bitrix\Im\Model\StatusTable',
						['=ref.USER_ID' => 'this.ID'],
						['join_type'=>'left']
					));

					$query->registerRuntimeField('',
						new ExpressionField(
							'IS_ONLINE_CUSTOM',
							'CASE WHEN %1$s > '.$timeHelper.' AND (%2$s IS NULL OR %1$s > %2$s) THEN \'Y\' ELSE \'N\' END',
							['LAST_ACTIVITY_DATE', 'IM_STATUS.IDLE'])
					);

					$query->setFilter(['ID' => $id]);

					$resultQuery = $query->exec();

					if ($resultQuery->fetch()['IS_ONLINE_CUSTOM'] == 'Y')
					{
						$result = true;
					}

					$cache->endDataCache($result);
				}
			}
		}

		return $result;
	}
}