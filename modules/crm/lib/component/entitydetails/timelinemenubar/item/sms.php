<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Integration\SmsManager;

class Sms extends Item
{
	public function getId(): string
	{
		return 'sms';
	}

	public function getName(): string
	{
		return
			(\Bitrix\Main\Loader::includeModule('messageservice') && \Bitrix\MessageService\Sender\Sms\Ednaru::isSupported())
				? \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_SMS_TITLE2')
				: \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_SMS_TITLE')
		;
	}

	public function getTitle(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_SMS');
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

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->getEntityTypeId()))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($this->getEntityTypeId());

			return ($factory && $factory->isClientEnabled());
		}

		return true;
	}

	public function prepareSettings(): array
	{
		$settings = [
			'serviceUrl' => '/bitrix/components/bitrix/crm.timeline/ajax.php',
			'canSendMessage' => SmsManager::canSendMessage(),
			'smsConfig' => SmsManager::getEditorConfig(
				$this->getEntityTypeId(),
				$this->getEntityId()
			),
		];
		$isSalescenterEnabled = !in_array($this->getEntityTypeId(), [
			\CCrmOwnerType::StoreDocument,
			\CCrmOwnerType::Order,
			\CCrmOwnerType::OrderPayment,
			\CCrmOwnerType::OrderShipment,
			\CCrmOwnerType::ShipmentDocument,
		]) && \Bitrix\Crm\Integration\SalesCenterManager::getInstance()->isShowApplicationInSmsEditor();

		$settings['smsConfig']['isSalescenterEnabled'] = $isSalescenterEnabled;

		$documentGeneratorManager = \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance();
		$isDocumentsEnabled = $documentGeneratorManager->isDocumentButtonAvailable();
		if ($isDocumentsEnabled)
		{
			$extension =  \Bitrix\Main\UI\Extension::getConfig('documentgenerator.selector');
			if ($extension)
			{
				$providersMap = $documentGeneratorManager->getCrmOwnerTypeProvidersMap();
				$provider = $providersMap[$this->getEntityTypeId()];
				if(!$provider)
				{
					$isDocumentsEnabled = false;
				}
				else
				{
					$settings['smsConfig']['documentsProvider'] = $provider;
					$settings['smsConfig']['documentsValue'] = $this->getEntityId();
				}
			}
			else
			{
				$isDocumentsEnabled = false;
			}
		}
		$settings['smsConfig']['isDocumentsEnabled'] = $isDocumentsEnabled;

		$settings['showFiles'] = false;
		$enableFiles = (\Bitrix\Main\Loader::includeModule('disk') && \Bitrix\Disk\Configuration::isPossibleToShowExternalLinkControl());
		if ($enableFiles)
		{
			$settings['smsConfig']['isFilesEnabled'] = $enableFiles;
			$enableFilesExternalLink = \Bitrix\Disk\Configuration::isEnabledManualExternalLink();
			$settings['smsConfig']['isFilesExternalLinkEnabled'] = $enableFilesExternalLink;
			if ($enableFilesExternalLink)
			{
				$settings['smsConfig']['diskUrls'] = [
					'urlSelect' => '/bitrix/tools/disk/uf.php?action=selectFile&SITE_ID='.SITE_ID,
					'urlRenameFile' => '/bitrix/tools/disk/uf.php?action=renameFile',
					'urlDeleteFile' => '/bitrix/tools/disk/uf.php?action=deleteFile',
					'urlUpload' => '/bitrix/tools/disk/uf.php?action=uploadFile&ncc=1',
				];
			}
			else
			{
				$settings['showFiles'] = \Bitrix\Crm\Integration\Bitrix24Manager::isEnabled();
			}
		}
		else
		{
			$settings['smsConfig']['isFilesEnabled'] = false;
		}
		$settings['enableFiles'] = $enableFiles;

		return $settings;
	}

	public function loadAssets(): void
	{
		if ($this->getSettings()['smsConfig']['isFilesExternalLinkEnabled'] ?? false)
		{
			\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');
		}
	}

}
