<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;

abstract class Item
{
	protected ?array $settings = null;
	protected Context $context;

	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	abstract public function isAvailable(): bool;

	public function hasTariffRestrictions(): bool
	{
		return false;
	}

	abstract public function getId(): string;

	abstract public function getName(): string;

	public function getTitle(): string
	{
		return $this->getName();
	}

	public function loadAssets(): void
	{
	}

	final public function getSettings(): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		if (is_null($this->settings))
		{
			$this->settings = $this->prepareSettings();
		}

		return $this->settings;
	}

	protected function getEntityTypeId(): int
	{
		return $this->context->getEntityTypeId();
	}

	protected function getEntityId(): int
	{
		return $this->context->getEntityId();
	}

	protected function prepareSettings(): array
	{
		return [];
	}

	protected function isCatalogEntityType(): bool
	{
		return in_array($this->getEntityTypeId(), [\CCrmOwnerType::ShipmentDocument, \CCrmOwnerType::StoreDocument]);
	}

	protected function isMyCompany(): bool
	{
		return $this->getEntityTypeId() === \CCrmOwnerType::Company
			&& $this->getEntityId() > 0
			&& \CCrmCompany::isMyCompany($this->getEntityId());
	}
}
