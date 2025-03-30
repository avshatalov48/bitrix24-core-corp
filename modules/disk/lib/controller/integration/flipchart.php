<?php

declare(strict_types=1);

namespace Bitrix\Disk\Controller\Integration;

use Bitrix\Disk;
use Bitrix\Disk\Bitrix24Disk\TmpFile;
use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\Flipchart\BoardApiService;
use Bitrix\Disk\Document\Flipchart\BoardService;
use Bitrix\Disk\Document\Flipchart\Configuration;
use Bitrix\Disk\Document\Flipchart\SessionManager;
use Bitrix\Disk\Document\Flipchart\WebhookEventType;
use Bitrix\Disk\Document\Models\DocumentService;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\Models\DocumentSessionContext;
use Bitrix\Disk\Document\Models\DocumentSessionTable;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Flipchart\Models\FlipchartWebhookLogTable;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Type\JwtHolder;
use Bitrix\Disk\User;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Disk\Controller\Integration\Filter\JwtFilter;
use Bitrix\Disk\Internals\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Http\Method;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Http\Request;
use Bitrix\Main\Web\MimeType;
use Bitrix\Main\Web\Uri;

final class Flipchart extends Controller implements JwtHolder
{

	private const NEW_BOARD_MAX_SIZE = 500;

	private ?object $jwtData;
	public function setJwtData(?object $data): void
	{
		$this->jwtData = $data;
	}

	public function getJwtData(): ?object
	{
		return $this->jwtData;
	}

	public function configureActions(): array
	{
		return [
			'webhook' => [
				'+prefilters' => [
					new JwtFilter(
						Configuration::getJwtSecret(),
						$this
					),
				],
				'-prefilters' => [
					Csrf::class,
					Authentication::class,
				],
			],
			'getDocument' => [
				'+prefilters' => [
					// new Jwt(self::FLIPCHART_JWT_SECRET, $this),
				],
				'-prefilters' => [
					Csrf::class,
					Authentication::class,
				],
			],
			'openDocument' => [
				'+prefilters' => [
					// new Jwt(self::FLIPCHART_JWT_SECRET, $this),
				],
				'-prefilters' => [
					Csrf::class,
					Authentication::class,
				],
			],
			'viewDocument' => [
				'+prefilters' => [
					// new Jwt(self::FLIPCHART_JWT_SECRET, $this),
				],
				'-prefilters' => [
					Csrf::class,
					Authentication::class,
				],
			],
		];
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new Parameter(
				DocumentSession::class,
				function($className, $sessionId): ?DocumentSession
				{
					return (new SessionManager())
						->setExternalHash($sessionId)
						->setUserId((int)$this->getCurrentUser()?->getId())
						->setSessionType(DocumentSession::TYPE_EDIT)
						->findSession();
				}
			),
		];
	}

	public function isSaveByChanceNeeded(DocumentSession $session): bool
	{
		$timeToSave = Configuration::getSaveDeltaTime();
		$chanceToSave = Configuration::getSaveProbabilityCoef();

		$savedSecondAgo = time() - $session->getFile()->getUpdateTime()->getTimestamp();

		return ((random_int(0,1000000) / 1000000) < $chanceToSave)
			|| ($savedSecondAgo > $timeToSave);
	}

	public function webhookAction(): ?HttpResponse
	{
		$type = $this->getJwtData()?->type;
		$sessionId = $this->getJwtData()?->sessionId;
		$documentIdLong = $this->getJwtData()?->documentId;
		$userId = $this->getJwtData()?->user_id ?: null;

		$documentId = BoardService::getDocumentIdFromExternal($documentIdLong);
		$siteId = BoardService::getSiteIdFromExternal($documentIdLong);


		if (!$sessionId || !$documentId || !$type)
		{
			return null;
		}
		$manager = new SessionManager();
		$manager->setExternalHash($sessionId);
		if(!is_null($userId)){
			$manager->setUserId((int)$userId);
		}

		$session = $manager->findSession();

		if (!$session)
		{
			return null;
		}

		$boardService = new BoardService($session);

		/*
		 * - WAS_MODIFIED
		 * - LAST_USER_LEFT_THE_FLIP
		 * - FLIP_DELETED
		 * - FLIP_RENAMED
		 * - USER_ENTRY
		 * - USER_LEFT
		 */

		$editAllowed = $session->getType() === DocumentSession::TYPE_EDIT;

		switch ($type)
		{
			case WebhookEventType::WasModified->value:
				if (
					$editAllowed
					&& $this->isSaveByChanceNeeded($session)
				)
				{
					$boardService->saveDocument();
				}

				break;
				//			case WebhookEventType::UserLeft->value:
				$boardService->closeSession();
			//				if ($editAllowed){
			//					$boardService->saveDocument();
			//				}

			//				break;
			case WebhookEventType::LastUserLeftTheFlip->value:
				if ($editAllowed)
				{
					$boardService->saveDocument();
				}
				$boardService->closeSession();

				break;
		}

		return null;
	}

	public function getDocumentAction(): ?BFile
	{
		$sessionId = $this->request->getQuery('sessionId');
		$data = DocumentSessionTable::getList(
			[
				'select' => [
					'OBJECT_ID'
				],
				'filter' => [
					'=EXTERNAL_HASH' => $sessionId,
					'=SERVICE' => DocumentService::FlipChart->value,
					'=STATUS' => DocumentSession::STATUS_ACTIVE,
				],
			],
		);
		$document = $data->fetch();
		if (!$document)
		{
			$this->addError(new Error('Document Not Found', 404));

			return null;
		}

		$object = File::getById($document['OBJECT_ID'])->getFile();
		if (!$object)
		{
			$this->addError(new Error('File Not Found', 404));

			return null;
		}

		return new BFile($object);
	}

	public function openDocumentAction(int $fileId, CurrentUser $currentUser): ?HttpResponse
	{
		$file = File::getById($fileId);
		if (!$file)
		{
			return $this->getErrorPageResponse();
		}

		$manager = new SessionManager();
		$manager->setFile($file);
		$manager->setUserId((int)$currentUser->getId());
		$manager->setSessionContext(
			new DocumentSessionContext(
				(int)$file->getId(),
				null,
				null,
			),
		);
		$session = $manager->findSession(true);

		if ($session)
		{
			return $this->viewDocumentAction($session, $currentUser, $file);
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		$fileCanUpdate = $file->canUpdate($securityContext);
		$fileCanRead = $fileCanUpdate || $file->canRead($securityContext);

		$attachedCanRead = false;
		$attachedCanUpdate = false;
		$attachedObjectId = null;
		if (!$fileCanRead && !$fileCanUpdate)
		{
			$attachedObjects = $file->getAttachedObjects();
			if ($attachedObjects)
			{
				foreach ($attachedObjects as $attachedObject)
				{
					if (!$attachedCanUpdate && $attachedObject->canUpdate((int)$currentUser->getId()))
					{
						$attachedCanUpdate = true;
						$attachedCanRead = true;
						$attachedObjectId = (int)$attachedObject->getId();
						break;
					}
					if (!$attachedCanRead && $attachedObject->canRead((int)$currentUser->getId()))
					{
						$attachedCanRead = true;
						$attachedObjectId = (int)$attachedObject->getId();
					}
				}
			}

			if (!$attachedCanRead && !$attachedCanUpdate)
			{
				return $this->getErrorPageResponse();
			}
		}

		$sessionType = ($fileCanUpdate || $attachedCanUpdate)
			? DocumentSession::TYPE_EDIT
			: DocumentSession::TYPE_VIEW;

		$manager = new SessionManager();
		$manager->setFile($file);
		$manager->setUserId((int)$currentUser->getId());
		$manager->setSessionType($sessionType);
		$manager->setSessionContext(
			new DocumentSessionContext(
				(int)$file->getId(),
				$attachedObjectId,
				null,
			),
		);

		$session = $manager->findOrCreateSession();

		if (!$session)
		{
			return $this->getErrorPageResponse();
		}

		return $this->viewDocumentAction($session, $currentUser, $file);
	}

	public function createDocumentAction(CurrentUser $currentUser): ?array
	{
		$userStorage = Driver::getInstance()->getStorageByUserId((int)$currentUser->getId());
		$folder = $userStorage->getFolderForCreatedFiles();

		$res = BoardService::createNewDocument(User::loadById($currentUser->getId()), $folder);

		if (!$res->isSuccess())
		{
			$this->addError($res->getError());

			return null;
		}

		$openUrl = Driver::getInstance()->getUrlManager()->getUrlForViewBoard($res->getData()['file']->getId());
		$res->setData(
			[
				'viewUrl' => $openUrl,
			]
			+ $res->getData()
		);

		return $res->getData();
	}

	public function viewDocumentAction(DocumentSession $session, CurrentUser $currentUser, ?File $originalFile = null): HttpResponse
	{
		$userRow = UserTable::getById((int)$currentUser->getId())->fetch();
		/** @var User $userModel */
		$userModel = User::buildFromRow($userRow);

		$documentUrl = $this->getActionUri(
			'getDocument',
			[
				'sessionId' => $session->getExternalHash(),
				'userId' => $session->getUserId(),
			],
			true,
		);

		if (
			($session->isEdit() && !$session->canUserEdit($currentUser))
			|| ($session->isView() && !$session->canUserRead($currentUser))
		)
		{
			return $this->getErrorPageResponse();
		}

		$file = $session->getFile();
		$showTemplatesModal = false;
		if (
			$file->getCreatedBy() == $currentUser->getId()
			&& $file->getRealObjectId() == $file->getId()
			&& $file->getSize() < self::NEW_BOARD_MAX_SIZE
		)
		{
			$diff = time() - $file->getCreateTime()->getTimestamp();
			if ($diff < 30)
			{
				$showTemplatesModal = true;
			}
		}

		$urlManager = UrlManager::getInstance();
		$avatarUrl = $urlManager->getHostUrl() . $userModel->getAvatarSrc();

		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.flipchart.editor',
				'POPUP_COMPONENT_PARAMS' => [
					'DOCUMENT_SESSION' => $session,
					'DOCUMENT_URL' => $documentUrl,
					'STORAGE_MODULE_ID' => 'disk',
					'STORAGE_ENTITY_TYPE' => 'Bitrix\Disk\ProxyType\User',
					'STORAGE_ENTITY_ID' => $currentUser->getId(),
					'USER_ID' => $currentUser->getId(),
					'USERNAME' => $currentUser->getFormattedName(),
					'AVATAR_URL' => $avatarUrl,
					'CAN_EDIT_BOARD' => true,
					'SHOW_TEMPLATES_MODAL' => $showTemplatesModal,
					'ORIGINAL_FILE' => $originalFile,
				],
				'PLAIN_VIEW' => true,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => false,
				'USE_PADDING' => false,
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}

	private function getErrorPageResponse(): HttpResponse
	{
		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.error.page',
				'POPUP_COMPONENT_PARAMS' => [
				],
				'PLAIN_VIEW' => false,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => false,
				'USE_PADDING' => true,
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}
}