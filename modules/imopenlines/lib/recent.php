<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Im\Color;
use Bitrix\Im\Model\LinkReminderTable;
use Bitrix\Im\Model\MessageParamTable;
use Bitrix\Im\Settings;
use Bitrix\Im\Text;
use Bitrix\Im\V2\Chat\OpenLineChat;
use Bitrix\Im\V2\Message;
use Bitrix\Im\V2\Message\Params;
use Bitrix\Im\V2\Entity\File\FileCollection;
use Bitrix\Im\V2\Entity\File\FileItem;
use Bitrix\Im\V2\Message\CounterService;
use Bitrix\ImOpenLines\Model\RecentTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\DB\SqlExpression;

class Recent
{
	public static function setRecent(
		$userId,
		$chatId,
		$messageId,
		$sessionId
	): void
	{
		$connection = \Bitrix\Main\Application::getInstance()->getConnection();
		$merge = $connection->getSqlHelper()->prepareMerge(
			RecentTable::getTableName(),
			['USER_ID', 'CHAT_ID'],
			[
				'USER_ID' => $userId,
				'CHAT_ID' => $chatId,
				'MESSAGE_ID' => $messageId,
				'SESSION_ID' => $sessionId,
				'DATE_CREATE' => new DateTime(),
			],
			[
				'MESSAGE_ID' => $messageId,
				'SESSION_ID' => $sessionId,
				'DATE_CREATE' => new DateTime(),
			]
		);
		if ($merge && $merge[0] != "")
		{
			$connection->query($merge[0]);
		}

		if (Loader::includeModule('im'))
		{
			CounterService::clearCache($userId);
		}
	}

	public static function removeRecent(int $userId, int $chatId): Result
	{
		$result = RecentTable::delete([
			'USER_ID' => $userId,
			'CHAT_ID' => $chatId
		]);

		if (Loader::includeModule('im'))
		{
			CounterService::clearCache($userId);
		}

		return $result;
	}

	/**
	 * @param int|null $userId
	 * @param array $options
	 *
	 * @return array|false
	 */
	public static function getRecent(?int $userId = null, array $options = [])
	{
		$userId = \Bitrix\Im\Common::getUserId($userId);
		if (!$userId)
		{
			return false;
		}

		$recentRows = RecentTable::getList([
			'select' => self::getSelect(),
			'filter' => [
				'=USER_ID' => $userId
			],
			'runtime' => self::getRuntime()
		])->fetchAll();

		$recentRows = self::prepareRows($recentRows, $userId);
		foreach ($recentRows as $row)
		{
			$id = 'chat' . $row['CHAT_ID'];
			if (isset($result[$id]))
			{
				continue;
			}

			$item = self::formatRow($row);
			$result[$id] = $item;
		}
		$result = array_values($result ?? []);

		\Bitrix\Main\Type\Collection::sortByColumn(
			$result,
			['PINNED' => SORT_DESC, 'MESSAGE' => SORT_DESC, 'ID' => SORT_DESC],
			[
				'ID' => function($row) {
					return $row;
				},
				'MESSAGE' => function($row) {
					return $row['DATE'] instanceof \Bitrix\Main\Type\DateTime ? $row['DATE']->getTimeStamp() : 0;
				},
			]
		);

		if (isset($options['JSON']) && $options['JSON'])
		{
			foreach ($result as $index => $item)
			{
				$result[$index] = self::jsonRow($item);
			}
		}

		if (isset($options['ONLY_IN_QUEUE']) && $options['ONLY_IN_QUEUE'])
		{
			return $result;
		}

		$imRecent = \Bitrix\Im\Recent::get(
			$userId,
			[
				'ONLY_OPENLINES' => 'Y',
				'FORCE_OPENLINES' => 'Y',
				'JSON' => isset($options['JSON']) ?: false
			]
		);

		return self::mergeRecent(
			$result,
			is_array($imRecent) ? $imRecent : [],
			(isset($options['JSON']) && $options['JSON'])
		);
	}

	public static function update(Message $message): void
	{
		$chat = $message->getChat();

		if (!$chat instanceof OpenLineChat)
		{
			return;
		}

		RecentTable::updateByFilter(
			['=CHAT_ID' => $chat->getId()],
			[
				'MESSAGE_ID' => $message->getId(),
				'SESSION_ID' => $chat->getSessionId(),
				'DATE_CREATE' => $message->getDateCreate()
			]
		);
	}

	private static function mergeRecent(array $lineRecent, array $commonRecent, bool $lowerCase = true): array
	{
		$keyId = $lowerCase ? 'id' : 'ID';
		foreach ($lineRecent as $queueItem)
		{
			foreach ($commonRecent as $imIndex => $imItem)
			{
				if ($imItem[$keyId] == $queueItem[$keyId])
				{
					unset($commonRecent[$imIndex]);
				}
			}
		}

		return array_merge($lineRecent, $commonRecent);
	}

	public static function getNonAnsweredLines(?int $userId = null): array
	{
		$result = [];

		$userId = \Bitrix\Im\Common::getUserId($userId);
		if (!$userId)
		{
			return $result;
		}

		$recentRows = RecentTable::getList([
			'select' => [
				'CHAT_ID'
			],
			'filter' => [
				'=USER_ID' => $userId
			],
		])->fetchAll();

		foreach ($recentRows as $row)
		{
			$result[] = (int)$row['CHAT_ID'];
		}

		return $result;
	}

	public static function getUserIdsByChatId(int $chatId): array
	{
		$recentRows = RecentTable::getList([
			'select' =>['USER_ID'],
			'filter' => [
				'=CHAT_ID' => $chatId
			],
		]);

		$userIds = [];
		while($row = $recentRows->fetch())
		{
			$userIds[] = (int)$row['USER_ID'];
		}

		return $userIds;
	}

	protected static function prepareRows(array $rows, int $userId): array
	{
		$rows = static::fillCounters($rows, $userId);
		$rows = static::fillFiles($rows);

		return static::fillLastMessageStatuses($rows);
	}

	private static function fillCounters(array $rows, int $userId): array
	{
		$chatIds = [];

		foreach ($rows as $row)
		{
			$chatIds[] = (int)$row['CHAT_ID'];
		}

		$counters = (new CounterService($userId))->getForEachChat($chatIds);

		foreach ($rows as $key => $row)
		{
			$rows[$key]['COUNTER'] = (int)($counters[(int)$row['CHAT_ID']] ?? 0);
		}

		return $rows;
	}

	private static function fillLastMessageStatuses(array $rows): array
	{
		foreach ($rows as $key => $row)
		{
			$boolStatus = $row['CHAT_LAST_MESSAGE_STATUS_BOOL'] ?? 'N';
			$rows[$key]['CHAT_LAST_MESSAGE_STATUS'] = $boolStatus === 'Y' ? \IM_MESSAGE_STATUS_DELIVERED : \IM_MESSAGE_STATUS_RECEIVED;
		}

		return $rows;
	}

	private static function fillFiles(array $rows): array
	{
		if (Settings::isLegacyChatActivated())
		{
			foreach ($rows as $key => $row)
			{
				$rows[$key]['MESSAGE_FILE'] = (bool)($row['MESSAGE_FILE'] ?? false);
			}

			return $rows;
		}

		$fileIds = [];

		foreach ($rows as $row)
		{
			if (isset($row['MESSAGE_FILE']) && $row['MESSAGE_FILE'] > 0)
			{
				$fileIds[] = (int)$row['MESSAGE_FILE'];
			}
		}

		$files = FileCollection::initByDiskFilesIds($fileIds);

		foreach ($rows as $key => $row)
		{
			$fileId = $row['MESSAGE_FILE'] ?? null;
			$rows[$key]['MESSAGE_FILE'] = false;
			if (isset($fileId) && $fileId > 0)
			{
				$file = $files->getById((int)$fileId);
				if ($file !== null)
				{
					/** @var FileItem $file */
					$rows[$key]['MESSAGE_FILE'] = [
						'TYPE' => $file->getContentType(),
						'NAME' => $file->getDiskFile()->getName(),
					];
				}
			}
		}

		return $rows;
	}

	private static function getSelect(): array
	{
		$shortInfoFields = [
			'*',
			'CHAT_TITLE' => 'CHAT.TITLE',
			'CHAT_TYPE' => 'CHAT.TYPE',
			'CHAT_AVATAR' => 'CHAT.AVATAR',
			'CHAT_AUTHOR_ID' => 'CHAT.AUTHOR_ID',
			'CHAT_EXTRANET' => 'CHAT.EXTRANET',
			'CHAT_COLOR' => 'CHAT.COLOR',
			'CHAT_ENTITY_TYPE' => 'CHAT.ENTITY_TYPE',
			'CHAT_ENTITY_ID' => 'CHAT.ENTITY_ID',
			'CHAT_ENTITY_DATA_1' => 'CHAT.ENTITY_DATA_1',
			'CHAT_ENTITY_DATA_2' => 'CHAT.ENTITY_DATA_2',
			'CHAT_ENTITY_DATA_3' => 'CHAT.ENTITY_DATA_3',
			'CHAT_DATE_CREATE' => 'CHAT.DATE_CREATE',
			'CHAT_USER_COUNT' => 'CHAT.USER_COUNT',
			'MESSAGE_DATE' => 'MESSAGE.DATE_CREATE',
			'MESSAGE_AUTHOR_ID' => 'MESSAGE.AUTHOR.ID',
			'MESSAGE_AUTHOR_NAME' => 'MESSAGE.AUTHOR.NAME',
			'MESSAGE_TEXT' => 'MESSAGE.MESSAGE',
			'MESSAGE_FILE' => 'FILE.PARAM_VALUE',
			'MESSAGE_ATTACH' => 'ATTACH.PARAM_VALUE',
			'MESSAGE_ATTACH_JSON' => 'ATTACH.PARAM_JSON',
			'MESSAGE_USER_LAST_ACTIVITY_DATE' => 'MESSAGE.AUTHOR.LAST_ACTIVITY_DATE',
			'MESSAGE_USER_IDLE' => 'MESSAGE.STATUS.IDLE',
			'MESSAGE_USER_MOBILE_LAST_DATE' => 'MESSAGE.STATUS.MOBILE_LAST_DATE',
			'MESSAGE_USER_DESKTOP_LAST_DATE' => 'MESSAGE.STATUS.DESKTOP_LAST_DATE',
			'LINES_ID' => 'SESSION.ID',
			'LINES_STATUS' => 'SESSION.STATUS',
			'LINES_DATE_CREATE' => 'SESSION.DATE_CREATE',
			'HAS_REMINDER' => 'HAS_REMINDER',
			'CHAT_LAST_MESSAGE_STATUS_BOOL' => 'MESSAGE.NOTIFY_READ',
		];

		return $shortInfoFields;
	}

	private static function getRuntime(): array
	{
		$reminderTable = LinkReminderTable::getTableName();

		return [
			new Reference(
				'ATTACH',
				MessageParamTable::class,
				[
					"=ref.MESSAGE_ID" => "this.MESSAGE_ID",
					"=ref.PARAM_NAME" => new SqlExpression("?s", Params::ATTACH)
				],
				["join_type" => "LEFT"]
			),
			new Reference(
				'FILE',
				MessageParamTable::class,
				[
					"=ref.MESSAGE_ID" => "this.MESSAGE_ID",
					"=ref.PARAM_NAME" => new SqlExpression("?s", Params::FILE_ID)
				],
				["join_type" => "LEFT"]
			),
			new ExpressionField(
				'HAS_REMINDER',
				"CASE WHEN EXISTS (
					SELECT 1
					FROM {$reminderTable}
					WHERE CHAT_ID = %s AND AUTHOR_ID = %s AND IS_REMINDED = 'Y'
				) THEN 'Y' ELSE 'N' END",
				['CHAT_ID', 'USER_ID'],
				['data_type' => 'boolean', 'values' => ['N', 'Y']]
			),
		];
	}

	private static function formatRow($row): ?array
	{
		$chatOwner = $row['CHAT_OWNER'] ?? null;

		$id = 'chat' . $row['CHAT_ID'];
		$row['MESSAGE_ID'] ??= null;

		if (!$row['MESSAGE_ID'] || !$row['CHAT_ID'])
		{
			return null;
		}

		if ($row['MESSAGE_ID'] > 0)
		{
			$attach = false;
			if ($row['MESSAGE_ATTACH'] || $row['MESSAGE_ATTACH_JSON'])
			{
				if (preg_match('/^(\d+)$/', $row['MESSAGE_ATTACH']))
				{
					$attach = true;
				}
				else if ($row['MESSAGE_ATTACH'] === \CIMMessageParamAttach::FIRST_MESSAGE)
				{
					try
					{
						$value = \Bitrix\Main\Web\Json::decode($row['MESSAGE_ATTACH_JSON']);
						$attachRestored = \CIMMessageParamAttach::PrepareAttach($value);
						$attach = $attachRestored['DESCRIPTION'];
					}
					catch (\Bitrix\Main\SystemException $e)
					{
						$attach = true;
					}
				}
				else if (!empty($row['MESSAGE_ATTACH']))
				{
					$attach = $row['MESSAGE_ATTACH'];
				}
				else
				{
					$attach = true;
				}
			}

			$text = Text::removeBbCodes(
				str_replace("\n", " ", $row['MESSAGE_TEXT'] ?? ''),
				$row['MESSAGE_FILE'] > 0,
				$attach
			);

			$message = [
				'ID' => (int)$row['MESSAGE_ID'],
				'TEXT' => $text,
				'FILE' => $row['MESSAGE_FILE'],
				'AUTHOR_ID' =>  (int)$row['MESSAGE_AUTHOR_ID'],
				'ATTACH' => $attach,
				'DATE' => $row['MESSAGE_DATE'],
				'STATUS' => $row['CHAT_LAST_MESSAGE_STATUS'],
				'UUID' => $row['MESSAGE_UUID_VALUE'] ?? null,
			];
		}
		else
		{
			$row['MESSAGE_DATE'] ??= null;
			$message = [
				'ID' => 0,
				'TEXT' => "",
				'FILE' => false,
				'AUTHOR_ID' =>  0,
				'ATTACH' => false,
				'DATE' => $row['MESSAGE_DATE'],
				'STATUS' => $row['CHAT_LAST_MESSAGE_STATUS'],
			];
		}

		$item = [
			'ID' => $id,
			'CHAT_ID' => (int)$row['CHAT_ID'],
			'TYPE' => 'chat',
			'MESSAGE' => $message,
			'COUNTER' => 1,
			'PINNED' => $row['PINNED'] === 'Y',
			'UNREAD' => $row['UNREAD'] === 'Y',
			'HAS_REMINDER' => isset($row['HAS_REMINDER']) && $row['HAS_REMINDER'] === 'Y',
			'DATE_UPDATE' => $row['DATE_CREATE']
		];

		$avatar = \CIMChat::GetAvatarImage($row['CHAT_AVATAR'], 200, false);
		$color = $row['CHAT_COLOR'] <> ''
			? Color::getColor($row['CHAT_COLOR'])
			: Color::getColorByNumber(
				$row['CHAT_ID']
			);
		$chatType = \Bitrix\Im\Chat::getType($row);

		$muteList = [];
		if ($row['RELATION_NOTIFY_BLOCK'] == 'Y')
		{
			$muteList = [$row['RELATION_USER_ID'] => true];
		}

		$managerList = [];
		if (
			$chatOwner == $row['RELATION_USER_ID']
			|| $row['RELATION_IS_MANAGER'] == 'Y'
		)
		{
			$managerList = [(int)$row['RELATION_USER_ID']];
		}

		if ($row['RELATION_NOTIFY_BLOCK'] == 'Y')
		{
			$muteList = [$row['RELATION_USER_ID'] => true];
		}

		$chatOptions = \CIMChat::GetChatOptions();
		$restrictions = $chatOptions['DEFAULT'];
		if ($row['CHAT_ENTITY_TYPE'] && array_key_exists($row['CHAT_ENTITY_TYPE'], $chatOptions))
		{
			$restrictions = $chatOptions[$row['CHAT_ENTITY_TYPE']];
		}

		$item['AVATAR'] = [
			'URL' => $avatar,
			'COLOR' => $color
		];
		$item['TITLE'] = $row['CHAT_TITLE'];
		$item['CHAT'] = [
			'ID' => (int)$row['CHAT_ID'],
			'NAME' => $row['CHAT_TITLE'],
			'OWNER' => (int)$row['CHAT_AUTHOR_ID'],
			'EXTRANET' => $row['CHAT_EXTRANET'] == 'Y',
			'AVATAR' => $avatar,
			'COLOR' => $color,
			'TYPE' => $chatType,
			'ENTITY_TYPE' => (string)$row['CHAT_ENTITY_TYPE'],
			'ENTITY_ID' => (string)$row['CHAT_ENTITY_ID'],
			'ENTITY_DATA_1' => (string)$row['CHAT_ENTITY_DATA_1'],
			'ENTITY_DATA_2' => (string)$row['CHAT_ENTITY_DATA_2'],
			'ENTITY_DATA_3' => (string)$row['CHAT_ENTITY_DATA_3'],
			'MUTE_LIST' => $muteList,
			'MANAGER_LIST' => $managerList,
			'DATE_CREATE' => $row['CHAT_DATE_CREATE'],
			'MESSAGE_TYPE' => $row["CHAT_TYPE"],
			'USER_COUNTER' => (int)$row['CHAT_USER_COUNT'],
			'RESTRICTIONS' => $restrictions
		];
		if ($row['CHAT_ENTITY_TYPE'] == 'LINES')
		{
			$item['LINES'] = [
				'ID' => (int)$row['LINES_ID'],
				'STATUS' => (int)$row['LINES_STATUS'],
				'DATE_CREATE' => $row['LINES_DATE_CREATE'] ?? $row['DATE_UPDATE'],
			];
		}
		$item['USER'] = [
			'ID' => 0,
		];

		return $item;
	}

	private static function jsonRow($item)
	{
		if (!is_array($item))
		{
			return $item;
		}

		foreach ($item as $key => $value)
		{
			if ($value instanceof \Bitrix\Main\Type\DateTime)
			{
				$item[$key] = date('c', $value->getTimestamp());
			}
			else if (is_array($value))
			{
				foreach ($value as $subKey => $subValue)
				{
					if ($subValue instanceof \Bitrix\Main\Type\DateTime)
					{
						$value[$subKey] = date('c', $subValue->getTimestamp());
					}
					else if (
						is_string($subValue)
						&& $subValue
						&& in_array($subKey, ['URL', 'AVATAR'])
						&& mb_strpos($subValue, 'http') !== 0
					)
					{
						$value[$subKey] = \Bitrix\Im\Common::getPublicDomain().$subValue;
					}
					else if (is_array($subValue))
					{
						$value[$subKey] = array_change_key_case($subValue, CASE_LOWER);
					}
				}
				$item[$key] = array_change_key_case($value, CASE_LOWER);
			}
		}

		return array_change_key_case($item, CASE_LOWER);
	}

	/**
	 * Checking if recent OL is available
	 *
	 * @param int $sessionId
	 *
	 * @return bool
	 */
	public static function recentAvailable(int $sessionId): bool
	{
		if ($session = SessionTable::getByPrimary($sessionId, ['select' => ['STATUS']])->fetch())
		{
			if ($session['STATUS'] < Session::STATUS_ANSWER)
			{
				return true;
			}
		}

		return false;
	}

	public static function isRecentAvailableByStatus(?int $status): bool
	{
		if ($status === null)
		{
			return false;
		}

		return $status < Session::STATUS_ANSWER;
	}

	/**
	 * Returns true if the recent is empty or there is a current user
	 * Used to create an entry or update without adding new rows
	 *
	 * @param $userId
	 * @param $chatId
	 *
	 * @return bool|array
	 */
	public static function isCurrentRecent($userId, $chatId)
	{
		$recentRows = RecentTable::getList([
			'select' => [
				'USER_ID'
			],
			'filter' => [
				'=CHAT_ID' => $chatId
			],
		]);

		if (!$recentRows->getSelectedRowsCount())
		{
			return true;
		}

		while ($row = $recentRows->fetch())
		{
			if ($row['USER_ID'] == $userId)
			{
				return true;
			}
		}

		return false;
	}

	public static function clearRecent(int $sessionId): void
	{
		$userIds = [];
		if (Loader::includeModule('im'))
		{
			$userIds = RecentTable::query()
				->setSelect(['USER_ID'])
				->where('SESSION_ID', $sessionId)
				->fetchCollection()
				->getUserIdList()
			;
			$userIds = array_unique($userIds);
		}

		RecentTable::deleteByFilter([
			'=SESSION_ID' => $sessionId
		]);

		foreach ($userIds as $userId)
		{
			CounterService::clearCache($userId);
		}
	}

	public static function getElement(int $chatId, ?int $userId = null, array $options = [])
	{
		$userId = \Bitrix\Im\Common::getUserId($userId);
		if (!$userId)
		{
			return false;
		}

		$orm = RecentTable::getList([
			'select' => self::getSelect(),
			'filter' => [
				'=USER_ID' => $userId,
				'=CHAT_ID' => $chatId
			],
			'runtime' => self::getRuntime()
		]);

		$result = null;
		$rows = $orm->fetchAll();
		$rows = self::prepareRows($rows, $userId);
		foreach ($rows as $row)
		{
			$item = self::formatRow($row);
			if (!$item)
			{
				continue;
			}

			$result = $item;
		}
		$result = self::prepareRows([$result], $userId)[0];
		if (isset($options['fakeCounter']))
		{
			$result['counter'] = $options['fakeCounter'];
		}

		if (isset($options['JSON']) && $options['JSON'])
		{
			$result = self::jsonRow($result);
		}

		return $result;
	}

	/**
	 * @event 'imopenlines:OnQueueOperatorsDelete'
	 * @param \Bitrix\Main\Event $event
	 * @return bool
	 */
	public static function onQueueOperatorsDelete(\Bitrix\Main\Event $event): bool
	{
		$configId = $event->getParameter('line');
		$operatorIds = $event->getParameter('operators');

		if (!$configId || !is_array($operatorIds) || empty($operatorIds))
		{
			return false;
		}

		$sessions = SessionTable::getList([
			'select' => [
				'CHAT_ID'
			],
			'filter' => [
				'=CONFIG_ID' => $configId,
				'<STATUS' => Session::STATUS_ANSWER,
			]
		]);

		$chatIds = [];
		while ($session = $sessions->fetch())
		{
			$chatIds[] = (int)$session['CHAT_ID'];
		}

		foreach ($operatorIds as $operatorId)
		{
			$recentRows = RecentTable::getList([
				'filter' => [
					'=USER_ID' => $operatorId
				],
			]);

			while ($row = $recentRows->fetch())
			{
				if (in_array((int)$row['CHAT_ID'], $chatIds, true))
				{
					self::removeRecent((int)$operatorId, (int)$row['CHAT_ID']);
				}
			}
		}

		return true;
	}
}