<?php

use Bitrix\Disk\Document\Flipchart\BoardService;
use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Disk\Document\Flipchart\JwtService;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\Models\GuestUser;
use Bitrix\Disk\document\SharingControlType;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\User;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Context;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die;

Loc::loadMessages(__FILE__);

class CDiskFlipchartViewerComponent extends DiskComponent
{

	const DISPLAY_VARIANTS = [
		'desktop',
		'mobile',
	];

	const DEFAULT_DISPLAY_VARIANT = 'desktop';

	private bool $isExternalLinkMode = false;
	private bool $unifiedLinkAccessOnly = false;
	private bool $isViewMode = false;
	private bool $isEditMode = false;
	private ?DocumentSession $session = null;

	private function convertDocumentId(int|string $documentId, $versionId = null): string
	{
		return BoardService::convertDocumentIdToExternal($documentId, $versionId);
	}

	private function generateToken(JwtService $jwt): ?string
	{
		$session = $this->session;

		if ($this->isNewElement())
		{
			$jwt->addWebhookGetParam('newBoard', '1');
		}

		if ($_GET['c_element'] ?? null)
		{
			$jwt->addWebhookGetParam('c_element', $_GET['c_element']);
		}

		return $jwt->generateToken(
			$this->isViewMode,
			[
				'user_id' => $this->arParams['USER_ID'],
				'username' => $this->arParams['USERNAME'],
				'avatar_url' => $this->arParams['AVATAR_URL'] ?? null,
				'fileUrl' => $this->arParams['DOCUMENT_URL'],
				'download_link' => $this->arParams['DOCUMENT_URL'],
				'document_id' => $this->convertDocumentId($session->getObject()->getId(), $session->getVersionId()),
				'session_id' => $session->getExternalHash(),
				'file_name' => $this->arParams['ORIGINAL_FILE']?->getNameWithoutExtension() ?? $session->getObject()->getNameWithoutExtension(),
				'salt' => crc32($session->getCreateTime()),
			],
		);
	}

	private function getDisplayVariant()
	{
		$variant = $this->arParams['DISPLAY_VARIANT'] ?? $_GET['variant'] ?? null;
		if (!in_array($variant, self::DISPLAY_VARIANTS))
		{
			$variant = self::DEFAULT_DISPLAY_VARIANT;
		}

		return $variant;
	}

	private function prepareSdkParams(): void
	{
		$session = $this->session;
		$jwt = new JwtService($session->getUser());
		$token = $this->generateToken($jwt);

		if (Configuration::isUsingDocumentProxy())
		{
			$appUrl = $jwt->getAppUrlFromProxy();
		}
		else
		{
			$appUrl = Configuration::getAppUrl();
		}

		$this->arResult['DOCUMENT_SESSION'] = $session;
		$this->arResult['DOCUMENT_URL'] = $this->arParams['DOCUMENT_URL'];
		$this->arResult['DOCUMENT_ID'] = $this->convertDocumentId($session->getObject()->getId(), $session->getVersionId());
		$this->arResult['ORIGINAL_DOCUMENT_ID'] = $this->arParams['ORIGINAL_FILE']?->getId() ?? $session->getObject()->getId();
		$this->arResult['DOCUMENT_NAME'] = $this->arParams['ORIGINAL_FILE']?->getName() ?? $session->getObject()->getName();
		$this->arResult['DOCUMENT_NAME_WITHOUT_EXTENSION'] = $this->arParams['ORIGINAL_FILE']?->getNameWithoutExtension() ?? $this->arParams['DOCUMENT_NAME_WITHOUT_EXTENSION'] ?? $session->getObject()->getNameWithoutExtension();
		$this->arResult['SESSION_ID'] = $session->getExternalHash();
		$this->arResult['APP_URL'] = $appUrl;
		$this->arResult['TOKEN'] = $token;
		$this->arResult['ACCESS_LEVEL'] = $this->isViewMode ? 'readonly' : 'editable';
		$this->arResult['EDIT_BOARD'] = $this->isEditMode;
		$this->arResult['SHOW_TEMPLATES_MODAL'] = (bool)($this->arParams['SHOW_TEMPLATES_MODAL'] ?? false);
		$this->arResult['LANGUAGE'] = $this->getLanguage();
		$this->arResult['HEADER_LOGO_URL'] = $this->getHeaderLogoUrl();
	}

	private function prepareOtherParams(): void
	{
		$featureBlocker = Bitrix24Manager::filterJsAction('disk_board_external_link', '');
		$this->arResult['SHOULD_BLOCK_EXTERNAL_LINK_FEATURE'] = (bool)$featureBlocker;
		$this->arResult['BLOCKER_EXTERNAL_LINK_FEATURE'] = $featureBlocker;
		$this->arResult['SHOULD_SHOW_SHARING_BUTTON'] = $this->isEditMode && !$this->isExternalLinkMode;
		$this->arResult['SHARING_CONTROL_TYPE'] = $this->getSharingControlType()->value;
		$this->arResult['DISPLAY_VARIANT'] = $this->getDisplayVariant();
		$this->arResult['UNIFIED_LINK_ACCESS_ONLY'] = $this->unifiedLinkAccessOnly;
		$this->arResult['FILE_UNIQUE_CODE'] = $this->arParams['FILE_UNIQUE_CODE'] ?? '';
	}

	protected function prepareParams(): void
	{
		parent::prepareParams();

		if ($this->arParams['DOCUMENT_SESSION'] instanceof DocumentSession)
		{
			$this->session = $this->arParams['DOCUMENT_SESSION'];
			$this->isExternalLinkMode = (bool)($this->arParams['EXTERNAL_LINK_MODE'] ?? false);
			$this->unifiedLinkAccessOnly = (bool)($this->arParams['UNIFIED_LINK_ACCESS_ONLY'] ?? false);
			$this->isViewMode = $this->session->getType() === DocumentSession::TYPE_VIEW;
			$this->isEditMode = $this->session->getType() === DocumentSession::TYPE_EDIT;
		}
		else
		{
			throw new ArgumentException('DOCUMENT_SESSION is a required parameter!');
		}
	}

	protected function processActionDefault(): void
	{
		$this->prepareSdkParams();
		$this->prepareOtherParams();
		$this->includeComponentTemplate();
		$this->sendAnalytics();
	}

	protected function isNewElement(): bool
	{
		/** @var ?File $originalFile */
		$originalFile = $this->arParams['ORIGINAL_FILE'];

		return $originalFile && time() - $originalFile->getCreateTime()->getTimestamp() < 30;
	}

	protected function sendAnalytics(): void
	{
		$isNewElement = $this->isNewElement();

		Application::getInstance()->addBackgroundJob(function () use ($isNewElement) {
			$event = new AnalyticsEvent('open', 'boards', 'boards');
			if ($_GET['c_element'] ?? null)
			{
				$event->setElement($_GET['c_element']);
			}
			$event
				->setSubSection($isNewElement ? 'new_element' : 'old_element')
				->send()
			;
		});
	}

	private function getLanguage(): string
	{
		return in_array(Context::getCurrent()?->getLanguage(), Configuration::getAllowedLanguages())
			? Context::getCurrent()?->getLanguage()
			: Configuration::getDefaultLanguage();
	}

	private function getHeaderLogoUrl(): string
	{
		if ($this->isExternalLinkMode && ModuleManager::isModuleInstalled('bitrix24'))
		{
			return 'https://bitrix24.com';
		}

		if ($this->isExternalLinkMode && !ModuleManager::isModuleInstalled('bitrix24'))
		{
			return UrlManager::getInstance()->getHostUrl();
		}

		$userId = $this->getCurrentUser()->getId();
		$proxyTypeUser = Driver::getInstance()->getStorageByUserId($userId)?->getProxyType();
		if ($proxyTypeUser instanceof \Bitrix\Disk\ProxyType\User)
		{
			return $proxyTypeUser->getBaseUrlBoardsList();
		}

		return '';
	}

	private function getCurrentUser(): User
	{
		if (CurrentUser::get()->getId())
		{
			return User::getById(CurrentUser::get()->getId());
		}

		return GuestUser::create();
	}

	private function getSharingControlType(): SharingControlType
	{
		$currentUser = CurrentUser::get();
		$canUserChangeRights = $this->session->canUserChangeRights($currentUser);
		$canUserShare = $this->session->canUserShare($currentUser);

		// todo: block by feature?

		if (!$canUserChangeRights && !$canUserShare)
		{
			return SharingControlType::WithoutEdit;
		}

		if ($canUserChangeRights)
		{
			return SharingControlType::WithChangeRights;
		}

		if ($canUserShare)
		{
			return SharingControlType::WithSharing;
		}

		return SharingControlType::WithoutEdit;
	}
}
