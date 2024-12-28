<?php

namespace Bitrix\Crm\Copilot\CallAssessment\EntitySelector;

use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;

final class CallScriptProvider extends BaseProvider
{
	public const ENTITY_ID = 'copilot_call_script';
	private const MAX_COUNT = 20;

	public function __construct()
	{
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function fillDialog(Dialog $dialog): void
	{
		$this->addRecentItems($dialog);
		$this->addItems($dialog);
		$this->addTab($dialog);
	}

	private function addRecentItems(Dialog $dialog): void
	{
		$recentIds = $this->getRecentItemIds($dialog);
		$items = $this->getItems($recentIds);

		array_walk(
			$items,
			static function (Item $item, int $index) use ($dialog) {
				if (empty($dialog->getContext()))
				{
					$item->setSort($index);
				}
				$dialog->addRecentItem($item);
			}
		);
	}

	private function addItems(Dialog $dialog): void
	{
		if ($dialog->getItemCollection()->count() >= self::MAX_COUNT)
		{
			return;
		}

		$recentIds = $this->getRecentItemIds($dialog);
		$filter = empty($recentIds) ? [] : ['!ID' => $recentIds];
		$filter['IS_ENABLED'] = 'Y';

		$collection = CopilotCallAssessmentController::getInstance()->getList([
			'select' => ['*', 'CLIENT_TYPES'],
			'filter' => $filter,
			'order' => ['TITLE' => 'ASC'],
		]);

		$items = $this->createItemsByCollection($collection);

		foreach ($items as $item)
		{
			$dialog->addItem($item);
			if ($dialog->getItemCollection()->count() >= self::MAX_COUNT)
			{
				break;
			}
		}
	}

	private function getRecentItemIds(Dialog $dialog): array
	{
		$itemsRecent = $dialog->getRecentItems()->getEntityItems(self::ENTITY_ID);

		return array_keys($itemsRecent);
	}

	private function addTab(Dialog $dialog): void
	{
		$tab = new Tab([
			'id' => self::ENTITY_ID,
			'title' => Loc::getMessage('COPILOT_CALL_SCRIPT_TAB_TITLE'),
			'stub' => true,
		]);

		$dialog->addTab($tab);
	}

	public function getItems(array $ids): array
	{
		if (empty($ids))
		{
			return [];
		}

		$collection = CopilotCallAssessmentController::getInstance()->getList([
			'select' => ['*', 'CLIENT_TYPES'],
			'filter' => [
				'@ID' => $ids,
				'IS_ENABLED' => 'Y',
			],
		]);

		return $this->createItemsByCollection($collection);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$query = $searchQuery->getQuery();

		$collection = CopilotCallAssessmentController::getInstance()->getList([
			'filter' => [
				'%TITLE' => $query,
				'IS_ENABLED' => 'Y',
			],
		]);

		$items = $this->createItemsByCollection($collection);
		$dialog->addItems($items);
	}

	/**
	 * @return Item[]
	 */
	private function createItemsByCollection(Collection $collection): array
	{
		$items = [];

		/** @var EntityObject $callAssessmentEntity */
		foreach ($collection as $callAssessmentEntity)
		{
			$callAssessmentItem = CallAssessmentItem::createFromEntity($callAssessmentEntity);
			$items[] = (new ItemAdapter($callAssessmentItem))->addTab(self::ENTITY_ID);
		}

		return $items;
	}
}
