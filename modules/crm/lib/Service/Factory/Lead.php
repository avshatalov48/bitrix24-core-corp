<?php

namespace Bitrix\Crm\Service\Factory;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Item;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\Service;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\NotSupportedException;

class Lead extends Service\Factory
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

	public function isDocumentGenerationEnabled(): bool
	{
		return true;
	}

	public function isLinkWithProductsEnabled(): bool
	{
		return true;
	}

	public function getStagesEntityId(?int $categoryId = null): ?string
	{
		return 'STATUS';
	}

	public function isNewRoutingForListEnabled(): bool
	{
		return false;
	}

	public function isAutomationEnabled(): bool
	{
		return true;
	}

	public function isBizProcEnabled(): bool
	{
		return true;
	}

	public function isObserversEnabled(): bool
	{
		return true;
	}

	public function isClientEnabled(): bool
	{
		return true;
	}

	public function getDataClass(): string
	{
		return LeadTable::class;
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldsMap(): array
	{
		return [
			Item::FIELD_NAME_STAGE_ID => 'STATUS_ID',
			Item::FIELD_NAME_CREATED_TIME => 'DATE_CREATE',
			Item::FIELD_NAME_MOVED_TIME => 'DATE_MODIFY',
			// todo common field CLOSE_DATE is Date, lead DATE_CLOSED is DateTime. What do we do?
			// Item::FIELD_NAME_CLOSE_DATE => 'DATE_CLOSED',
			Item::FIELD_NAME_CREATED_BY => 'CREATED_BY_ID',
			Item::FIELD_NAME_UPDATED_BY => 'MODIFY_BY_ID'
		];
	}

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Lead;
	}

	protected function getFieldsSettings(): array
	{
		return \CCrmLead::GetFieldsInfo();
	}

	public function createCategory(array $data = []): Category
	{
		throw new NotSupportedException('Lead doesn\'t support categories');
	}

	protected function loadCategories(): array
	{
		throw new NotSupportedException('Lead doesn\'t support categories');
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
		throw new InvalidOperationException('Lead factory is not ready to work with items yet');
	}

	public function getItem(int $id): ?Item
	{
		throw new InvalidOperationException('Lead factory is not ready to work with items yet');
	}

	public function createItem(array $data = []): Item
	{
		throw new InvalidOperationException('Lead factory is not ready to work with items yet');
	}
}
