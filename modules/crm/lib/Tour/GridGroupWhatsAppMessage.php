<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\MessageSender\MassWhatsApp;
use Bitrix\Crm\Settings;

class GridGroupWhatsAppMessage extends Base
{
	protected const OPTION_NAME = 'grid-group-whatsapp-message';

	protected function canShow(): bool
	{
		if (!$this->isFeatureEnabled())
		{
			return false;
		}

		if ($this->isUserSeenTour())
		{
			return false;
		}

		return $this->isSmsProviderEdnaEnabled();
	}

	protected function getSteps(): array
	{
		return [
			[
				'id' => 'grid-group-whatsapp-message',
				'title' => Loc::getMessage('CRM_TOUR_GRID_GROUP_WHATSAPP_MESSAGE_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_GRID_GROUP_WHATSAPP_MESSAGE_TEXT'),
				'position' => 'top',
				'useDynamicTarget' => true,
				'eventName' => 'BX.Crm.Tour.GridGroupWhatsAppMessage::selectRow',
			],
		];
	}

	private function isSmsProviderEdnaEnabled(): bool
	{
		$sender = SmsManager::getSenderById(MassWhatsApp\SendItem::DEFAULT_PROVIDER);

		if (!$sender)
		{
			return false;
		}

		return $sender::isSupported() && $sender->canUse();
	}

	private function isFeatureEnabled(): bool
	{
		return Settings\Crm::isWhatsAppScenarioEnabled();
	}

	protected function getOptions(): array
	{
		return [
			'hideTourOnMissClick' => true,
			'steps' => [
				'popup' => [
					'width' => 400,
				],
			],
		];
	}
}