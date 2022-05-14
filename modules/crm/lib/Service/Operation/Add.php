<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Statistics;
use Bitrix\Crm\Timeline\MarkController;
use Bitrix\Crm\Timeline\RelationController;
use Bitrix\Crm\Timeline\TimelineManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Add extends Operation
{
	public function __construct(Item $item, Operation\Settings $settings, Collection $fieldsCollection = null)
	{
		parent::__construct($item, $settings, $fieldsCollection);
		$this->bizProcEventType = \CCrmBizProcEventType::Create;
	}

	public function checkAccess(): Result
	{
		$result = new Result();

		$userPermissions = Container::getInstance()->getUserPermissions($this->getContext()->getUserId());
		$canAddItem = $userPermissions->canAddItem($this->item);

		if(!$canAddItem)
		{
			$result->addError(
				new Error(
					Loc::getMessage('CRM_TYPE_ITEM_PERMISSIONS_ADD_DENIED'),
					static::ERROR_CODE_ITEM_ADD_ACCESS_DENIED
				)
			);
		}

		return $result;
	}

	protected function save(): Result
	{
		return $this->item->save($this->isCheckFieldsEnabled());
	}

	protected function registerDuplicateCriteria(): void
	{
		$registrar = Integrity\DuplicateManager::getCriterionRegistrar($this->getItem()->getEntityTypeId());

		$registrar->registerByItem($this->getItem());
	}

	protected function isCountersUpdateNeeded(): bool
	{
		return true;
	}

	protected function getUserIdsForCountersReset(): array
	{
		if ($this->item->hasField(Item::FIELD_NAME_ASSIGNED) && $this->item->getAssignedById() > 0)
		{
			return [$this->item->getAssignedById()];
		}

		return [];
	}

	protected function getTypesOfCountersToReset(): array
	{
		$factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());
		if (!$factory || !$factory->getCountersSettings()->isIdleCounterEnabled())
		{
			return [];
		}

		return [
			EntityCounterType::IDLE,
			EntityCounterType::ALL,
		];
	}

	protected function registerStatistics(Statistics\OperationFacade $statisticsFacade): Result
	{
		return $statisticsFacade->add($this->item);
	}

	protected function createTimelineRecord(): void
	{
		$timelineController = TimelineManager::resolveController([
			'ASSOCIATED_ENTITY_TYPE_ID' => $this->item->getEntityTypeId()
		]);

		if ($timelineController)
		{
			$timelineController->onCreate(
				$this->item->getId(),
				[
					'FIELDS' => $this->item->getData(),
					'FIELDS_MAP' => $this->item->getFieldsMap(),
				]
			);
		}

		RelationController::getInstance()->registerEventsByFieldsChange(
			$this->getItemIdentifier(),
			$this->fieldsCollection->toArray(),
			[],
			$this->item->getData(),
			$this->getItemsThatExcludedFromTimelineRelationEventsRegistration()
		);

		$factory = Container::getInstance()->getFactory($this->getItem()->getEntityTypeId());

		if ($factory->isClientEnabled())
		{
			RelationController::getInstance()->registerEventsByBindingsChange(
				$this->getItemIdentifier(),
				\CCrmOwnerType::Contact,
				[],
				$this->item->getContactBindings(),
				$this->getItemsThatExcludedFromTimelineRelationEventsRegistration()
			);
		}

		if (!$factory->isStagesSupported())
		{
			return;
		}

		$newStage = $factory->getStage((string)$this->item->getStageId());
		if (!$newStage)
		{
			return;
		}

		$wasItemMovedToFinalStage = (
			$factory->isStagesEnabled()
			&& PhaseSemantics::isFinal($newStage->getSemantics())
		);

		if ($wasItemMovedToFinalStage)
		{
			MarkController::getInstance()->onItemMoveToFinalStage(
				$this->getItemIdentifier(),
				$newStage->getSemantics()
			);
		}
	}

	protected function sendPullEvent(): void
	{
		parent::sendPullEvent();

		\Bitrix\Crm\Kanban\SupervisorTable::sendItem(
			$this->item->getId(),
			\CCrmOwnerType::ResolveName($this->item->getEntityTypeId()),
			'kanban_add'
		);

		PullManager::getInstance()->sendItemAddedEvent($this->pullItem, $this->pullParams);
	}

	protected function runAutomation(): Result
	{
		$result = parent::runAutomation();

		if($result->isSuccess())
		{
			/** @var \Bitrix\Crm\Automation\Starter $starter */
			$starter = $result->getData()['starter'];
			return $starter->runOnAdd();
		}

		return $result;
	}

	protected function checkLimits(): Result
	{
		$result = parent::checkLimits();

		$restriction = RestrictionManager::getDynamicTypesLimitRestriction();
		if ($restriction->isCreateItemRestricted($this->item->getEntityTypeId()))
		{
			$result->addError($restriction->getCreateItemRestrictedError());
		}

		return $result;
	}
}
