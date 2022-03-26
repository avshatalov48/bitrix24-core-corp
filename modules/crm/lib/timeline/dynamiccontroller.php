<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Entity\PaymentDocumentsRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox\CheckManager;

class DynamicController extends FactoryBasedController
{
	public const ADD_EVENT_NAME = 'timeline_dynamic_add';
	public const REMOVE_EVENT_NAME = 'timeline_dynamic_remove';
	public const RESTORE_EVENT_NAME = 'timeline_dynamic_restore';

	protected $entityTypeId;

	protected function __construct(int $entityTypeId)
	{
		parent::__construct();
		$this->entityTypeId = $entityTypeId;
	}

	public static function getInstance(int $entityTypeId = null)
	{
		if ($entityTypeId <= 0)
		{
			throw new ArgumentException('Invalid value for $entityTypeId', 'entityTypeId');
		}

		$identifier = static::getServiceLocatorIdentifier($entityTypeId);

		if (!ServiceLocator::getInstance()->has($identifier))
		{
			$instance = new static($entityTypeId);
			ServiceLocator::getInstance()->addInstance($identifier, $instance);
		}

		return ServiceLocator::getInstance()->get($identifier);
	}

	protected static function getServiceLocatorIdentifier(int $entityTypeId = null): string
	{
		return parent::getServiceLocatorIdentifier() . ".{$entityTypeId}";
	}

	protected function getTrackedFieldNames(): array
	{
		return [
			Item::FIELD_NAME_TITLE,
			Item::FIELD_NAME_ASSIGNED,
			Item::FIELD_NAME_CATEGORY_ID,
			Item::FIELD_NAME_STAGE_ID,
			Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY,
		];
	}

	public function getEntityTypeID(): int
	{
		return $this->entityTypeId;
	}

	/**
	 * @inheritDoc
	 */
	public function onModify($entityID, array $params): void
	{
		parent::onModify($entityID, $params);

		$factory = Service\Container::getInstance()->getFactory($this->getEntityTypeId());
		if ($factory && $factory->isPaymentsEnabled() && $factory->isStagesEnabled())
		{
			$entityID = $this->prepareEntityIdFromArgs($entityID);
			$item = $factory->getItem($entityID);
			if (!$item)
			{
				return;
			}

			$currentSemanticId = $this->getCurrentSemanticId($factory, $item);
			if ($this->needSummaryDocuments($currentSemanticId, $params))
			{
				$entryParams = $this->prepareSummaryDocumentsEntryParams($entityID, $this->getEntityTypeID(), $params);
				if ($entryParams)
				{
					if (\CCrmSaleHelper::isWithOrdersMode())
					{
						$timelineEntryId = $this->getTimelineEntryFacade()->create(
							TimelineEntry\Facade::FINAL_SUMMARY,
							$entryParams
						);
					}
					else
					{
						$timelineEntryId = $this->getTimelineEntryFacade()->create(
							TimelineEntry\Facade::FINAL_SUMMARY_DOCUMENTS,
							$entryParams
						);
					}

					if ($timelineEntryId)
					{
						$this->sendPullEvent($entityID, Pusher::ADD_ACTIVITY_PULL_COMMAND, $timelineEntryId);
					}
				}
			}
		}
	}

	private function getCurrentSemanticId(Service\Factory $factory, Item $item): ?string
	{
		$currentStage = $factory->getStage($item->getStageId());
		if ($currentStage)
		{
			return $currentStage->getSemantics();
		}

		return null;
	}

	private function needSummaryDocuments($currentSemanticId, array $params): bool
	{
		$previousFields = (array)($params['PREVIOUS_FIELDS'] ?? []);
		$currentFields = (array)($params['CURRENT_FIELDS'] ?? []);

		$prevStageID = $previousFields['STAGE_ID'] ?? '';
		$curStageID = $currentFields['STAGE_ID'] ?? $prevStageID;

		return (
			(
				$currentSemanticId === PhaseSemantics::SUCCESS
				|| $currentSemanticId === PhaseSemantics::FAILURE
			)
			&& $prevStageID !== $curStageID
		);
	}

	private function prepareSummaryDocumentsEntryParams(int $ownerId, int $ownerTypeId, array $params): array
	{
		$entryParams = [];

		$orderIdList = Binding\OrderEntityTable::getOrderIdsByOwner($ownerId, $ownerTypeId);
		if ($orderIdList)
		{
			$currentFields = (array)($params['CURRENT_FIELDS'] ?? []);

			$entryParams = [
				'ENTITY_ID' => $ownerId,
				'ENTITY_TYPE_ID' => $ownerTypeId,
				'TYPE_CATEGORY_ID' => TimelineType::CREATION,
				'AUTHOR_ID' => $this->resolveAuthorId($currentFields),
				'SETTINGS' => [
					'ORDER_IDS' => $orderIdList
				],
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $ownerTypeId,
						'ENTITY_ID' => $ownerId
					]
				]
			];
		}

		return $entryParams;
	}
}
