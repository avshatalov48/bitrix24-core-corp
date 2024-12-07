<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\Autorun\Dto\SetExportPreparedData;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\TransactionWrapper;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

final class SetExport extends Base
{
	protected function isEntityTypeSupported(Factory $factory): bool
	{
		return $factory->isFieldExists(Item\Contact::FIELD_NAME_EXPORT);
	}

	protected function getPreparedDataDtoClass(): string
	{
		return SetExportPreparedData::class;
	}

	protected function prepareData(
		string $hash,
		string $gridId,
		int $entityTypeId,
		array $filter,
		array $params,
		Factory $factory
	): Dto\SetExportPreparedData
	{
		return new SetExportPreparedData([
			'hash' => $hash,
			'gridId' => $gridId,
			'entityTypeId' => $entityTypeId,
			'filter' => $filter,
			'export' => $params['export'] ?? null,
		]);
	}

	protected function isItemShouldBeSkipped(Factory $factory, Item $item, PreparedData $data): bool
	{
		if (!($data instanceof SetExportPreparedData))
		{
			throw new ArgumentTypeException('data', SetExportPreparedData::class);
		}

		return $item->get(Item\Contact::FIELD_NAME_EXPORT) === $data->export;
	}

	protected function isWrapItemProcessingInTransaction(): bool
	{
		return false;
	}

	protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
	{
		if (!($data instanceof SetExportPreparedData))
		{
			throw new ArgumentTypeException('data', SetExportPreparedData::class);
		}

		$item->set(Item\Contact::FIELD_NAME_EXPORT, $data->export);

		$operation = $factory->getUpdateOperation($item);

		return (new TransactionWrapper($operation))->launch();
	}
}
