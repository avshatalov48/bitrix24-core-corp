<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\MessageService\Sender\Sms\Ednaru;
use CCrmOwnerType;

class Sms extends Item
{
	public const WHATSAPP_PROVIDER_ID = 'ednaru';
	public const SERVICE_URL = '/bitrix/components/bitrix/crm.timeline/ajax.php';

	public function getId(): string
	{
		return 'sms';
	}

	public function getName(): string
	{
		$isOldScenario = Loader::includeModule('messageservice')
			&& Ednaru::isSupported()
			&& !Settings\Crm::isWhatsAppScenarioEnabled()
		;

		return $isOldScenario
			? Loc::getMessage('CRM_TIMELINE_SMS_TITLE2')
			: Loc::getMessage('CRM_TIMELINE_SMS_TITLE')
		;
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_SMS');
	}

	public function isAvailable(): bool
	{
		if (!SmsManager::canUse())
		{
			return false;
		}

		if ($this->isCatalogEntityType())
		{
			return false;
		}

		if ($this->isMyCompany())
		{
			return false;
		}

		if (CCrmOwnerType::isUseDynamicTypeBasedApproach($this->getEntityTypeId()))
		{
			$factory = Container::getInstance()->getFactory($this->getEntityTypeId());

			return ($factory && $factory->isClientEnabled());
		}

		return true;
	}

	public function prepareSettings(): array
	{
		return [
			'serviceUrl' => self::SERVICE_URL,
		];
	}

	public function loadAssets(): void
	{
		if ($this->getSettings()['smsConfig']['isFilesExternalLinkEnabled'] ?? false)
		{
			Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');
		}
	}
}
