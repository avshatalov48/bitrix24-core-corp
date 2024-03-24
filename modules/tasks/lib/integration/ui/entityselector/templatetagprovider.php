<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\EntitySelector\EntityUsageTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;

class TemplateTagProvider extends BaseProvider
{
	private const TEMPLATE_ENTITY_ID = 'template-tag';
	private const TASK_ENTITY_ID = 'task-tag';
	private const LIMIT = 100;
	private const TAGS_STUB_ID = 'all';

	public function __construct()
	{
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		global $USER;
		return $USER->IsAuthorized();
	}

	public function getItems(array $ids): array
	{
		return array_map(function ($tag): Item {
			$name = is_string($tag) ? $tag : '';

			return new Item([
				'id' => $name,
				'entityId' => self::TEMPLATE_ENTITY_ID,
				'title' => $name,
				'tabs' => [self::TAGS_STUB_ID],
			]);
		}, $ids);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$tags = $this->findTags($searchQuery->getQuery());
		$dialog->addItems($this->getItems($tags));
	}

	public function fillDialog(Dialog $dialog): void
	{
		$this->addTagsTab($dialog);
		$this->refillDialog($dialog, $this->getTags());
	}

	private function getTags(): array
	{
		$query = TemplateTagTable::query();
		$query
			->setSelect(['MAX_ID', 'NAME'])
			->setOrder(['MAX_ID' => 'DESC'])
			->setGroup(['NAME'])
			->setLimit(self::LIMIT)
		;

		return $this->getTitles($query->exec()->fetchAll());
	}

	private function findTags(string $title): array
	{
		$query = TemplateTagTable::query();
		$query
			->setSelect(['NAME'])
			->whereLike('NAME', "%{$title}%")
			->setDistinct()
		;

		return $this->getTitles($query->exec()->fetchAll());
	}

	private function refillDialog(Dialog $dialog, array $tags): void
	{
		$diff = self::LIMIT - count($tags);
		if ($diff <= 0)
		{
			$dialog->addItems($this->getItems($tags));

			return;
		}

		$titleColumn = 'ITEM_ID';
		$query = EntityUsageTable::query();
		$query
			->setSelect(['MAX_LAST_USE_DATE', $titleColumn])
			->where('ENTITY_ID', self::TASK_ENTITY_ID)
			->setOrder(['MAX_LAST_USE_DATE' => 'DESC'])
			->setLimit($diff)
			->setGroup([$titleColumn])
		;

		$additionalTags = $this->getTitles($query->exec()->fetchAll(), $titleColumn);
		$tags = array_unique(array_merge($tags, $additionalTags));

		$dialog->addItems($this->getItems($tags));
	}

	private function addTagsTab(Dialog $dialog): void
	{
		$dialog->addTab(
			new Tab([
				'id' => self::TAGS_STUB_ID,
				'title' => Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_TAG_TAB'),
				'stub' => true,
			])
		);
	}

	private function getTitles(array $tags, string $titleColumn = 'NAME'): array
	{
		return array_map(function (array $el) use ($titleColumn): string {
			return $el[$titleColumn];
		}, $tags);
	}
}