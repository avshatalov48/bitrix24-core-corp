<?php

use Bitrix\Disk;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Document\OnlyOffice\Models\DocumentSessionTable;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Internals\Engine\Contract\SidePanelWrappable;
use Bitrix\Disk\User;
use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loader::requireModule('disk');

class CDiskFileEditorOnlyOfficeComponent extends BaseComponent implements Controllerable
{
	public const ERROR_CODE_EXCEEDED_LIMIT = 'exceeded_limit';
	public const ERROR_CODE_COULD_NOT_LOCK = 'could_not_lock';

	protected User $currentUser;
	protected OnlyOffice\Configuration $onlyOfficeConfiguration;
	protected OnlyOffice\RestrictionManager $restrictionManager;

	protected function processBeforeAction($actionName)
	{
		$this->onlyOfficeConfiguration = new OnlyOffice\Configuration();
		$this->restrictionManager = new OnlyOffice\RestrictionManager();

		if (!OnlyOffice\OnlyOfficeHandler::isEnabled())
		{
			return false;
		}

		return parent::processBeforeAction($actionName);
	}

	public function prepareParams()
	{
		parent::prepareParams();

		if (!isset($this->arParams['EDITOR_MODE']))
		{
			$this->arParams['EDITOR_MODE'] = OnlyOffice\Editor\ConfigBuilder::VISUAL_MODE_USUAL;
		}

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
		if (isset($this->arParams['TEMPLATE']) && $this->arParams['TEMPLATE'] === 'not-found')
		{
			$this->includeComponentTemplate('not-found');

			return;
		}

		if (!isset($this->arParams['SHOW_BUTTON_OPEN_NEW_WINDOW']))
		{
			$this->arParams['SHOW_BUTTON_OPEN_NEW_WINDOW'] = true;
		}
		/** @var OnlyOffice\Models\DocumentSession $documentSession */
		$documentSession = $this->arParams['DOCUMENT_SESSION'];

		$bitrix24Scenario = new OnlyOffice\Bitrix24Scenario();
		if ($documentSession->isEdit())
		{
			if (!$bitrix24Scenario->canUseEdit())
			{
				$this->arResult['INFO_HELPER_CODE'] = 'limit_office_small_documents';
			}
			if (!$bitrix24Scenario->canUseView())
			{
				$this->arResult['INFO_HELPER_CODE'] = 'limit_office_no_document';
			}
		}
		elseif (!$bitrix24Scenario->canUseView())
		{
			$this->arResult['INFO_HELPER_CODE'] = 'limit_office_no_document';
		}

		if (!empty($this->arResult['INFO_HELPER_CODE']))
		{
			$this->includeComponentTemplate('feature-restriction');

			return;
		}

		$this->arResult['EXTERNAL_LINK_MODE'] = (bool)($this->arParams['EXTERNAL_LINK_MODE'] ?? false);
		$documentInfo = $documentSession->getInfo();
		if (!$documentInfo)
		{
			$documentInfo = $documentSession->createInfo();
		}

		if ($documentSession->isEdit() && $documentInfo->isAbandoned())
		{
			DocumentSessionTable::deactivateByHash($documentSession->getExternalHash());
			$documentSession = $documentSession->cloneWithNewHash($documentSession->getUserId());
			$documentInfo = $documentSession->getInfo();
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

		$allowRename = false;
		$allowEdit = $this->isEditAllowed($documentSession);
		if ($documentSession->isEdit() && !$documentSession->isVersion())
		{
			$allowRename = $documentSession->canUserRename(CurrentUser::get());
		}

		$configBuilder = new OnlyOffice\Editor\ConfigBuilder($documentSession);
		$configBuilder
			->allowEdit($allowEdit)
			->allowRename($allowRename)
			->setUser($this->currentUser)
			/** @see Disk\Controller\OnlyOffice::handleOnlyOfficeAction() */
			->setCallbackUrl($onlyOfficeController->getActionUri('handleOnlyOffice', $sessionGetParams, true))
			/** @see Disk\Controller\OnlyOffice::downloadAction() */
			->setDocumentUrl($onlyOfficeController->getActionUri('download', $sessionGetParams, true))
		;

		if ($this->arParams['EDITOR_MODE'] === OnlyOffice\Editor\ConfigBuilder::VISUAL_MODE_COMPACT)
		{
			$configBuilder
				->hideRightMenu()
				->hideRulers()
				->setCompactHeader()
				->setCompactToolbar()
			;
		}

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
			'SIZE' => $documentSession->getObject()->getSize(),
		];
		$this->arResult['ATTACHED_OBJECT'] = [
			'ID' => $documentSession->getContext()->getAttachedObjectId(),
		];
		/** @see \Bitrix\Disk\Controller\DocumentService::viewDocumentAction */
		$this->arResult['LINK_OPEN_NEW_WINDOW'] = (new Disk\Controller\DocumentService())->getActionUri(
			'viewDocument',
			['documentSessionId' => $documentSession->getId()]
		);

		$this->arResult['LINK_TO_EDIT'] = $this->getLinkToEdit($documentSession);
		$this->arResult['LINK_TO_DOWNLOAD'] = $this->getLinkToDownload($documentSession);

		$infoToken = Disk\Document\Online\UserInfoToken::generateTimeLimitedToken(
			$this->getUserIdForOnline(),
			$documentSession->getObject()->getRealObjectId()
		);

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

		$featureBlocker = Bitrix24Manager::filterJsAction('disk_manual_external_link', '');
		$this->arResult['SHOULD_BLOCK_EXTERNAL_LINK_FEATURE'] = (bool)$featureBlocker;
		$this->arResult['BLOCKER_EXTERNAL_LINK_FEATURE'] = $featureBlocker;

		if ($allowEdit && $configBuilder->isEditMode() && $this->shouldUseRestriction())
		{
			if ($this->lockForRestriction())
			{
				if (
					!$this->isAllowedEditByRestriction($documentSession->getExternalHash(), $this->currentUser->getId())
				)
				{
					$this->errorCollection[] = new Disk\Internals\Error\Error('Exceeded limit.', self::ERROR_CODE_EXCEEDED_LIMIT, [
						'limit' => $this->restrictionManager->getLimit(),
					]);

					AddEventToStatFile(
						'disk',
						'disk_oo_limit_edit',
						$documentSession->getExternalHash(),
						ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getServer(),
						'',
						$this->currentUser->getId()
					);
				}
				else
				{
					$this->registerRestrictionUsage($documentSession->getExternalHash(), $this->currentUser->getId());
				}
			}

			if ($this->getErrorByCode(self::ERROR_CODE_EXCEEDED_LIMIT) || $this->getErrorByCode(self::ERROR_CODE_COULD_NOT_LOCK))
			{
				$this->processRestrictionError();

				return;
			}
		}

		$editorJsonConfigResult = $this->getEditorJsonConfig($configBuilder);
		if (!$editorJsonConfigResult->isSuccess())
		{
			$this->processCloudError($editorJsonConfigResult);

			return;
		}

		$editorConfigData = $editorJsonConfigResult->getData();
		$this->arResult['SERVER'] = $this->getServerByEditorConfig($editorConfigData);

		if (Application::getInstance()->isUtfMode())
		{
			$this->arResult['EDITOR_JSON'] = Json::encode($editorConfigData);
		}
		else
		{
			$this->arResult['EDITOR_JSON'] = \CUtil::PhpToJSObject($editorConfigData);
		}

		$this->arResult['SHARING_CONTROL_TYPE'] = $this->getSharingControlType($documentSession);
		$this->arResult['PULL_CONFIG'] = null;
		$publicPullConfigurator = new Disk\Document\Online\PublicPullConfigurator();
		if ($publicPullConfigurator->getErrors())
		{
			$this->errorCollection->add($publicPullConfigurator->getErrors());
		}
		else
		{
			$realObjectId = $documentSession->getObject()->getRealObjectId();
			$this->arResult['PULL_CONFIG'] = $publicPullConfigurator->getConfig($realObjectId);
			$this->arResult['PUBLIC_CHANNEL'] = $publicPullConfigurator->getChannel($realObjectId)->getSignedPublicId();
		}

		if (OnlyOffice\OnlyOfficeHandler::shouldRestrictedBySize($documentSession->getObject()->getSize()))
		{
			$this->includeComponentTemplate('large-file');
		}
		else
		{
			$this->includeComponentTemplate();
		}

		if ($this->shouldUseRestriction())
		{
			$this->unlockForRestriction();
		}
	}

	private function shouldUseRestriction(): bool
	{
		return $this->restrictionManager->shouldUseRestriction();
	}

	private function isAllowedEditByRestriction(string $documentKey, int $userId): bool
	{
		return $this->restrictionManager->isAllowedEdit($documentKey, $userId);
	}

	private function registerRestrictionUsage(string $documentKey, int $userId): void
	{
		$this->restrictionManager->registerUsage($documentKey, $userId);
	}

	private function lockForRestriction(): bool
	{
		if (!$this->restrictionManager->lock())
		{
			$this->errorCollection[] = new Disk\Internals\Error\Error('Could not get exclusive lock', self::ERROR_CODE_COULD_NOT_LOCK);

			return false;
		}

		return true;
	}

	private function unlockForRestriction(): void
	{
		$this->restrictionManager->unlock();
	}

	protected function getLinkToEdit(OnlyOffice\Models\DocumentSession $documentSession)
	{
		if (isset($this->arParams['LINK_TO_EDIT']))
		{
			return $this->arParams['LINK_TO_EDIT'];
		}

		return (new Disk\Controller\DocumentService())->getActionUri(
			'goToEdit',
			[
				'documentSessionId' => $documentSession->getId(),
				'documentSessionHash' => $documentSession->getExternalHash(),
				'serviceCode' => OnlyOffice\OnlyOfficeHandler::getCode(),
			]
		);
	}

	protected function getLinkToDownload(OnlyOffice\Models\DocumentSession $documentSession)
	{
		if (isset($this->arParams['LINK_TO_DOWNLOAD']))
		{
			return $this->arParams['LINK_TO_DOWNLOAD'];
		}

		/** @see \Bitrix\Disk\Controller\DocumentService::downloadDocumentAction */
		return (new Disk\Controller\DocumentService())->getActionUri(
			'downloadDocument',
			[
				'documentSessionId' => $documentSession->getId(),
				'documentSessionHash' => $documentSession->getExternalHash(),
			]
		);
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
			'SIZE' => $documentSession->getObject()->getSize(),
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

	protected function getEditorJsonConfig(OnlyOffice\Editor\ConfigBuilder $configBuilder): Result
	{
		$cloudRegistrationData = (new OnlyOffice\Configuration())->getCloudRegistrationData();
		if ($cloudRegistrationData)
		{
			$configSigner = new OnlyOffice\Cloud\SingDocumentConfig(
				$cloudRegistrationData['serverHost']
			);

			return $configSigner->sign($configBuilder->build());
		}

		return (new Result())->setData($configBuilder->build());
	}

	protected function processRestrictionError(): void
	{
		$cloudErrorResult = [];

		if ($this->getErrorByCode(self::ERROR_CODE_EXCEEDED_LIMIT))
		{
			$cloudErrorResult['LIMIT'] = [
				'RESTRICTION' => true,
				'LIMIT_VALUE' => $this->getErrorByCode(self::ERROR_CODE_EXCEEDED_LIMIT)->getCustomData()['limit'],
			];
		}
		$cloudErrorResult['ERRORS'] = $this->getErrors();
		$this->arResult['CLOUD_ERROR'] = $cloudErrorResult;

		$this->includeComponentTemplate('cloud-error');
	}

	protected function processCloudError(Result $result): void
	{
		$cloudErrorResult = [];

		$errorCollection = $result->getErrorCollection();
		/** @see \Bitrix\DocumentProxy\Controller\SignDocumentConfiguration::ERROR_CODE_EXCEEDED_LIMIT */
		if ($errorCollection->getErrorByCode('exceeded_limit'))
		{
			$cloudErrorResult['LIMIT'] = [
				'RESTRICTION' => true,
				'LIMIT_VALUE' => $errorCollection->getErrorByCode('exceeded_limit')->getCustomData()['limit'],
			];
		}
		/** @see \Bitrix\DocumentProxy\Engine\Filter\DemoRestriction::ERROR_DEMO_RESTRICTION */
		if ($errorCollection->getErrorByCode('demo_restriction'))
		{
			$cloudErrorResult['DEMO'] = [
				'END' => true,
			];
		}
		$cloudErrorResult['ERRORS'] = $errorCollection->toArray();


		$this->arResult['CLOUD_ERROR'] = $cloudErrorResult;

		$this->includeComponentTemplate('cloud-error');
	}

	private function getServerByEditorConfig(array $editorConfigData): string
	{
		if (!empty($editorConfigData['_server']))
		{
			return $editorConfigData['_server'];
		}

		return rtrim(ServiceLocator::getInstance()->get('disk.onlyofficeConfiguration')->getServer(), '/');
	}

	protected function isEditAllowed(OnlyOffice\Models\DocumentSession $documentSession): bool
	{
		$allowEdit = false;

		if (
			!$documentSession->isVersion()
			&& OnlyOffice\OnlyOfficeHandler::isEditable($documentSession->getObject()->getExtension())
		)
		{
			$allowEdit = $documentSession->canTransformUserToEdit(CurrentUser::get());
			if ($allowEdit && $this->arResult['EXTERNAL_LINK_MODE'])
			{
				$externalLink = $documentSession->getContext()->getExternalLink();
				if ($externalLink && !$externalLink->allowEdit())
				{
					$allowEdit = false;
				}
			}
		}

		return $allowEdit;
	}
}