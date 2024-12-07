<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TemplatePathMaker;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\RecentItem;
use Bitrix\UI\EntitySelector\SearchQuery;
use CTaskTemplates;

class TaskTemplateProvider extends BaseProvider
{
	private const ENTITY_ID = 'task-template';
	private const LIMIT = 30;

	private int $templateId;
	private string $context;

	public function __construct(array $options = [])
	{
		parent::__construct();
		$this->templateId = $options['templateId'] ?? 0;
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

		if ($dialog->getItemCollection()->count() < static::LIMIT)
		{
			$templateItems = $this->getTemplateItems(['excludeIds' => $this->getRecentItemsIds($dialog)]);
			foreach ($templateItems as $item)
			{
				/** @var Item $item */
				$item->addTab('recents');
				$dialog->addItem($item);

				if ($dialog->getItemCollection()->count() >= static::LIMIT)
				{
					break;
				}
			}
		}

		$this->context = (string)$dialog->getContext();
		$dialog->setFooter('BX.Tasks.EntitySelector.Footer', $this->getFooterOptions());
	}

	private function getFooterOptions(): array
	{
		$userId = (int)CurrentUser::get()->getId();
		$templateAddUrl = (new TemplatePathMaker(0, PathMaker::EDIT_ACTION, $userId))
			->addQueryParam('context', $this->context)
			->makeEntityPath();

		return [
			'templateAddUrl' => $templateAddUrl,
			'canCreateTemplate' => TemplateAccessController::can($userId, ActionDictionary::ACTION_TEMPLATE_CREATE),
			'context' => $this->context,
		];
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
				|| $dialog->getItemCollection()->get(static::ENTITY_ID, $itemId)
			)
			{
				continue;
			}

			$dialog->addItem(
				new Item([
					'entityId' => static::ENTITY_ID,
					'id' => $itemId,
					'title' => $templates[$itemId],
					'tabs' => 'recents',
				])
			);

			if ($dialog->getItemCollection()->count() >= static::ENTITY_ID)
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
		return $this->makeTemplateItems($this->getTemplates($options));
	}

	private function getTemplates(array $options = []): array
	{
		$options = array_merge($this->getOptions(), $options);
		$templates = [];

		$order = ['ID' => 'desc'];
		$filter = $this->getFilterByOptions($options);
		$parameters = [
			'USER_ID' => $GLOBALS['USER']->getId(),
		];

		$navigation = [
			'NAV_PARAMS' => [
				'nTopCount' => static::LIMIT,
			],
		];
		$select = ['ID', 'TITLE'];

		$templatesResult = CTaskTemplates::GetList($order, $filter, $navigation, $parameters, $select);
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

		$filter['!ID'][] = $this->templateId;

		return $filter;
	}

	private function makeTemplateItems(array $templates): array
	{
		return self::makeItems($templates);
	}

	private static function makeItems(array $templates): array
	{
		$result = [];
		foreach ($templates as $id => $title)
		{
			if ($title !== '')
			{
				$result[] = new Item([
					'entityId' => static::ENTITY_ID,
					'id' => $id,
					'title' => $title,
					'tabs' => 'recents',
				]);
			}
		}

		return $result;
	}

	public static function getTemplateUrl(): string
	{
		$userId = (int)CurrentUser::get()->getId();

		return (new TemplatePathMaker(0, PathMaker::DEFAULT_ACTION, $userId))->makePathForEntitySelector();
	}

	public static function getTemplateLinkTitle(): string
	{
		return Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_TASK_TEMPLATE_PROVIDER_ITEM_LINK_TITLE');
	}
}