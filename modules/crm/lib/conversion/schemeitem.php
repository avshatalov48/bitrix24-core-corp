<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Restriction\AvailabilityManager;

class SchemeItem
{
	protected $entityTypeIds = [];
	protected $phrase;
	protected $id;
	protected $name;
	protected ?array $availabilityLock = null;

	protected const CHECKABLE_ENTITY_TYPES_ID = [
		\CCrmOwnerType::Invoice,
		\CCrmOwnerType::Quote,
		\CCrmOwnerType::SmartInvoice,
	];

	/**
	 * @param int[] $entityTypeIds
	 * @param string $phrase
	 */
	public function __construct(array $entityTypeIds, string $phrase)
	{
		$this->entityTypeIds = $entityTypeIds;
		sort($this->entityTypeIds);
		$this->phrase = $phrase;
	}

	public function getEntityTypeIds(): array
	{
		return $this->entityTypeIds;
	}

	public function getPhrase(): string
	{
		return $this->phrase;
	}

	public function setId($schemeId): self
	{
		$this->id = $schemeId;

		return $this;
	}

	public function getId(): string
	{
		if (!empty($this->id))
		{
			return (string)$this->id;
		}

		return implode('|', $this->entityTypeIds);
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getName(): string
	{
		if (!empty($this->name))
		{
			return $this->name;
		}

		$entityNames = [];
		foreach ($this->entityTypeIds as $typeId)
		{
			$entityNames[] = \CCrmOwnerType::ResolveName($typeId);
		}

		return implode('|', $entityNames);
	}

	public function getAvailabilityLock(): string
	{
		if ($this->availabilityLock === null)
		{
			$this->availabilityLock = $this->getPreparedLockScripts();
		}

		return implode('', $this->availabilityLock);
	}

	protected function getPreparedLockScripts(): array
	{
		$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
		$availabilityManager = AvailabilityManager::getInstance();

		$result = [];
		foreach ($this->entityTypeIds as $entityTypeId)
		{
			if (
				!in_array($entityTypeId, self::CHECKABLE_ENTITY_TYPES_ID, true)
				|| $toolsManager->checkEntityTypeAvailability($entityTypeId)
			)
			{
				continue;
			}

			$result[] = $availabilityManager->getEntityTypeAvailabilityLock($entityTypeId);
		}

		return $result;
	}

	public function toJson(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'entityTypeIds' => $this->getEntityTypeIds(),
			'phrase' => $this->getPhrase(),
			'availabilityLock' => $this->getAvailabilityLock(),
		];
	}
}
