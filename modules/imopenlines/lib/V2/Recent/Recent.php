<?php

namespace Bitrix\ImOpenLines\V2\Recent;

use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Recent\BirthdayPopupItem;
use Bitrix\Im\V2\Registry;
use Bitrix\Im\V2\Rest\PopupData;
use Bitrix\ImOpenLines\Model\RecentTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\V2\Session\SessionPopupItem;
use Bitrix\ImOpenLines\V2\Status\StatusGroup;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;

Loader::requireModule('im');

/**
 * @extends Registry<RecentItem>
 */
class Recent extends \Bitrix\Im\V2\Recent\Recent
{
	public static function getByChatId(int $chatId): ?self
	{
		$recent = new static();

		$chat = Chat\OpenLineChat::getInstance($chatId);

		if (!$chat->isExist())
		{
			return null;
		}

		$sessionId = $chat->getSessionId();
		$messageId = $chat->getLastMessageId();
		$dialogId = 'chat' . $chatId;

		$recent[] = (new RecentItem())
			->setMessageId($messageId)
			->setChatId($chatId)
			->setSessionId($sessionId)
			->setDialogId($dialogId)
		;

		return $recent;
	}

	public static function getOpenLines(CurrentUser $user, Cursor $cursor, int $limit): self
	{
		$recent = new static();

		$recentArray = static::getOpenLinesArray($user, $limit, $cursor);
		foreach ($recentArray as $item)
		{
			$dialogId = 'chat' . $item['CHAT_ID'];

			$recentItem = new RecentItem();
			$recentItem
				->setMessageId($item['MESSAGE_ID'])
				->setChatId($item['CHAT_ID'])
				->setSessionId($item['SESSION_ID'])
				->setDialogId($dialogId)
			;
			$recent[] = $recentItem;
		}

		return $recent;
	}

	public static function getOpenLinesArray(CurrentUser $user, int $limit, Cursor $cursor): array
	{
		$userId = $user->getId();
		$query = null;
		$statusGroup = $cursor->getStatusGroup() ?? StatusGroup::NEW;
		$statusGroups = StatusGroup::cases();
		$result = [];
		$currentCount = 0;

		foreach ($statusGroups as $group)
		{
			if ($currentCount >= $limit)
			{
				break;
			}

			if ($group === $statusGroup)
			{
				$query = self::getQuery($userId, $statusGroup, $cursor->getSortPointer());
			}

			if ($query === null)
			{
				continue;
			}

			if ($group !== $statusGroup)
			{
				$query = self::getQuery($userId, $group);
			}

			$query->setLimit($limit - $currentCount);
			$queryResult = $query->fetchAll();
			$result = array_merge($result, $queryResult);
			$currentCount += count($queryResult);
		}

		return array_slice($result, 0, $limit);
	}

	protected static function getQuery(int $userId, StatusGroup $statusGroup, DateTime|int|null $sortPointer = null): Query
	{
		$orderDirection = $statusGroup === StatusGroup::ANSWERED ? 'DESC' : 'ASC';
		$orderField = $statusGroup === StatusGroup::ANSWERED ? 'DATE_MESSAGE' : 'ITEM_OLID';
		$comparisonSign = $statusGroup === StatusGroup::ANSWERED ? '<' : '>';

		if ($statusGroup === StatusGroup::NEW)
		{
			$query = RecentTable::query()
				->setSelect([
					'USER_ID',
					'CHAT_ID',
					'MESSAGE_ID',
					'SESSION_ID',
				])
				->where('USER_ID', $userId)
				->setOrder(['SESSION_ID' => $orderDirection])
			;

			if (isset($sortPointer))
			{
				$query->where('SESSION_ID', $comparisonSign, $sortPointer);
			}

			return $query;
		}

		$query = \Bitrix\Im\Model\RecentTable::query()
			->setSelect([
				'USER_ID' => 'USER_ID',
				'CHAT_ID' => 'ITEM_CID',
				'MESSAGE_ID' => 'ITEM_MID',
				'SESSION_ID' => 'ITEM_OLID',
			])
			->where('USER_ID', $userId)
			->where('ITEM_TYPE', Chat::IM_TYPE_OPEN_LINE)
			->where('SESSION.STATUS', '>=', $statusGroup->getLowerBorder())
			->registerRuntimeField((new Reference(
				'SESSION',
				SessionTable::class,
				Join::on('this.ITEM_OLID', 'ref.ID')
			))->configureJoinType('inner')
			)
			->setOrder([$orderField => $orderDirection])
		;

		if ($statusGroup->getUpperBorder() !== null)
		{
			$query->where('SESSION.STATUS', '<=', $statusGroup->getUpperBorder());
		}

		if (isset($sortPointer))
		{
			$filterField = $statusGroup === StatusGroup::ANSWERED ? 'DATE_MESSAGE' : 'ITEM_OLID';
			$query->where($filterField, $comparisonSign, $sortPointer);
		}

		return $query;
	}

	public function getPopupData(array $excludedList = []): PopupData
	{
		$excludedList[] = BirthdayPopupItem::class; // TODO: Think about excluded list
		$sessionIds = [];

		foreach ($this as $item)
		{
			$sessionIds[] = $item->getSessionId();
		}
		$popupData = new PopupData([new SessionPopupItem($sessionIds)], $excludedList);
		$popupData->merge(parent::getPopupData($excludedList));

		return $popupData;
	}
}