<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Internals\Registry\UserRegistry;
use Bitrix\Tasks\Internals\Task\EO_Label;
use Bitrix\Tasks\Internals\Task\EO_Label_Collection;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\TaskTagTable;
use Bitrix\Main\Loader;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;

/**
 * Class TagProvider
 *
 * @package Bitrix\Tasks\Integration\UI\EntitySelector
 */
class TaskTagProvider extends BaseProvider
{
	private static string $entityId = 'task-tag';
	private static int $maxCount = 100;
	private int $userId;

	private static array $fields = [
		'ID',
		'NAME',
		'USER_ID',
		'GROUP_ID',
		'GROUP.ID',
		'USER.ID',
		'GROUP.NAME',
		'USER.NAME',
		'USER.LAST_NAME',
	];

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['taskId'] = (int)($options['taskId'] ?? 0);
		$this->options['groupId'] = (int)($options['groupId'] ?? 0);
		$this->options['groupName'] = $this->getGroupName($this->options['groupId']);
		$this->options['fromFilter'] = (bool)$options['filter'];
		$this->options['type'] = ($options['type'] ?? 'task');

		$this->userId = CurrentUser::get()->getId();
	}

	/**
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
	}

	/**
	 * @param array $ids
	 * @return array|Item[]
	 */
	public function getItems(array $ids): array
	{
		return array_map(
			static function ($tag) {
				return new Item([
					'id' => $tag['ID'],
					'entityId' => self::$entityId,
					'title' => $tag['NAME'],
					'selected' => false,
					'tabs' => ['all'],
				]);
			},
			$this->makeTagEntities($ids)
		);
	}

	/**
	 * @param array $ids
	 * @return array|Item[]
	 */
	public function getSelectedItems(array $ids): array
	{
		return [];
	}

	/**
	 * @param SearchQuery $searchQuery
	 * @param Dialog $dialog
	 * @return void
	 */
	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$searchQuery->setCacheable(false);
		$query = LabelTable::query();

		$query
			->setSelect(self::$fields);
		if ($this->options['fromFilter'])
		{
			$groups = UserRegistry::getInstance(CurrentUser::get()->getId())->getUserGroups();
			$groupIds = array_keys($groups);

			$filter = $query::filter()
				->logic('or')
				->whereIn('GROUP_ID', $groupIds)
				->where('USER_ID', $this->userId)
			;

			$query
				->whereLike('NAME',"{$searchQuery->getQuery()}%")
				->where($filter)
			;

			$data = $query->exec()->fetchAll();

			$uniqueTags = array_unique(
				array_map(
					static function (array $el): string {
						return (string)$el['NAME'];
					},
					$data
				)
			);

			$collection = new EO_Label_Collection();
			foreach ($uniqueTags as $tag)
			{
				$collection->add(
					new EO_Label([
						'NAME' => $tag,
						'ID' => $tag,
					])
				);
			}

			$dialog->addItems($this->makeTagItems($collection));

			return;
		}
		if (!empty($this->options['groupId']))
		{
			$query->where('GROUP_ID', $this->options['groupId']);
		}
		else
		{
			$query->where('USER_ID', $this->userId);
		}
		$query
			->setDistinct()
			->whereLike('NAME', "{$searchQuery->getQuery()}%")
		;

		$searchData = $query->exec()->fetchCollection();

		$dialog->addItems($this->makeTagItems($searchData));
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getTagItems(array $options = []): array
	{
		return $this->makeTagItems($this->getTagCollection($options), $options);
	}

	public function getTagCollection(array $options = []): EO_Label_Collection
	{
		$options = array_merge($this->getOptions(), $options);

		return self::getTags($options);
	}

	public static function getTags(array $options = []): EO_Label_Collection
	{
		$currentUserId = $GLOBALS['USER']->getId();

		$query = LabelTable::query();

		$query
			->setSelect(self::$fields)
			->setOrder(['ID' => 'DESC'])
			->setLimit(self::$maxCount)
			->registerRuntimeField(
				'',
				new ReferenceField(
					'rel',
					TaskTagTable::getEntity(),
					[
						'=this.ID' => 'ref.TAG_ID',
					],
					[
						'join_type' => 'left',
					],
				)
			);

		if ($options['fromFilter'])
		{
			$groups = UserRegistry::getInstance(CurrentUser::get()->getId())->getUserGroups();
			$groupIds = array_keys($groups);
			$filter = $query::filter()
				->logic('or')
				->whereIn('GROUP_ID', $groupIds)
				->where('USER_ID', $currentUserId)
			;
			$query->where($filter);

			return $query->exec()->fetchCollection();
		}
		if ($options['selected'])
		{
			$query->where('rel.TASK_ID', $options['taskId']);
		}
		elseif ($options['groupContext'])
		{
			$query->where('GROUP_ID', $options['groupId']);
		}
		elseif ($options['excludeSelected'])
		{
			$query->where('USER_ID', $currentUserId);
		}

		if (!empty($options['searchQuery']) && is_string($options['searchQuery']))
		{
			$query->setDistinct(true);
			$query->whereLike('NAME', "{$options['searchQuery']}%");
		}

		return $query->exec()->fetchCollection();
	}

	public function makeTagItems(EO_Label_Collection $tags, array $options = []): array
	{
		return self::makeItems($tags, array_merge($this->getOptions(), $options));
	}

	public static function makeItems(EO_Label_Collection $tags, array $options = []): array
	{
		$result = [];
		foreach ($tags as $tag)
		{
			if ($tag->getName() !== '')
			{
				$result[] = self::makeItem($tag, $options);
			}
		}

		return $result;
	}

	public static function makeItem(EO_Label $tag, array $options = []): Item
	{
		if ($options['fromFilter'])
		{
			return new Item([
				'id' => $tag->getName(),
				'entityId' => self::$entityId,
				'title' => $tag->getName(),
				'selected' => (isset($options['selected']) && $options['selected']),
				'tabs' => ['all'],
			]);
		}

		$badge = '';

		if ($tag->getUser())
		{
			$badge = $tag->getUser()->getName() . " " . $tag->getUser()->getLastName();
		}

		if ($tag->getGroup())
		{
			$badge = $tag->getGroup()->getName();
		}

		return new Item([
			'id' => $tag->getId(),
			'entityId' => self::$entityId,
			'title' => $tag->getName(),
			'badges' => [
				[
					'title' => $badge,
				],
				[
					'textColor' => '#bb8412',
				],
				[
					'bgColor' => '#fff599',
				],
			],
			'selected' => (isset($options['selected']) && $options['selected']),
			'tabs' => ['all'],
		]);
	}

	/**
	 * @param Dialog $dialog
	 * @return void
	 */
	public function fillDialog(Dialog $dialog): void
	{
		$options = $this->getOptions();

		$dialog->addTab(
			new Tab([
				'id' => 'all',
				'title' => Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_TAG_TAB'),
				'stub' => true,
			])
		);

		$dialog->addItems($this->getTagItems(['selected' => true]));

		if ($options['groupId'] !== 0)
		{
			$dialog->addItems($this->getTagItems(['groupContext' => true]));
		}
		if ($options['groupId'] === 0)
		{
			$dialog->addItems($this->getTagItems(['excludeSelected' => true]));
		}
	}

	/**
	 * @param Item $item
	 * @return void
	 */
	public function handleBeforeItemSave(Item $item): void
	{
	}

	private function getGroupName(int $groupId): string
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return '';
		}

		if (!$groupId)
		{
			return '';
		}

		$group = WorkgroupTable::getById($groupId)->fetchAll();

		if (empty($group))
		{
			return '';
		}

		return $group[0]['NAME'];
	}

	private function makeTagEntities(array $titles): array
	{
		return LabelTable::getList([
			'select' => [
				'ID',
				'NAME',
			],
			'filter' => array_merge($this->makeFilter(), ['@NAME' => $titles]),
		])->fetchAll();
	}

	private function makeFilter(): array
	{
		$filter = ['=USER_ID' => $this->userId];
		if (!empty($this->options['groupId']))
		{
			$filter = ['=GROUP_ID' => $this->options['groupId']];
		}
		return $filter;
	}
}