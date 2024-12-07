<?php

namespace Bitrix\Disk\Controller\Integration;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Controller\OnlyOffice;
use Bitrix\Disk\Document;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\FileLink;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\RightsManager;
use Bitrix\Disk\Sharing;
use Bitrix\Im\Call\Call;
use Bitrix\Im\Disk\Sender;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Router;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Web\Uri;

final class MessengerCall extends Engine\Controller
{
	public function configureActions()
	{
		return [
			'createResumeByTemplate' => [
				'+prefilters' => [
					new Engine\ActionFilter\B24Feature("disk_im_call_resume"),
				]
			],
			'selectTemplateOrOpenExisting' => [
				'+prefilters' => [
					new Engine\ActionFilter\B24Feature("disk_im_call_resume"),
				]
			],
			'createDocumentInCall' => ['-prefilters' => [Csrf::class]],
			'selectTemplate' => ['-prefilters' => [Csrf::class]],
		];
	}

	public function getAutoWiredParameters()
	{
		$autoWiredParameters = parent::getAutoWiredParameters();
		$autoWiredParameters[] = new ExactParameter(
			Call::class,
			'call',
			function ($className, int $callId) {
				$call = Call::loadWithId($callId);

				if (!$call)
				{
					return null;
				}

				$associatedEntity = $call->getAssociatedEntity();
				if (!$associatedEntity->checkAccess($this->getCurrentUser()->getId()))
				{
					return null;
				}

				return $call;
			}
		);

		return $autoWiredParameters;
	}

	protected function processBeforeAction(Action $action)
	{
		if (!Document\OnlyOffice\OnlyOfficeHandler::isEnabled())
		{
			$this->addError(new Error('OnlyOffice handler is not configured.'));

			return false;
		}

		$bitrix24Scenario = new Document\OnlyOffice\Bitrix24Scenario();
		if (!$bitrix24Scenario->canUseView())
		{
			$this->addError(new Error('Feature is not available.'));

			return false;
		}

		if (!Loader::includeModule('im'))
		{
			$this->addError(new Error("Required module 'im' was not found"));

			return false;
		}

		return parent::processBeforeAction($action);
	}

	public function listResumesInChatByCallAction(Call $call): array
	{
		return $this->listResumesInChatAction($call->getChatId());
	}

	public function listResumesInChatAction(int $chatId): array
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		[$className, $moduleId] = $userFieldManager->getConnectorDataByEntityType('im_call');

		$filter = [
			'=OBJECT.DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			'=OBJECT.CODE' => Document\OnlyOffice\Templates\CreateDocumentByCallTemplateScenario::CODE_RESUME,
			'=ENTITY_TYPE' => $className,
			'=MODULE_ID' => $moduleId,
			'=CALL.CHAT_ID' => $chatId,
		];
		$attachedObjects = AttachedObject::getModelList(
			[
				'filter' => $filter,
				'runtime' => [
					new Reference('CALL',
						\Bitrix\Im\Model\CallTable::class,
						Join::on('this.ENTITY_ID', 'ref.ID'),
					),
				],
				'with' => [
					'OBJECT',
				],
				'limit' => 5,
				'order' => [
					'ID' => 'DESC',
				],
			]
		);

		$resumes = [];
		$culture = Context::getCurrent()->getCulture();
		$currentUserId = $this->getCurrentUser()->getId();
		foreach ($attachedObjects as $attachedObject)
		{
			if ($attachedObject->canRead($currentUserId))
			{
				$resumes[] = [
					'id' => $attachedObject->getId(),
					'object' => [
						'id' => $attachedObject->getObjectId(),
						'name' => $attachedObject->getName(),
						'createDate' => $attachedObject->getCreateTime()->toUserTime()->format($culture->getShortDateFormat()),
					],
					'links' => [
						'edit' => $this->getUrlToEditAttachedObject($attachedObject),
						'view' => $this->getUrlToViewAttachedObject($attachedObject),
					],
				];
			}
		}

		return [
			'resumes' => $resumes,
		];
	}

	public function selectTemplateOrOpenExistingAction(Call $call): array
	{
		$attachedObject = $this->findResumeInCall($call);
		if ($attachedObject)
		{
			return [
				'document' => [
					'urlToEdit' => $this->getUrlToEditAttachedObject($attachedObject),
				]
			];
		}
		else
		{
			/** @see \DiskFileEditorTemplatesController::getSliderContentAction */
			$url = UrlManager::getInstance()->create('getSliderContent', [
				'c' => 'bitrix:disk.file.editor-templates',
				'mode' => Router::COMPONENT_MODE_AJAX,
				'callId' => $call->getId(),
			]);

			return [
				'template' => [
					'urlToSelect' => $url,
				]
			];
		}
	}

	protected function getUrlToEditAttachedObject(AttachedObject $attachedObject): Uri
	{
		/** @see OnlyOffice::loadDocumentEditorAction() */
		return UrlManager::getInstance()->createByController(new OnlyOffice(), 'loadDocumentEditor', [
			'attachedObjectId' => $attachedObject->getId(),
			'editorMode' => Document\OnlyOffice\Editor\ConfigBuilder::VISUAL_MODE_COMPACT,
		]);
	}

	protected function getUrlToViewAttachedObject(AttachedObject $attachedObject): Uri
	{
		/** @see OnlyOffice::loadDocumentViewerAction() */
		return UrlManager::getInstance()->createByController(new OnlyOffice(), 'loadDocumentViewer', [
			'attachedObjectId' => $attachedObject->getId(),
			'editorMode' => Document\OnlyOffice\Editor\ConfigBuilder::VISUAL_MODE_COMPACT,
		]);
	}

	protected function forwardToEditAttachedObject(AttachedObject $attachedObject)
	{
		/** @see OnlyOffice::loadDocumentEditorAction() */
		return $this->forward(OnlyOffice::class, 'loadDocumentEditor', [
			'attachedObjectId' => $attachedObject->getId(),
			'editorMode' => Document\OnlyOffice\Editor\ConfigBuilder::VISUAL_MODE_COMPACT,
		]);
	}

	protected function findResumeInCall(Call $call): ?AttachedObject
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		[$className, $moduleId] = $userFieldManager->getConnectorDataByEntityType('im_call');

		$attachedObject = AttachedObject::load(
			[
				'=MODULE_ID' => $moduleId,
				'=ENTITY_TYPE' => $className,
				'=ENTITY_ID' => $call->getId(),
				'=OBJECT.CODE' => Document\OnlyOffice\Templates\CreateDocumentByCallTemplateScenario::CODE_RESUME,
			],
			['OBJECT']
		);

		if (!$attachedObject || !$attachedObject->getFile())
		{
			return null;
		}

		return $attachedObject;
	}

	public function createResumeByTemplateAction(Call $call, int $templateId)
	{
		$attachedObject = $this->findResumeInCall($call);
		if ($attachedObject)
		{
			return $this->forwardToEditAttachedObject($attachedObject);
		}

		$documentByCallTemplateScenario = new Document\OnlyOffice\Templates\CreateDocumentByCallTemplateScenario(
			$this->getCurrentUser()->getId(),
			$call,
			Context::getCurrent()->getLanguage()
		);

		$result = $documentByCallTemplateScenario->create($templateId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		/** @var File $newFile */
		$newFile = $result->getData()['file'];

		$attachedObject = $this->attachFileToCall($call, $newFile);
		if (!$attachedObject)
		{
			return null;
		}

		return [
			'document' => [
				'urlToEdit' => $this->getUrlToEditAttachedObject($attachedObject),
			],
		];
	}

	public function createDocumentInCallAction(Call $call, string $typeFile)
	{
		//create document and attach to call
		$createBlankDocumentScenario = new Document\OnlyOffice\CreateBlankDocumentScenario(
			$this->getCurrentUser()->getId(),
			Context::getCurrent()->getLanguage()
		);

		$result = $createBlankDocumentScenario->createBlankInDefaultFolder($typeFile);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		/** @var File $newFile */
		$newFile = $result->getData()['file'];

		$attachedObject = $this->attachFileToCall($call, $newFile);
		if (!$attachedObject)
		{
			return null;
		}

		return $this->forwardToEditAttachedObject($attachedObject);
	}

	protected function attachFileToCall(Call $call, File $newFile): ?AttachedObject
	{
		$userId = $this->getCurrentUser()->getId();
		$chatId = $call->getAssociatedEntity()->getChatId();

		$text = Loc::getMessage("DISK_INTEGRATION_IM_CALL_CALL_DOCUMENT_CREATED");
		if ($newFile->getCode() === Document\OnlyOffice\Templates\CreateDocumentByCallTemplateScenario::CODE_RESUME)
		{
			$text = Loc::getMessage("DISK_INTEGRATION_IM_CALL_CALL_RESUME_CREATED");
		}

		$result = Sender::sendFileToChat(
			$newFile,
			$chatId,
			$text,
			['CALL_ID' => $call->getId()],
			$userId
		);
		if (!$result->isSuccess())
		{
			return null;
		}

		/** @var FileLink $fileInChat */
		$fileInChat = $result->getData()['IM_FILE'];
		$errorCollection = new ErrorCollection();
		$sharing = Sharing::add(
			[
				'FROM_ENTITY' => Sharing::CODE_USER . $userId,
				'REAL_OBJECT' => $newFile,
				'CREATED_BY' => $userId,
				'CAN_FORWARD' => false,
				'LINK_OBJECT_ID' => $fileInChat->getId(),
				'LINK_STORAGE_ID' => $fileInChat->getStorageId(),
				'TO_ENTITY' => Sharing::CODE_CHAT . $chatId,
				'TASK_NAME' => RightsManager::TASK_EDIT,
			],
			$errorCollection
		);
		// (new ChatAuthProvider())->updateChatCodesByRelations($chatId);

		if ($newFile->getCode() === Document\OnlyOffice\Templates\CreateDocumentByCallTemplateScenario::CODE_RESUME)
		{
			$fileInChat->changeCode($newFile->getCode());
		}

		return $newFile->attachToEntity(
			[
				'id' => $call->getId(),
				'type' => 'im_call',
			],
			[
				'allowEdit' => true,
				'isEditable' => true,
				'createdBy' => $newFile->getCreatedBy(),
			]
		);
	}
}
