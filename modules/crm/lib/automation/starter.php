<?php
namespace Bitrix\Crm\Automation;

use Bitrix\Crm\Automation\Trigger\FieldChangedTrigger;
use Bitrix\Crm\Automation\Trigger\ResponsibleChangedTrigger;
use Bitrix\Main;
use Bitrix\Bizproc;

class Starter
{
	protected const CONTEXT_WEB = 'web';
	protected const CONTEXT_MOBILE = 'mob';
	protected const CONTEXT_REST = 'rest';
	protected const CONTEXT_BIZPROC = 'bizproc';
	protected const CONTEXT_IMPORT = 'import';

	protected $context = self::CONTEXT_WEB;
	protected $contextModuleId = 'crm';
	protected $entityTypeId;
	protected $entityId;
	protected $userId;

	private $statusFieldKey = 'STAGE_ID';

	public function __construct(int $entityTypeId, int $entityId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;

		if ($entityTypeId === \CCrmOwnerType::Lead || $entityTypeId === \CCrmOwnerType::Quote)
		{
			$this->statusFieldKey = 'STATUS_ID';
		}
	}

	public function setContextToWeb(): self
	{
		$this->context = static::CONTEXT_WEB;
		return $this;
	}

	public function setContextToMobile(): self
	{
		$this->context = static::CONTEXT_MOBILE;
		$this->contextModuleId = 'mobile';
		return $this;
	}

	public function setContextToRest(): self
	{
		$this->context = static::CONTEXT_REST;
		return $this;
	}

	public function setContextToBizproc(): self
	{
		$this->context = static::CONTEXT_BIZPROC;
		$this->contextModuleId = 'bizproc';
		return $this;
	}

	public function setContextToImport(): self
	{
		$this->context = static::CONTEXT_IMPORT;
		return $this;
	}

	public function setContextModuleId(string $moduleId): self
	{
		$this->contextModuleId = $moduleId;
		return $this;
	}

	public function setUserId(int $userId): self
	{
		$this->userId = $userId;
		return $this;
	}

	public function setUserIdFromCurrent(): self
	{
		return $this->setUserId((int) \CCrmSecurityHelper::GetCurrentUserID());
	}

	public function runOnAdd(): Result
	{
		return Factory::runOnAdd($this->entityTypeId, $this->entityId);
	}

	public function runOnUpdate(array $fields, array $prevFields): Result
	{
		$triggerApplied = false;
		$changedFields = $this->compareFields($fields, $prevFields);

		if ($changedFields)
		{
			Factory::onFieldsChanged($this->entityTypeId, $this->entityId, $changedFields);
		}

		if ($this->isResponsibleChanged($changedFields))
		{
			$result = ResponsibleChangedTrigger::execute([[
					'OWNER_TYPE_ID' => $this->entityTypeId,
					'OWNER_ID' => $this->entityId
				]],
				$fields
			);
			$data = $result->getData();
			$triggerApplied = $data['triggersApplied'];
		}

		if (!$triggerApplied && $changedFields)
		{
			$result = FieldChangedTrigger::execute(
				[['OWNER_TYPE_ID' => $this->entityTypeId, 'OWNER_ID' => $this->entityId]],
				['CHANGED_FIELDS' => $changedFields]
			);
			$data = $result->getData();
			$triggerApplied = $data['triggersApplied'];
		}

		if (!$triggerApplied && $this->isStatusChanged($changedFields))
		{
			return Factory::runOnStatusChanged($this->entityTypeId, $this->entityId);
		}

		return new Result();
	}

	private function compareFields(array $actual, array $previous)
	{
		$diff = [];
		foreach ($actual as $key => $field)
		{
			if ($key !== 'ID' && (!array_key_exists($key, $previous) || $previous[$key] != $field))
			{
				$diff[] = $key;
			}
		}
		return $diff;
	}

	private function isStatusChanged(array $changedFields): bool
	{
		return in_array($this->statusFieldKey, $changedFields);
	}

	private function isResponsibleChanged(array $changedFields): bool
	{
		return in_array('ASSIGNED_BY_ID', $changedFields);
	}
}