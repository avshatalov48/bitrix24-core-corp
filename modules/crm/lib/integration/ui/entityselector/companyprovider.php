<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\CompanyTable;

class CompanyProvider extends EntityProvider
{
	/** @var CompanyTable */
	protected static $dataClass = CompanyTable::class;

	protected bool $enableMyCompanyOnly = false;
	protected bool $excludeMyCompany = false;
	protected $categoryId;

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->categoryId = (int)($options['categoryId'] ?? 0);
		$this->options['categoryId'] = $this->categoryId;

		$this->enableMyCompanyOnly = (bool)($options['enableMyCompanyOnly'] ?? $this->enableMyCompanyOnly);
		$this->excludeMyCompany = (bool)($options['excludeMyCompany'] ?? $this->excludeMyCompany);

		$this->options['enableMyCompanyOnly'] = $this->enableMyCompanyOnly;
		$this->options['excludeMyCompany'] = $this->excludeMyCompany;
	}

	public function getRecentItemIds(string $context): array
	{
		if($this->enableMyCompanyOnly || $this->excludeMyCompany)
		{
			$ids = CompanyTable::getList([
				'select' => ['ID'],
				'order' => [
					'ID' => 'ASC',
				],
				'filter' => $this->getCompanyFilter(),
			])->fetchCollection()->getIdList();
		}
		else
		{
			$ids = parent::getRecentItemIds($context);
		}

		return $ids;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}

	protected function getCategoryId(): int
	{
		return $this->options['categoryId'];
	}

	protected function fetchEntryIds(array $filter): array
	{
		$collection = static::$dataClass::getList([
			'select' => ['ID'],
			'filter' => array_merge($filter, $this->getAdditionalFilter()),
		])->fetchCollection();

		return $collection->getIdList();
	}

	protected function getAdditionalFilter(): array
	{
		$filter = [
			'=CATEGORY_ID' =>  $this->categoryId,
		];

		return array_merge($filter, $this->getCompanyFilter());
	}

	private function getCompanyFilter(): array
	{
		$filter = [];

		if($this->enableMyCompanyOnly)
		{
			$filter = [
				'=IS_MY_COMPANY' => 'Y',
			];
		}
		elseif ($this->excludeMyCompany)
		{
			$filter = [
				'=IS_MY_COMPANY' => 'N',
			];
		}

		return $filter;
	}
}
