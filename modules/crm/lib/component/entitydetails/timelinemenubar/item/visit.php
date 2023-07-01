<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Main\UI\Extension;

class Visit extends Item
{
	public function getId(): string
	{
		return 'visit';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_VISIT');
	}

	public function isAvailable(): bool
	{
		return
			!$this->isCatalogEntityType()
			&& \Bitrix\Crm\Activity\Provider\Visit::isAvailable()
			&& !\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->getEntityTypeId())
			&& !$this->isMyCompany();
	}

	public function hasTariffRestrictions(): bool
	{
		return !\Bitrix\Crm\Restriction\RestrictionManager::getVisitRestriction()->hasPermission();
	}

	public function prepareSettings():array
	{
		return \Bitrix\Crm\Activity\Provider\Visit::getPopupParameters();
	}

	public function loadAssets(): void
	{
		Extension::load('crm_visit_tracker');
	}
}
