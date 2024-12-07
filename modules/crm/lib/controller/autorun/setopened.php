<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\Autorun\Dto\SetOpenedPreparedData;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\TransactionWrapper;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

final class SetOpened extends Base
{
	protected function getPreparedDataDtoClass(): string
	{
		return SetOpenedPreparedData::class;
	}

	protected function prepareData(
		string $hash,
		string $gridId,
		int $entityTypeId,
		array $filter,
		array $params,
		Factory $factory
	): Dto\PreparedData
	{
		return new SetOpenedPreparedData([
			'hash' => $hash,
			'gridId' => $gridId,
			'entityTypeId' => $entityTypeId,
			'filter' => $filter,
			'isOpened' => $params['isOpened'],
		]);
	}

	protected function isItemShouldBeSkipped(Factory $factory, Item $item, PreparedData $data): bool
	{
		if (!($data instanceof SetOpenedPreparedData))
		{
			throw new ArgumentTypeException('data', SetOpenedPreparedData::class);
		}

		return $item->getOpened() === $data->isOpened;
	}

	protected function isWrapItemProcessingInTransaction(): bool
	{
		return false;
	}

	protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
	{
		if (!($data instanceof SetOpenedPreparedData))
		{
			throw new ArgumentTypeException('data', SetOpenedPreparedData::class);
		}

		$item->setOpened($data->isOpened);

		$operation = $factory->getUpdateOperation($item);

		return (new TransactionWrapper($operation))->launch();
	}
}
