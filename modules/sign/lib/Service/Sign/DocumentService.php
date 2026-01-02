<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Document;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Internal\DocumentTable;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Document\BindingCollection;
use Bitrix\Sign\Item\Document\Template\TemplateFolderRelation;
use Bitrix\Sign\Main\User;
use Bitrix\Sign\Operation\CheckDocumentAccess;
use Bitrix\Sign\Operation\ConfigureFillAndStart;
use Bitrix\Sign\Operation\Document\Validation;
use Bitrix\Sign\Operation\Document\Blank\Delete;
use Bitrix\Sign\Operation\Kanban\B2e\SendDeleteEntityPullEvent;
use Bitrix\Sign\Operation\Result\ConfigureResult;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\Document\TemplateFolderRelationRepository;
use Bitrix\Sign\Repository\BlockRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Service\Sign\Template\CreateTemplateFolderRelationResult;
use Bitrix\Sign\Result\CreateDocumentResult;
use Bitrix\Sign\Result\Service\Sign\Document\CreateTemplateResult;
use Bitrix\Sign\Service;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\Document\EntityType;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\ProviderCode;
use Bitrix\Sign\Type\Template;
use Bitrix\Sign\Type\Template\Visibility;

class DocumentService
{
	private const DOCUMENT_NAME_LENGTH_LIMIT = 100;

	private DocumentRepository $documentRepository;
	private BlankRepository $blankRepository;
	private BlankService $blankService;
	private Service\Integration\Crm\EventHandlerService $eventHandlerService;
	private Document\Entity\Factory $documentEntityFactory;
	private readonly Service\Sign\Document\ProviderCodeService $providerCodeService;
	private readonly TemplateRepository $documentTemplateRepository;
	private readonly Service\Sign\Document\TemplateService $documentTemplateService;
	private readonly Service\Sign\Document\SignUntilService $signUntilService;
	private readonly MemberRepository $memberRepository;
	private readonly TemplateFolderRelationRepository $templateFolderRelationRepository;
	private readonly BlockRepository $blockRepository;
	private readonly Service\Integration\HumanResources\StructureNodeService $structureNodeService;

	public function __construct(
		?DocumentRepository $documentRepository = null,
		?BlankService $blankService = null,
		?BlankRepository $blankRepository = null,
		?Service\Integration\Crm\EventHandlerService $eventHandlerService = null,
		private bool $checkPermission = true,
		?Service\Sign\Document\ProviderCodeService $providerCodeService = null,
		?TemplateRepository $documentTemplateRepository = null,
		?Service\Sign\Document\TemplateService $documentTemplateService = null,
		?Service\Sign\Document\SignUntilService $signUntilService = null,
		?MemberRepository $memberRepository = null,
		?TemplateFolderRelationRepository $templateFolderRelationRepository = null,
	)
	{
		$container = Container::instance();

		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->blankService = $blankService ?? $container->getSignBlankService();
		$this->blankRepository = $blankRepository ?? $container->getBlankRepository();
		$this->eventHandlerService = $eventHandlerService ?? $container->getEventHandlerService();
		$this->documentEntityFactory = new Document\Entity\Factory();
		$this->providerCodeService = $providerCodeService ?? $container->getProviderCodeService();
		$this->documentTemplateRepository = $documentTemplateRepository ?? $container->getDocumentTemplateRepository();
		$this->documentTemplateService = $documentTemplateService ?? $container->getDocumentTemplateService();
		$this->signUntilService = $signUntilService ?? $container->getSignUntilService();
		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
		$this->templateFolderRelationRepository = $templateFolderRelationRepository ?? $container->getTemplateFolderRelationRepository();
		$this->blockRepository = $container->getBlockRepository();
		$this->structureNodeService = Service\Container::instance()->getHumanResourcesStructureNodeService();
	}

	/**
	 * @param bool $checkPermission
	 *
	 * @return \Bitrix\Sign\Service\Sign\DocumentService
	 */
	public function setCheckPermission(bool $checkPermission): static
	{
		$this->checkPermission = $checkPermission;

		return $this;
	}

	/**
	 * @param int $blankId
	 * @param string $title
	 *
	 * @return \Bitrix\Main\Result
	 */
	public function register(
		int $blankId,
		int $createdById,
		string $title = '',
		?int $entityId = null,
		?string $entityType = null,
		bool $asTemplate = false,
		InitiatedByType $initiatedByType = InitiatedByType::COMPANY,
		int $chatId = 0,
		?int $templateId = null,
		?BindingCollection $bindings = null,
	): Main\Result
	{
		$result = new Main\Result();
		if($createdById < 1)
		{
			return $result->addError(new Main\Error(Loc::getMessage('SIGN_SERVICE_DOCUMENT_USER_NOT_FOUND')));
		}

		try
		{
			$blank = $this->blankRepository->getById($blankId);
		}
		catch (Main\ObjectPropertyException|Main\ArgumentException|Main\SystemException $e)
		{
			$blank = null;
		}

		if (!$blank)
		{
			return $result->addError(new Main\Error(Loc::getMessage('SIGN_SERVICE_DOCUMENT_BLANK_NOT_FOUND')));
		}

		if (
			$blank->scenario === Type\BlankScenario::B2B && $entityType === Type\Document\EntityType::SMART_B2E
			|| $blank->scenario === Type\BlankScenario::B2E && $entityType === Type\Document\EntityType::SMART
		)
		{
			return (new Main\Result())->addError(new Main\Error('Wrong blank scenario for current document'));
		}

		$documentItem = new Item\Document(entityType: $entityType, entityId: $entityId, templateId: $templateId);
		$documentItem->initiatedByType = $initiatedByType;

		$documentItem->dateSignUntil = $blank->scenario === Type\BlankScenario::B2B
			? null
			: $this->signUntilService->calcDefaultSignUntilDate(new DateTime())
		;

		if ($blank->scenario === Type\BlankScenario::B2B && $chatId)
		{
			$chatService = Service\Container::instance()->getImService();
			$chat = $chatService->getCollabById($chatId);

			if ($chat && $chatService->isUserHaveAccessToChat($chat, $createdById))
			{
				$documentItem->chatId = $chatId;
			}
		}

		$template = null;
		if ($result->isSuccess() && $asTemplate)
		{
			$createTemplateResult = $this->makeTemplateForDocument(
				$documentItem,
				$title,
				Main\Engine\CurrentUser::get()->getId(),
			);

			if (!$createTemplateResult instanceof CreateTemplateResult)
			{
				return $createTemplateResult;
			}

			$createTemplateFolderRelationResult = $this->makeTemplateFolderRelation($createTemplateResult->template);
			if (!$createTemplateFolderRelationResult instanceof CreateTemplateFolderRelationResult)
			{
				return $createTemplateFolderRelationResult;
			}

			$template = $createTemplateResult->template;
		}

		$result = $this->insertToDB($title, $blank, $documentItem, $createdById, $bindings);

		if (!$result->isSuccess())
		{
			return $result;
		}

		$apiDocument = Service\Container::instance()
			->getApiDocumentService();

		$documentRegisterRequest = new Item\Api\Document\RegisterRequest(
			lang: $documentItem->langId,
			scenario: $documentItem->scenario,
			title: $documentItem->title,
		);
		$documentRegisterResponse = $apiDocument->register($documentRegisterRequest);

		if (!$documentRegisterResponse->isSuccess())
		{
			return $result
				->addErrors($documentRegisterResponse->getErrors())
				->setData(['documentId' => $documentItem->id])
			;
		}

		$documentItem->uid = $documentRegisterResponse->uid;

		$result = $this->documentRepository->update($documentItem);

		return $result->setData([...$result->getData(),
			'template' => $template,
			'documentId' => $documentItem->id,
		]);
	}

	/**
	 * @param string $uid
	 * @param string $title
	 *
	 * @return \Bitrix\Main\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function modifyTitle(string $uid, string $title): Main\Result
	{
		$result = new Main\Result;
		if ($title === '')
		{
			return $result->addError(new Main\Error('Title is empty'));
		}

		$document = $this->documentRepository->getByUid($uid);
		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}

		$document->title = $title;
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Main\Error('Error when trying to save document'));
		}

		if (
			Type\DocumentScenario::isB2EScenario($document->scenario)
			&& $this->documentRepository->getCountByBlankId($document->blankId) === 1
		)
		{
			$this->blankService->changeBlankTitleByDocument($document, $document->title);
			$result->setData(['blankTitle' => $document->title]);
		}
		if (
			Type\DocumentScenario::isB2EScenario($document->scenario)
			&& $document->isTemplated()
		)
		{
			$updateResult = $this->documentTemplateService->updateTitle($document->templateId, $title);
			if (!$updateResult->isSuccess())
			{
				return $updateResult;
			}
		}

		$smartDocument = $this->documentEntityFactory->getByDocument($document);
		$smartDocument?->setTitle($this->composeSmartDocumentTitle($document, $smartDocument));

		return $result;
	}

	private function composeSmartDocumentTitle(
		Item\Document $item,
		?\Bitrix\Sign\Document\Entity\Dummy $smartDocument,
	): string
	{
		if (!Type\DocumentScenario::isB2EScenario($item->scenario))
		{
			return (string)$item->title;
		}

		$number =
			Application::getInstance()->getLicense()->getRegion() !== 'ru'
			&& (string)$smartDocument?->getNumber() !== ''
				? $smartDocument->getNumber()
				: $item->externalId
		;

		return match (Application::getInstance()->getLicense()->getRegion())
		{
			'ru' => trim($number ? Loc::getMessage('SIGN_SERVICE_DOCUMENT_TITLE_FORMAT', [
				'#TITLE#' => $item->title,
				'#NUM#' => $number,
			]) : $item->title) ,
			default => trim($item->title),
		};
	}

	public function getComposedTitleByDocument(Item\Document $item): string
	{
		if (!Type\DocumentScenario::isB2EScenario($item->scenario) || $item->externalId === null)
		{
			return (string)$item->title;
		}

		return match (Application::getInstance()->getLicense()->getRegion())
		{
			'ru' => trim(Loc::getMessage('SIGN_SERVICE_DOCUMENT_TITLE_FORMAT', [
				'#TITLE#' => $item->title,
				'#NUM#' => $item->externalId,
			])),
			default => (string)$item->title,
		};
	}

	public function getTitleWithAutoNumber(Item\Document $item): string
	{
		if (!Type\DocumentScenario::isB2EScenario($item->scenario))
		{
			return (string)$item->title;
		}

		$number = (string)$item->externalId;
		if (Application::getInstance()->getLicense()->getRegion() !== 'ru')
		{
			$smartDocument = $this->documentEntityFactory->getByDocument($item);
			$smartDocumentNumber = (string)$smartDocument?->getNumber();
			if ($smartDocumentNumber !== '')
			{
				$number = "#$smartDocumentNumber";
			}
		}

		return trim("$item->title $number");
	}

	/**
	 * @param string $uid
	 * @param string $title
	 *
	 * @return \Bitrix\Main\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function modifyLangId(string $uid, string $langId): Main\Result
	{
		$result = new Main\Result;
		if ($langId === '')
		{
			return $result->addError(new Main\Error('Lang id is empty'));
		}

		$document = $this->documentRepository->getByUid($uid);
		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}

		$document->langId = $langId;
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Main\Error('Error when trying to save document'));
		}

		return $result;
	}

	/**
	 * @param string $uid
	 * @param string $initiator
	 *
	 * @return \Bitrix\Main\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function modifyInitiator(string $uid, string $initiator): Main\Result
	{
		$result = new Main\Result;
		if ($initiator === '')
		{
			return $result->addError(new Main\Error('Initiator is empty'));
		}

		$document = $this->documentRepository->getByUid($uid);
		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}

		$document->initiator = $initiator;
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Main\Error('Error when trying to save document'));
		}

		return $result;
	}

	/**
	 * @param string $uid
	 * @param InitiatedByType $initiatedByType
	 */
	public function modifyInitiatedByType(string $uid, InitiatedByType $initiatedByType): Main\Result|CreateDocumentResult
	{
		$result = new Main\Result();

		$document = $this->documentRepository->getByUid($uid);

		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}
		if ($document->initiatedByType === $initiatedByType)
		{
			return $result;
		}

		$document->initiatedByType = $initiatedByType;
		if ($initiatedByType === InitiatedByType::EMPLOYEE)
		{
			$document->externalId = null;
			$document->externalDateCreate = null;
			$document->externalDateCreateSourceType = Type\Document\ExternalDateCreateSourceType::MANUAL;
			$document->externalIdSourceType = Type\Document\ExternalIdSourceType::MANUAL;
			$document->hcmLinkDateSettingId = 0;
			$document->hcmLinkExternalIdSettingId = 0;
		}
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Main\Error('Error when trying to save document'));
		}

		$successResult = new CreateDocumentResult($document);
		if (!$document->isTemplated())
		{
			return $successResult;
		}
		$template = $this->documentTemplateRepository->getById($document->templateId);
		if ($template === null)
		{
			return $successResult;
		}
		if ($template->status === Type\Template\Status::NEW && $template->visibility === Visibility::INVISIBLE)
		{
			return $successResult;
		}

		// When the initiator type is changed, user should complete the template creation process again because some data may be outdated. ticket: 0213028
		$template->status = Type\Template\Status::NEW;
		$template->visibility = Visibility::INVISIBLE;

		$result = $this->documentTemplateRepository->update($template);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return $successResult;
	}

	public function setResultFileId(Item\Document $document, int $resultFileId): Main\Result
	{
		$document->resultFileId = $resultFileId;

		return DocumentTable::update($document->id, ['RESULT_FILE_ID' => $resultFileId]);
	}

	public function modifyRepresentativeId(
		string $documentUid,
		int $representativeId,
		string $entityType,
	): Main\Result
	{
		$result = new Main\Result();

		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}

		if (Main\UserTable::getCount(['=ID' => $representativeId]) < 1 && $entityType === Type\Member\EntityType::COMPANY)
		{
			return $result->addError(new Main\Error("User with id $representativeId does not exist"));
		}

		if (
			$entityType === Type\Member\EntityType::ROLE
			&& $this->structureNodeService->getRoleNameById($representativeId) === null
		)
		{
			return $result->addError(new Main\Error("Role with id $representativeId does not exist"));
		}

		$document->representativeId = $representativeId;

		return $this->documentRepository->update($document);
	}

	public function modifyCompany(string $documentUid, string $companyUid, int $companyEntityId): Main\Result
	{
		$result = new Main\Result();

		if ($companyUid === '' || $companyEntityId < 1)
		{
			return $result->addError(new Main\Error('Company is empty'));
		}

		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document || !$this->canBeChanged($document))
		{
			return $result->addError(new Main\Error('Document not found'));
		}

		$document->companyUid = $companyUid;
		$document->companyEntityId = $companyEntityId;
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Main\Error('Error when trying to save document'));
		}

		return $result;
	}

	public function modifyProviderCode(Item\Document $document, string $providerCode): Main\Result
	{
		$result = new Main\Result();
		if (!Type\ProviderCode::isValid($providerCode))
		{
			return $result->addError(new Main\Error("Invalid provider code. `$providerCode`"));
		}

		if (!$this->canBeChanged($document))
		{
			return $result->addError(new Main\Error('Document cannot be changed'));
		}

		return $this->providerCodeService->updateProviderCode($document, $providerCode);
	}

	public function update(Item\Document $document): Main\Result
	{
		return $this->documentRepository->update($document);
	}

	private function insertToDB(
		string $title,
		Item\Blank $blank,
		Item\Document $documentItem,
		int $createdById = 0,
		?BindingCollection $bindings = null,
	): Main\Result
	{
		// backward compatibility
		if ($documentItem->entityType === null)
		{
			$documentItem->entityType = EntityType::SMART;
		}

		if ($createdById)
		{
			$documentItem->createdById = $createdById;
		}

		if ((int)$documentItem->entityId === 0 && !$documentItem->isTemplated())
		{
			$result = $this->documentEntityFactory->createNewEntity($documentItem, $this->checkPermission);
			if (!$result->isSuccess())
			{
				return $result;
			}

			$documentItem->entityId = $result->getId();
		}

		$entity = null;
		if (!$documentItem->isTemplated())
		{
			$entity = $this->documentEntityFactory->getByDocument($documentItem);
			if ($entity === null && $documentItem->entityType)
			{
				return (new Main\Result())->addError(new Main\Error("Document doesnt contains linked entity"));
			}
		}

		$documentTitle = $this->makeDocumentTitle($title, $blank, $entity);

		// linked smart-document also needs to be renamed
		if ($documentTitle !== $entity?->getTitle())
		{
			$entity?->setTitle($documentTitle);
		}

		$notB2e = $documentItem->entityType !== Type\Document\EntityType::SMART_B2E;

		$documentItem->title = $documentTitle;
		$documentItem->langId = Context::getCurrent()->getLanguage();
		$documentItem->status = DocumentStatus::NEW;
		$documentItem->blankId = $blank->id;
		$documentItem->initiator = $this->createInitiatorName($notB2e);

		/* @todo Default scenarios for these type of scenarios, change logic when other scenarios are going to be implemented */
		$documentItem->scenario = match ($documentItem->entityType) {
			Type\Document\EntityType::SMART_B2E => Type\DocumentScenario::DSS_SECOND_PARTY_MANY_MEMBERS,
			default => Type\DocumentScenario::SIMPLE_SIGN_MANY_PARTIES_ONE_MEMBERS,
		};
		$documentItem->version = 2;

		$documentItem->entityTypeId = EntityType::getEntityTypeIdByType($documentItem->entityType);
		$addResult = Service\Container::instance()
			->getDocumentRepository()
			->add($documentItem);

		if (!$addResult->isSuccess())
		{
			return $addResult;
		}

		if ($bindings !== null)
		{
			$addBindingsResult = $this->addBindings($documentItem, $bindings);

			if (!$addBindingsResult->isSuccess())
			{
				return $addBindingsResult;
			}
		}

		if (!$documentItem->isTemplated())
		{
			$eventData = new EventData();
			$eventData
				->setEventType(EventData::TYPE_ON_REGISTER)
				->setDocumentItem($documentItem)
			;

			try
			{
				$this->eventHandlerService->createTimelineEvent($eventData);
			}
			catch (ArgumentException|Main\ArgumentOutOfRangeException $e)
			{
			}
		}

		return $addResult;
	}

	private function addBindings(Item\Document $document, BindingCollection $bindings): Result
	{
		$result = new Result();
		if ((int)$document->entityId < 1)
		{
			return $result->addError(new Main\Error('Invalid document entity id'));
		}

		foreach ($bindings as $binding)
		{
			if ($binding->entityId < 1)
			{
				continue;
			}

			if ($binding->entityType < 1)
			{
				continue;
			}

			$addResult = Container::instance()
				->getCrmEntityRelationService()
				->addRelationToSmartB2eDocument($document, $binding->entityId, $binding->entityType)
			;

			$result->addErrors($addResult->getErrors());
		}

		return $result;
	}

	/**
	 * @return string
	 * @see \SignMasterComponent::getResponsibleName
	 */
	private function createInitiatorName(bool $fromPrevious = true): string
	{
		if ($fromPrevious)
		{
			$previousInitiator = $this->getPreviousDocumentInitiatorName();
			if ($previousInitiator !== null)
			{
				return $previousInitiator;
			}
		}

		return User::getCurrentUserName();
	}

	private function getPreviousDocumentInitiatorName(): ?string
	{
		$currentUserId = Main\Engine\CurrentUser::get()->getId();

		if ($currentUserId === null)
		{
			return null;
		}

		$lastUserDocuments = $this->getUserLastDocuments(
			(int)$currentUserId,
			5,
		);

		foreach ($lastUserDocuments as $userDocument)
		{
			$initiatorName = $userDocument->initiator;
			if ($initiatorName !== null)
			{
				return $initiatorName;
			}
		}

		return null;
	}

	public function changeBlank(string $uid, int $blankId, bool $copyBlocksFromPreviousBlank = false): Main\Result
	{
		['document' => $document, 'blank' => $blank, 'result' => $extractionResult] = $this->extractDocumentAndBlank($uid, $blankId);

		if (!$extractionResult->isSuccess())
		{
			return $extractionResult;
		}

		if (
			$blank->scenario === Type\BlankScenario::B2B && in_array($document->scenario, Type\DocumentScenario::getB2EScenarios(), true)
			|| $blank->scenario === Type\BlankScenario::B2E && in_array($document->scenario, Type\DocumentScenario::getB2BScenarios(), true)
		)
		{
			return (new Main\Result())->addError(new Main\Error('Wrong blank scenario for current document'));
		}

		if (
			Type\DocumentScenario::isB2EScenario($document->scenario)
			&& $this->documentRepository->getCountByBlankId($blankId) === 0
		)
		{
			$this->blankService->changeBlankTitleByDocument($document, $document->title);
		}

		$oldBlankId = $document->blankId;

		$document->blankId = $blankId;

		$result = $this->documentRepository->update($document);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$oldBlank = $this->blankRepository->getById($oldBlankId);
		if ($copyBlocksFromPreviousBlank && $oldBlank !== null)
		{
			$this->blockRepository->loadBlocks($oldBlank);
			$newBlocks = $oldBlank->blockCollection->copyWithBlackId($blankId);

			$result = $this->blockRepository->addCollection($newBlocks);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}
		$result = (new Delete($oldBlank))->launch();
		$skippableErrors = [Delete::ERROR_BLANK_USED_IN_DOCUMENTS, Delete::ERROR_BLANK_USED_FOR_RESENT_DOCUMENTS];
		$isErrorSkippable = in_array($result->getError()?->getCode(), $skippableErrors, true);

		if (!$result->isSuccess() && !$isErrorSkippable)
		{
			return $result;
		}

		return \Bitrix\Sign\Result\Result::createByData(['document' => $document]);
	}

	/**
	 * Change "sign until" date for document
	 *
	 * @param string $uid
	 * @param Main\Type\DateTime|null $dateSignUntil
	 *
	 * @return \Bitrix\Main\Result
	 */
	public function modifyDateSignUntil(string $uid, ?Main\Type\DateTime $dateSignUntil): Main\Result
	{
		$result = new Main\Result;
		$document = $this->documentRepository->getByUid($uid);

		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}

		$document->dateSignUntil = $dateSignUntil;

		$validationResult = (new Validation\ValidateDateSignUntil($document))->launch();
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		$api = Service\Container::instance()->getApiDocumentService();
		$request = new Item\Api\Document\ModifyDateSignUntilRequest($document->uid, $document->dateSignUntil->getTimestamp());
		$response = $api->modifyDateSignUntil($request);
		if (!$response->isSuccess())
		{
			return (new Main\Result())->addErrors($response->getErrors());
		}

		$updateResult = $this->documentRepository->update($document);

		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Main\Error('Error when trying to save document'));
		}

		$this->fireDocumentUpdatedTimelineEvent($document);

		return $updateResult;
	}

	/**
	 * Upload document file to signing server
	 *
	 * @param string $uid
	 *
	 * @return \Bitrix\Main\Result
	 */
	public function upload(string $uid, bool $skipReuse = false): Main\Result
	{
		['document' => $document, 'blank' => $blank, 'result' => $extractionResult] = $this->extractDocumentAndBlank(
			$uid,
		);
		if (!$extractionResult->isSuccess())
		{
			return $extractionResult;
		}

		if (!$skipReuse)
		{
			$reuseResult = $this->reuse($document->uid, $blank->getId());
			if ($reuseResult->isSuccess())
			{
				$document->status = DocumentStatus::UPLOADED;

				return $this->documentRepository->update($document);
			}
		}

		$fileCollection = new Item\Api\Property\Request\Document\Upload\FileCollection();

		foreach ($blank->fileCollection->toArray() as $file)
		{
			if (empty($file->content->data))
			{
				return (new Main\Result())->addError(
					new Main\Error(Loc::getMessage('SIGN_SERVICE_DOCUMENT_FILE_EMPTY')),
				);
			}

			$fileCollection->addItem(
				new Item\Api\Property\Request\Document\Upload\File(
					$file->name, $file->type, base64_encode($file->content->data),
				),
			);
		}
		$documentUploadRequest = new Item\Api\Document\UploadRequest($uid, $fileCollection);
		$apiDocument = Service\Container::instance()
			->getApiDocumentService()
		;
		$documentUploadResponse = $apiDocument->upload($documentUploadRequest);

		if (!$documentUploadResponse->isSuccess())
		{
			return (new Main\Result())->addErrors($documentUploadResponse->getErrors());
		}

		$document->status = DocumentStatus::UPLOADED;

		return $this->documentRepository->update($document);
	}

	/**
	 * Reuse document file on the signing server
	 *
	 * @param string $documentUid
	 * @param int $blankId
	 * @return Result
	 */
	private function reuse(string $documentUid, int $blankId): Main\Result
	{
		$result = new Main\Result();
		$apiDocument = Service\Container::instance()
			->getApiDocumentService();

		$lastDocumentByBlankId = $this->getLastByBlankId($blankId);

		if ($lastDocumentByBlankId === null)
		{
			return $result->addError(new Main\Error('Last document can not be empty'));
		}

		if ($lastDocumentByBlankId->uid === null)
		{
			return $result->addError(new Main\Error('Last document uid can not be empty'));
		}

		$documentReuseRequest = new Item\Api\Document\ReuseRequest($documentUid, $lastDocumentByBlankId->uid);
		$documentReuseResponse = $apiDocument->reuse($documentReuseRequest);
		if (!$documentReuseResponse->isSuccess())
		{
			return (new Main\Result())->addErrors($documentReuseResponse->getErrors());
		}

		return $result;
	}

	/**
	 * @param string $uid
	 * @param int|null $blankId
	 *
	 * @return array{document: Item\Document, blank: Item\Blank, result: Main\Result}
	 */
	private function extractDocumentAndBlank(string $uid, ?int $blankId = null): array
	{
		$result = (new Main\Result());
		try
		{
			$document = $this->documentRepository->getByUid($uid);
		}
		catch (Main\ObjectPropertyException|Main\ArgumentException|Main\SystemException $e)
		{
			$document = null;
		}

		if (!$document)
		{
			$result->addError(new Main\Error(Loc::getMessage('SIGN_SERVICE_DOCUMENT_NOT_FOUND')));
		}

		try
		{
			$blank = $this->blankRepository->getById($blankId ? : $document->blankId);
		}
		catch (Main\ObjectPropertyException|Main\ArgumentException|Main\SystemException $e)
		{
			$blank = null;
		}

		if (!$blank)
		{
			$result->addError(
				new Main\Error(Loc::getMessage('SIGN_SERVICE_DOCUMENT_BLANK_NOT_FOUND')),
			);
		}

		return [
			'document' => $document,
			'blank' => $blank,
			'result' => $result,
		];
	}

	/**
	 * Get document by uid or null if not found
	 *
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getByUid(string $uid): ?Item\Document
	{
		return $this->documentRepository->getByUid($uid);
	}

	public function getById(int $id): ?Item\Document
	{
		return $this->documentRepository->getById($id);
	}

	public function getUserLastDocuments(int $userId, int $limit = 10): Item\DocumentCollection
	{
		return $this->documentRepository->listLastByUserCreateId($userId, $limit);
	}

	/**
	 * Configure and start signing process
	 *
	 * @param string $uid
	 *
	 * @return \Bitrix\Main\Result|ConfigureResult
	 */
	public function configureAndStart(string $uid): Main\Result|ConfigureResult
	{
		return (new ConfigureFillAndStart($uid))->launch();
	}

	/**
	 * Get last document by blank id
	 *
	 * @param int $blankId
	 *
	 * @return \Bitrix\Sign\Item\Document|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getLastByBlankId(int $blankId): ?Item\Document
	{
		return $this->documentRepository->getLastByBlankId($blankId);
	}

	public function canEditBlank(Item\Document $document): bool
	{
		return in_array($document->status, [null, Type\DocumentStatus::NEW], true);
	}

	public function refreshEntityNumber(Item\Document $document): Main\Result
	{
		$entity = $this->documentEntityFactory->getByDocument($document);
		if ($entity === null)
		{
			return (new Main\Result())->addError(new Main\Error("Document doesnt contains linked crm entity"));
		}

		$entity->refreshNumber();

		return new Main\Result();
	}

	public function rollbackDocument(int $documentId): Main\Result
	{
		$document = $this->getById($documentId);
		if ($document === null)
		{
			return (new Main\Result())->addError(new Main\Error('Document not found'));
		}

		if (!in_array($document->entityType, EntityType::getAll(), true))
		{
			return (new Main\Result())->addError(new Main\Error('Invalid document entityType'));
		}

		$documentResult = $this->documentRepository->delete($document);
		if (!$documentResult->isSuccess())
		{
			return $documentResult;
		}

		$smartDocument = $this->documentEntityFactory->getByDocument($document);
		if ($smartDocument && $smartDocument->getId())
		{
			$sendDeleteEntityPullEventResult = (new SendDeleteEntityPullEvent($document))->launch();
			if (!$sendDeleteEntityPullEventResult->isSuccess())
			{
				return $sendDeleteEntityPullEventResult;
			}

			$smartDocumentDeleteResult = $smartDocument->delete();
			if (!$smartDocumentDeleteResult->isSuccess())
			{
				return $smartDocumentDeleteResult;
			}
		}

		$deleteMembersResult = $this->memberRepository->deleteAllByDocumentId($documentId);
		if (!$deleteMembersResult->isSuccess())
		{
			return $deleteMembersResult;
		}

		if ($document->templateId)
		{
			$templateFolderRelationResult = $this->templateFolderRelationRepository->deleteByIdAndType($document->templateId, Template\EntityType::TEMPLATE);
			if (!$templateFolderRelationResult->isSuccess())
			{
				return $templateFolderRelationResult;
			}

			$templateDeleteResult = $this->documentTemplateRepository->deleteById($document->templateId);
			if (!$templateDeleteResult->isSuccess())
			{
				return $templateDeleteResult;
			}
		}

		if ($document->blankId && $document->groupId === null)
		{
			// skip blank deletion, if assigned to documents
			if ($this->documentRepository->getCountByBlankId($document->blankId) > 0)
			{
				return new Main\Result();
			}

			$blank = $this->blankRepository->getById($document->blankId);
			if ($blank === null)
			{
				return (new Main\Result())->addError(new Main\Error('Blank not found'));
			}

			return $this->blankService->deleteWithResources($blank);
		}

		return new Main\Result();
	}

	public function rollbackDocumentByUid(string $uid): Main\Result
	{
		$document = $this->getByUid($uid);

		return $this->rollbackDocument($document->id);
	}

	public function canBeChanged(Item\Document $document): bool
	{
		if ($document->uid === null)
		{
			return true;
		}

		return !in_array(
			$document->status,
			DocumentStatus::getEnding(),
			true,
		);
	}

	private function makeDocNameFromBlank(Item\Blank $blank, ?Document\Entity\Dummy $entity = null): string
	{
		// generate doc name from blank (first file) name
		$name = \Bitrix\Main\IO\Path::replaceInvalidFilename($blank->title, fn() => '');
		$name = trim(str_replace('.' . Path::getExtension($name ?? ''), '', Path::getName($name ?? '')));
		$ellipsis = mb_strlen($name) > self::DOCUMENT_NAME_LENGTH_LIMIT ? '...' : '';
		$name = mb_substr($name, 0, self::DOCUMENT_NAME_LENGTH_LIMIT);
		$name = removeScriptExtension($name);
		$name .= $ellipsis;

		if ($name === '')
		{
			return $entity?->getTitle() ?? 'Document';
		}

		return Loc::getMessage(
			'SIGN_SERVICE_DOCUMENT_TITLE_PLACEHOLDER',
			[
				'#DOC_NAME#' => $name,
				'#DATE#' => Main\Type\Date::createFromTimestamp(time())->toString(),
			],
		);
	}

	public function modifyRegionDocumentType(string $documentUid, string $regionDocumentType): Main\Result
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document || !$this->canBeChanged($document))
		{
			return (new Main\Result())->addError(new Main\Error('Document not found'));
		}

		$document->regionDocumentType = $regionDocumentType;
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return (new Main\Result())->addError(new Main\Error('Error when trying to save document'));
		}

		return new Main\Result();
	}

	public function modifyExternalId(
		string $documentUid,
		?string $externalId,
		Type\Document\ExternalIdSourceType $sourceType,
		?int $hcmLinkSettingId,
	): Main\Result
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document || !$this->canBeChanged($document))
		{
			return (new Main\Result())->addError(new Main\Error('Document not found'));
		}

		if ($sourceType === Type\Document\ExternalIdSourceType::MANUAL)
		{
			if (empty((string)$externalId))
			{
				return (new Main\Result())->addError(new Main\Error('ExternalId is empty'));
			}

			$document->externalId = trim($externalId);
			$document->hcmLinkExternalIdSettingId = 0;
		}
		else
		{
			$checkDocumentIntegrationResult = $this->checkDocumentIntegration($document);
			if (!$checkDocumentIntegrationResult->isSuccess())
			{
				return $checkDocumentIntegrationResult;
			}

			if ((int)$hcmLinkSettingId < 1)
			{
				return (new Main\Result())->addError(new Main\Error('Empty hcmLinkSettingId'));
			}

			if (!Container::instance()->getHcmLinkFieldService()->isExternalIdSettingFieldById($hcmLinkSettingId))
			{
				return (new Main\Result())->addError(new Main\Error('Invalid hcmLinkSettingId'));
			}

			$document->externalId = null;
			$document->hcmLinkExternalIdSettingId = $hcmLinkSettingId;
		}
		$document->externalIdSourceType = $sourceType;

		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return (new Main\Result())->addError(new Main\Error('Error when trying to save document'));
		}

		if ($sourceType === Type\Document\ExternalIdSourceType::MANUAL)
		{
			$smartDocument = $this->documentEntityFactory->getByDocument($document);
			$smartDocument?->setTitle($this->composeSmartDocumentTitle($document, $smartDocument));
		}

		return new Main\Result();
	}

	public function modifyHcmLinkDocumentType(
		string $documentUid,
		?int $hcmLinkSettingId
	): Result
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document || !$this->canBeChanged($document))
		{
			return (new Main\Result())->addError(new Main\Error('Document not found'));
		}

		if ($hcmLinkSettingId > 0)
		{
			$checkDocumentIntegrationResult = $this->checkDocumentIntegration($document);
			if (!$checkDocumentIntegrationResult->isSuccess())
			{
				return $checkDocumentIntegrationResult;
			}

			if (!Container::instance()->getHcmLinkFieldService()->isDocumentTypeSettingFieldById($hcmLinkSettingId))
			{
				return (new Main\Result())->addError(new Main\Error('Invalid hcmLinkSettingId'));
			}
		}

		$document->hcmLinkDocumentTypeSettingId = $hcmLinkSettingId ?? 0;

		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return (new Main\Result())->addError(new Main\Error('Error when trying to save document'));
		}

		return new Main\Result();
	}

    public function modifyScheme(string $documentUid, string $scheme): Main\Result
    {
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document || !$this->canBeChanged($document))
		{
			return (new Main\Result())->addError(new Main\Error('Document not found'));
		}

		if (!Type\DocumentScenario::isB2EScenario($document->scenario))
		{
			return (new Main\Result())->addError(new Main\Error('Modification of scheme is only available for documents of b2e scenario'));
		}

		if (!Type\Document\SchemeType::isValid($scheme))
		{
			return (new Main\Result())->addError(new Main\Error('Invalid scheme type'));
		}

		$document->scheme = $scheme;
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return (new Main\Result())->addError(new Main\Error('Error when trying to save document'));
		}

		return new Main\Result();
    }

	public function unsetEntityId(Item\Document $document): Result
	{
		return $this->documentRepository->unsetEntityId($document);
	}

	public function isCurrentUserCanEditDocument(Item\Document $document): bool
	{
		$result = (new CheckDocumentAccess(
			$document,
			PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE,
		))->launch();

		return $result->isSuccess();
	}

	public function modifyExternalDate(
		string $documentUid,
		Type\Document\ExternalDateCreateSourceType $sourceType,
		?string $externalDate = null,
		?int $hcmLinkSettingId = null,
	): Main\Result
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document || !$this->canBeChanged($document))
		{
			return (new Main\Result())->addError(new Main\Error('Document not found'));
		}

		if ($sourceType == Type\Document\ExternalDateCreateSourceType::MANUAL)
		{
			if (empty($externalDate))
			{
				return (new Main\Result())->addError(new Main\Error('Empty date'));
			}

			\CTimeZone::Disable();
			try
			{
				$date = Main\Type\DateTime::createFromUserTime($externalDate);
			}
			catch (Main\ObjectException $exception)
			{
				\CTimeZone::Enable();

				return (new Main\Result())->addError(new Main\Error('Incorrect date format'));
			}

			$document->externalDateCreateSourceType = Type\Document\ExternalDateCreateSourceType::MANUAL;
			$document->externalDateCreate = $date->disableUserTime();
			$document->hcmLinkDateSettingId = 0;
		}
		else
		{
			$checkDocumentIntegrationResult = $this->checkDocumentIntegration($document);
			if (!$checkDocumentIntegrationResult->isSuccess())
			{
				return $checkDocumentIntegrationResult;
			}

			if (empty($hcmLinkSettingId))
			{
				return (new Main\Result())->addError(new Main\Error('Empty hcmLinkSettingId'));
			}

			if (!Container::instance()->getHcmLinkFieldService()->isDateSettingFieldById($hcmLinkSettingId))
			{
				return (new Main\Result())->addError(new Main\Error('Invalid hcmLinkSettingId'));
			}

			$document->externalDateCreateSourceType = Type\Document\ExternalDateCreateSourceType::HCMLINK;
			$document->externalDateCreate = null;
			$document->hcmLinkDateSettingId = $hcmLinkSettingId;
		}

		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return (new Main\Result())->addError(new Main\Error('Error when trying to save document'));
		}

		return new Main\Result();
	}

	private function checkDocumentIntegration(Item\Document $document): Main\Result
	{
		$result = new Main\Result();
		if (!$this->isIntegrationDocumentSettingsAvailableForProvider($document->providerCode))
		{
			return $result->addError(new Main\Error('Invalid document provider code'));
		}

		if (!Container::instance()->getHcmLinkService()->isAvailable())
		{
			return $result->addError(new Main\Error('Integration is not available', 'HCM_LINK_NOT_AVAILABLE'));
		}

		if ((int)$document->hcmLinkCompanyId < 1)
		{
			return $result->addError(new Main\Error('Incorrect hcmLinkCompanyId', 'HCM_LINK_NOT_AVAILABLE'));
		}

		if (!Container::instance()->getHcmLinkService()->isCompanyExistWithId((int)$document->hcmLinkCompanyId))
		{
			return $result->addError(new Main\Error('HcmLinkCompany not found', 'HCM_LINK_NOT_AVAILABLE'));
		}

		return $result;
	}

	public function modifyHcmLinkCompanyId(string $documentUid, ?int $hcmLinkCompanyId = null): ?Main\Result
	{
		$document = $this->documentRepository->getByUid($documentUid);
		if (!$document || !$this->canBeChanged($document))
		{
			return (new Main\Result())->addError(new Main\Error('Document not found'));
		}

		$document->hcmLinkCompanyId = $hcmLinkCompanyId ?? 0;
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return (new Main\Result())->addError(new Main\Error('Error when trying to save document'));
		}

		return new Main\Result();
	}

	public function resolveDocumentByCrmEntity(string $entityType, int $entityId): ?Item\Document
	{
		return $this->documentRepository
			->getByEntityIdAndType($entityId, $entityType)
		;
	}

	public function getSignDocumentBySmartDocumentId(int $entityId): ?Item\Document
	{
		return $this->resolveDocumentByCrmEntity(EntityType::SMART, $entityId);
	}

	private function makeTemplateForDocument(
		Item\Document $document,
		string $title,
		int $currentUserId,
	): Result|CreateTemplateResult
	{
		$template = new Item\Document\Template(
			title: $title ?: 'Template title',
			createdById: $currentUserId,
			modifiedById: $currentUserId,
			visibility: Visibility::INVISIBLE,
		);
		$result = $this->documentTemplateRepository->add($template);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$document->templateId = $template->id;

		return new CreateTemplateResult($template);
	}

	private function makeTemplateFolderRelation(Item\Document\Template $template): CreateTemplateFolderRelationResult|Result
	{
		if (!$template->id)
		{
			return (new Main\Result())->addError(new Main\Error('Incorrect template id'));
		}
		$currentUserId = (int)CurrentUser::get()->getId();
		if (!$currentUserId)
		{
			return (new Main\Result())->addError(new Main\Error('User not found'));
		}

		$template = $this->documentTemplateRepository->getById($template?->id);
		if ($template === null)
		{
			return (new Main\Result())->addError(new Main\Error('Template not found'));
		}

		$templateFolderRelation = new TemplateFolderRelation(
			entityId: $template->id,
			entityType: Template\EntityType::TEMPLATE,
			createdById: $currentUserId,
			parentId: 0,
		);

		$result = $this->templateFolderRelationRepository->add($templateFolderRelation);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return new CreateTemplateFolderRelationResult($templateFolderRelation);
	}

	/**
	 * @param string $title
	 * @param Item\Blank $blank
	 * @param Document\Entity\Dummy|null $entity
	 *
	 * @return string
	 */
	public function makeDocumentTitle(string $title, Item\Blank $blank, ?Document\Entity\Dummy $entity): string
	{
		return $title !== ''
			? $title
			: $this->makeDocNameFromBlank($blank, $entity)
		;
	}

	public function getMyCompanyIdByDocument(Item\Document $document): ?int
	{
		if ($document->id === null)
		{
			return null;
		}

		$companyMember = $this->memberRepository->getCompanyMemberByDocument(
			$document->id,
		);

		return $companyMember?->entityId;
	}

	/**
	 * @return list<int, int> Document id to company id
	 */
	public function listMyCompanyIdsForDocuments(Item\DocumentCollection $documents): array
	{
		$companyIds = [];
		foreach ($documents as $document)
		{
			if ($document === null)
			{
				continue;
			}

			if ($document->id === null)
			{
				continue;
			}

			$companyId = $this->getMyCompanyIdByDocument($document);
			if ($companyId !== null)
			{
				$companyIds[$document->id] = $companyId;
			}
		}

		return $companyIds;
	}

	public function getLastCreatedEmployeeDocumentFromDocuments(int $creatorUserId, Item\DocumentCollection $documents): ?Item\Document
	{
		$documentIds = $documents->listIdsWithoutNull();
		if (empty($documentIds))
		{
			return null;
		}

		$lastDocument = $this->documentRepository->getByCreatedFromDocumentIdsAndInitiatedByTypeAndCreatedByIdOrderedByDateCreateDesc(
			$documentIds,
			Type\Document\InitiatedByType::EMPLOYEE,
			$creatorUserId,
		);

		return $lastDocument;
	}

	public function getDocumentEntity(Item\Document $document): ?Document\Entity\Dummy
	{
		return $this->documentEntityFactory->getByDocument($document);
	}

	public function getByTemplateId(int $templateId): ?Item\Document
	{
		if ($templateId < 1)
		{
			return null;
		}

		return $this->documentRepository->getByTemplateId($templateId);
	}

	public function getByTemplateIds(int ...$ids): Item\DocumentCollection
	{
		return $this->documentRepository->getByTemplateIds(...$ids);
	}

	private function fireDocumentUpdatedTimelineEvent(Item\Document $document): void
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			return;
		}

		/**
		 * @see \CCrmOwnerType::SmartB2eDocument
		 * @see \CCrmOwnerType::SmartDocument
		 */
		$itemFactory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($document->entityTypeId);

		if (
			$itemFactory
			&& $item = $itemFactory->getItem($document->entityId)
		)
		{
			\Bitrix\Crm\Activity\Provider\SignB2eDocument::onDocumentUpdate($item->getId());
		}
	}

	private function isIntegrationDocumentSettingsAvailableForProvider(?string $providerCode): bool
	{
		return $providerCode && $providerCode !== ProviderCode::SES_RU;
	}
}
