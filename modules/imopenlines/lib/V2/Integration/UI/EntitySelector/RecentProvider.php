<?php

namespace Bitrix\ImOpenLines\V2\Integration\UI\EntitySelector;

use Bitrix\Im\V2\Common\ContextCustomer;
use Bitrix\ImOpenLines\Model\ConfigQueueTable;
use Bitrix\ImOpenLines\Model\ConfigTable;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Search\Content;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

Loader::requireModule('im');

class RecentProvider extends BaseProvider
{
	use ContextCustomer;

	private const LIMIT = 30;
	private const ENTITY_ID = 'imopenlines-recent-v2';
	private const ENTITY_TYPE_USER = 'imopenlines-user';
	private const ENTITY_TYPE_QUEUE = 'imopenlines-queue';
	private const ENTITY_TYPE_CHAT = 'imopenlines-chat';
	private const INCLUDE_OPTION = 'include';
	private const SEARCH_FLAGS_OPTION = 'searchFlags';
	private const FLAG_USERS = 'users';
	private const FLAG_CHATS = 'chats';
	private const FLAG_QUEUES = 'queues';
	private const ALLOWED_SEARCH_FLAGS = [self::FLAG_CHATS, self::FLAG_USERS, self::FLAG_QUEUES];
	private string $preparedSearchString;
	private string $originalSearchString;

	public function __construct(array $options = [])
	{
		$this->prepareSearchFlags($options);
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		// TODO: think about permission for users(operator only can use search???)
		global $USER;

		return $USER->IsAuthorized();
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$this->originalSearchString = $searchQuery->getQuery();
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

	/**
	 * @param Item[] $items
	 * @return void
	 */
	private function fillItems(array $items): void
	{
		foreach ($items as $item)
		{
			//$id = $item->getCustomData()->get('id');
			if ($item->getEntityType() === self::ENTITY_TYPE_QUEUE)
			{
				$item->setTitle($item->getCustomData()->get('lineName'));
				//TODO: fill $customData for Queue
			}

		}
	}

	public function getItems(array $ids): array
	{
		// TODO: Think about sort
		$ids = array_slice($ids, 0, self::LIMIT);

		return [];
	}

	private function getSortedBlankItems(): array
	{
		// get items
		$items = $this->getItemsWithDates();
		// some sort
		return array_slice($items, 0, self::LIMIT);
	}

	private function getItemsWithDates(): array
	{
		$queueItemsWithDate = $this->getQueueItems();

		return $this->mergeByKey($queueItemsWithDate);
	}

	private function mergeByKey(array ...$arrays): array
	{
		$result = [];
		foreach ($arrays as $array)
		{
			foreach ($array as $key => $value)
			{
				$result[$key] = $value;
			}
		}

		return $result;
	}

	private function getUserItems(): array
	{
		return [];
	}

	private function needSearch(string $flag): bool
	{
		return isset($this->options[self::SEARCH_FLAGS_OPTION][$flag])
			&& $this->options[self::SEARCH_FLAGS_OPTION][$flag] === true;
	}

	private function getQueueItems(): array
	{
		$result = [];
		if (!$this->needSearch(self::FLAG_QUEUES))
		{
			return $result;
		}

		$query = ConfigTable::query()
			->setSelect(['ID', 'LINE_NAME'])
			->where('ACTIVE', true)
			->setOrder(['ID' => 'ASC'])
			->setLimit(self::LIMIT)
		;

		if (isset($this->preparedSearchString))
		{
			$searchString = '%' . $this->preparedSearchString . '%';
			$query->whereLike('LINE_NAME', $searchString);
		}
		else
		{
			return [];
		}

		$raw = $query->fetchAll();

		foreach ($raw as $row)
		{
			$queueId = 'queue' . (int)$row['ID'];
			$result[$queueId] = $this->getBlankItem(
				$queueId,
				self::ENTITY_TYPE_QUEUE,
				['lineName' => $row['LINE_NAME']]
			);
		}

		return $result;
	}

	private function getBlankItem(string $id, string $entityType, array $additionalData = []): Item
	{
		$data = [];

		if ($entityType === self::ENTITY_TYPE_CHAT)
		{
			$data['description'] = $additionalData['description'];
		}

		$sort = 0;

		return new Item([
			'id' => $this->getIdByEntityType($id, $entityType),
			'entityId' => self::ENTITY_ID,
			'entityType' => $entityType,
			'sort' => $sort,
			'customData' => $data,
		]);
	}

	private function getIdByEntityType(string $id, string $entityType): ?int
	{
		if ($entityType === self::ENTITY_TYPE_QUEUE)
		{
			return (int)substr($id, 5);
		}

		if ($entityType === self::ENTITY_TYPE_CHAT)
		{
			return (int)substr($id, 4);
		}

		return is_numeric($id) ? $id : null;
	}

	private function prepareSearchString(string $searchString): string
	{
		$searchString = trim($searchString);
		// TODO: think about operations for prepare string
		return $searchString;
	}

	private function prepareSearchFlags(array $options): void
	{
		$this->options[self::SEARCH_FLAGS_OPTION] = [];

		if (isset($options[self::INCLUDE_OPTION]) && is_array($options[self::INCLUDE_OPTION]))
		{
			foreach (self::ALLOWED_SEARCH_FLAGS as $searchFlag)
			{
				$this->options[self::SEARCH_FLAGS_OPTION][$searchFlag] = false;
			}

			foreach ($options[self::INCLUDE_OPTION] as $searchFlag)
			{
				if ($this->isValidSearchFlag($searchFlag))
				{
					$this->options[self::SEARCH_FLAGS_OPTION][$searchFlag] = true;
				}
			}
		}
	}

	private function isValidSearchFlag(string $searchFlag): bool
	{
		return in_array($searchFlag, self::ALLOWED_SEARCH_FLAGS, true);
	}
}