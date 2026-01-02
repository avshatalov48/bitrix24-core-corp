<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer\Entity;

use Bitrix\Crm\Copilot\AiQueueBuffer\Enum\Status;

final class AiQueueBufferItem
{
	private ?int $id;
	private int $providerId;
	private Status $status;
	private ?array $providerData = null;
	private int $retryCount;

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	public static function createFromEntityFields(array $fields): self
	{
		$instance = new self();

		$instance->id = $fields['ID'] ?? null;
		$instance->providerId = $fields['PROVIDER_ID'];
		$instance->status = isset($fields['STATUS']) ? Status::tryFrom($fields['STATUS']) : Status::Waiting;
		$instance->providerData = $fields['PROVIDER_DATA'] ?? null;
		$instance->retryCount = $fields['RETRY_COUNT'] ?? 0;

		return $instance;
	}

	public static function createFromEntity(AiQueueBuffer $entity): self
	{
		$instance = new self();

		$instance->id = $entity->getId();
		$instance->providerId = $entity->getProviderId();
		$instance->status = Status::from($entity->getStatus());
		$instance->providerData = $entity->getProviderData();
		$instance->retryCount = $entity->getRetryCount();

		return $instance;
	}

	public function incrementRetryCount(): self
	{
		$this->retryCount++;

		return $this;
	}

	public function getRetryCount(): int
	{
		return $this->retryCount;
	}

	public function toEntityFieldsArray(): array
	{
		return [
			'PROVIDER_ID' => $this->providerId,
			'STATUS' => $this->status->value,
			'PROVIDER_DATA' => $this->providerData,
			'RETRY_COUNT' => $this->retryCount,
		];
	}
}
