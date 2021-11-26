<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Automation\Starter;
use Bitrix\Crm\Field;
use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Search\SearchContentBuilderFactory;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Result;

abstract class Operation
{
	public const ERROR_CODE_ITEM_READ_ACCESS_DENIED = 'CRM_ITEM_READ_ACCESS_DENIED';
	public const ERROR_CODE_ITEM_ADD_ACCESS_DENIED = 'CRM_ITEM_ADD_ACCESS_DENIED';
	public const ERROR_CODE_ITEM_UPDATE_ACCESS_DENIED = 'CRM_ITEM_UPDATE_ACCESS_DENIED';
	public const ERROR_CODE_ITEM_DELETE_ACCESS_DENIED = 'CRM_ITEM_DELETE_ACCESS_DENIED';
	public const ERROR_CODE_ITEM_HAS_RUNNING_WORKFLOWS = 'CRM_ITEM_HAS_RUNNING_WORKFLOWS';

	public const ACTION_BEFORE_SAVE = 'beforeSave';
	public const ACTION_AFTER_SAVE = 'afterSave';

	/** @var \CCrmBizProcHelper */
	protected $bizProcHelper = \CCrmBizProcHelper::class;
	/** @var Item */
	protected $itemBeforeSave;
	/** @var Item */
	protected $item;
	/** @var Operation\Settings */
	protected $settings;
	protected $fieldsCollection;
	protected $actions = [
		self::ACTION_BEFORE_SAVE => [],
		self::ACTION_AFTER_SAVE => [],
	];
	protected $bizProcEventType;

	protected $pullItem = [];
	protected $pullParams = [];

	/** @var FieldAttributeManager */
	protected $fieldAttributeManager = FieldAttributeManager::class;

	public function __construct(Item $item, Operation\Settings $settings, Collection $fieldsCollection = null)
	{
		$this->item = $item;
		$this->settings = $settings;
		$this->fieldsCollection = $fieldsCollection;
		Container::getInstance()->getLocalization()->loadMessages();
	}

	/**
	 * Returns an object that describes a configuration of this Operation
	 *
	 * @return Operation\Settings
	 */
	public function exportSettings(): Operation\Settings
	{
		return $this->settings;
	}

	/**
	 * Set a new settings object that describes a configuration of this Operation
	 *
	 * @param Operation\Settings $settings
	 *
	 * @return $this
	 */
	public function importSettings(Operation\Settings $settings): self
	{
		$this->settings = $settings;

		return $this;
	}

	/**
	 * Returns a Context object from the current settings
	 *
	 * @return Context
	 */
	public function getContext(): Context
	{
		return $this->settings->getContext();
	}

	/**
	 * Sets a new Context object to the current settings
	 *
	 * @param Context $context
	 *
	 * @return $this
	 */
	public function setContext(Context $context): self
	{
		$this->settings->setContext($context);

		return $this;
	}

	public function getItem(): Item
	{
		return $this->item;
	}

	protected function getItemIdentifier(): ?ItemIdentifier
	{
		if ($this->getItem()->isNew())
		{
			return null;
		}

		return ItemIdentifier::createByItem($this->getItem());
	}

	public function getItemBeforeSave(): Item
	{
		return $this->itemBeforeSave;
	}

	public function addAction(string $actionPlacement, Action $action): self
	{
		if (
			$actionPlacement !== static::ACTION_BEFORE_SAVE
			&& $actionPlacement !== static::ACTION_AFTER_SAVE
		)
		{
			throw new ArgumentOutOfRangeException('actionPlacement');
		}

		$this->actions[$actionPlacement][] = $action;

		return $this;
	}

	public function launch(): Result
	{
		if ($this->isCheckLimitsEnabled())
		{
			$checkLimitsResult = $this->checkLimits();
			if (!$checkLimitsResult->isSuccess())
			{
				return $checkLimitsResult;
			}
		}

		if ($this->isCheckAccessEnabled())
		{
			$checkAccessResult = $this->checkAccess();
			if (!$checkAccessResult->isSuccess())
			{
				return $checkAccessResult;
			}

			$processFieldsResult = $this->processFieldsWithPermissions();
			if (!$processFieldsResult->isSuccess())
			{
				return $processFieldsResult;
			}
		}

		if ($this->isCheckWorkflowsEnabled())
		{
			$checkWorkflowsResult = $this->checkRunningWorkflows();
			if (!$checkWorkflowsResult->isSuccess())
			{
				return $checkWorkflowsResult;
			}
		}

		if ($this->isFieldProcessionEnabled())
		{
			$processFieldsResult = $this->processFieldsBeforeSave();
			if (!$processFieldsResult->isSuccess())
			{
				return $processFieldsResult;
			}
		}

		if ($this->isCheckFieldsEnabled())
		{
			$checkFieldsResult = $this->checkFields();
			if (!$checkFieldsResult->isSuccess())
			{
				return $checkFieldsResult;
			}
		}

		if ($this->isItemChanged() && $this->isBeforeSaveActionsEnabled())
		{
			$actionsResult = $this->processActions(static::ACTION_BEFORE_SAVE);
			if (!$actionsResult->isSuccess())
			{
				return $actionsResult;
			}
		}

		// no changes - no actions
		if (!$this->isItemChanged())
		{
			$this->item->reset(Item::FIELD_NAME_UPDATED_TIME);
			$this->item->reset(Item::FIELD_NAME_UPDATED_BY);
			return new Result();
		}

		$this->itemBeforeSave = clone $this->item;
		$result = $this->save();

		if ($result->isSuccess() && $this->isFieldProcessionEnabled())
		{
			$processFieldsResult = $this->processFieldsAfterSave();
			if (!$processFieldsResult->isSuccess())
			{
				$result->addErrors($processFieldsResult->getErrors());
			}
		}

		if ($result->isSuccess())
		{
			$this->updatePermissions();
			$this->updateSearchIndexes();
		}

		if ($result->isSuccess() && $this->isSaveToHistoryEnabled())
		{
			$this->saveToHistory();
			$this->createTimelineRecord();
		}

		if ($result->isSuccess() && $this->isAfterSaveActionsEnabled())
		{
			$actionsResult = $this->processActions(static::ACTION_AFTER_SAVE);
			if (!$actionsResult->isSuccess())
			{
				$result->addErrors($actionsResult->getErrors());
			}
		}

		if ($result->isSuccess() && $this->getItem()->isStagesEnabled())
		{
			$this->preparePullEvent();
			$this->sendPullEvent();
		}

		if ($result->isSuccess() && $this->isBizProcEnabled())
		{
			$bizProcResult = $this->runBizProc();
			if (!$bizProcResult->isSuccess())
			{
				$result->addErrors($bizProcResult->getErrors());
			}
		}

		if ($result->isSuccess() && $this->isAutomationEnabled())
		{
			$eventType = $this->item->getEntityEventName('OnAfterUpdate');
			$eventId = EventManager::getInstance()->addEventHandler(
				'crm',
				$eventType,
				[$this, 'updateItemFromUpdateEvent']
			);
			$automationResult = $this->runAutomation();
			if (!$automationResult->isSuccess())
			{
				$result->addErrors($automationResult->getErrors());
			}
			EventManager::getInstance()->removeEventHandler('crm', $eventType, $eventId);
		}

		return $result;
	}

	abstract public function checkAccess(): Result;

	public function processFieldsWithPermissions(): Result
	{
		$result = new Result();

		if (!$this->fieldsCollection)
		{
			return $result;
		}

		$userPermissions = Container::getInstance()->getUserPermissions($this->getContext()->getUserId());

		foreach($this->fieldsCollection as $field)
		{
			$fieldResult = $field->processWithPermissions($this->item, $userPermissions);
			if (!$fieldResult->isSuccess())
			{
				$result->addErrors($fieldResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * Invoke Field::process() on each field from $this->fieldsCollection and collect errors.
	 *
	 * @return Result
	 */
	public function processFieldsBeforeSave(): Result
	{
		$result = new Result();

		if (!$this->fieldsCollection)
		{
			return $result;
		}

		foreach($this->fieldsCollection as $field)
		{
			$fieldResult = $field->process($this->item, $this->getContext());
			if (!$fieldResult->isSuccess())
			{
				$result->addErrors($fieldResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * It is not always necessary to check that required fields are not empty.
	 * During update it is necessary only if stage changed on the same category.
	 *
	 * This method return true if only changed required fields should be checked on emptiness.
	 *
	 * @return bool
	 */
	protected function isCheckRequiredOnlyChanged(): bool
	{
		return false;
	}

	/**
	 * This method checks that user fields filled properly according to their types and settings.
	 * Also here checks that required fields are not empty.
	 *
	 * @return Result
	 */
	public function checkFields(): Result
	{
		$result = new Result();
		if (empty($this->fieldsCollection))
		{
			return $result;
		}

		$requiredFields = [];
		$notDisplayedFields = [];

		foreach ($this->fieldsCollection as $field)
		{
			$fieldName = $field->getName();
			if ($field->isRequired())
			{
				if ($field->isUserField() && !$this->isCheckRequiredUserFields())
				{
					continue;
				}
				$requiredFields[] = $fieldName;
			}
			if (!$field->isDisplayed())
			{
				$notDisplayedFields[] = $fieldName;
			}
		}

		$factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());
		if (!$factory)
		{
			throw new InvalidOperationException('Factory not found');
		}
		if (
			$this->isCheckRequiredUserFields()
			&& $this->fieldAttributeManager::isPhaseDependent()
			&& $this->item->isStagesEnabled()
		)
		{
			$requiredFields = array_merge($requiredFields, $this->getStageDependantRequiredFields($factory));
		}

		if ($this->isCheckRequiredOnlyChanged())
		{
			$notChangedFields = [];
			foreach ($requiredFields as $fieldName)
			{
				if ($this->item->hasField($fieldName) && !$this->item->isChanged($fieldName))
				{
					$notChangedFields[] = $fieldName;
				}
			}

			$requiredFields = array_diff($requiredFields, $notChangedFields);
		}

		$requiredFields = array_diff($requiredFields, $notDisplayedFields);

		$result = $this->checkRequiredFields($requiredFields, $factory);

		return $result;
	}

	/**
	 * Check that required fields are filled.
	 *
	 * @param string[] $requiredFields - required field names.
	 * @param Factory $factory
	 * @return Result
	 */
	public function checkRequiredFields(array $requiredFields, Factory $factory): Result
	{
		/**
		 * we can`t pass required attribute into collection for 2 reasons:
		 * - we would have to clone fieldsCollection entirely, because it is entity-dependant, and not item-specific.
		 * - we would have to separate checking required fields from processAttributes() to another method,
		 *   to call it after processLogic, because stage can be changed during processLogic()
		 **/

		$result = new Result();
		$dependantFieldNames = $factory->getDependantFieldsMap();

		foreach ($requiredFields as $fieldName)
		{
			$field = $this->fieldsCollection->getField($fieldName);
			if ($field && $field->isItemValueEmpty($this->item))
			{
				$result->addError($field::getRequiredEmptyError($fieldName, $field->getTitle()));
			}
			elseif (isset($dependantFieldNames[$fieldName]))
			{
				$emptyValuesCount = 0;
				foreach ($dependantFieldNames[$fieldName] as $dependantFieldName)
				{
					$dependantField = $this->fieldsCollection->getField($dependantFieldName);
					if (
						$dependantField
						&& $this->item->hasField($dependantFieldName)
						&& $dependantField->isValueEmpty($this->item->get($dependantFieldName))
					)
					{
						$emptyValuesCount++;
					}
				}

				if ($emptyValuesCount >= count ($dependantFieldNames[$fieldName]))
				{
					$result->addError(Field::getRequiredEmptyError($fieldName, $factory->getFieldCaption($fieldName)));
				}
			}
		}

		return $result;
	}

	protected function runBizProc(): Result
	{
		$result = new Result();

		if (is_int($this->bizProcEventType))
		{
			$errors = [];

			$this->bizProcHelper::AutoStartWorkflows(
				$this->item->getEntityTypeId(),
				$this->item->getId(),
				$this->bizProcEventType,
				$errors
			);

			if ($errors)
			{
				foreach ($errors as $errorMessage)
				{
					$result->addError(new Error($errorMessage));
				}
			}
		}

		return $result;
	}

	protected function runAutomation(): Result
	{
		$starter = new Starter($this->item->getEntityTypeId(), $this->item->getId());

		switch ($this->getContext()->getScope())
		{
			case Context::SCOPE_AUTOMATION:
				$starter->setContextToBizproc();
				break;
			case Context::SCOPE_REST:
				$starter->setContextToRest();
				break;
			default:
				$starter->setUserId($this->getContext()->getUserId());
				break;
		}

		return (new Result())->setData(['starter' => $starter]);
	}

	abstract protected function save(): Result;

	public function processFieldsAfterSave(): Result
	{
		$result = new Result();

		if (!$this->fieldsCollection)
		{
			return $result;
		}

		$isChanged = false;
		foreach($this->fieldsCollection as $field)
		{
			$fieldResult = $field->processAfterSave($this->itemBeforeSave, $this->item, $this->getContext());
			if (!$fieldResult->isSuccess())
			{
				$result->addErrors($fieldResult->getErrors());
			}
			elseif ($fieldResult->hasNewValues())
			{
				foreach($fieldResult->getNewValues() as $fieldName => $value)
				{
					$this->item->set($fieldName, $value);
				}
				$isChanged = true;
			}
		}

		if ($isChanged)
		{
			$saveAfterSaveResult = $this->save();
			if (!$saveAfterSaveResult->isSuccess())
			{
				$result->addErrors($saveAfterSaveResult->getErrors());
			}
		}

		return $result;
	}

	public function disableAllChecks(): self
	{
		$this->settings->disableAllChecks();

		return $this;
	}

	public function enableAutomation(): self
	{
		$this->settings->enableAutomation();

		return $this;
	}

	public function disableAutomation(): self
	{
		$this->settings->disableAutomation();

		return $this;
	}

	public function isAutomationEnabled(): bool
	{
		return $this->settings->isAutomationEnabled();
	}

	public function enableBizProc(): self
	{
		$this->settings->enableBizProc();

		return $this;
	}

	public function disableBizProc(): self
	{
		$this->settings->disableBizProc();

		return $this;
	}

	public function isBizProcEnabled(): bool
	{
		return $this->settings->isBizProcEnabled();
	}

	public function enableCheckAccess(): self
	{
		$this->settings->enableCheckAccess();

		return $this;
	}

	public function disableCheckAccess(): self
	{
		$this->settings->disableCheckAccess();

		return $this;
	}

	public function isCheckAccessEnabled(): bool
	{
		return $this->settings->isCheckAccessEnabled();
	}

	public function enableCheckLimits(): self
	{
		$this->settings->enableCheckLimits();

		return $this;
	}

	public function disableCheckLimits(): self
	{
		$this->settings->disableCheckLimits();

		return $this;
	}

	public function isCheckLimitsEnabled(): bool
	{
		return $this->settings->isCheckLimitsEnabled();
	}

	public function isCheckFieldsEnabled(): bool
	{
		return $this->settings->isCheckFieldsEnabled();
	}

	public function enableCheckFields(): self
	{
		$this->settings->enableCheckFields();

		return $this;
	}

	public function disableCheckFields(): self
	{
		$this->settings->disableCheckFields();

		return $this;
	}

	public function isCheckRequiredUserFields(): bool
	{
		return $this->settings->isCheckRequiredUserFields();
	}

	public function enableCheckRequiredUserFields(): self
	{
		$this->settings->enableCheckRequiredUserFields();

		return $this;
	}

	public function disableCheckRequiredUserFields(): self
	{
		$this->settings->disableCheckRequiredUserFields();

		return $this;
	}

	public function enableFieldProcession(): self
	{
		$this->settings->enableFieldProcession();

		return $this;
	}

	public function disableFieldProcession(): self
	{
		$this->settings->disableFieldProcession();

		return $this;
	}

	public function isFieldProcessionEnabled(): bool
	{
		return $this->settings->isFieldProcessionEnabled();
	}

	public function enableSaveToHistory(): self
	{
		$this->settings->enableSaveToHistory();

		return $this;
	}

	public function disableSaveToHistory(): self
	{
		$this->settings->disableSaveToHistory();

		return $this;
	}

	public function isSaveToHistoryEnabled(): bool
	{
		return $this->settings->isSaveToHistoryEnabled();
	}

	/**
	 * Exclude the specified items from being registered in this item's timeline as bound item
	 *
	 * @param ItemIdentifier[] $itemsToExclude
	 *
	 * @return $this
	 */
	public function excludeItemsFromTimelineRelationEventsRegistration(array $itemsToExclude): self
	{
		$this->settings->excludeItemsFromTimelineRelationEventsRegistration($itemsToExclude);

		return $this;
	}

	/**
	 * Get items that are excluded from being registered in this item's timeline as bound item
	 *
	 * @return ItemIdentifier[]
	 */
	public function getItemsThatExcludedFromTimelineRelationEventsRegistration(): array
	{
		return $this->settings->getItemsThatExcludedFromTimelineRelationEventsRegistration();
	}

	public function enableBeforeSaveActions(): self
	{
		$this->settings->enableBeforeSaveActions();

		return $this;
	}

	public function disableBeforeSaveActions(): self
	{
		$this->settings->disableBeforeSaveActions();

		return $this;
	}

	public function isBeforeSaveActionsEnabled(): bool
	{
		return $this->settings->isBeforeSaveActionsEnabled();
	}

	public function enableAfterSaveActions(): self
	{
		$this->settings->enableAfterSaveActions();

		return $this;
	}

	public function disableAfterSaveActions(): self
	{
		$this->settings->disableAfterSaveActions();

		return $this;
	}

	public function isAfterSaveActionsEnabled(): bool
	{
		return $this->settings->isAfterSaveActionsEnabled();
	}

	public function enableCheckWorkflows(): self
	{
		$this->settings->enableAfterSaveActions();

		return $this;
	}

	public function disableCheckWorkflows(): self
	{
		$this->settings->disableCheckWorkflows();

		return $this;
	}

	public function isCheckWorkflowsEnabled(): bool
	{
		return $this->settings->isCheckWorkflowsEnabled();
	}

	protected function saveToHistory(): Result
	{
		return new Result();
	}

	protected function createTimelineRecord(): void
	{
	}

	protected function processActions(string $placementCode): Result
	{
		if (!empty($this->actions[$placementCode]))
		{
			foreach($this->actions[$placementCode] as $action)
			{
				/** @var Action $action */
				$actionResult = $action->process($this->item);
				if (!$actionResult->isSuccess())
				{
					return $actionResult;
				}
			}
		}

		return new Result();
	}

	protected function sendPullEvent(): void
	{
	}

	protected function preparePullEvent(): void
	{
		$data = $this->getPullData();
		$entityName = \CCrmOwnerType::ResolveName($this->item->getEntityTypeId());

		$this->pullItem = Entity::getInstance($entityName)->createPullItem($data);

		$params = ['TYPE' => $entityName];
		if (!empty($data['CATEGORY_ID']))
		{
			$params['CATEGORY_ID'] = $data['CATEGORY_ID'];
		}

		if ($this->getContext()->getScope() === Context::SCOPE_AUTOMATION)
		{
			$params['SKIP_CURRENT_USER'] = false;
		}

		$this->pullParams = $params;
	}

	protected function getPullData(): array
	{
		return $this->getItem()->getCompatibleData();
	}

	protected function updatePermissions(): void
	{
		$userPermissions = Container::getInstance()->getUserPermissions($this->getContext()->getUserId());

		$permissionEntityType = \Bitrix\Crm\Service\UserPermissions::getItemPermissionEntityType($this->item);
		$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
			->setEntityAttributes($userPermissions->prepareItemPermissionAttributes($this->item))
		;

		\Bitrix\Crm\Security\Manager::resolveController($permissionEntityType)
			->register(
				$permissionEntityType,
				$this->item->getId(),
				$securityRegisterOptions
			)
		;
	}

	protected function updateSearchIndexes(): void
	{
		$filter = [
			'ID' => $this->item->getId(),
		];
		if (!$this->isCheckAccessEnabled())
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}

		\CCrmSearch::UpdateSearch($filter, \CCrmOwnerType::ResolveName($this->item->getEntityTypeId()), true);

		SearchContentBuilderFactory::create($this->item->getEntityTypeId())->build($this->item->getId());
	}

	public function getStageDependantRequiredFields(Factory $factory): array
	{
		$fieldsData = $this->fieldAttributeManager::getList(
			$this->item->getEntityTypeId(),
			$this->fieldAttributeManager::getItemConfigScope($this->item)
		);

		$categoryId = $this->item->getCategoryId();
		$stages = $factory->getStages($categoryId);
		$requiredFields = $this->fieldAttributeManager::processFieldsForStages(
			$fieldsData,
			$stages,
			$this->item->getStageId()
		);

		return VisibilityManager::filterNotAccessibleFields($this->item->getEntityTypeId(), $requiredFields);
	}

	/**
	 * @param Result $original
	 * @param string $newClass Must be a subclass of Result
	 *
	 * @return Result
	 * @throws ArgumentException
	 */
	protected function changeResultClass(Result $original, string $newClass): Result
	{
		if (!is_a($newClass, Result::class, true))
		{
			throw new ArgumentException('New class should be a subclass of Result', 'newClass');
		}

		if (is_a($original, $newClass))
		{
			return $original;
		}

		/** @var Result $newResult */
		$newResult = new $newClass();
		if (!$original->isSuccess())
		{
			$newResult->addErrors($original->getErrors());
		}
		if (!empty($original->getData()))
		{
			$newResult->setData($original->getData());
		}

		return $newResult;
	}

	/**
	 * Return true if item has been changed.
	 *
	 * @return bool
	 */
	public function isItemChanged(): bool
	{
		return true;
	}

	/**
	 * Check running workflows and abort operation if needed.
	 *
	 * @return Result
	 */
	public function checkRunningWorkflows(): Result
	{
		return new Result();
	}

	protected function checkLimits(): Result
	{
		return new Result();
	}

	public function updateItemFromUpdateEvent(Event $event): void
	{
		$item = $event->getParameter('object');
		if ($item->getId() === $this->item->getId())
		{
			$factory = Container::getInstance()->getFactory($this->item->getEntityTypeId());
			if ($factory)
			{
				$this->item = $factory->getItemByEntityObject($item);
			}
		}
	}
}
