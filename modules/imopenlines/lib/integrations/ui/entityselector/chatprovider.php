<?php

namespace Bitrix\ImOpenlines\Integrations\UI\EntitySelector;

use Bitrix\Im\Model\ChatTable;
use Bitrix\Im\Model\RecentTable;
use Bitrix\Im\Model\RelationTable;
use Bitrix\Im\V2\Entity\User\User;
use Bitrix\ImOpenLines\Model\ChatIndexTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Filter\Helper;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Search\Content;
use Bitrix\Main\Type\DateTime;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class ChatProvider extends BaseProvider
{
	private const LIMIT = 30;
	private const ENTITY_ID = 'imol-chat';

	private string $preparedSearchString;
	private array $chatIds;
	private bool $sortEnable = true;

	public function __construct()
	{
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		global $USER;

		return $USER->IsAuthorized() && Loader::includeModule('im');
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$this->preparedSearchString = $this->prepareSearchString($searchQuery->getQuery());
		if (!Content::canUseFulltextSearch($this->preparedSearchString))
		{
			return;
		}
		$searchQuery->setCacheable(false);
		$items = $this->getSortedBlankItems();
		$this->fillItems($items);
		$dialog->addItems($items);
	}

	public function getItems(array $ids): array
	{
		$this->sortEnable = false;
		$ids = array_slice($ids, 0, self::LIMIT);
		$this->setChatIds($ids);
		$datesUpdate = $this->getChatIdsWithDates();
		$ids = array_keys($datesUpdate);
		$items = $this->getBlankItems($ids, $datesUpdate);
		$this->fillItems($items);

		return $items;
	}

	public function getPreselectedItems(array $ids): array
	{
		$this->sortEnable = false;
		$ids = array_slice($ids, 0, self::LIMIT);
		$this->setChatIds($ids);
		$datesUpdate = $this->getChatIdsWithDates();
		$items = $this->getBlankItems($ids, $datesUpdate);
		$this->fillItems($items);

		return $items;
	}

	private function getSortedBlankItems(): array
	{
		$datesUpdate = $this->getChatIdsWithDates();
		$ids = array_keys($datesUpdate);
		$items = $this->getBlankItems($ids, $datesUpdate);
		usort($items, function(Item $a, Item $b) {
			return $a->getSort() <=> $b->getSort();
		});

		return $items;
	}

	private function getChatIdsWithDates(): array
	{
		if (!Loader::includeModule('im'))
		{
			return [];
		}
		if (User::getCurrent()->isExtranet())
		{
			return [];
		}
		$result = [];
		$query = ChatTable::query()
			->setSelect([
				'ID',
				'RECENT_DATE_UPDATE' => 'RECENT.DATE_UPDATE',
				'RECENT_DATE_UPDATE_OL' => 'RECENT_OL.DATE_CREATE',
			])
			->setLimit(self::LIMIT)
			->registerRuntimeField(
				new Reference(
					'RECENT',
					RecentTable::class,
					Join::on('this.ID', 'ref.ITEM_CID')->where('ref.USER_ID', User::getCurrent()->getId()),
					['join_type' => isset($this->preparedSearchString) ? Join::TYPE_LEFT : Join::TYPE_INNER]
				)
			)
			->registerRuntimeField(
				new Reference(
					'RECENT_OL',
					\Bitrix\ImOpenLines\Model\RecentTable::class,
					Join::on('this.ID', 'ref.CHAT_ID')->where('ref.USER_ID', User::getCurrent()->getId()),
					['join_type' => Join::TYPE_LEFT]
				)
			)
		;
		if (isset($this->preparedSearchString))
		{
			$condition = (new ConditionTree())
				->logic(ConditionTree::LOGIC_OR)
				->where('RECENT.USER_ID', User::getCurrent()->getId())
				->where('RECENT_OL.USER_ID', User::getCurrent()->getId())
			;

			$query
				->registerRuntimeField(new ExpressionField(
						'RECENT_DATE_UPDATE_ORDER',
						'COALESCE(%s, %s)',
						['RECENT.DATE_UPDATE', 'RECENT_OL.DATE_CREATE']
					)
				)
				->registerRuntimeField(
					new Reference(
						'OL_INDEX',
						ChatIndexTable::class,
						Join::on('this.ID', 'ref.CHAT_ID'),
						['join_type' => Join::TYPE_INNER]
					)
				)
				->whereMatch('OL_INDEX.SEARCH_TITLE', $this->preparedSearchString)
				->where($condition)
				->setOrder(['RECENT_DATE_UPDATE_ORDER' => 'ASC'])
			;
		}
		elseif (isset($this->chatIds) && !empty($this->chatIds))
		{
			$query->whereIn('ID', $this->chatIds);
		}
		else
		{
			return [];
		}

		$raw = $query->fetchAll();

		foreach ($raw as $row)
		{
			$result[(int)$row['ID']] = $row['RECENT_DATE_UPDATE'] ?: $row['RECENT_DATE_UPDATE_OL'];
		}

		return $result;
	}

	private function getBlankItems(array $ids, array $datesUpdate = []): array
	{
		$result = [];

		foreach ($ids as $id)
		{
			$result[] = $this->getBlankItem($id, $datesUpdate[$id] ?? null);
		}

		return $result;
	}

	private function getBlankItem(string $chatId, ?DateTime $dateUpdate = null): Item
	{
		$sort = 0;
		$customData['dateUpdate'] = $dateUpdate;
		if (isset($dateUpdate))
		{
			if ($this->sortEnable)
			{
				$sort = $dateUpdate->getTimestamp();
			}
		}

		return new Item([
			'id' => $chatId,
			'entityId' => self::ENTITY_ID,
			'sort' => $sort,
			'customData' => $customData,
		]);
	}

	/**
	 * @param Item[] $items
	 * @return void
	 */
	private function fillItems(array $items): void
	{
		$chatIds = [];

		foreach ($items as $item)
		{
			$id = (int)$item->getId();
			$chatIds[$id] = $id;
		}

		$chats = $this->getChatsById($chatIds);

		foreach ($items as $item)
		{
			$chat = $chats[(int)$item->getId()] ?? null;
			if ($chat === null)
			{
				continue;
			}
			$item
				->setTitle($chat['TITLE'] ?? '')
				->setAvatar(\CIMChat::GetAvatarImage($chat['AVATAR'], 200, false))
				->setCustomData(['imChat' => \Bitrix\Im\Chat::formatChatData($chat)])
				->setEntityType('LINES')
			;
		}
	}

	private function getChatsById(array $ids): array
	{
		if (empty($ids))
		{
			return [];
		}

		$chats = ChatTable::query()
			->setSelect([
				'*',
				'RELATION_USER_ID' => 'RELATION.USER_ID',
				'RELATION_NOTIFY_BLOCK' => 'RELATION.NOTIFY_BLOCK',
				'RELATION_START_COUNTER' => 'RELATION.START_COUNTER',
				'ALIAS_NAME' => 'ALIAS.ALIAS',
			])
			->registerRuntimeField(
				'RELATION',
				new Reference(
					'RELATION',
					RelationTable::class,
					Join::on('this.ID', 'ref.CHAT_ID')->where('ref.USER_ID', User::getCurrent()->getId()),
				)
			)
			->whereIn('ID', $ids)
			->fetchAll()
		;
		$chats = \Bitrix\Im\Chat::fillCounterData($chats);
		$chatsByIds = [];

		foreach ($chats as $chat)
		{
			$chatsByIds[(int)$chat['ID']] = $chat;
		}

		return $chatsByIds;
	}

	private function setChatIds(array $ids): void
	{
		$this->chatIds = $ids;
	}

	private function prepareSearchString(string $searchString): string
	{
		$searchString = trim($searchString);

		return Helper::matchAgainstWildcard(Content::prepareStringToken($searchString));
	}
}