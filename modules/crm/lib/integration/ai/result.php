<?php

namespace Bitrix\Crm\Integration\AI;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ArgumentOutOfRangeException;

/**
 * @template T of Dto
 */
final class Result extends \Bitrix\Main\Result implements \JsonSerializable
{
	public const OPERATION_STATUS_CONFLICT = 'CONFLICT';
	public const OPERATION_STATUS_APPLIED = 'APPLIED';
	public const OPERATION_STATUS_REJECTED = 'REJECTED';

	public const ALL_OPERATION_STATUSES = [
		self::OPERATION_STATUS_CONFLICT,
		self::OPERATION_STATUS_APPLIED,
		self::OPERATION_STATUS_REJECTED,
	];

	public const MAX_RETRY_COUNT = 1; // we allow only one retry

	/**
	 * @param int $typeId
	 * @param ItemIdentifier|null $target
	 * @param int|null $userId
	 * @param int|null $jobId
	 * @param bool $isPending
	 * @param T|null $payload
	 * @param string|null $operationStatus
	 * @param int|null $parentJobId
	 * @param int|null $retryCount
	 * @param bool $isManualLaunch
	 * @param string|null $languageId
	 */
	public function __construct(
		private int $typeId,
		private ?ItemIdentifier $target = null,
		private ?int $userId = null,
		private ?int $jobId = null,
		private bool $isPending = true,
		private ?Dto $payload = null,
		private ?string $operationStatus = null,
		private ?int $parentJobId = null,
		private ?int $retryCount = null,
		private bool $isManualLaunch = true,
		private ?string $languageId = null,
		private ?int $nextTypeId = null,
	)
	{
		parent::__construct();
	}

	public function __clone()
	{
		parent::__clone();

		// we don't need this since ItemIdentifier is immutable
		// $this->target = clone $this->target;

		if (is_object($this->payload))
		{
			$this->payload = clone $this->payload;
		}
	}

	public function getTypeId(): int
	{
		return $this->typeId;
	}

	public function getTarget(): ?ItemIdentifier
	{
		return $this->target;
	}

	public function getUserId(): ?int
	{
		return $this->userId;
	}

	public function setJobId(int $jobId): self
	{
		$this->jobId = $jobId;

		return $this;
	}

	public function getJobId(): ?int
	{
		return $this->jobId;
	}

	public function getParentJobId(): ?int
	{
		return $this->parentJobId;
	}

	public function isPending(): bool
	{
		return $this->isPending;
	}

	/**
	 * @return T|null
	 */
	public function getPayload(): ?Dto
	{
		return $this->payload;
	}

	public function getOperationStatus(): ?string
	{
		return $this->operationStatus;
	}

	public function getRetryCount(): ?int
	{
		return $this->retryCount;
	}

	public function isErrorsLimitExceeded(): bool
	{
		return $this->retryCount >= self::MAX_RETRY_COUNT;
	}

	public function setOperationStatus(string $operationStatus): self
	{
		if (!in_array($operationStatus, self::ALL_OPERATION_STATUSES, true))
		{
			throw new ArgumentOutOfRangeException('operationStatus', self::ALL_OPERATION_STATUSES);
		}

		$this->operationStatus = $operationStatus;

		return $this;
	}

	public function isInFinalOperationStatus(): bool
	{
		return self::isFinalOperationStatus($this->getOperationStatus());
	}

	public function isManualLaunch(): bool
	{
		return $this->isManualLaunch;
	}

	public function getLanguageId(): ?string
	{
		return $this->languageId;
	}

	public function getNextTypeId(): ?int
	{
		return $this->nextTypeId;
	}

	public function jsonSerialize(): array
	{
		return [
			'typeId' => $this->typeId,
			'target' => $this->target,
			'userId' => $this->userId,
			'jobId' => $this->jobId,
			'parentJobId' => $this->parentJobId,
			'isPending' => $this->isPending,
			'payload' => $this->payload,
			'operationStatus' => $this->operationStatus,
			'retryCount' => $this->retryCount,
			'isSuccess' => $this->isSuccess(),
			'errors' => $this->getErrors(),
			'isManualLaunch' => $this->isManualLaunch,
			'languageId' => $this->languageId,
			'nextTypeId' => $this->nextTypeId,
		];
	}

	public static function isFinalOperationStatus(?string $operationStatus): bool
	{
		static $final = [
			self::OPERATION_STATUS_APPLIED,
			self::OPERATION_STATUS_REJECTED,
		];

		return in_array($operationStatus, $final, true);
	}
}
