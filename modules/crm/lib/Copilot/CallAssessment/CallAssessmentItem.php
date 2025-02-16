<?php

namespace Bitrix\Crm\Copilot\CallAssessment;

use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessment;
use Bitrix\Crm\Copilot\CallAssessment\Enum\AutoCheckType;
use Bitrix\Crm\Copilot\CallAssessment\Enum\CallType;
use Bitrix\Crm\Integration\AI\Model\QueueTable;

final class CallAssessmentItem
{
	public const LOW_BORDER_DEFAULT = 30;
	public const HIGH_BORDER_DEFAULT = 70;

	private ?int $id;
	private string $title;
	private string $prompt;
	private ?string $gist = null;
	private array $clientTypeIds;
	private ?CallType $callType = null;
	private ?AutoCheckType $autoCheckType = null;
	private bool $isEnabled = true;
	private bool $isDefault = false;
	private int $jobId = 0;
	private string $status = QueueTable::EXECUTION_STATUS_PENDING;
	private ?string $code = null;
	private int $lowBorder = self::LOW_BORDER_DEFAULT;
	private int $highBorder = self::HIGH_BORDER_DEFAULT;

	public static function createFromEntity(CopilotCallAssessment $callAssessmentItem): self
	{
		$instance = new self();

		$instance->id = $callAssessmentItem->getId();
		$instance->title = $callAssessmentItem->getTitle();
		$instance->prompt = $callAssessmentItem->getPrompt();
		$instance->gist = $callAssessmentItem->getGist();

		$instance->clientTypeIds = [];
		$clientTypes = $callAssessmentItem->getClientTypes() ?? [];
		/** @var EO_CopilotCallAssessmentClientType $clientType */
		foreach ($clientTypes as $clientType)
		{
			$instance->clientTypeIds[] = $clientType->getClientTypeId();
		}

		$instance->callType = CallType::from($callAssessmentItem->getCallType());
		$instance->autoCheckType = AutoCheckType::from($callAssessmentItem->getAutoCheckType());

		$instance->isEnabled = $callAssessmentItem->getIsEnabled();
		$instance->isDefault = true; // not used in Crm.Copilot.CallAssessment
		$instance->jobId = $callAssessmentItem->getJobId();
		$instance->status = $callAssessmentItem->getStatus();
		$instance->code = $callAssessmentItem->getCode();
		$instance->lowBorder = $callAssessmentItem->getLowBorder();
		$instance->highBorder = $callAssessmentItem->getHighBorder();

		return $instance;
	}

	public static function createFromArray(array $data): self
	{
		$instance = new self();

		$instance->id = $data['id'] ?? null;
		$instance->title = $data['title'] ?? '';
		$instance->prompt = $data['prompt'] ?? '';
		$instance->gist = $data['gist'] ?? null;
		$instance->clientTypeIds = $data['clientTypeIds'] ?? [];

		if (isset($data['callTypeId']))
		{
			$instance->callType = CallType::from($data['callTypeId']);
		}

		if (isset($data['autoCheckTypeId']))
		{
			$instance->autoCheckType = AutoCheckType::from($data['autoCheckTypeId']);
		}

		$instance->isEnabled = $data['isEnabled'] ?? true;
		$instance->isDefault = true; // not used in Crm.Copilot.CallAssessment
		$instance->jobId = $data['jobId'] ?? 0;
		$instance->status = $data['status'] ?? QueueTable::EXECUTION_STATUS_PENDING;
		$instance->code = $data['code'] ?? null;
		$instance->lowBorder = $data['lowBorder'] ?? self::LOW_BORDER_DEFAULT;
		$instance->highBorder = $data['highBorder'] ?? self::HIGH_BORDER_DEFAULT;

		return $instance;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'prompt' => $this->prompt,
			'gist' => $this->gist,
			'clientTypeIds' => $this->clientTypeIds,
			'callTypeId' => $this->callType?->value,
			'autoCheckTypeId' => $this->autoCheckType?->value,
			'isEnabled' => $this->isEnabled,
			'jobId' => $this->jobId,
			'status' => $this->status,
			'code' => $this->code,
			'lowBorder' => $this->lowBorder,
			'highBorder' => $this->highBorder,
		];
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getPrompt(): string
	{
		return $this->prompt;
	}

	public function getGist(): ?string
	{
		return $this->gist;
	}

	public function setGist(?string $gist): CallAssessmentItem
	{
		$this->gist = $gist;

		return $this;
	}

	public function getClientTypeIds(): array
	{
		return $this->clientTypeIds;
	}

	public function getCallTypeId(): ?int
	{
		return $this->callType?->value;
	}

	public function getAutoCheckTypeId(): ?int
	{
		return $this->autoCheckType?->value;
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	public function isDefault(): bool
	{
		return $this->isDefault;
	}

	public function getJobId(): int
	{
		return $this->jobId;
	}

	public function setJobId(int $jobId): CallAssessmentItem
	{
		$this->jobId = $jobId;

		return $this;
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function setStatus(string $status): CallAssessmentItem
	{
		$this->status = $status;

		return $this;
	}

	public function setIsEnabled(bool $isEnabled): CallAssessmentItem
	{
		$this->isEnabled = $isEnabled;

		return $this;
	}

	public function getCode(): ?string
	{
		return $this->code;
	}

	public function setCode(?string $code): CallAssessmentItem
	{
		$this->code = $code;

		return $this;
	}

	public function getLowBorder(): int
	{
		return $this->lowBorder;
	}

	public function getHighBorder(): int
	{
		return $this->highBorder;
	}
}
