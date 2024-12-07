<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Integration\SalesCenterManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Settings;
use Bitrix\Disk\Configuration;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Salescenter\Restriction\ToolAvailabilityManager;
use CCrmOwnerType;

final class Sms extends Base
{
	private const SALES_CENTER_ENTITY_TYPES = [
		CCrmOwnerType::StoreDocument,
		CCrmOwnerType::Order,
		CCrmOwnerType::OrderPayment,
		CCrmOwnerType::OrderShipment,
		CCrmOwnerType::ShipmentDocument,
	];

	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();

		$filters[] = new Scope(Scope::AJAX);
		$filters[] = new ContentType([ContentType::JSON]);

		return $filters;
	}

	public function getConfigAction(int $entityTypeId, int $entityId): ?array
	{
		if ($entityTypeId <= 0 || $entityId <= 0)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		$sms = new TimelineMenuBar\Item\Sms(
			new TimelineMenuBar\Context($entityTypeId, $entityId)
		);
		if (!$sms->isAvailable())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$settings = $this->getBaseSettings($entityTypeId, $entityId);

		$isSalescenterEnabled = !in_array($entityTypeId, self::SALES_CENTER_ENTITY_TYPES, true)
			&& SalesCenterManager::getInstance()->isShowApplicationInSmsEditor()
		;
		$settings['smsConfig']['isSalescenterEnabled'] = $isSalescenterEnabled;
		$settings['smsConfig']['isSalescenterToolEnabled'] = $isSalescenterEnabled
			&& ToolAvailabilityManager::getInstance()->checkSalescenterAvailability()
		;

		$this->applyDocumentSettings($entityTypeId, $entityId, $settings);
		$this->applyFilesSettings($settings);

		return $settings;
	}

	private function getBaseSettings(int $entityTypeId, int $entityId): array
	{
		$smsConfig = SmsManager::getEditorConfig($entityTypeId, $entityId);
		$canSendMessage = SmsManager::canSendMessage();

		if (Settings\Crm::isWhatsAppScenarioEnabled())
		{
			// exclude Edna\WhatsApp sender
			$smsConfig['senders'] = array_values(array_filter(
				$smsConfig['senders'],
				static fn(array $sender) => $sender['id'] !== TimelineMenuBar\Item\Sms::WHATSAPP_PROVIDER_ID
			));

			$canSendMessage = count(
				array_filter(
					$smsConfig['senders'],
					static fn(array $sender) => $sender['canUse'] !== false
				)
			) > 0;

			$smsConfig['canSendMessage'] = $canSendMessage;
		}

		return [
			'serviceUrl' => TimelineMenuBar\Item\Sms::SERVICE_URL,
			'canSendMessage' => $canSendMessage,
			'smsConfig' => $smsConfig,
		];
	}

	private function applyDocumentSettings(int $entityTypeId, int $entityId, array &$settings): void
	{
		$documentGenerator = DocumentGeneratorManager::getInstance();
		$isDocumentsEnabled = $documentGenerator->isDocumentButtonAvailable();
		if ($isDocumentsEnabled)
		{
			$extension = Extension::getConfig('documentgenerator.selector');
			if ($extension)
			{
				$provider = $documentGenerator->getCrmOwnerTypeProvider($entityTypeId, false);
				if ($provider)
				{
					$settings['smsConfig']['documentsProvider'] = $provider;
					$settings['smsConfig']['documentsValue'] = $entityId;
				}
				else
				{
					$isDocumentsEnabled = false;
				}
			}
			else
			{
				$isDocumentsEnabled = false;
			}
		}

		$settings['smsConfig']['isDocumentsEnabled'] = $isDocumentsEnabled;
	}

	private function applyFilesSettings(array &$settings): void
	{
		$settings['showFiles'] = false;
		$enableFiles = Loader::includeModule('disk') && Configuration::isPossibleToShowExternalLinkControl();
		if ($enableFiles)
		{
			$settings['smsConfig']['isFilesEnabled'] = true;
			$enableFilesExternalLink = Configuration::isEnabledManualExternalLink();
			$settings['smsConfig']['isFilesExternalLinkEnabled'] = $enableFilesExternalLink;
			if ($enableFilesExternalLink)
			{
				$settings['smsConfig']['diskUrls'] = [
					'urlSelect' => '/bitrix/tools/disk/uf.php?action=selectFile&SITE_ID=' . SITE_ID,
					'urlRenameFile' => '/bitrix/tools/disk/uf.php?action=renameFile',
					'urlDeleteFile' => '/bitrix/tools/disk/uf.php?action=deleteFile',
					'urlUpload' => '/bitrix/tools/disk/uf.php?action=uploadFile&ncc=1',
				];
			}
			else
			{
				$settings['showFiles'] = Bitrix24Manager::isEnabled();
			}
		}
		else
		{
			$settings['smsConfig']['isFilesEnabled'] = false;
		}

		$settings['enableFiles'] = $enableFiles;
	}
}
