<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class SprintSelectorProvider extends BaseProvider
{
	private $entityId = 'sprint-selector';
	private $maxCount = 20;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['groupId'] = (int) ($options['groupId'] ?? null);
		$this->options['onlyCompleted'] = (bool) ($options['onlyCompleted'] ?? null);
	}

	public function isAvailable(): bool
	{
		if (!$GLOBALS['USER']->isAuthorized())
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$groupId = $this->getOption('groupId');

		$group = Workgroup::getById($groupId);
		if (!$group || !$group->isScrumProject())
		{
			return false;
		}

		return Group::canReadGroupTasks($GLOBALS['USER']->getId(), $groupId);
	}

	public function getItems(array $ids): array
	{
		$groupId = $this->getOption('groupId');

		return $this->getSelectedSprintItems($groupId, $ids);
	}

	public function getSelectedItems(array $ids): array
	{
		$groupId = $this->getOption('groupId');

		return $this->getSelectedSprintItems($groupId, $ids);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$groupId = $this->getOption('groupId');

		$dialog->addItems($this->getSprintItems($groupId, $this->maxCount, $searchQuery));

		if ($dialog->getItemCollection()->count() >= $this->maxCount)
		{
			$searchQuery->setCacheable(false);
		}
	}

	public function fillDialog(Dialog $dialog): void
	{
		$groupId = $this->getOption('groupId');

		$dialog->addRecentItems($this->getSprintItems($groupId, $this->maxCount));
	}

	/**
	 * @param int $groupId Group id.
	 * @return Item[]
	 */
	private function getSprintItems(int $groupId, int $maxCount, ?SearchQuery $searchQuery = null): array
	{
		$items = [];

		foreach ($this->getSprints($groupId, $maxCount, $searchQuery) as $sprint)
		{
			$items[] = $this->getItem($sprint);
		}

		return $items;
	}

	/**
	 * @param int $groupId Group id.
	 * @return EntityForm[]
	 */
	private function getSprints(int $groupId, int $maxCount, ?SearchQuery $searchQuery = null): array
	{
		$sprints = [];

		$entityService = new EntityService($GLOBALS['USER']->getId());

		$select = [
			'ID',
			'NAME',
			'DATE_START',
			'DATE_END',
		];

		$onlyCompleted = $this->getOption('onlyCompleted');
		$filter = [
			'=GROUP_ID' => $groupId,
			'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
		];
		if ($onlyCompleted)
		{
			$filter['=STATUS'] = EntityForm::SPRINT_COMPLETED;
		}
		else
		{
			$filter['!=STATUS'] = EntityForm::SPRINT_PLANNED;
		}
		if ($searchQuery)
		{
			$filter['?NAME'] = $searchQuery->getQuery();
		}

		$order = ['DATE_END' => 'DESC'];

		$nav = $this->getNavigation($maxCount);

		$queryResult = $entityService->getList($nav, $filter, $select, $order);
		$n = 0;
		while ($sprintData = $queryResult->fetch())
		{
			if ($nav && (++$n > $nav->getPageSize()))
			{
				break;
			}

			$sprint = new EntityForm();

			$sprint->fillFromDatabase($sprintData);

			$sprints[] = $sprint;
		}

		return $sprints;
	}

	/**
	 * @param int $groupId Group id.
	 * @return Item[]
	 */
	private function getSelectedSprintItems(int $groupId, array $ids): array
	{
		$items = [];

		$entityService = new EntityService($GLOBALS['USER']->getId());

		$select = [
			'ID',
			'NAME',
			'DATE_START',
			'DATE_END',
		];
		$filter = [
			'=ID' => $ids,
			'=GROUP_ID' => $groupId,
			'=ENTITY_TYPE' => EntityForm::SPRINT_TYPE,
		];
		$order = ['DATE_END' => 'DESC'];

		$sort = 0;

		$queryResult = $entityService->getList(null, $filter, $select, $order);
		while ($sprintData = $queryResult->fetch())
		{
			$sprint = new EntityForm();

			$sprint->fillFromDatabase($sprintData);

			$items[] = $this->getItem($sprint, ++$sort);
		}

		return $items;
	}

	private function getItem(EntityForm $sprint, ?int $sort = null): Item
	{
		$dateStartFormatted = $sprint->getDateStart()->format(Date::getFormat());
		$dateEndFormatted = $sprint->getDateEnd()->format(Date::getFormat());

		return new Item([
			'entityId' => $this->entityId,
			'id' => $sprint->getId(),
			'title' => $sprint->getName() . ' (' . $dateStartFormatted . ' - ' . $dateEndFormatted . ')',
			'tabs' => 'recents',
			'sort' => $sort,
			'customData' => [
				'name' => $sprint->getName(),
				'label' => $dateStartFormatted . ' - ' . $dateEndFormatted,
				'dateStart' => $dateStartFormatted,
				'dateEnd' => $dateEndFormatted,
			]
		]);
	}

	private function getNavigation(int $maxCount): PageNavigation
	{
		$navigation = new PageNavigation('sprint-selector-provider');

		$navigation->setCurrentPage(1);
		$navigation->setPageSize($maxCount);

		return $navigation;
	}
}