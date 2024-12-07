<?php

namespace Bitrix\Crm\MessageSender\MassWhatsApp;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Communications;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Traits\Singleton;

class MessageDataRepo
{
	use Singleton;

	public function getFirstMessageFrom(): ?string
	{
		$smsManager = SmsManager::getSenderById(SendItem::DEFAULT_PROVIDER);
		if ($smsManager === null)
		{
			return null;
		}

		$messageFrom = $smsManager->getFirstFromList();
		if ($messageFrom === null)
		{
			return null;
		}

		return $messageFrom;
	}

	public function getFromListByProviderId(string $providerId): array
	{
		$sender = SmsManager::getSenderById($providerId);

		if ($sender === null)
		{
			return [];
		}

		return $sender->getFromList();
	}

	public function getMessageTo(ItemIdentifier $identifier): ?string
	{
		$comm = new Communications(
			$identifier->getEntityTypeId(),
			$identifier->getEntityId(),
		);

		$communications = $comm->get();

		if (empty($communications))
		{
			return null;
		}

		$phones = $communications[0]['phones'] ?? [];

		if (empty($phones))
		{
			return null;
		}

		return $phones[0]['value'] ?? null;
	}
}