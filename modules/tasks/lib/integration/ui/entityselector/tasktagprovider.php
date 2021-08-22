<?php
namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\EO_Tag;
use Bitrix\Tasks\Internals\Task\EO_Tag_Collection;
use Bitrix\Tasks\Internals\Task\TagTable;
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
	private static $entityId = 'task-tag';
	private static $maxCount = 100;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['taskId'] = $options['taskId'];
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
	}

	public function getItems(array $ids): array
	{
		return [];
	}

	public function getSelectedItems(array $ids): array
	{
		return [];
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$dialog->addItems(
			$this->getTagItems(['searchQuery' => $searchQuery->getQuery()])
		);
	}

	public function getTagItems(array $options = []): array
	{
		return $this->makeTagItems($this->getTagCollection($options), $options);
	}

	public function getTagCollection(array $options = []): EO_Tag_Collection
	{
		$options = array_merge($this->getOptions(), $options);

		return self::getTags($options);
	}

	public static function getTags(array $options = []): EO_Tag_Collection
	{
		$currentUserId = $GLOBALS['USER']->getId();

		$query = TagTable::query();
		$query->setSelect(['TASK_ID', 'USER_ID', 'NAME']);

		if ($options['selected'])
		{
			$query->where('TASK_ID', $options['taskId']);
		}
		else
		{
			$query->where('USER_ID', $currentUserId);

			if ($options['excludeSelected'])
			{
				$query->where('TASK_ID', '<>', $options['taskId']);
			}
		}

		if (!empty($options['searchQuery']) && is_string($options['searchQuery']))
		{
			$query->setDistinct(true);
			$query->whereLike('NAME', "{$options['searchQuery']}%");
		}

		return $query->exec()->fetchCollection();
	}

	public function makeTagItems(EO_Tag_Collection $tags, array $options = []): array
	{
		return self::makeItems($tags, array_merge($this->getOptions(), $options));
	}

	/**
	 * @param EO_Tag_Collection $tags
	 * @param array $options
	 * @return array
	 */
	public static function makeItems(EO_Tag_Collection $tags, array $options = []): array
	{
		$result = [];
		foreach ($tags as $tag)
		{
			$result[] = self::makeItem($tag, $options);
		}

		return $result;
	}

	/**
	 * @param EO_Tag $tag
	 * @param array $options
	 *
	 * @return Item
	 */
	public static function makeItem(EO_Tag $tag, array $options = []): Item
	{
		return new Item([
			'id' => $tag->getName(),
			'entityId' => self::$entityId,
			'title' => $tag->getName(),
			'selected' => (isset($options['selected']) && $options['selected']),
			'tabs' => ['all'],
		]);
	}

	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addTab(
			new Tab([
				'id' => 'all',
				'title' => Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_TAG_TAB'),
				'stub' => true,
			])
		);

		$dialog->addItems(
			$this->getTagItems(['selected' => true])
		);

		if ($dialog->getItemCollection()->count() < self::$maxCount)
		{
			$this->fillWithRecentTags($dialog);
		}

		if ($dialog->getItemCollection()->count() < self::$maxCount)
		{
			$dialog->addItems(
				$this->getTagItems(['excludeSelected' => true])
			);
		}
	}

	private function fillWithRecentTags(Dialog $dialog): void
	{
		$recentItems = $dialog->getRecentItems()->getAll();
		foreach ($recentItems as $item)
		{
			/** @var RecentItem $item */
			$name = (string)$item->getId();

			if ($dialog->getItemCollection()->get(self::$entityId, $name))
			{
				continue;
			}

			$dialog->addItem(
				new Item([
					'id' => $name,
					'entityId' => self::$entityId,
					'title' => $name,
					'selected' => false,
					'tabs' => ['all'],
				])
			);

			if ($dialog->getItemCollection()->count() >= self::$maxCount)
			{
				break;
			}
		}
	}
}