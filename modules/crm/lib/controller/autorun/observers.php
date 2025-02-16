<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\ObserversPreparedData;
use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\TransactionWrapper;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Result;

final class Observers extends Base
{
	protected function getPreparedDataDtoClass(): string
	{
		return ObserversPreparedData::class;
	}

	protected function prepareData(
		string $hash,
		string $gridId,
		int $entityTypeId,
		array $filter,
		array $params,
		Factory $factory
	): Dto\ObserversPreparedData
	{
		return new ObserversPreparedData([
			'hash' => $hash,
			'gridId' => $gridId,
			'entityTypeId' => $entityTypeId,
			'filter' => $filter,
			'observerIdList' => $params['observerIdList'] ?? null,
		]);
	}

	protected function isItemShouldBeSkipped(Factory $factory, Item $item, PreparedData $data): bool
	{
		if (!($data instanceof ObserversPreparedData))
		{
			throw new ArgumentTypeException('data', ObserversPreparedData::class);
		}

		$existObserverList = $item->getObservers();
		sort($existObserverList);
		$newObserverList = $data->observerIdList;
		sort($newObserverList);

		return $existObserverList === $newObserverList;
	}

	protected function isWrapItemProcessingInTransaction(): bool
	{
		// transaction is managed manually in this action
		return false;
	}

    protected function processItem(Factory $factory, Item $item, PreparedData $data): Result
    {
		if (!($data instanceof ObserversPreparedData))
		{
			throw new ArgumentTypeException('data', ObserversPreparedData::class);
		}

		$userPermissions = Container::getInstance()->getUserPermissions();

		if (!$userPermissions->checkUpdatePermissions($item->getEntityTypeId(), $item->getId(), $item->getCategoryId()))
		{
			return (new Result())->addError(ErrorCode::getAccessDeniedError());
		}

		$item->setObservers(array_unique(array_merge($item->getObservers(), $data->observerIdList)));

		$operation = $factory->getUpdateOperation($item);

		return (new TransactionWrapper($operation))->launch();
    }
}