<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Activity\Entity\ToDo;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\EntityActivityDeadline;
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
		return $this->item->save($this->isCheckFieldsEnabled() && $this->isCheckRequiredUserFields());
	}

	protected function registerDuplicateCriteria(): void
	{
		$registrar = Integrity\DuplicateManager::getCriterionRegistrar($this->getItem()->getEntityTypeId());

		$registrar->registerByItem($this->getItem());
	}

	protected function notifyCounterMonitor(): void
	{
		$fieldsValues = [];
		foreach ($this->getCounterMonitorSignificantFields() as $commonFieldName => $entityFieldName)
		{
			$fieldsValues[$entityFieldName] = $this->item->get($commonFieldName);
		}
		\Bitrix\Crm\Counter\Monitor::getInstance()->onEntityAdd($this->getItem()->getEntityTypeId(), $fieldsValues);
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
			$this->getItemsThatExcludedFromTimelineRelationEventsRegistration(),
			$this->getContext()->getUserId(),
		);

		$factory = Container::getInstance()->getFactory($this->getItem()->getEntityTypeId());

		if ($this->item->hasField(Item::FIELD_NAME_CONTACT_BINDINGS))
		{
			RelationController::getInstance()->registerEventsByBindingsChange(
				$this->getItemIdentifier(),
				\CCrmOwnerType::Contact,
				[],
				$this->item->getContactBindings(),
				$this->getItemsThatExcludedFromTimelineRelationEventsRegistration(),
				$this->getContext()->getUserId(),
			);
		}

		if ($this->item->hasField(Item\Contact::FIELD_NAME_COMPANY_BINDINGS))
		{
			RelationController::getInstance()->registerEventsByBindingsChange(
				$this->getItemIdentifier(),
				\CCrmOwnerType::Company,
				[],
				$this->item->get(Item\Contact::FIELD_NAME_COMPANY_BINDINGS),
				$this->getItemsThatExcludedFromTimelineRelationEventsRegistration(),
				$this->getContext()->getUserId(),
			);
		}

		if (!$factory->isStagesEnabled())
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
				$newStage->getSemantics(),
				$this->getContext()->getUserId(),
			);
		}
	}

	protected function createToDoActivity(): void
	{
		parent::createToDoActivity();

		$context = $this->getContext();
		$viewMode = $context->getItemOption('VIEW_MODE');

		if ($viewMode === \Bitrix\Crm\Kanban\ViewMode::MODE_ACTIVITIES)
		{
			$stageId = $context->getItemOption('STAGE_ID');
			if (!$stageId)
			{
				return;
			}

			$deadline = (new EntityActivityDeadline())->getDeadline($stageId);

			if ($deadline)
			{
				ToDo::createWithDefaultDescription(
					$this->item->getEntityTypeId(),
					$this->item->getId(),
					$deadline
				);
			}
		}
	}

	protected function sendPullEvent(): void
	{
		parent::sendPullEvent();

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

		$addOperationRestriction = RestrictionManager::getAddOperationRestriction($this->item->getEntityTypeId());
		if (!$addOperationRestriction->hasPermission())
		{
			$result->addError(
				new Error(
					$addOperationRestriction->getErrorMessage(),
					$addOperationRestriction->getErrorCode(),
				)
			);
		}

		return $result;
	}
}
