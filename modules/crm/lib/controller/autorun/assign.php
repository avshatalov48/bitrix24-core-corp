<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\AssignPreparedData;
use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\TransactionWrapper;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

final class Assign extends Base
{
	protected function getPreparedDataDtoClass(): string
	{
		return AssignPreparedData::class;
	}

	protected function prepareData(
		string $hash,
		string $gridId,
		int $entityTypeId,
		array $filter,
		array $params,
		Factory $factory
	): Dto\AssignPreparedData
	{
		return new AssignPreparedData([
			'hash' => $hash,
			'gridId' => $gridId,
			'entityTypeId' => $entityTypeId,
			'filter' => $filter,
			'assignedById' => $params['assignedById'] ?? null,
		]);
	}

	protected function isItemShouldBeSkipped(Factory $factory, Item $item, PreparedData $data): bool
	{
		if (!($data instanceof AssignPreparedData))
		{
			throw new ArgumentTypeException('data', AssignPreparedData::class);
		}

		return $item->getAssignedById() === $data->assignedById;
	}

	protected function isWrapItemProcessingInTransaction(): bool
	{
		// transaction is managed manually in this action
		return false;
	}

	protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
	{
		if (!($data instanceof AssignPreparedData))
		{
			throw new ArgumentTypeException('data', AssignPreparedData::class);
		}

		$item->setAssignedById($data->assignedById);

		$operation = $factory->getUpdateOperation($item);

		return (new TransactionWrapper($operation))->launch();
	}
}
