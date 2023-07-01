<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

class Sms extends Base
{
	public function sendAction(int $ownerTypeId, int $ownerId, array $params): Result
	{
		$owner = new ItemIdentifier($ownerTypeId, $ownerId);

		$message = new \Bitrix\Crm\Activity\Provider\Sms\MessageDto([
			'senderId' => $params['senderId'] ?? null,
			'from' => $params['from'] ?? null,
			'to' => $params['to'] ?? null,
			'body' => $params['body'] ?? null,
			'template' => $params['template'] ?? null,
		]);

		$sender = (new \Bitrix\Crm\Activity\Provider\Sms\Sender($owner, $message));

		if (isset($params['entityTypeId'], $params['entityId']))
		{
			$sender->setEntityIdentifier(new ItemIdentifier($params['entityTypeId'], $params['entityId']));
		}

		return $sender->send();
	}

	public function getConfigAction(int $entityTypeId, int $entityId): array
	{
		return [
			'enable' => SmsManager::canUse(),
			'manageUrl' => SmsManager::getManageUrl(),
			'contactCenterUrl' => (
				Loader::includeModule('bitrix24')
					? '/contact_center/'
					: '/services/contact_center/'
			),
			'canSendMessage' => SmsManager::canSendMessage(),
			'statusDescription' => SmsManager::getMessageStatusDescriptions(),
			'statusSemantics' => SmsManager::getMessageStatusSemantics(),
			'config' => $this->getConfig($entityTypeId, $entityId),
		];
	}

	private function getConfig(int $entityTypeId, int $entityId): array
	{
		$config = SmsManager::getEditorConfig($entityTypeId, $entityId);

		if (empty($config['communications']))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			$item = $factory->getItem($entityId);

			if ($item && $item->hasField(Item::FIELD_NAME_CONTACT_BINDINGS))
			{
				$contacts = $item->getContacts();
				foreach ($contacts as $contact)
				{
					$config['communications'][] = [
						'entityId' => $contact->getId(),
						'entityTypeId' => \CCrmOwnerType::Contact,
						'caption' => $contact->getFormattedName(),
					];
				}

				$company = $item->getCompany();
				if ($company)
				{
					$config['communications'][] = [
						'entityId' => $company->getId(),
						'entityTypeId' => \CCrmOwnerType::Company,
						'caption' => $company->getTitle(),
					];
				}
			}
		}

		foreach ($config['senders'] as &$sender)
		{
			$isTemplatesBased = ($sender['isTemplatesBased'] ?? true);
			if ($isTemplatesBased)
			{
				$sender['canUse'] = false;
				if (!empty($config['defaults']) && $config['defaults']['senderId'] === $sender['id'])
				{
					$config['defaults'] = null;
				}
			}
		}
		unset($sender);

		return $config;
	}
}
