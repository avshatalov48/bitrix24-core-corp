<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Integration\SmsManager;

class Delivery extends Item
{
	public function getId(): string
	{
		return 'delivery';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_DELIVERY');
	}

	public function isAvailable(): bool
	{
		return
			$this->getEntityTypeId() === \CCrmOwnerType::Deal
			&& SmsManager::canUse() // Delivery available only if sms and salescenter available
			&& \Bitrix\Crm\Integration\SalesCenterManager::getInstance()->hasInstallableDeliveryItems()
			&& \Bitrix\Crm\Integration\SalesCenterManager::getInstance()->isShowApplicationInSmsEditor();
	}
}
