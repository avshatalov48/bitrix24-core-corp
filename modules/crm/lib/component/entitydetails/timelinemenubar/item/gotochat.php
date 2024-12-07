<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

class GoToChat extends Item
{
	public function getId(): string
	{
		return 'gotochat';
	}

	public function getName(): string
	{
		return Loc::getMessage('CRM_TIMELINE_GOTOCHAT_TITLE');
	}

	public function isAvailable(): bool
	{
		if (!\Bitrix\Crm\Integration\ImOpenLines\GoToChat::isActive())
		{
			return false;
		}

		if ($this->getEntityTypeId() === \CCrmOwnerType::Company && !$this->isMyCompany())
		{
			return true;
		}

		$availableEntityTypes = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
		];

		return in_array($this->getEntityTypeId(), $availableEntityTypes, true);
	}

	public function prepareSettings(): array
	{
		return [
			'region' => Application::getInstance()->getLicense()->getRegion() ?? $this->context->getRegion(),
		];
	}
}
