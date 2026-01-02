<?php

namespace Bitrix\Crm\Recurring\Entity;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Model\Dynamic\RecurringTable;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;

final class Dynamic extends Base
{
	use Singleton;

	public const UNSET_DATE_PAY_BEFORE = 0;
	public const SET_DATE_PAY_BEFORE = 1;

	public function getList(array $parameters = [])
	{
		return RecurringTable::getList($parameters);
	}

	public function createEntity(array $entityFields, array $recurringParams): Result
	{
		$result = new Result();
		try
		{
			$entity = Item\DynamicNew::create();
			$entity->initFields($recurringParams);
			$entity->setTemplateFields($entityFields);
			$result = $entity->save();
		}
		catch (\Exception $exception)
		{
			$result->addError(new Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	public function add(array $fields): Result
	{
		$invoiceItem = Item\DynamicNew::create();
		$invoiceItem->initFields($fields);

		return $invoiceItem->save();
	}

	public function update($primary, array $data): Result
	{
		$entity = Item\DynamicExist::load($primary);
		if (!$entity)
		{
			$result = new Result();
			$result->addError(new Error('Recurring invoice not found'));
		}

		$entity->setFields($data);

		return $entity->save();
	}

	public function expose(array $filter, $limit = null, bool $recalculate = true): Result
	{
		$result = new Result();

		$recurringMap = [];
		$newItemsIds = [];

		$params = [
			'filter' => $filter,
			'select' => ['ID', 'ITEM_ID', 'ENTITY_TYPE_ID'],
		];

		$limit = (int)$limit;
		if ($limit > 0)
		{
			$params['limit'] = $limit;
		}

		$recurring = $this->getList($params);
		while ($recurData = $recurring->fetch())
		{
			$itemIdentifier = new ItemIdentifier($recurData['ENTITY_TYPE_ID'], $recurData['ITEM_ID']);
			$itemIdentifiers[$itemIdentifier->getEntityTypeId()][] = $itemIdentifier;
			$recurringMap[$itemIdentifier->getHash()] = $recurData['ID'];
		}

		if (empty($itemIdentifiers))
		{
			return $result;
		}

		try
		{
			foreach ($itemIdentifiers as $entityTypeId => $entityTypeItemIdentifiers)
			{
				$itemIdentifiersChunks = array_chunk($entityTypeItemIdentifiers, 100);

				foreach ($itemIdentifiersChunks as $itemIdentifiersChunk)
				{
					/**@var ItemIdentifier[] $itemIdentifiersChunk * */
					$factory = Container::getInstance()->getFactory($entityTypeId);
					if (!$factory || !$factory->isRecurringEnabled())
					{
						continue;
					}

					$ids = $this->getEntityIdsFromItemIdentifiers($itemIdentifiersChunk);
					if (empty($ids))
					{
						continue;
					}

					$itemsData = $factory->getItems(['filter' => ['ID' => $ids]]);
					foreach ($itemsData as $item)
					{
						$recurringItemIdentifier = new ItemIdentifier($item->getEntityTypeId(), $item->getId());

						$recurringItem = Item\DynamicExist::load($recurringMap[$recurringItemIdentifier->getHash()]);
						if (!$recurringItem)
						{
							continue;
						}

						$recurringItem->setTemplateItem($item);
						$r = $recurringItem->expose($recalculate);

						if ($r->isSuccess())
						{
							$exposingData = $r->getData();
							$newItemsIds[] = $exposingData['NEW_ITEM_ID'];
						}
						else
						{
							$result->addErrors($r->getErrors());
							if ($recalculate)
							{
								$recurringItem->deactivate();
								$recurringItem->save();
							}
						}
						unset($recurringItem);
					}
				}
			}
		}
		catch (SystemException $exception)
		{
			$result->addError(new Error($exception->getMessage(), $exception->getCode()));
		}

		if (!empty($newItemsIds))
		{
			$result->setData(['ID' => $newItemsIds]);
		}

		return $result;
	}

	private function getEntityIdsFromItemIdentifiers(array $itemIdentifiers): array
	{
		$ids = [];
		foreach ($itemIdentifiers as $itemIdentifier)
		{
			$ids[] = $itemIdentifier->getEntityId();
		}

		return $ids;
	}

	public function cancel($entityId, $reason = ''): void
	{
		$this->deactivate($entityId);
	}

	public function deactivate($entityId): Result
	{
		return $this->update($entityId, ['ACTIVE' => 'N']);
	}

	public function activate($entityId): Result
	{
		return $this->update($entityId, ['ACTIVE' => 'Y']);
	}

	// @todo ***recurring
	public function isAllowedExpose(): bool
	{
		return RestrictionManager::getInvoiceRecurringRestriction()?->hasPermission() ?? false;
	}

	public static function getParameterMapper(array $params = [])
	{
		return Item\DynamicEntity::getFormMapper($params);
	}

	public static function getNextDate(array $params, $startDate = null): ?Date
	{
		$mapper = self::getParameterMapper($params);
		$mapper->fillMap($params);

		return parent::getNextDate($mapper->getPreparedMap(), $startDate);
	}

	public function delete($primary): Result
	{
		$entity = Item\DynamicExist::load($primary);
		if (!$entity)
		{
			$result = new Result();
			$result->addError(new Error('Recurring smart invoice not found'));

			return $result;
		}

		return $entity->delete();
	}

	public function getRuntimeTemplateField(): array
	{
		return [];
	}
}
