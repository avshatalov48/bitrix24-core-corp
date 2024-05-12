<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Activity\Provider\Sms\PlaceholderContext;
use Bitrix\Crm\Activity\Provider\Sms\PlaceholderManager;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;
use Bitrix\MessageService\Controller\Sender;

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

	public function getTemplatesAction(string $senderId, array $context = null): array
	{
		if (!Loader::includeModule('messageservice'))
		{
			$this->addError(new Error(Loc::getMessage('CRM_ACTIVITY_SMS_MESSAGESERVICE_NOT_INSTALLED')));

			return [];
		}

		$result = $this->forward(
			Sender::class,
			'getTemplates',
			[
				'id' => $senderId,
				'context' => $context,
			]
		);

		$entityCategoryId = $context['entityCategoryId'] ?? null;
		if (!isset($context['entityTypeId']))
		{
			return $result;
		}

		$placeholderManager = new PlaceholderManager();
		$ids = [];
		$templates = $result['templates'] ?? [];
		foreach ($templates as &$template)
		{
			$ids[] = $template['ORIGINAL_ID'];
		}
		unset($template);

		$placeholderContext = PlaceholderContext::createInstance($context['entityTypeId'], $entityCategoryId);
		$filledPlaceholders = $placeholderManager->getPlaceholders($ids, $placeholderContext);

		$result['templates'] = $this->appendTemplateFilledPlaceholders($templates, $filledPlaceholders);

		return $result;
	}

	private function appendTemplateFilledPlaceholders(array $templates, array $filledPlaceholders): array
	{
		foreach ($templates as &$template)
		{
			foreach ($filledPlaceholders as $filledPlaceholder)
			{
				if ($template['ORIGINAL_ID'] !== (int)$filledPlaceholder['TEMPLATE_ID'])
				{
					continue;
				}

				if (!isset($template['FILLED_PLACEHOLDERS']))
				{
					$template['FILLED_PLACEHOLDERS'] = [];
				}

				$template['FILLED_PLACEHOLDERS'][] = $filledPlaceholder;
			}
		}
		unset($template);

		return $templates;
	}

	public function getConfigAction(int $entityTypeId, int $entityId): array
	{
		return [
			'enable' => SmsManager::canUse(),
			'manageUrl' => SmsManager::getManageUrl(),
			'contactCenterUrl' => Container::getInstance()->getRouter()->getContactCenterUrl(),
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

				if ($item->hasField(Item::FIELD_NAME_COMPANY))
				{
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
		}

		$isMessageServiceInstalled = ModuleManager::isModuleInstalled('messageservice');

		foreach ($config['senders'] as &$sender)
		{
			$isTemplatesBased = ($sender['isTemplatesBased'] ?? false);
			$canUse = ($sender['canUse'] ?? false);
			$senderId = $sender['id'];

			if (
				$isTemplatesBased
				&& $canUse
				&& !empty($config['defaults'])
				&& $config['defaults']['senderId'] === $senderId
			)
			{
				if ($isMessageServiceInstalled)
				{
					$senderEntity = \Bitrix\MessageService\Sender\SmsManager::getSenderById($senderId);
					if ($senderEntity)
					{
						$sender['templates'] = $senderEntity->getTemplatesList();
					}
				}
				else
				{
					$config['defaults'] = null;
				}
			}
		}
		unset($sender);

		return $config;
	}
}
