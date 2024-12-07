<?php

namespace Bitrix\Crm\Controller\Action\MessageSender;

use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Main\Engine\Action;

class ProvidersConfig extends Action
{
	public function run(?string $providerName = null): array
	{
        $senders = SmsManager::getSenderInfoList(true);

		if (empty($senders) || empty($providerName))
		{
			return [];
		}

        $result = [];

        foreach ($senders as $sender)
        {
            if ($sender['id'] !== $providerName)
            {
                continue;
            }

            $result[] = $sender;
        }

		return $result;
	}
}