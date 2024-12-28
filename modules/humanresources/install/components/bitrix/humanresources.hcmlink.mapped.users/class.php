<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection;
use Bitrix\HumanResources\Item\HcmLink\Company;
use Bitrix\HumanResources\Item\HcmLink\Person;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\UI\PageNavigation;

\Bitrix\Main\Loader::includeModule('humanresources');
\Bitrix\Main\Loader::includeModule('crm');

class HumanResourcesHcmLinkMappedUsersComponent extends \CBitrixComponent
{
	private const HCMLINK_FILTER_ID = 'HCMLINK_FILTER_ID_MAPPED';
	private const DEFAULT_PAGE_SIZE = 20;
	private const DEFAULT_NAV_KEY = 'hr-mapped-users-list-nav';

	private ?Company $company = null;

	private string $templatePage = '';
	private int $pageSize = self::DEFAULT_PAGE_SIZE;

	public function executeComponent()
	{
		if (!Feature::instance()->isHcmLinkAvailable())
		{
			ShowError('Feature is not available.');
			return;
		}

		if (!$this->checkAccess())
		{
			$this->includeComponentTemplate('not-available');
			return;
		}

		$this->init();
		if ($this->company === null)
		{
			$this->includeComponentTemplate('error');
		}
		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	protected function init()
	{
		$this->company = $this->arParams['COMPANY'] ?? null;
	}

	private function checkAccess(): bool
	{
		return Container::getHcmLinkAccessService()->canRead();
	}

	private function prepareResult(): void
	{
		$this->arResult['COMPANY'] = $this->company;

		$totalCount = Container::getHcmLinkPersonRepository()->countAllMappedByCompanyId($this->company->id);
		$this->arResult['NAVIGATION_OBJECT'] = $this->getNavigationObject($totalCount);

		$mappedPersons = $this->preparePersonsData();
		$this->arResult['MAPPED_PERSONS'] = $mappedPersons;

		$userIds = $mappedPersons->map(static fn(Person $person) => $person->userId);
		$this->arResult['USERS'] = $this->getUsers($userIds);

		$this->arResult['COMPANY_ID'] = $this->company->id;
		$this->arResult['PAGE_TITLE'] = $this->company->title;
		$this->arResult['COUNT'] = $totalCount;
		$this->arResult['COLUMNS'] = $this->getColumns();
		$this->arResult['FILTER'] = $this->getFilter();
		$this->arResult['UNMAPPED_COUNT'] = Container::getHcmLinkPersonRepository()->countUnmappedPersons(
			$this->company->id
		);
	}

	private function getNavigationObject(int $count): PageNavigation
	{
		$this->pageSize = isset($this->arParams['PAGE_SIZE']) && (int)$this->arParams['PAGE_SIZE'] > 0
				? (int)$this->arParams['PAGE_SIZE']
				: self::DEFAULT_PAGE_SIZE
		;
		$navigationKey = $this->arParams['NAVIGATION_KEY'] ?? self::DEFAULT_NAV_KEY;

		$pageNavigation = new PageNavigation($navigationKey);
		$pageNavigation
			->setPageSize($this->pageSize)
			->setRecordCount($count)
			->allowAllRecords(false)
			->initFromUri()
		;
		$this->arResult['NAVIGATION_OBJECT'] = $pageNavigation;

		return $pageNavigation;
	}

	private function getUsers(array $userIds): array
	{
		$result = [];
		$users = \Bitrix\Main\UserTable::query()->whereIn('ID', $userIds)->fetchCollection()->getAll();
		foreach ($users as $user)
		{
			$result[$user->getId()] = $user;
		}

		return $result;
	}

	private function getColumns(): array
	{
		return [
			[
				'id' => 'ID',
				'name' => 'ID',
				'editable' => false,
				'type' => Grid\Types::GRID_INT,
			],
			[
				'id' => 'LOCAL',
				'name' => Loc::getMessage('HCMLINK_GRID_MAP_FIELD_TITLE_USER'),
				'default' => true,
				'editable' => false,
				'resizeable' => false,
			],
			[
				'id' => 'PERSON',
				'name' => Loc::getMessage('HCMLINK_GRID_MAP_FIELD_TITLE_PERSON'),
				'default' => true,
				'editable' => false,
				'resizeable' => false,
			],
		];
	}

	public function preparePersonsData(): PersonCollection
	{
		$filterOptions = $this->getFilterOptions();
		$requestFilter = $this->getRequestFilters($filterOptions);

		return Container::getHcmLinkPersonRepository()
			->getMappedPersonsByCompanyId(
				$this->company->id,
				$this->getFilterForQuery($requestFilter),
				$this->pageSize,
				(int)$this->arResult['NAVIGATION_OBJECT']->getOffset()
			)
		;
	}

	private function getFilter(): array
	{
		return [
			'person' => [
				'id' => 'PERSON',
				'default' => true,
				'name' => Loc::getMessage('HCMLINK_GRID_MAP_FILTER_FIELD_PERSON'),
				'type' => 'string',
			],
		];
	}

	private function getFilterForQuery(array $requestFilter): ConditionTree
	{
		$filter = Bitrix\Main\ORM\Query\Query::filter();

		if (
			(isset($requestFilter['PERSON']) && $requestFilter['PERSON'] !== '')
			|| (isset($requestFilter['FIND']) && $requestFilter['FIND'] !== '')
		)
		{
			$title = (string)$requestFilter['PERSON'];
			if (empty($title))
			{
				$title = (string)$requestFilter['FIND'];
			}

			$filter->whereLike('TITLE', "%{$title}%");
		}

		return $filter;
	}

	private function getFilterOptions(): Filter\Options
	{
		return new Filter\Options(self::HCMLINK_FILTER_ID);
	}

	private function getRequestFilters(Filter\Options $filterOptions): array
	{
		return $filterOptions->getFilter($this->arResult['FILTER']);
	}
}