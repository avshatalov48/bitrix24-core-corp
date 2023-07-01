<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loader::includeModule('crm');

class CrmChannelSelectorComponent extends Base
{
	protected ?ItemIdentifier $itemIdentifier;

	public function onPrepareComponentParams($arParams): array
	{
		$id = $arParams['id'] ?? 'channel-selector-' . \Bitrix\Main\Security\Random::getString(5);

		$arParams['id'] = $id;
		$arParams['body'] = $arParams['body'] ?? '';
		$arParams['title'] = $arParams['title'] ?? '';
		$arParams['link'] = $arParams['link'] ?? '';
		$arParams['isLinkObtainable'] = ($arParams['isLinkObtainable'] ?? null) === true;
		$arParams['entityTypeId'] = (int)($arParams['entityTypeId'] ?? 0);
		$arParams['entityId'] = (int)($arParams['entityId'] ?? 0);
		$arParams['files'] = (array)($arParams['files'] ?? []);
		$arParams['storageTypeId'] = (int)($arParams['storageTypeId'] ?? 0);
		$arParams['activityEditorId'] = $arParams['activityEditorId'] ?? 'activity-editor-' . $id;
		$arParams['isConfigurable'] = (bool)($arParams['isConfigurable'] ?? false);
		$arParams['configureContext'] = (string)($arParams['configureContext'] ?? null);
		$arParams['config'] = (array)($arParams['config'] ?? null);
		$arParams['isForceDefaultConfig'] = (bool)($arParams['isForceDefaultConfig'] ?? false);
		$arParams['skipTemplate'] = (bool)($arParams['skipTemplate'] ?? false);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();
		if ($this->getErrors())
		{
			return;
		}

		try
		{
			$itemIdentifier = new ItemIdentifier(
				$this->arParams['entityTypeId'],
				$this->arParams['entityId'],
			);
		}
		catch (ArgumentException $e)
		{
			$itemIdentifier = null;
		}
		if (!($itemIdentifier instanceof ItemIdentifier))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error('Message owner is not specified');
			return;
		}
		$this->itemIdentifier = $itemIdentifier;
	}

	protected function prepareResult(): array
	{
		$communications = $this->loadCommunications();
		$channels = $this->arParams['channels'] ?? [];

		if (!$this->arParams['channels'])
		{
			$channels = $this->getPhoneChannels($communications);
			$channels[] = $this->getEmailChannel($communications);
			$channels[] = $this->getOpenlineChannel($communications);
		}

		$this->applyConfig($channels);

		$smsUrl = null;
		$smsComponentPath = \CComponentEngine::makeComponentPath('bitrix:crm.sms.send');
		if (!empty($smsComponentPath))
		{
			$smsUrl = new Uri(getLocalPath('components' . $smsComponentPath . '/slider.php'));
		}
		$hasVisibleChannels = !empty($communications); // channels are visible if there are any communications
		if (!$hasVisibleChannels)
		{
			foreach ($channels as $channel)
			{
				if ($channel['canBeShown'])
				{
					$hasVisibleChannels = true;
					break;
				}
			}
		}

		return [
			'id' => $this->arParams['id'],
			'channels' => $channels,
			'communications' => $communications,
			'body' => $this->arParams['body'],
			'title' => $this->arParams['title'],
			'link' => $this->arParams['link'],
			'isLinkObtainable' => $this->arParams['isLinkObtainable'],
			'isConfigurable' => $this->arParams['isConfigurable'],
			'configureContext' => $this->arParams['configureContext'],
			'files' => $this->arParams['files'],
			'storageTypeId' => $this->arParams['storageTypeId'],
			'activityEditorId' => $this->arParams['activityEditorId'],
			'smsUrl' => $smsUrl,
			'entityTypeId' => $this->itemIdentifier->getEntityTypeId(),
			'entityId' => $this->itemIdentifier->getEntityId(),
			'entityTypeName' => \CCrmOwnerType::ResolveName($this->itemIdentifier->getEntityTypeId()),
			'hasVisibleChannels' => $hasVisibleChannels,
		];
	}

	protected function getPhoneChannels(array $communications): array
	{
		$channels = [];

		foreach (Integration\SmsManager::getSenderInfoList() as $senderInfo)
		{
			// template based providers can not send links
			if ($senderInfo['isTemplatesBased'])
			{
				continue;
			}

			$channels[] = [
				'type' => Phone::ID,
				'title' => $senderInfo['name'],
				'canBeShown' => $senderInfo['canUse'],
				'isAvailable' =>
					$senderInfo['canUse']
					&& !empty($communications[Phone::ID])
				,
				'id' => $senderInfo['id'],
			];
		}

		return $channels;
	}

	protected function getEmailChannel(array $communications): array
	{
		return [
			'id' => Email::ID,
			'type' => Email::ID,
			'title' => 'E-mail',
			'canBeShown' => !empty($communications[Email::ID]),
			'isAvailable' => !empty($communications[Email::ID]),
		];
	}

	protected function getOpenlineChannel(array $communications): array
	{
		$isAvailable = true;
		// todo load activities with open lines
		// get session info
		// if there is an active session - the channel is available

		return [
			'id' => \Bitrix\Crm\Multifield\Type\Im::ID,
			'type' => \Bitrix\Crm\Multifield\Type\Im::ID,
			'title' => Loc::getMessage('CRM_CHANNEL_SELECTOR_OPENLINE_CHANNEL_TITLE'),
			'canBeShown' => !empty($communications[\Bitrix\Crm\Multifield\Type\Im::ID]),
			'isAvailable' =>
				$isAvailable
				&& !empty($communications[\Bitrix\Crm\Multifield\Type\Im::ID])
			,
		];
	}

	protected function loadCommunications(): array
	{
		$identifiers = $this->collectIdentifiers();
		$entities = [];
		foreach ($identifiers as $identifier)
		{
			$entities[] = [
				'NAME' => \CCrmOwnerType::ResolveName($identifier->getEntityTypeId()),
				'ID' => $identifier->getEntityId(),
			];
		}
		$result = [];
		$multiFieldsCollection = FieldMultiTable::getList([
			'filter' => FieldMultiTable::prepareFilter($entities, [
				Phone::ID,
				Email::ID,
				\Bitrix\Crm\Multifield\Type\Im::ID,
			]),
		]);
		while ($multiFieldRow = $multiFieldsCollection->fetchObject())
		{
			$typeId = $multiFieldRow->getTypeId();
			if (
				$typeId === \Bitrix\Crm\Multifield\Type\Im::ID
				&& !FieldMultiTable::isImOpenLinesValue($multiFieldRow['VALUE'])
			)
			{
				continue;
			}
			$entityTypeId = \CCrmOwnerType::ResolveID($multiFieldRow->getEntityId());
			if (!isset($result[$typeId]))
			{
				$result[$typeId] = $multiFieldRow->collectValues();
				$result[$typeId]['ENTITY_TYPE_ID'] = $entityTypeId;
				continue;
			}
			if ($entityTypeId === $this->itemIdentifier->getEntityTypeId())
			{
				continue;
			}
			$foundEntityTypeId = $result[$multiFieldRow->getTypeId()]['ENTITY_TYPE_ID'];
			if ($entityTypeId === \CCrmOwnerType::Company && $foundEntityTypeId === \CCrmOwnerType::Contact)
			{
				$result[$typeId] = $multiFieldRow->collectValues();
				$result[$typeId]['ENTITY_TYPE_ID'] = $entityTypeId;
			}
		}

		return $result;
	}

	protected function applyConfig(array &$channels): void
	{
		$config = $this->getConfig();
		$sortedChannels = [];
		$processedIds = [];
		$channelIndexes = array_flip(array_column($channels, 'id'));
		foreach ($config as $channel)
		{
			if (isset($channelIndexes[$channel['id']]))
			{
				$sortedChannel = $channels[$channelIndexes[$channel['id']]];
				$sortedChannel['isHidden'] = $channel['isHidden'] !== false;
				$sortedChannels[] = $sortedChannel;
				$processedIds[$channel['id']] = $channel['id'];
			}
		}
		foreach ($channels as $channel)
		{
			if (!isset($processedIds[$channel['id']]))
			{
				$channel['isHidden'] = true;
				$sortedChannels[] = $channel;
			}
		}

		$channels = $sortedChannels;
	}

	protected function getConfig(): array
	{
		$config = $this->arParams['isForceDefaultConfig'] ? $this->arParams['config'] : $this->loadConfig();
		if (empty($config))
		{
			$config = $this->getDefaultConfig();
		}

		return $config;
	}

	protected function loadConfig(): ?array
	{
		$options = CUserOptions::GetOption('crm', 'channel-selector');
		if (empty($options) || empty($options['items']))
		{
			return null;
		}

		try
		{
			$items = \Bitrix\Main\Web\Json::decode($options['items']);
		}
		catch(ArgumentException $e)
		{
			$items = [];
		}

		return $items;
	}

	protected function getDefaultConfig(): array
	{
		return [
			[
				'id' => 'twilio',
				'isHidden' => false,
			],
			[
				'id' => 'smsednaru',
				'isHidden' => false,
			],
			[
				'id' => 'ednaruimhpx',
				'isHidden' => false,
			],
			[
				'id' => 'EMAIL',
				'isHidden' => false,
			],
			[
				'id' => 'IM',
				'isHidden' => false,
			],
		];
	}

	protected function collectIdentifiers(): array
	{
		$identifiers = [$this->itemIdentifier];

		$factory = Service\Container::getInstance()->getFactory($this->itemIdentifier->getEntityTypeId());
		$isClientEnabled = !$factory || ($factory && $factory->isClientEnabled());
		if ($isClientEnabled)
		{
			$companyRelation = Service\Container::getInstance()->getRelationManager()->getRelation(
				new RelationIdentifier(
					\CCrmOwnerType::Company,
					$this->itemIdentifier->getEntityTypeId(),
				)
			);
			if ($companyRelation && $companyRelation->isPredefined())
			{
				foreach ($companyRelation->getParentElements($this->itemIdentifier) as $identifier)
				{
					$identifiers[] = $identifier;
				}
			}
			$contactRelation = Service\Container::getInstance()->getRelationManager()->getRelation(
				new RelationIdentifier(
					\CCrmOwnerType::Contact,
					$this->itemIdentifier->getEntityTypeId(),
				)
			);
			if ($contactRelation && $contactRelation->isPredefined())
			{
				foreach ($contactRelation->getParentElements($this->itemIdentifier) as $identifier)
				{
					$identifiers[] = $identifier;
				}
			}
		}

		return $identifiers;
	}

	protected function render()
	{
		if (!$this->arParams['skipTemplate'])
		{
			$this->includeComponentTemplate();
		}
	}

	/**
	 * @return array|null
	 */
	public function executeComponent(): ?array
	{
		$this->init();
		if ($this->getErrors())
		{
			$this->render();
			return null;
		}

		$this->arResult = $this->prepareResult();
		$this->render();
		return $this->arResult;
	}
}
