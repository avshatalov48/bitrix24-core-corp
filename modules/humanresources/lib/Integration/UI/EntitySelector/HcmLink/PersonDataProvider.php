<?php

namespace Bitrix\HumanResources\Integration\UI\EntitySelector\HcmLink;

use Bitrix\HumanResources\Contract\Repository\HcmLink\CompanyRepository;
use Bitrix\HumanResources\Contract\Repository\HcmLink\EmployeeRepository;
use Bitrix\HumanResources\Contract\Repository\HcmLink\PersonRepository;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item\Collection\HcmLink\EmployeeCollection;
use Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection;
use Bitrix\HumanResources\Item\HcmLink\Employee;
use Bitrix\HumanResources\Item\HcmLink\Person;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\EmployeeDataType;
use Bitrix\HumanResources\Util\PersonUtil;
use Bitrix\Main\Search\Content;
use Bitrix\UI\EntitySelector;
use Bitrix\UI\EntitySelector\Item;

/**
 * Data provider for Person
 *
 * Custom options:
 * - companyId - target company
 * - nameTemplate - string for formatting output title. Supports *_TEMPLATE constants
 * 		of PersonUtil (see PersonUtil::sanitizeNameTemplate) as well as whitespaces and commas (",")
 */
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

		if (isset($options['nameTemplate']) && is_string($options['nameTemplate']))
		{
			$this->options['nameTemplate'] = PersonUtil::sanitizeNameTemplate($options['nameTemplate']);
		}

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

		return $this->getItemsByPersonCollection($personCollection);
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
			$items = $this->getItemsByPersonCollection($personCollection);

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
		$items = $this->getItemsByPersonCollection($personCollection);

		$dialog->addItems($items);
	}

	/**
	 * @param PersonCollection $personCollection
	 * @return array<Item>
	 * @throws WrongStructureItemException
	 */
	private function getItemsByPersonCollection(\Bitrix\HumanResources\Item\Collection\HcmLink\PersonCollection $personCollection): array
	{
		$items = [];

		$employeeCollection = $this->employeeRepository->getCollectionByPersonIds(
			$personCollection->map(fn(Person $person) => $person->id)
		);
		foreach ($personCollection as $person)
		{
			$employees = $employeeCollection->filter(fn(Employee $employee) => $employee->personId === $person->id);
			$subTitle = PersonUtil::formatPersonSubtitle($employees);
			$snils = PersonUtil::getSnils($employees);

			$items[] = new EntitySelector\Item([
				'id' => $person->id,
				'entityId' => self::ENTITY_ID,
				'entityType' => self::ENTITY_TYPE,
				'tabs' => ['persons'],
				'title' => $this->getTitle($person, $employees),
				'subtitle' => $subTitle,
				'customData' => [
					'snils' => $snils,
				],
			]);
		}

		return $items;
	}

	/**
	 * Get Title of current item, formatting by nameTemplate if provided
	 *
	 * @param Person $person
	 * @param EmployeeCollection $employees
	 * @return string
	 */
	private function getTitle(Person $person, EmployeeCollection $employees): string
	{
		if (!$this->getOption('nameTemplate') || $employees->empty())
		{
			return $person->title;
		}

		return PersonUtil::formatFullName($employees, $this->getOption('nameTemplate'));
	}
}
