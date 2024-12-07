<?php

namespace Bitrix\Crm\Service\Factory;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Sale\OrderTable;

final class Order extends Service\Factory
{
	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Order;
	}

	public function isNewRoutingForDetailEnabled(): bool
	{
		return false;
	}

	public function isNewRoutingForAutomationEnabled(): bool
	{
		return false;
	}

	public function isUseInUserfieldEnabled(): bool
	{
		return false;
	}

	public function isNewRoutingForListEnabled(): bool
	{
		return false;
	}

	public function isDocumentGenerationEnabled(): bool
	{
		return false;
	}

	public function isClientEnabled(): bool
	{
		return true;
	}

	public function isDeferredCleaningEnabled(): bool
	{
		return false;
	}

	public function isLastActivitySupported(): bool
	{
		return false;
	}

	public function getDataClass(): string
	{
		return OrderTable::class;
	}

	protected function getFieldsSettings(): array
	{
		$result = [];

		$orderFields = \Bitrix\Crm\Order\Order::getFieldsDescription();

		$specialTypes = [
			'USER_ID' => 'user',
			'CURRENCY' => 'crm_currency',
			'COMPANY_ID' => 'crm_company',
			'CREATED_BY' => 'user',
			'RESPONSIBLE_ID' => 'user',
			'LOCKED_BY' => 'user',
			'EMP_PAYED_ID' => 'user',
			'EMP_DEDUCTED_ID' => 'user',
			'EMP_STATUS_ID' => 'user',
			'EMP_MARKED_ID' => 'user',
			'EMP_CANCELED_ID' => 'user',
		];

		$ignoredFields = [
			'SEARCH_CONTENT',
		];

		/** @var \Bitrix\Main\ORM\Fields\Field $field */
		foreach ($orderFields as $field)
		{
			$fieldName = $field['CODE'];
			if (in_array($fieldName, $ignoredFields, true))
			{
				continue;
			}

			$result[$fieldName] = [
				'TYPE' => $specialTypes[$fieldName] ?? $field['TYPE'],
			];
		}

		return $result;
	}

	public function getStagesEntityId(?int $categoryId = null): ?string
	{
		throw new NotSupportedException('Order stages do not use crm stages mechanism');
	}

	public function createCategory(array $data = []): Category
	{
		throw new NotSupportedException('Order doesn\'t support categories');
	}

	protected function loadCategories(): array
	{
		throw new NotSupportedException('Order doesn\'t support categories');
	}

	protected function getTrackedFieldNames(): array
	{
		return [];
	}

	protected function getDependantTrackedObjects(): array
	{
		return [];
	}

	public function getAddOperation(Item $item, Context $context = null): Operation\Add
	{
		throw new InvalidOperationException('Order factory is not ready to work with operations yet');
	}

	public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
	{
		throw new InvalidOperationException('Order factory is not ready to work with operations yet');
	}

	public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
	{
		throw new InvalidOperationException('Order factory is not ready to work with operations yet');
	}

	public function getConversionOperation(
		Item $item,
		EntityConversionConfig $configs,
		Context $context = null
	): Service\Operation\Conversion
	{
		throw new InvalidOperationException('Order factory is not ready to work with operations yet');
	}

	public function getCopyOperation(Item $item, Context $context = null): Operation\Copy
	{
		throw new InvalidOperationException('Order factory is not ready to work with operations yet');
	}

	public function getRestoreOperation(Item $item, Context $context = null): Operation\Restore
	{
		throw new InvalidOperationException('Order factory is not ready to work with operations yet');
	}

	public function getImportOperation(Item $item, Context $context = null): Operation\Import
	{
		throw new InvalidOperationException('Order factory is not ready to work with operations yet');
	}

	public function getItems(array $parameters = []): array
	{
		throw new InvalidOperationException('Order factory is not ready to work with items yet');
	}

	public function getItem(int $id, array $fieldsToSelect = ['*']): ?Item
	{
		throw new InvalidOperationException('Order factory is not ready to work with items yet');
	}

	public function createItem(array $data = []): Item
	{
		throw new InvalidOperationException('Order factory is not ready to work with items yet');
	}

	public function isCountersEnabled(): bool
	{
		return true;
	}

	public function getFieldsMap(): array
	{
		return [
			Item::FIELD_NAME_ASSIGNED => 'RESPONSIBLE_ID',
		];
	}
}
