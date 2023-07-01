<?php


namespace Bitrix\Crm\Badge;


class SourceIdentifier
{
	private string $providerId;
	private int $entityTypeId;
	private int $entityId;

	public const CRM_OWNER_TYPE_PROVIDER = 'crm_owner';
	public const CALENDAR_SHARING_TYPE_PROVIDER = 'calendar_sharing';

	public function __construct(string $providerId, int $entityTypeId, int $entityId)
	{
		$this->setProviderId($providerId);
		$this->setEntityTypeId($entityTypeId);
		$this->setEntityId($entityId);
	}

	public function getProviderId(): string
	{
		return $this->providerId;
	}

	private function setProviderId(string $providerId): SourceIdentifier
	{
		$this->providerId = $providerId;
		return $this;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	private function setEntityTypeId(int $entityTypeId): SourceIdentifier
	{
		if ($entityTypeId < 0)
		{
			throw new ArgumentOutOfRangeException('The provided $entityTypeId is invalid', 1);
		}

		$this->entityTypeId = $entityTypeId;
		return $this;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	private function setEntityId(int $entityId): SourceIdentifier
	{
		if ($entityId <= 0)
		{
			throw new ArgumentOutOfRangeException('The provided $entityId is invalid', 1);
		}

		$this->entityId = $entityId;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'PROVIDER_ID' => $this->getProviderId(),
			'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'ENTITY_ID' => $this->getEntityId(),
		];
	}

	public static function createFromArray(array $data): ?self
	{
		if (isset($data['PROVIDER_ID'], $data['ENTITY_TYPE_ID'], $data['ENTITY_ID']))
		{
			return new self((string)$data['PROVIDER_ID'], (int)$data['ENTITY_TYPE_ID'], (int)$data['ENTITY_ID']);
		}

		return null;
	}
}
