<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Service\Context;

class Settings
{
	/** @var Context */
	protected $context;

	protected $isCheckAccessEnabled = true;
	protected $isCheckFieldsEnabled = true;
	protected $iscCheckRequiredUserFields = true;
	protected $isAutomationEnabled = true;
	protected $isBizProcEnabled = true;
	protected $isFieldProcessionEnabled = true;
	protected $isSaveToHistoryEnabled = true;
	protected $isBeforeSaveActionsEnabled = true;
	protected $isAfterSaveActionsEnabled = true;
	protected $isCheckWorkflowsEnabled = true;

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

	/**
	 * Disable all checks that will be performed in an Operation
	 *
	 * @return $this
	 */
	public function disableAllChecks(): self
	{
		return $this->disableCheckWorkflows()
			->disableCheckAccess()
			->disableCheckFields()
			->disableCheckRequiredUserFields();
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
}
