<?php

namespace Bitrix\HumanResources\Ui\EntitySelector\HcmLink;

use Bitrix\HumanResources\Contract\Repository\HcmLink\CompanyRepository;
use Bitrix\HumanResources\Contract\Repository\HcmLink\EmployeeRepository;
use Bitrix\HumanResources\Contract\Repository\HcmLink\PersonRepository;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\HcmLink\EmployeeCollection;
use Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection;
use Bitrix\HumanResources\Item\HcmLink\Company;
use Bitrix\HumanResources\Item\HcmLink\Employee;
use Bitrix\HumanResources\Item\HcmLink\Person;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Search\Content;
use Bitrix\UI\EntitySelector;
use Bitrix\UI\EntitySelector\Item;

class PersonDataProvider extends EntitySelector\BaseProvider
{
	public const ENTITY_ID = 'hcmlink-person-data';
	public const ENTITY_TYPE = 'hcmlink-person-data';

	private PersonRepository $personRepository;
	private EmployeeRepository $employeeRepository;
	private CompanyRepository $companyRepository;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options = $options;

		$this->companyRepository = Container::getHcmLinkCompanyRepository();
		$this->personRepository = Container::getHcmLinkPersonRepository();
		$this->employeeRepository = Container::getHcmLinkEmployeeRepository();
	}

	public function isAvailable(): bool
	{
		return true;
	}

	/**
	 * @param array $ids
	 * @return array
	 * @throws WrongStructureItemException
	 */
	public function getItems(array $ids): array
	{
		$result = [];

		$companyId = (int)$this->getOption('companyId');
		$company = $this->companyRepository->getById($companyId);

		if ($company === null)
		{
			return $result;
		}

		/** @var object{id: int} $company */
		$personCollection = $this->personRepository->getByIdsExcludeMapped($ids, $company->id);

		return $this->getItemsByPeronCollection($personCollection);
	}

	public function getPreselectedItems(array $ids): array
	{
		return $this->getItems($ids);
	}

	public function fillDialog(EntitySelector\Dialog $dialog): void
	{
		$companyId = (int)$this->getOption('companyId');
		if ($companyId > 0)
		{
			$personCollection = $this->personRepository->getByCompanyExcludeMapped($companyId, 50);
			$items = $this->getItemsByPeronCollection($personCollection);

			$dialog->addItems($items);
		}
	}

	public function doSearch(EntitySelector\SearchQuery $searchQuery, EntitySelector\Dialog $dialog): void
	{
		$companyId = (int)$this->getOption('companyId');
		$searchText = $searchQuery->getQuery();
		if (!\Bitrix\Main\Search\Content::canUseFulltextSearch($searchText))
		{
			return;
		}

		$personCollection = Container::getHcmLinkPersonRepository()->searchByIndexAndCompanyId(Content::prepareStringToken($searchText), $companyId, 20);
		$items = $this->getItemsByPeronCollection($personCollection);

		$dialog->addItems($items);
	}

	private function prepareSubTitle(EmployeeCollection $employees): string
	{
		$positions = [];
		$snils = '';
		foreach ($employees as $employee)
		{
			$data = $employee->data;
			if (empty($snils) && isset($data['snils']))
			{
				$snils = (string)$data['snils'];
			}

			if (isset($data['position']))
			{
				$positions[] = $data['position'];
			}
		}

		return implode(', ', $positions) . ' ' . $snils;
	}

	/**
	 * @param PersonCollection $personCollection
	 * @return array<Item>
	 * @throws WrongStructureItemException
	 */
	private function getItemsByPeronCollection(\Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection $personCollection): array
	{
		$items = [];

		$employeeCollection = $this->employeeRepository->getCollectionByPersonIds(
			$personCollection->map(fn(Person $person) => $person->id)
		);
		foreach ($personCollection as $person)
		{
			$employees = $employeeCollection->filter(fn(Employee $employee) => $employee->personId === $person->id);
			$subTitle = $this->prepareSubTitle($employees);

			$items[] = new EntitySelector\Item([
				'id' => $person->id,
				'entityId' => self::ENTITY_ID,
				'entityType' => self::ENTITY_TYPE,
				'tabs' => ['persons'],
				'title' => $person->title,
				'subtitle' => $subTitle,
				'customData' => [],
			]);
		}

		return $items;
	}
}