<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;

use Bitrix\Main\Config\Option;
use CCrmCompany;
use CCrmOwnerType;

abstract class Item
{
	protected ?array $settings = null;
	protected Context $context;

	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	abstract public function isAvailable(): bool;

	abstract public function getId(): string;

	abstract public function getName(): string;

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

	public function hasTariffRestrictions(): bool
	{
		return false;
	}

	public function isNew(): bool
	{
		return false;
	}

	public function getTitle(): string
	{
		return $this->getName();
	}

	public function loadAssets(): void
	{
	}

	final protected function isCatalogEntityType(): bool
	{
		return in_array(
			$this->getEntityTypeId(),
			[CCrmOwnerType::ShipmentDocument, CCrmOwnerType::StoreDocument],
			true
		);
	}

	final protected function isMyCompany(): bool
	{
		return $this->getEntityTypeId() === CCrmOwnerType::Company
			&& $this->getEntityId() > 0
			&& CCrmCompany::isMyCompany($this->getEntityId());
	}

	final protected function getEntityTypeId(): int
	{
		return $this->context->getEntityTypeId();
	}

	final protected function getEntityId(): int
	{
		return $this->context->getEntityId();
	}

	final protected function getEntityCategoryId(): ?int
	{
		return $this->context->getEntityCategoryId();
	}

	final protected function isHideAllTours(): bool
	{
		return Option::get('crm.tour', 'HIDE_ALL_TOURS', 'N') === 'Y';
	}

	protected function prepareSettings(): array
	{
		return [];
	}
}
