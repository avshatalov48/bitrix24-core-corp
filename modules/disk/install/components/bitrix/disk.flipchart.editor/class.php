<?php

use Bitrix\Disk\Document\Flipchart\BoardService;
use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Disk\Document\Flipchart\JwtService;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\Models\GuestUser;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\User;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Context;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

Loc::loadMessages(__FILE__);

class CDiskFlipchartViewerComponent extends DiskComponent
{

	private function convertDocumentId(int | string $documentId): string
	{
		return BoardService::convertDocumentIdToExternal($documentId);
	}

	private function generateToken(): string
	{
		/** @var DocumentSession $session */
		$session = $this->arParams['DOCUMENT_SESSION'];
		$jwt = new JwtService($session->getUser());

		return $jwt->generateToken(
			$session->getType() === DocumentSession::TYPE_VIEW,
			[
				'user_id' => $this->arParams['USER_ID'],
				'username' => $this->arParams['USERNAME'],
				'avatar_url' => $this->arParams['AVATAR_URL'] ?? null,
				'fileUrl' => $this->arParams['DOCUMENT_URL'],
				'download_link' => $this->arParams['DOCUMENT_URL'],
				'document_id' => $this->convertDocumentId($session->getObject()->getId()),
				'session_id' => $session->getExternalHash(),
				'file_name' => $this->arParams['ORIGINAL_FILE']?->getNameWithoutExtension() ?? $session->getObject()->getNameWithoutExtension(),
			]
		);
	}

	private function prepareSdkParams(): void
	{
		/** @var DocumentSession $session */
		$session = $this->arParams['DOCUMENT_SESSION'];
		$this->arResult['DOCUMENT_SESSION'] = $session;
		$this->arResult['DOCUMENT_URL'] = $this->arParams['DOCUMENT_URL'];
		$this->arResult['DOCUMENT_ID'] = $this->convertDocumentId($session->getObject()->getId());
		$this->arResult['ORIGINAL_DOCUMENT_ID'] = $this->arParams['ORIGINAL_FILE']?->getId() ?? $session->getObject()->getId();
		$this->arResult['DOCUMENT_NAME'] = $this->arParams['ORIGINAL_FILE']?->getName() ?? $session->getObject()->getName();
		$this->arResult['DOCUMENT_NAME_WITHOUT_EXTENSION'] = $this->arParams['ORIGINAL_FILE']?->getNameWithoutExtension() ?? $this->arParams['DOCUMENT_NAME_WITHOUT_EXTENSION'] ?? $session->getObject()->getNameWithoutExtension();
		$this->arResult['SESSION_ID'] = $session->getExternalHash();
		$this->arResult['APP_URL'] = Configuration::getAppUrl();
		$this->arResult['TOKEN'] = $this->generateToken($session->getType() === DocumentSession::TYPE_VIEW);
		$this->arResult['ACCESS_LEVEL'] = ($session->getType() === DocumentSession::TYPE_VIEW) ? 'readonly' : 'editable';
		$this->arResult['EDIT_BOARD'] = $session->getType() === DocumentSession::TYPE_EDIT;
		$this->arResult['SHOW_TEMPLATES_MODAL'] = (bool)($this->arParams['SHOW_TEMPLATES_MODAL'] ?? false);
		$this->arResult['LANGUAGE'] = $this->getLanguage();
		$this->arResult['HEADER_LOGO_URL'] = $this->getHeaderLogoUrl();
	}

	private function prepareOtherParams(): void
	{
		$featureBlocker = Bitrix24Manager::filterJsAction('disk_board_external_link', '');
		$this->arResult['SHOULD_BLOCK_EXTERNAL_LINK_FEATURE'] = (bool)$featureBlocker;
		$this->arResult['BLOCKER_EXTERNAL_LINK_FEATURE'] = $featureBlocker;
	}

	protected function processActionDefault(): void
	{
		$this->prepareSdkParams();
		$this->prepareOtherParams();
		$this->includeComponentTemplate();
	}

	private function getLanguage(): string
	{
		return in_array(Context::getCurrent()->getLanguage(), ['ru', 'en'])
			? Context::getCurrent()->getLanguage()
			: 'en';
	}

	private function getHeaderLogoUrl(): string
	{
		$isExternalLinkMode = (bool)($this->arParams['EXTERNAL_LINK_MODE'] ?? false);

		if ($isExternalLinkMode && ModuleManager::isModuleInstalled('bitrix24'))
		{
			return 'https://bitrix24.com';
		}

		if ($isExternalLinkMode && !ModuleManager::isModuleInstalled('bitrix24'))
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

}