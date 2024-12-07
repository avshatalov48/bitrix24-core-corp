<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Communications;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\MessageService\Sender\Sms\Ednaru;
use CCrmOwnerType;
use CUserOptions;

final class WhatsApp extends Sms
{
	private const USER_OPTION_PROVIDER_OFF = 'is_tour_provider_off_viewed';
	private const USER_OPTION_TEMPLATES_READY = 'is_tour_templates_ready_viewed';
	private const USER_OPTION_PROVIDER_ON = 'is_tour_provider_on_viewed';

	public function getId(): string
	{
		return 'whatsapp';
	}

	public function isNew(): bool
	{
		return true;
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_WHATSAPP_TITLE');
	}

	public function getName(): string
	{
		return Loc::getMessage('CRM_TIMELINE_WHATSAPP_TITLE');
	}

	public function isAvailable(): bool
	{
		return
			parent::isAvailable()
			&& Ednaru::isSupported()
			&& !in_array($this->getEntityTypeId(), [
				\CCrmOwnerType::Order,
				\CCrmOwnerType::SmartDocument,
				\CCrmOwnerType::SmartB2eDocument,
			], true)
			&& DocumentGeneratorManager::getInstance()->isEnabled()
		;
	}

	public function prepareSettings(): array
	{
		$canUse = $this->getProvider()['canUse'] ?? false;

		return [
			'canUse' => $canUse,
			'serviceUrl' => self::SERVICE_URL,
			'demoTemplate' => [
				'ID' => '{ demo_template }',
				'ORIGINAL_ID' => 0,
				'TITLE' => Loc::getMessage('CRM_TIMELINE_WHATSAPP_DEMO_TPL_TITLE'),
				'PREVIEW' => Loc::getMessage('CRM_TIMELINE_WHATSAPP_DEMO_TPL_PREVIEW'),
				'PLACEHOLDERS' => [
					'PREVIEW' => ['{{1}}'],
				],
			],
			'unViewedTourList' => $this->getUnViewedTourList($canUse),
		];
	}

	public function getProvider(): ?array
	{
		$smsConfig = SmsManager::getEditorConfig($this->getEntityTypeId(), $this->getEntityId());

		return array_values(
			array_filter(
				$smsConfig['senders'],
				static fn(array $sender) => $sender['id'] === self::WHATSAPP_PROVIDER_ID
			)
		)[0] ?? null;
	}

	private function getUnViewedTourList(bool $isEdnaCanUse): array
	{
		if ($this->isHideAllTours())
		{
			return []; // tour feature is disabled
		}

		$todoOptions = CUserOptions::getOption('crm', 'todo', []);
		$isTodoTourViewedInWeb = (bool)($todoOptions['isTimelineTourViewedInWeb'] ?? false);
		if (!$isTodoTourViewedInWeb)
		{
			return []; // show tour after other tour about "ToDo"
		}

		if (!in_array($this->getEntityTypeId(), [CCrmOwnerType::Lead, CCrmOwnerType::Deal], true))
		{
			return []; // only leads and deals have tours
		}

		$communications = (new Communications($this->getEntityTypeId(), $this->getEntityId()))->get();
		if (empty($communications))
		{
			return []; // no communications
		}

		$result = [];
		$options = CUserOptions::getOption('crm', 'whatsapp', []);
		if ($isEdnaCanUse)
		{
			$isTourProviderOn = (bool)($options[self::USER_OPTION_PROVIDER_ON] ?? false);
			if (!$isTourProviderOn)
			{
				$result[] = self::USER_OPTION_PROVIDER_ON;
			}

			$isTourTemplatesReady = (bool)($options[self::USER_OPTION_TEMPLATES_READY] ?? false);
			if (!$isTourTemplatesReady)
			{
				$result[] = self::USER_OPTION_TEMPLATES_READY;
			}

			return $result;
		}

		$isTourProviderOff = (bool)($options[self::USER_OPTION_PROVIDER_OFF] ?? false);
		if (!$isTourProviderOff)
		{
			$result[] = self::USER_OPTION_PROVIDER_OFF;
		}

		return $result;
	}
}
