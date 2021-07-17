<?php

use Bitrix\Disk;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Internals\Engine\Contract\SidePanelWrappable;
use Bitrix\Disk\User;
use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CDiskFileEditorOnlyOfficeComponent extends BaseComponent implements Controllerable, SidePanelWrappable
{
	/** @var User */
	protected $currentUser;

	public function prepareParams()
	{
		parent::prepareParams();

		if (CurrentUser::get()->getId())
		{
			$this->currentUser = User::getById(CurrentUser::get()->getId());
		}
		else
		{
			$this->currentUser = OnlyOffice\Models\GuestUser::create();
		}
	}

	public function configureActions()
	{
		return [];
	}

	protected function processActionDefault()
	{
		if (!isset($this->arParams['SHOW_BUTTON_OPEN_NEW_WINDOW']))
		{
			$this->arParams['SHOW_BUTTON_OPEN_NEW_WINDOW'] = true;
		}
		/** @var OnlyOffice\Models\DocumentSession $documentSession */
		$documentSession = $this->arParams['DOCUMENT_SESSION'];

		$documentInfo = $documentSession->getInfo();
		if (!$documentInfo)
		{
			$documentInfo = $documentSession->createInfo();
		}

		if ($documentInfo->isSaving())
		{
			$this->processSavingTemplate($documentSession);

			return;
		}

		if ($documentSession->isNonActive())
		{
			$documentSession->setAsActive();
		}

		$sessionGetParams = [
			'userId' => $documentSession->getUserId(),
			'sourceDocumentSessionId' => $documentSession->getId(),
			'documentSessionHash' => $documentSession->getExternalHash(),
		];
		$onlyOfficeController = new Disk\Controller\OnlyOffice();

		$allowEdit = false;
		$allowRename = false;
		if (!$documentSession->isVersion())
		{
			$allowEdit = $documentSession->canTransformUserToEdit(CurrentUser::get());
		}
		if ($documentSession->isEdit() && !$documentSession->isVersion())
		{
			$allowRename = $documentSession->canUserRename(CurrentUser::get());
		}

		$configBuilder = new OnlyOffice\ConfigBuilder($documentSession);
		$configBuilder
			->allowEdit($allowEdit)
			->allowRename($allowRename)
			->setUser($this->currentUser)
			/** @see Disk\Controller\OnlyOffice::handleOnlyOfficeAction() */
			->setCallbackUrl($onlyOfficeController->getActionUri('handleOnlyOffice', $sessionGetParams, true))
			/** @see Disk\Controller\OnlyOffice::downloadAction() */
			->setDocumentUrl($onlyOfficeController->getActionUri('download', $sessionGetParams, true))
		;

		$this->arResult['SERVER'] = rtrim(ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getServer(), '/');
		$this->arResult['DOCUMENT_HANDLERS'] = $this->getDocumentHandlersForEditingFile();
		$this->arResult['EDITOR'] = [
			'MODE' => $configBuilder->getMode(),
			'ALLOW_EDIT' => $allowEdit,
		];
		$this->arResult['DOCUMENT_SESSION'] = [
			'ID' => $documentSession->getId(),
			'HASH' => $documentSession->getExternalHash(),
		];
		$this->arResult['OBJECT'] = [
			'ID' => $documentSession->getObjectId(),
			'NAME' => $documentSession->getObject()->getName(),
		];
		$this->arResult['ATTACHED_OBJECT'] = [
			'ID' => $documentSession->getContext()->getAttachedObjectId(),
		];
		/** @see \Bitrix\Disk\Controller\DocumentService::viewDocumentAction */
		$this->arResult['LINK_OPEN_NEW_WINDOW'] = (new Disk\Controller\DocumentService())->getActionUri(
			'viewDocument',
			['documentSessionId' => $documentSession->getId()]
		);
		if ($this->arParams['LINK_TO_EDIT'])
		{
			$this->arResult['LINK_TO_EDIT'] = $this->arParams['LINK_TO_EDIT'];
		}
		else
		{
			$this->arResult['LINK_TO_EDIT'] = (new Disk\Controller\DocumentService())->getActionUri(
				'goToEdit',
				[
					'documentSessionId' => $documentSession->getId(),
					'documentSessionHash' => $documentSession->getExternalHash(),
					'serviceCode' => OnlyOffice\OnlyOfficeHandler::getCode(),
				]
			);
		}

		$infoToken = Disk\Document\Online\UserInfoToken::generateTimeLimitedToken(
			$this->getUserIdForOnline(),
			$documentSession->getObject()->getRealObjectId()
		);

		$this->arResult['EXTERNAL_LINK_MODE'] = (bool)($this->arParams['EXTERNAL_LINK_MODE'] ?? false);
		$this->arResult['CURRENT_USER_AS_GUEST'] = $this->currentUser instanceof OnlyOffice\Models\GuestUser;
		$this->arResult['CURRENT_USER'] = Json::encode([
			'id' => $this->getUserIdForOnline(),
			'name' => $this->currentUser->getFormattedName(),
			'avatar' => $this->currentUser->getAvatarSrc(),
			'infoToken' => $infoToken,
		]);

		if ($this->arResult['EXTERNAL_LINK_MODE'])
		{
			$configBuilder->allowDownload(false);
		}
		else
		{
			//for BX.desktopUtils.runningCheck
			Loader::includeModule('im');
		}

		if ($this->arResult['EXTERNAL_LINK_MODE'] && ModuleManager::isModuleInstalled('bitrix24'))
		{
			$this->arResult['HEADER_LOGO_LINK'] = 'https://bitrix24.com';
			if (Loader::includeModule('bitrix24') && !\CBitrix24::isCustomDomain())
			{
				$host = parse_url(UrlManager::getInstance()->getHostUrl(), PHP_URL_HOST);
				$baseUrlToLogo = new Uri("https://bitrix24public.com/{$host}");

				$configBuilder->setBaseUrlToLogo($baseUrlToLogo);
			}
		}
		elseif ($this->arResult['EXTERNAL_LINK_MODE'] && !ModuleManager::isModuleInstalled('bitrix24'))
		{
			$this->arResult['HEADER_LOGO_LINK'] = UrlManager::getInstance()->getHostUrl();
		}
		else
		{
			$proxyTypeUser = Driver::getInstance()->getStorageByUserId($this->currentUser->getId())->getProxyType();
			if($proxyTypeUser instanceof Disk\ProxyType\User)
			{
				$this->arResult['HEADER_LOGO_LINK'] = $proxyTypeUser->getBaseUrlDocumentList();
			}
		}

		$this->arResult['EDITOR_JSON'] = Json::encode($configBuilder->build());
		$this->arResult['SHARING_CONTROL_TYPE'] = $this->getSharingControlType($documentSession);
		$this->arResult['PULL_CONFIG'] = null;
		$publicPullConfigurator = new Disk\Document\Online\PublicPullConfigurator();
		if ($publicPullConfigurator->getErrors())
		{
			$this->errorCollection->add($publicPullConfigurator->getErrors());
		}
		else
		{
			$this->arResult['PULL_CONFIG'] = $publicPullConfigurator->getConfig($documentSession->getObject()->getRealObjectId());
		}

		$this->includeComponentTemplate();
	}

	protected function getSharingControlType(OnlyOffice\Models\DocumentSession $documentSession): ?string
	{
		if ($this->arResult['EXTERNAL_LINK_MODE'] || !$documentSession->getObject())
		{
			return null;
		}

		$currentUser = CurrentUser::get();
		if (!$documentSession->canUserChangeRights($currentUser) && !$documentSession->canUserShare($currentUser))
		{
			return 'without-edit';
		}
		if ($documentSession->canUserChangeRights($currentUser))
		{
			return 'with-change-rights';
		}
		if ($documentSession->canUserShare($currentUser))
		{
			return 'with-sharing';
		}

		return 'without-edit';
	}

	protected function processSavingTemplate(OnlyOffice\Models\DocumentSession $documentSession): void
	{
		$this->arResult['DOCUMENT_SESSION'] = [
			'ID' => $documentSession->getId(),
			'HASH' => $documentSession->getExternalHash(),
		];
		$this->arResult['OBJECT'] = [
			'ID' => $documentSession->getObjectId(),
			'NAME' => $documentSession->getObject()->getName(),
		];

		$this->includeComponentTemplate('saving');
	}

	protected function getUserIdForOnline(): int
	{
		if ($this->currentUser instanceof OnlyOffice\Models\GuestUser)
		{
			return $this->currentUser->getUniqueId();
		}

		return $this->currentUser->getId();
	}

	private function getDocumentHandlersForEditingFile(): array
	{
		$handlers = [];
		foreach ($this->listCloudHandlersForCreatingFile() as $handler)
		{
			$handlers[] = [
				'code' => $handler::getCode(),
				'name' => $handler::getName(),
			];
		}

		return array_merge($handlers, [
			[
				'code' => LocalDocumentController::getCode(),
				'name' => LocalDocumentController::getName(),
			],
		]);
	}

	/**
	 * @return Disk\Document\DocumentHandler[]
	 */
	private function listCloudHandlersForCreatingFile(): array
	{
		if (!\Bitrix\Disk\Configuration::canCreateFileByCloud())
		{
			return [];
		}

		$list = [];
		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		foreach ($documentHandlersManager->getHandlers() as $handler)
		{
			if ($handler instanceof Disk\Document\Contract\FileCreatable)
			{
				$list[] = $handler;
			}
		}

		return $list;
	}
}