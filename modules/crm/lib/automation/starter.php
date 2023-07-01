<?php
namespace Bitrix\Crm\Automation;

use Bitrix\Crm\Automation\Trigger\FieldChangedTrigger;
use Bitrix\Crm\Automation\Trigger\ResponsibleChangedTrigger;
use Bitrix\Main\Loader;

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
	protected bool $isManual = false;

	private $statusFieldKey = 'STAGE_ID';
	private string $responsibleFieldKey = 'ASSIGNED_BY_ID';

	public function __construct(int $entityTypeId, int $entityId)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;

		if ($entityTypeId === \CCrmOwnerType::Lead || $entityTypeId === \CCrmOwnerType::Quote)
		{
			$this->statusFieldKey = 'STATUS_ID';
		}

		if ($entityTypeId === \CCrmOwnerType::Order)
		{
			$this->statusFieldKey = 'STATUS_ID';
			$this->responsibleFieldKey = 'RESPONSIBLE_ID';
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

	public function getContext(): string
	{
		return $this->context;
	}

	public function setContextModuleId(string $moduleId): self
	{
		$this->contextModuleId = $moduleId;
		return $this;
	}

	public function getContextModuleId(): string
	{
		return $this->contextModuleId;
	}

	public function setUserId(int $userId): self
	{
		$this->userId = $userId;
		return $this;
	}

	public function getUserId(): int
	{
		return (int)$this->userId;
	}

	public function setUserIdFromCurrent(): self
	{
		$this->isManual = true;

		return $this->setUserId((int) \CCrmSecurityHelper::GetCurrentUserID());
	}

	public function runOnAdd(): Result
	{
		return Factory::runOnAdd($this->entityTypeId, $this->entityId, $this);
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

	public function isManualOperation(): bool
	{
		return $this->isManual;
	}

	private function compareFields(array $actual, array $previous)
	{
		$diff = [];
		foreach ($actual as $key => $field)
		{
			if (
				$key !== 'ID'
				&& (!array_key_exists($key, $previous) || $previous[$key] != $field)
			)
			{
				if (!$this->isDefaultValue($key, $field) || !\CBPHelper::isEmptyValue($previous[$key] ?? null))
				{
					$diff[] = $key;
				}
			}
		}
		return $diff;
	}

	private function isDefaultValue(string $fieldName, $fieldValue): bool
	{
		static $documentFields = null;
		if (is_null($documentFields))
		{
			$documentFields = $this->getDocumentFields();
		}

		return (
			isset($documentFields[$fieldName], $documentFields[$fieldName]['Default'])
			&& $documentFields[$fieldName]['Default'] === $fieldValue
		);
 	}

	private function getDocumentFields(): array
	{
		if (!Loader::includeModule('bizproc'))
		{
			return [];
		}

		$documentService = \CBPRuntime::getRuntime(true)->getDocumentService();

		return $documentService->getDocumentFields($this->getDocumentType());
	}

	private function getDocumentType(): ?array
	{
		return \CCrmBizProcHelper::ResolveDocumentType($this->entityTypeId);
	}

	private function isStatusChanged(array $changedFields): bool
	{
		return in_array($this->statusFieldKey, $changedFields, true);
	}

	private function isResponsibleChanged(array $changedFields): bool
	{
		return in_array($this->responsibleFieldKey, $changedFields, true);
	}
}