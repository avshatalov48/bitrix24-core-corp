<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;

class TaskTemplateProvider extends BaseProvider
{
	private static $entityId = 'task-template';
	private static $maxCount = 30;

	public function __construct(array $options = [])
	{
		parent::__construct();
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
		return $this->getTemplateItems(['ids' => $ids]);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$dialog->addItems(
			$this->getTemplateItems(['searchQuery' => $searchQuery->getQuery()])
		);
	}

	public function fillDialog(Dialog $dialog): void
	{
		$this->fillWithRecentItems($dialog);

		if ($dialog->getItemCollection()->count() < self::$maxCount)
		{
			$templateItems = $this->getTemplateItems(['excludeIds' => $this->getRecentItemsIds($dialog)]);
			foreach ($templateItems as $item)
			{
				/** @var Item $item */
				$item->addTab('recents');
				$dialog->addItem($item);

				if ($dialog->getItemCollection()->count() >= self::$maxCount)
				{
					break;
				}
			}
		}
	}

	private function fillWithRecentItems(Dialog $dialog): void
	{
		if ($dialog->getRecentItems()->count() <= 0)
		{
			return;
		}

		$templates = $this->getTemplates(['ids' => $this->getRecentItemsIds($dialog)]);
		foreach ($dialog->getRecentItems()->getAll() as $item)
		{
			/** @var RecentItem $item */
			$itemId = $item->getId();

			if (
				!array_key_exists($itemId, $templates)
				|| $dialog->getItemCollection()->get(self::$entityId, $itemId)
			)
			{
				continue;
			}

			$dialog->addItem(
				new Item([
					'entityId' => self::$entityId,
					'id' => $itemId,
					'title' => $templates[$itemId],
					'tabs' => 'recents',
				])
			);

			if ($dialog->getItemCollection()->count() >= self::$maxCount)
			{
				break;
			}
		}
	}

	private function getRecentItemsIds(Dialog $dialog): array
	{
		$recentItems = $dialog->getRecentItems()->getAll();

		return array_map(
			static function (RecentItem $item) {
				return $item->getId();
			},
			$recentItems
		);
	}

	private function getTemplateItems(array $options = []): array
	{
		return $this->makeTemplateItems($this->getTemplates($options), $options);
	}

	private function getTemplates(array $options = []): array
	{
		$options = array_merge($this->getOptions(), $options);
		$templates = [];

		$order = ['ID' => 'desc'];
		$filter = $this->getFilterByOptions($options);
		$parameters = [
			'USER_ID' => $GLOBALS['USER']->getId(),
			'NAV_PARAMS' => [
				'nTopCount' => self::$maxCount,
			],
		];
		$select = ['ID', 'TITLE'];

		$templatesResult = \CTaskTemplates::GetList($order, $filter, [], $parameters, $select);
		while ($template = $templatesResult->Fetch())
		{
			$templates[$template['ID']] = $template['TITLE'];
		}

		return $templates;
	}

	private function getFilterByOptions(array $options): array
	{
		$filter = [];

		if (
			array_key_exists('searchQuery', $options)
			&& $options['searchQuery'] !== ''
		)
		{
			$filter['%TITLE'] = $options['searchQuery'];
		}

		if (
			array_key_exists('ids', $options)
			&& is_array($options['ids'])
			&& !empty($options['ids'])
		)
		{
			$filter['ID'] = $options['ids'];
		}

		if (
			array_key_exists('excludeIds', $options)
			&& is_array($options['excludeIds'])
			&& !empty($options['excludeIds'])
		)
		{
			$filter['!ID'] = $options['excludeIds'];
		}

		return $filter;
	}

	private function makeTemplateItems(array $templates, array $options = []): array
	{
		return self::makeItems($templates, array_merge($this->getOptions(), $options));
	}

	private static function makeItems(array $templates, array $options = []): array
	{
		$result = [];
		foreach ($templates as $id => $title)
		{
			if ($title !== '')
			{
				$result[] = new Item([
					'entityId' => self::$entityId,
					'id' => $id,
					'title' => $title,
					'tabs' => 'recents',
				]);
			}
		}

		return $result;
	}
}