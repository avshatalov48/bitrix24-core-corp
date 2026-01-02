<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Result;
use Bitrix\Crm\Traits\BackgroundProcessor;
use Bitrix\Main\Type\DateTime;
use Psr\Log\LoggerInterface;
use Bitrix\Crm\Service\LastActivity\QueueItem;

final class LastActivity
{
	use BackgroundProcessor;

	private array $queue = [];
	private LoggerInterface $logger;

	public function __construct()
	{
		$this->logger = Container::getInstance()->getLogger('Default');
	}

	public function setNow(ItemIdentifier $target, ?int $lastActivityBy = null): \Bitrix\Main\Result
	{
		return $this->enqueue($target, null, $lastActivityBy);
	}

	public function set(
		ItemIdentifier $target,
		DateTime $lastActivityTime,
		?int $lastActivityBy = null,
	): \Bitrix\Main\Result
	{
		/** @var QueueItem|null $queueItem */
		$queueItem = $this->queue[$target->getEntityTypeId()][$target->getEntityId()] ?? null;

		if ($queueItem && $lastActivityTime->getTimestamp() <= $queueItem->getTime()->getTimestamp())
		{
			$this->logger->debug(
				'{method}: Last activity time is not greater than the one already enqueued. Skipping new value',
				[
					'method' => __METHOD__,
					'target' => $target,
					'lastActivityTime' => $lastActivityTime,
					'lastActivityBy' => $lastActivityBy,
				],
			);

			return new \Bitrix\Main\Result();
		}

		return $this->enqueue($target, $lastActivityTime, $lastActivityBy);
	}

	private function enqueue(
		ItemIdentifier $target,
		?DateTime $lastActivityTime,
		?int $lastActivityBy,
	): \Bitrix\Main\Result
	{
		if (!$this->isEntitySupported($target->getEntityTypeId()))
		{
			return Result::failEntityTypeNotSupported($target->getEntityTypeId());
		}

		$queueItem = $this->queue[$target->getEntityTypeId()][$target->getEntityId()] ?? new QueueItem();

		if ($lastActivityTime === null)
		{
			$queueItem->setTimeNow();
		}
		else
		{
			$queueItem->setSpecificTime($lastActivityTime);
		}

		if ($lastActivityBy === null)
		{
			$queueItem->setUserCurrent();
		}
		else
		{
			$queueItem->setSpecificUser($lastActivityBy);
		}

		$this->queue[$target->getEntityTypeId()][$target->getEntityId()] = $queueItem;
		$this->ensureProcessingScheduled();

		return new \Bitrix\Main\Result();
	}

	private function isEntitySupported(int $entityTypeId): bool
	{
		if (!\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			$this->logger->debug(
				'{method}: Not factory based entity was provided. Entity type id: {entityTypeId}',
				[
					'method' => __METHOD__,
					'entityTypeId' => $entityTypeId,
				],
			);

			return false;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory?->isLastActivitySupported())
		{
			$this->logger->debug(
				'{method}: Entity without last activity fields provided. Entity type id: {entityTypeId}',
				[
					'method' => __METHOD__,
					'entityTypeId' => $entityTypeId,
				],
			);

			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function process(): void
	{
		$container = Container::getInstance();

		foreach ($this->queue as $entityTypeId => $queueOfItemsOfSameType)
		{
			$factory = $container->getFactory($entityTypeId);
			if (!$factory || !\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
			{
				$this->logger->error(
					'{method}: Not factory based entity! Enqueue made a mistake? Entity type id: {entityTypeId}',
					[
						'method' => __METHOD__,
						'entityTypeId' => $entityTypeId,
					],
				);

				continue;
			}

			$items = $factory->getItems([
				'select' => ['*'],
				'filter' => [
					'@' . Item::FIELD_NAME_ID => array_keys($queueOfItemsOfSameType),
				],
			]);

			foreach ($items as $singleItem)
			{
				$queueItem = $queueOfItemsOfSameType[$singleItem->getId()] ?? null;
				if (!($queueItem instanceof QueueItem))
				{
					$this->logger->critical(
						'{method}: QueueItem not found for target {target}',
						[
							'method' => __METHOD__,
							'target' => ItemIdentifier::createByItem($singleItem),
						],
					);

					continue;
				}

				$result = $this->processSingleItem($factory, $singleItem, $queueItem);
				if (!$result->isSuccess())
				{
					$this->logger->critical(
						'{method}: Failed to process target {target}. Errors: {errors}',
						[
							'method' => __METHOD__,
							'target' => ItemIdentifier::createByItem($singleItem),
							'errors' => $result->getErrors(),
						],
					);
				}
			}
		}

		$this->queue = [];
	}

	private function processSingleItem(Factory $factory, Item $item, QueueItem $queueItem): \Bitrix\Main\Result
	{
		$item->set(Item::FIELD_NAME_LAST_ACTIVITY_TIME, $queueItem->getTime());
		$item->set(Item::FIELD_NAME_LAST_ACTIVITY_BY, $queueItem->getUserId());

		$context = clone Container::getInstance()->getContext();
		$context->setScope(Context::SCOPE_TASK);
		$context->setUserId($queueItem->getUserId());

		return $factory->getUpdateOperation($item, $context)
			// system action, perms and fields validity are not checked
			->disableAllChecks()
			// system action, no automation
			->disableBizProc()
			->disableAutomation()
			->launch()
		;
	}
}
