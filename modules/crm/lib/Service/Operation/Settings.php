<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Statistics;

class Settings
{
	/** @var Context */
	protected $context;
	/** @var Statistics\OperationFacade|null */
	protected $statisticsFacade;

	protected $isCheckLimitsEnabled = true;
	protected $isCheckAccessEnabled = true;
	protected $isCheckFieldsEnabled = true;
	protected $iscCheckRequiredUserFields = true;
	protected $isAutomationEnabled = true;
	protected $isBizProcEnabled = true;
	protected $isFieldProcessionEnabled = true;
	protected $isSaveToHistoryEnabled = true;
	protected $isSaveToTimelineEnabled = true;
	/** @var ItemIdentifier[] */
	protected $itemsThatExcludedFromTimelineRelationEventsRegistration = [];
	protected $isBeforeSaveActionsEnabled = true;
	protected $isAfterSaveActionsEnabled = true;
	protected $isCheckWorkflowsEnabled = true;
	protected $isDeferredCleaningEnabled = true;
	protected $isDuplicatesIndexInvalidationEnabled = true;
	protected $isActivitiesAutocompletionEnabled = true;
	/** @var string[] */
	protected $activityProvidersToAutocomplete = [];

	/**
	 * Settings constructor.
	 *
	 * @param Context $context
	 */
	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	/**
	 * Returns a Context object, that is used in an Operation
	 *
	 * @return Context
	 */
	public function getContext(): Context
	{
		return $this->context;
	}

	/**
	 * Sets a Context object, that is used in an Operation
	 *
	 * @param Context $context
	 *
	 * @return $this
	 */
	public function setContext(Context $context): self
	{
		$this->context = $context;

		return $this;
	}

	public function getStatisticsFacade(): ?Statistics\OperationFacade
	{
		return $this->statisticsFacade;
	}

	public function setStatisticsFacade(?Statistics\OperationFacade $statisticsFacade): self
	{
		$this->statisticsFacade = $statisticsFacade;

		return $this;
	}

	/**
	 * Disable all checks that will be performed in an Operation
	 *
	 * @return $this
	 */
	public function disableAllChecks(): self
	{
		return $this->disableCheckWorkflows()
			->disableCheckLimits()
			->disableCheckAccess()
			->disableCheckFields()
			->disableCheckRequiredUserFields()
		;
	}

	/**
	 * Enables robots automation execution in an Operation
	 *
	 * @return $this
	 */
	public function enableAutomation(): self
	{
		$this->isAutomationEnabled = true;

		return $this;
	}

	/**
	 * Disables robots automation execution in an Operation
	 *
	 * @return $this
	 */
	public function disableAutomation(): self
	{
		$this->isAutomationEnabled = false;

		return $this;
	}

	/**
	 * Returns true if robots automation execution is enabled
	 *
	 * @return bool
	 */
	public function isAutomationEnabled(): bool
	{
		return $this->isAutomationEnabled;
	}

	/**
	 * Enables business processes execution in an Operation
	 *
	 * @return $this
	 */
	public function enableBizProc(): self
	{
		$this->isBizProcEnabled = true;

		return $this;
	}

	/**
	 * Disables business processes execution in an Operation
	 *
	 * @return $this
	 */
	public function disableBizProc(): self
	{
		$this->isBizProcEnabled = false;

		return $this;
	}

	/**
	 * Returns true if business processes execution is enabled
	 *
	 * @return bool
	 */
	public function isBizProcEnabled(): bool
	{
		return $this->isBizProcEnabled;
	}

	/**
	 * Enables user permission check in an Operation
	 *
	 * @return $this
	 */
	public function enableCheckAccess(): self
	{
		$this->isCheckAccessEnabled = true;

		return $this;
	}

	/**
	 * Disables user permission check in an Operation
	 *
	 * @return $this
	 */
	public function disableCheckAccess(): self
	{
		$this->isCheckAccessEnabled = false;

		return $this;
	}

	/**
	 * Returns true if user permission check is enabled
	 *
	 * @return bool
	 */
	public function isCheckAccessEnabled(): bool
	{
		return $this->isCheckAccessEnabled;
	}

	/**
	 * Enables limits check.
	 *
	 * @return $this
	 */
	public function enableCheckLimits(): self
	{
		$this->isCheckLimitsEnabled = true;

		return $this;
	}

	/**
	 * Disables limits check.
	 *
	 * @return $this
	 */
	public function disableCheckLimits(): self
	{
		$this->isCheckLimitsEnabled = false;

		return $this;
	}

	/**
	 * Returns true if limits check is enabled
	 *
	 * @return bool
	 */
	public function isCheckLimitsEnabled(): bool
	{
		return $this->isCheckLimitsEnabled;
	}

	/**
	 * Enables fields business logic procession in an Operation
	 *
	 * @return $this
	 */
	public function enableFieldProcession(): self
	{
		$this->isFieldProcessionEnabled = true;

		return $this;
	}

	/**
	 * Disables fields business-logic procession in an Operation
	 *
	 * @return $this
	 */
	public function disableFieldProcession(): self
	{
		$this->isFieldProcessionEnabled = false;

		return $this;
	}

	/**
	 * Returns true if fields business-logic procession is enabled
	 *
	 * @return bool
	 */
	public function isFieldProcessionEnabled(): bool
	{
		return $this->isFieldProcessionEnabled;
	}

	/**
	 * Enables registration of an Operation launch in history (EventHistory, Timeline)
	 *
	 * @return $this
	 */
	public function enableSaveToHistory(): self
	{
		$this->isSaveToHistoryEnabled = true;

		return $this;
	}

	/**
	 * Disables registration of an Operation launch in history (EventHistory, Timeline)
	 *
	 * @return $this
	 */
	public function disableSaveToHistory(): self
	{
		$this->isSaveToHistoryEnabled = false;

		return $this;
	}

	/**
	 * Returns true if registration of an Operation launch in history is enabled
	 *
	 * @return bool
	 */
	public function isSaveToHistoryEnabled(): bool
	{
		return $this->isSaveToHistoryEnabled;
	}

	public function enableSaveToTimeline(): self
	{
		$this->isSaveToTimelineEnabled = true;

		return $this;
	}

	public function disableSaveToTimeline(): self
	{
		$this->isSaveToTimelineEnabled = false;

		return $this;
	}

	public function isSaveToTimelineEnabled(): bool
	{
		return $this->isSaveToTimelineEnabled;
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
		$this->itemsThatExcludedFromTimelineRelationEventsRegistration = $itemsToExclude;

		return $this;
	}

	/**
	 * Get items that are excluded from being registered in this item's timeline as bound item
	 *
	 * @return ItemIdentifier[]
	 */
	public function getItemsThatExcludedFromTimelineRelationEventsRegistration(): array
	{
		return $this->itemsThatExcludedFromTimelineRelationEventsRegistration;
	}

	/**
	 * Enables 'before save' Actions procession in an Operation
	 * @see \Bitrix\Crm\Service\Operation::addAction
	 *
	 * @return $this
	 */
	public function enableBeforeSaveActions(): self
	{
		$this->isBeforeSaveActionsEnabled = true;

		return $this;
	}

	/**
	 * Disables 'before save' Actions procession in an Operation
	 * @see \Bitrix\Crm\Service\Operation::addAction
	 *
	 * @return $this
	 */
	public function disableBeforeSaveActions(): self
	{
		$this->isBeforeSaveActionsEnabled = false;

		return $this;
	}

	/**
	 * Returns true if 'before save' Actions procession is enabled
	 *
	 * @return bool
	 */
	public function isBeforeSaveActionsEnabled(): bool
	{
		return $this->isBeforeSaveActionsEnabled;
	}

	/**
	 * Enables 'after save' Actions procession in an Operation
	 * @see \Bitrix\Crm\Service\Operation::addAction
	 *
	 * @return $this
	 */
	public function enableAfterSaveActions(): self
	{
		$this->isAfterSaveActionsEnabled = true;

		return $this;
	}

	/**
	 * Disables 'after save' Actions procession in an Operation
	 * @see \Bitrix\Crm\Service\Operation::addAction
	 *
	 * @return $this
	 */
	public function disableAfterSaveActions(): self
	{
		$this->isAfterSaveActionsEnabled = false;

		return $this;
	}

	/**
	 * Returns true if 'after save' Actions procession is enabled
	 *
	 * @return bool
	 */
	public function isAfterSaveActionsEnabled(): bool
	{
		return $this->isAfterSaveActionsEnabled;
	}

	/**
	 * Enables checking running workflows during category change.
	 *
	 * @return $this
	 */
	public function enableCheckWorkflows(): self
	{
		$this->isCheckWorkflowsEnabled = true;

		return $this;
	}

	/**
	 * Disables checking running workflows during category change.
	 *
	 * @return $this
	 */
	public function disableCheckWorkflows(): self
	{
		$this->isCheckWorkflowsEnabled = false;

		return $this;
	}

	/**
	 * Return true if checking running workflows during category change enabled.
	 *
	 * @return bool
	 */
	public function isCheckWorkflowsEnabled(): bool
	{
		return $this->isCheckWorkflowsEnabled;
	}

	public function isCheckFieldsEnabled(): bool
	{
		return $this->isCheckFieldsEnabled;
	}

	public function enableCheckFields(): self
	{
		$this->isCheckFieldsEnabled = true;

		return $this;
	}

	public function disableCheckFields(): self
	{
		$this->isCheckFieldsEnabled = false;

		return $this;
	}

	public function isCheckRequiredUserFields(): bool
	{
		return $this->iscCheckRequiredUserFields;
	}

	public function enableCheckRequiredUserFields(): self
	{
		$this->iscCheckRequiredUserFields = true;

		return $this;
	}

	public function disableCheckRequiredUserFields(): self
	{
		$this->iscCheckRequiredUserFields = false;

		return $this;
	}

	public function isDeferredCleaningEnabled(): bool
	{
		return $this->isDeferredCleaningEnabled;
	}

	public function enableDeferredCleaning(): self
	{
		$this->isDeferredCleaningEnabled = true;

		return $this;
	}

	public function disableDeferredCleaning(): self
	{
		$this->isDeferredCleaningEnabled = false;

		return $this;
	}

	public function isDuplicatesIndexInvalidationEnabled(): bool
	{
		return $this->isDuplicatesIndexInvalidationEnabled;
	}

	public function enableDuplicatesIndexInvalidation(): self
	{
		$this->isDuplicatesIndexInvalidationEnabled = true;

		return $this;
	}

	public function disableDuplicatesIndexInvalidation(): self
	{
		$this->isDuplicatesIndexInvalidationEnabled = false;

		return $this;
	}

	public function isActivitiesAutocompletionEnabled(): bool
	{
		return $this->isActivitiesAutocompletionEnabled;
	}

	public function enableActivitiesAutocompletion(): self
	{
		$this->isActivitiesAutocompletionEnabled = true;

		return $this;
	}

	public function disableActivitiesAutocompletion(): self
	{
		$this->isActivitiesAutocompletionEnabled = false;

		return $this;
	}

	/**
	 * Returns ids of activity providers that should be autocompleted.
	 * Empty array as result means that no activities will be completed.
	 *
	 * @return string[]
	 */
	public function getActivityProvidersToAutocomplete(): array
	{
		return $this->activityProvidersToAutocomplete;
	}

	/**
	 * @param string[] $providersIds
	 * @return $this
	 */
	public function setActivityProvidersToAutocomplete(array $providersIds): self
	{
		$this->activityProvidersToAutocomplete = $providersIds;

		return $this;
	}
}
