<?php

namespace Bitrix\Crm\Service\Timeline;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Settings\Mode;

class Context
{
	public const MOBILE = 'mobile';
	public const DESKTOP = 'desktop';
	public const PULL = 'pull';
	public const REST = 'rest';

	private ItemIdentifier $identifier;
	private UserPermissions $userPermissions;
	private string $type;
	private int $userId;

	public function __construct(ItemIdentifier $identifier, string $type, int $userId = null)
	{
		$container = Container::getInstance();

		$this->identifier = $identifier;
		$this->type = $type;
		$this->userId = $userId ?? $container->getContext()->getUserId();
		$this->userPermissions = $container->getUserPermissions($this->userId);
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getIdentifier(): ItemIdentifier
	{
		return $this->identifier;
	}

	public function getEntityTypeId(): int
	{
		return $this->getIdentifier()->getEntityTypeId();
	}

	public function getEntityId(): int
	{
		return $this->getIdentifier()->getEntityId();
	}

	public function getEntityCategoryId(): ?int
	{
		return $this->getIdentifier()->getCategoryId();
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getUserPermissions(): UserPermissions
	{
		return $this->userPermissions;
	}

	public function getCurrentCrmMode(bool $useAnalyticsMap = true): string
	{
		$map = $useAnalyticsMap
			? [
				Mode::CLASSIC => 'crmMode_classic',
				Mode::SIMPLE => 'crmMode_simple',
			]
			: [
				Mode::CLASSIC => 'classic',
				Mode::SIMPLE => 'simple',
			]
		;

		return $map[Mode::getCurrent()] ?? '';
	}

	public function canReadEntity(): bool
	{
		return $this->getUserPermissions()->checkReadPermissions($this->getEntityTypeId(), $this->getEntityId());
	}

	final public function isClosedEntity(): bool
	{
		return ComparerBase::isClosed($this->getIdentifier(), true);
	}
}
