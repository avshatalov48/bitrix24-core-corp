<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\ContactTable;

class ContactProvider extends EntityProvider
{
	/** @var ContactTable */
	protected static $dataClass = ContactTable::class;

	protected int $categoryId;

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->categoryId = (int)($options['categoryId'] ?? 0);
		$this->options['categoryId'] = $this->categoryId;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}

	protected function fetchEntryIds(array $filter): array
	{
		$filter['=CATEGORY_ID'] = $this->categoryId;

		$collection = static::$dataClass::getList([
			'select' => ['ID'],
			'filter' => $filter,
		])->fetchCollection();

		return $collection->getIdList();
	}

	protected function getAdditionalFilter(): array
	{
		return [
			'=CATEGORY_ID' =>  $this->categoryId,
		];
	}
}