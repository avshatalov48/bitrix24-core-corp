<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Flow\Efficiency\Efficiency;
use Bitrix\Tasks\Flow\Efficiency\LastMonth;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\Query\FlowQuery;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

final class FlowProvider extends BaseProvider
{
	private string $entityId = 'flow';
	private int $maxCount = 20;

	private \Bitrix\Tasks\Flow\Provider\FlowProvider $flowProvider;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->flowProvider = new \Bitrix\Tasks\Flow\Provider\FlowProvider();

		$this->options['onlyActive'] = (bool) ($options['onlyActive'] ?? null);
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getItems(array $ids): array
	{
		return $this->getSelectedFlowItems($ids);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getSelectedItems(array $ids): array
	{
		return $this->getSelectedFlowItems($ids);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$items = $this->getFlowItems($this->maxCount, $searchQuery);

		$dialog->addItems($items);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addRecentItems($this->getFlowItems($this->maxCount));
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getFlowItems(int $maxCount, ?SearchQuery $searchQuery = null): array
	{
		$items = [];

		$flows = $this->getFlows($maxCount, $searchQuery);

		$this->loadEfficiency(...$flows);

		foreach ($flows as $flow)
		{
			$items[] = $this->getItem($flow);
		}

		return $items;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getSelectedFlowItems(array $ids): array
	{
		$items = [];

		$flows = $this->getSelectedFlows($ids);

		$this->loadEfficiency(...$flows);

		foreach ($flows as $flow)
		{
			$items[] = $this->getItem($flow);
		}

		return $items;
	}

	private function getFlows(int $maxCount, ?SearchQuery $searchQuery = null): array
	{
		$flowQuery = new FlowQuery(CurrentUser::get()->getId());

		$navigation = $this->getNavigation($maxCount);

		$select = [
			'ID',
			'NAME',
			'GROUP_ID',
			'TEMPLATE_ID',
		];

		$query = $flowQuery
			->setSelect($select)
			->setPageNavigation($navigation)
			->setOrderBy(['ACTIVITY' => 'DESC'])
		;

		$filter = new ConditionTree();

		if ($this->getOption('onlyActive'))
		{
			$filter->where('ACTIVE', 1);
		}

		if ($searchQuery)
		{
			$filter->whereLike('NAME', "%{$searchQuery->getQuery()}%");
		}

		$query->setWhere($filter);

		try
		{
			return $this->flowProvider->getList($query)->getFlows();
		}
		catch (ProviderException)
		{
			return [];
		}
	}

	private function getSelectedFlows(array $ids): array
	{
		$flowQuery = new FlowQuery(CurrentUser::get()->getId());

		$select = [
			'ID',
			'NAME',
			'GROUP_ID',
			'TEMPLATE_ID',
		];

		$filter = new ConditionTree();
		$filter->whereIn('ID', $ids);

		if ($this->getOption('onlyActive'))
		{
			$filter->where('ACTIVE', 1);
		}

		$query = $flowQuery
			->setSelect($select)
			->setWhere($filter)
			->setOrderBy(['ACTIVITY' => 'DESC'])
		;

		try
		{
			return $this->flowProvider->getList($query)->getFlows();
		}
		catch (ProviderException)
		{
			return [];
		}
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function loadEfficiency(Flow ...$flows): void
	{
		$efficiency = new Efficiency(new LastMonth());

		$flowIds = [];
		foreach ($flows as $flow)
		{
			$flowIds[] = $flow->getId();
		}

		if ($flowIds)
		{
			$efficiency->load(...$flowIds);
		}
	}

	private function getItem(Flow $flow): Item
	{
		$efficiency = $this->flowProvider->getEfficiency($flow);

		$lowEfficiencyThreshold = 70;

		return new Item([
			'entityId' => $this->entityId,
			'id' => $flow->getId(),
			'title' => $flow->getName(),
			'tabs' => 'recents',
			'customData' => [
				'groupId' => $flow->getGroupId(),
				'templateId' => $flow->getTemplateId(),
			],
			'badges' => [
				[
					'title' => $efficiency . '%',
					'textColor' => ($efficiency <= $lowEfficiencyThreshold) ? '#E92F2A' : '#7FA800',
					'bgColor' => ($efficiency <= $lowEfficiencyThreshold) ? '#FFE8E8' : '#F1FBD0',

				],
			],
			'badgesOptions' => [
				'fitContent' => true,
			],
		]);
	}

	private function getNavigation(int $maxCount): PageNavigation
	{
		$navigation = new PageNavigation('flow-provider');

		$navigation->setCurrentPage(1);
		$navigation->setPageSize($maxCount);

		return $navigation;
	}
}
