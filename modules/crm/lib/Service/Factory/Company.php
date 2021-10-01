<?php

namespace Bitrix\Crm\Service\Factory;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Crm\CompanyTable;

class Company extends Service\Factory
{
	public function isSourceEnabled(): bool
	{
		return true;
	}

	public function isNewRoutingForDetailEnabled(): bool
	{
		return false;
	}

	public function isRecyclebinEnabled(): bool
	{
		return true;
	}

	public function isNewRoutingForAutomationEnabled(): bool
	{
		return false;
	}

	public function isUseInUserfieldEnabled(): bool
	{
		return true;
	}

	public function isCrmTrackingEnabled(): bool
	{
		return true;
	}

	public function isStagesSupported(): bool
	{
		return false;
	}

	public function isStagesEnabled(): bool
	{
		return false;
	}

	public function getStagesEntityId(?int $categoryId = null): ?string
	{
		throw new NotSupportedException('Company doesn\'t support stages');
	}

	public function isNewRoutingForListEnabled(): bool
	{
		return false;
	}

	public function isAutomationEnabled(): bool
	{
		return false;
	}

	public function isBizProcEnabled(): bool
	{
		return true;
	}

	public function isObserversEnabled(): bool
	{
		return false;
	}

	public function isClientEnabled(): bool
	{
		return false;
	}

	public function getDataClass(): string
	{
		return CompanyTable::class;
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldsMap(): array
	{
		return [
			Item::FIELD_NAME_CREATED_TIME => 'DATE_CREATE',
			Item::FIELD_NAME_MOVED_TIME => 'DATE_MODIFY',
			Item::FIELD_NAME_CREATED_BY => 'CREATED_BY_ID',
			Item::FIELD_NAME_UPDATED_BY => 'MODIFY_BY_ID'
		];
	}

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}

	protected function getFieldsSettings(): array
	{
		return \CCrmCompany::GetFieldsInfo();
	}

	public function createCategory(array $data = []): Category
	{
		throw new NotSupportedException('Company doesn\'t support categories');
	}

	protected function loadCategories(): array
	{
		throw new NotSupportedException('Company doesn\'t support categories');
	}

	protected function getTrackedFieldNames(): array
	{
		return [];
	}

	protected function getDependantTrackedObjects(): array
	{
		return [];
	}

	public function getItems(array $parameters = []): array
	{
		throw new InvalidOperationException('Company factory is not ready to work with items yet');
	}

	public function getItem(int $id): ?Item
	{
		throw new InvalidOperationException('Company factory is not ready to work with items yet');
	}

	public function createItem(array $data = []): Item
	{
		throw new InvalidOperationException('Company factory is not ready to work with items yet');
	}
}
