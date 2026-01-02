<?php

namespace Bitrix\Sign\Controllers\V1;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Operation\CreateDocumentPreviewPage;
use Bitrix\Sign\Result\CreateDocumentResult;
use Bitrix\Sign\Service;
use Bitrix\Sign\Service\Sign\Document\Template\AccessService;
use Bitrix\Sign\Service\Sign\Document\TemplateService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Template\EntityType;

class Document extends Controller
{
	private Service\Sign\DocumentService $documentService;
	private TemplateService $templateService;
	private AccessService $templateAccessService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		$this->documentService = Service\Container::instance()->getDocumentService();
		$this->templateService = Service\Container::instance()->getDocumentTemplateService();
		$this->templateAccessService = Service\Container::instance()->getTemplateAccessService();
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_DOCUMENT_ADD),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_ADD),
	)]
	public function registerAction(
		int $blankId,
		?string $scenarioType = null,
		bool $asTemplate = false,
		int $chatId = 0,
		int $templateFolderId = 0,
		?string $initiatedByType = null,
	): array
	{
		$scenarioType ??= Type\DocumentScenario::SCENARIO_TYPE_B2B;

		if ($scenarioType === Type\DocumentScenario::SCENARIO_TYPE_B2E)
		{
			if (!Storage::instance()->isB2eAvailable())
			{
				$this->addError(new Error('Document scenario not available'));

				return [];
			}

			$result = Service\Container::instance()->getB2eTariffRestrictionService()->check();
			if (!$result->isSuccess())
			{
				Service\Container::instance()->getSignBlankService()->rollbackById($blankId);
				$this->addErrors($result->getErrors());

				return [];
			}

			if (B2eTariff::instance()->isB2eRestrictedInCurrentTariff())
			{
				Service\Container::instance()->getSignBlankService()->rollbackById($blankId);
				$this->addB2eTariffRestrictedError();

				return [];
			}
		}

		$createdById = (int)CurrentUser::get()->getId();
		if($createdById < 1)
		{
			$this->addError(new Error('User not found'));

			return [];
		}

		$result = $this->documentService->register(
			blankId: $blankId,
			createdById: $createdById,
			entityType: Type\Document\EntityType::getByScenarioType($scenarioType),
			asTemplate: $asTemplate,
			initiatedByType: InitiatedByType::tryFrom($initiatedByType ?? '') ?? InitiatedByType::COMPANY,
			chatId: $chatId,
		);
		$resultData = $result->getData();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			if ($docId = ($resultData['documentId'] ?? null))
			{
				$this->documentService->rollbackDocument($docId);
			}
			return [];
		}
		$template = $resultData['template'] ?? null;
		if ($template !== null && !$template instanceof Template)
		{
			$this->addErrorByMessage('Template not found');

			return [];
		}

		$result = $this->templateService->updateParent($templateFolderId, [$template?->id], EntityType::TEMPLATE);
		if (!$result->isSuccess())
		{
			$this->addError(new Error('Update parent id error'));
			return [];
		}

		return [
			'uid' => $resultData['document']->uid,
			'templateUid' => $template?->uid,
			'templateId' => $template?->id,
		];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_TEMPLATE_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
	)]
	public function modifyInitiatedByTypeAction(
		string $uid,
		string $initiatedByType,
	): array
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			$this->addError(new Error('Document scenario not available'));

			return [];
		}

		$initiatedByTypeValue = InitiatedByType::tryFrom($initiatedByType);

		if (null === $initiatedByTypeValue)
		{
			$this->addError(new Error('Initiator type is wrong'));

			return [];
		}

		$result = $this->documentService->modifyInitiatedByType($uid, $initiatedByTypeValue);

		if (!$result instanceof CreateDocumentResult)
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		$document = $result->document;

		return [
			'uid' => $document->uid,
			'initiatedByType' => $document->initiatedByType,
		];
	}

	/**
	 * @param string $uid
	 * @param int $blankId
	 *
	 * @return array{uid: string}
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function changeBlankAction(string $uid, int $blankId, bool $copyBlocksFromPreviousBlank = false): array
	{
		$result = $this->documentService->changeBlank($uid, $blankId, $copyBlocksFromPreviousBlank);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		$document = $result->getData()['document'] ?? null;

		if (!$document instanceof \Bitrix\Sign\Item\Document)
		{
			$this->addError(new Error('Cannot change document blank'));

			return [];
		}

		$template = Service\Container::instance()
			->getDocumentTemplateService()
			->getById((int)$document->templateId)
		;

		return [
			'uid' => $document->uid,
			'templateUid' => $template?->uid,
		];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
		)
	)]
	public function modifyDateSignUntilAction(string $uid,  ?int $dateSignUntilTs): array
	{
		$result = $this->documentService->modifyDateSignUntil($uid, DateTime::createFromTimestamp($dateSignUntilTs));

		if (!$result->isSuccess())
		{
			$this->addErrors(
				$this->container->getLocalizedErrorService()->localizeErrors(
					$result->getErrors()
				)
			);

			return [];
		}

		$document = $result->getData()['document'];

		return [
			'uid' => $document->uid,
			'dateSignUntil' => $document->dateSignUntil,
		];
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function uploadAction(
		string $uid,
	): array
	{
		$result = $this->documentService->upload($uid);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			$this->documentService->rollbackDocumentByUid($uid);
			return [];
		}

		return [];
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function loadAction(string $uid): array
	{
		$container = Service\Container::instance();
		$documentRepository = $container->getDocumentRepository();
		$document = $documentRepository->getByUid($uid);

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		return (new \Bitrix\Sign\Ui\ViewModel\Wizard\Document($document))->toArray();
	}

	/**
	 * @param list<int> $templateIds
	 * @return array
	 */
	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_ADD,
	)]
	public function loadByTemplateIdsAction(array $templateIds): array
	{
		$templateIds = array_filter(array_map(static fn(mixed $id): int => (int)$id, $templateIds));

		$templates = $this->templateService->getByIds($templateIds);
		if (!$this->templateAccessService->hasAccessToReadForCollection($templates))
		{
			$this->addError(new Error('No access rights to read templates in folder'));

			return [];
		}

		$documents = $this->documentService->getByTemplateIds(...$templateIds);

		$notFoundTemplates = array_diff($templateIds, array_keys($templates->mapWithIdKeys()));
		if (!empty($notFoundTemplates))
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_DOCUMENTS_NOT_FOUND')));

			return [];
		}

		return array_map(
			fn($document) => (new \Bitrix\Sign\Ui\ViewModel\Wizard\TemplateDocument($document))->toArray(),
			$documents->toArray()
		);
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function getDocumentPreviewUrlAction(string $uid): array
	{
		$container = Service\Container::instance();
		$document = $container->getDocumentService()->getByUid($uid);

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		$blankId = (int)$document->blankId;
		if ($blankId < 1)
		{
			$this->addError(new Error('Document blank id is empty'));

			return [];
		}

		return ['url' => $container->getSignBlankService()->getPreviewUrl($blankId)];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'id',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'id',
		),
	)]
	public function loadByIdAction(int $id): array
	{
		$documentRepository = $this->container->getDocumentRepository();
		$document = $documentRepository->getById($id);

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_DOCUMENT_NOT_FOUND')));
			return [];
		}

		return (new \Bitrix\Sign\Ui\ViewModel\Wizard\Document($document))->toArray();
	}

	/**
	 * @return array
	 */
	public function loadLanguageAction(): array
	{
		return Storage::instance()->getLanguages();
	}

	/**
	 * @param string $uid
	 * @param string $title
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function modifyTitleAction(
		string $uid,
		string $title,
	): array
	{
		$result = $this->documentService->modifyTitle($uid, trim($title));
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		return [
			'blankTitle' => $result->getData()['blankTitle'] ?? '',
		];
	}

	/**
	 * @param string $uid
	 * @param string $langId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function modifyLangIdAction(
		string $uid,
		string $langId,
	): array
	{
		$result = $this->documentService->modifyLangId($uid, $langId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return [];
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function modifyInitiatorAction(
		string $uid,
		string $initiator,
	): array
	{
		$result = $this->documentService->modifyInitiator($uid, $initiator);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return [];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid',
		)
	)]
	public function refreshEntityNumberAction(string $documentUid): array
	{
		$document = $this->documentService->getByUid($documentUid);
		if ($document === null)
		{
			return [];
		}
		$result = $this->documentService->refreshEntityNumber($document);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return [];
		}

		return [];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function configureAction(string $uid): array
	{
		$result = (new Operation\ConfigureFillAndStart($uid))->launch();
		$this->addErrorsFromResult($result);
		if ($result instanceof Operation\Result\ConfigureResult && !$result->completed)
		{
			Service\Container::instance()
				->getDocumentAgentService()
				->addConfigureAndStartAgent($uid)
			;
		}

		return [];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentUid'
	)]
	public function modifyCompanyAction(string $documentUid, string $companyUid, int $companyEntityId): array
	{
		if (empty($companyUid) || $companyEntityId < 1)
		{
			$this->addError(new Error('Empty company'));

			return [];
		}

		$container = Service\Container::instance();
		$apiService = $container->getApiService();
		$result = $apiService->get('v1/b2e.company.provider.get', [
			'companyUid' => $companyUid,
		]);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		$providerCode = $result->getData()['code'] ?? null;
		$result = $this->documentService->modifyCompany($documentUid, $companyUid, $companyEntityId);
		$this->addErrors($result->getErrors());
		if ($providerCode === null)
		{
			return [];
		}
		$document = $this->documentService->getByUid($documentUid);
		if ($document === null)
		{
			$this->addError(new Error('Document not found'));

			return [];
		}

		$convertedProviderCode = Type\ProviderCode::createFromProviderLikeString($providerCode);
		if ($convertedProviderCode === null)
		{
			$this->addErrorByMessage("Provider `$providerCode` is not valid");

			return [];
		}

		$result = $this->documentService->modifyProviderCode($document, $convertedProviderCode);
		$this->addErrors($result->getErrors());

		return [];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'uid',
	)]
	public function modifyRegionDocumentTypeAction(string $uid, string $type): array
	{
		$result = $this->documentService->modifyRegionDocumentType($uid, $type);
		$this->addErrors($result->getErrors());

		return [];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'uid',
	)]
	public function modifyExternalIdAction(
		string $uid,
		?string $id = null,
		?string $sourceType = null,
		?int $hcmLinkSettingId = null,
	): array
	{
		$sourceType ??= Type\Document\ExternalIdSourceType::MANUAL->value;
		$sourceType = Type\Document\ExternalIdSourceType::tryFrom($sourceType);
		if (!$sourceType)
		{
			$this->addError(new Error('Invalid sourceType'));
			return [];
		}

		$result = $this->documentService->modifyExternalId(
			documentUid: $uid,
			externalId: $id,
			sourceType: $sourceType,
			hcmLinkSettingId: $hcmLinkSettingId,
		);
		$this->addErrors($result->getErrors());

		return [];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'uid',
	)]
	public function modifyHcmLinkDocumentTypeAction(
		string $uid,
		?int $hcmLinkSettingId = null,
	)
	{
		$result = $this->documentService->modifyHcmLinkDocumentType(
			documentUid: $uid,
			hcmLinkSettingId: $hcmLinkSettingId,
		);
		$this->addErrors($result->getErrors());

		return [];
	}

	public function isNotAcceptedAgreement(): bool
	{
		$agreementOptions = \CUserOptions::GetOption('sign', 'sign-agreement', null);
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
		return (
				!is_array($agreementOptions)
				|| !isset($agreementOptions['decision'])
				|| $agreementOptions['decision'] !== 'Y'
			)
			&& !in_array($region,
				[
					'ru',
					'by',
					'kz',
				],
				true,
			);
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'uid',
	)]
	public function modifySchemeAction(string $uid, string $scheme): array
	{
		$result = $this->documentService->modifyScheme($uid, $scheme);
		$this->addErrors($result->getErrors());

		return [];
	}

	#[Attribute\ActionAccess(
		ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'uid',
	)]
	public function modifyExternalDateAction(
		string $uid,
		?string $externalDate,
		?string $sourceType = null,
		?int $hcmLinkSettingId = null,
	): array
	{
		$sourceType ??= Type\Document\ExternalDateCreateSourceType::MANUAL->value;
		$sourceType = Type\Document\ExternalDateCreateSourceType::tryFrom($sourceType);
		if (!$sourceType)
		{
			$this->addError(new Error('Invalid source type'));
			return [];
		}

		$result = $this->documentService->modifyExternalDate(
			documentUid: $uid,
			sourceType: $sourceType,
			externalDate: $externalDate,
			hcmLinkSettingId: $hcmLinkSettingId,
		);
		$this->addErrors($result->getErrors());

		return [];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_EDIT),
	)]
	public function modifyIntegrationIdAction(string $uid, ?int $integrationId = null): array
	{
		$result = $this->documentService->modifyHcmLinkCompanyId($uid, $integrationId);
		$this->addErrors($result->getErrors());

		return [];
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_READ,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_READ,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function getFillAndStartProgressAction(string $uid): array
	{
		$result = (new Operation\GetFillAndStartProgress($uid))->launch();
		$this->addErrorsFromResult($result);
		if ($result instanceof Operation\Result\ConfigureProgressResult)
		{
			return [
				'completed' => $result->completed,
				'progress' => $result->progress,
			];
		}

		return [];
	}

	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'uid'
	)]
	public function removeAction(string $uid): array
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			$this->addError(new Error('B2e document scenario is not available'));

			return [];
		}

		$document = Service\Container::instance()->getDocumentRepository()->getByUid($uid);

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		if ($document->id === null)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_DOCUMENT_NOT_FOUND')));

			return [];
		}

		if (!DocumentScenario::isB2EScenario($document->scenario))
		{
			$this->addError(new Error('Only b2e documents can be removed'));

			return [];
		}

		$expected = [Type\DocumentStatus::NEW, Type\DocumentStatus::UPLOADED];
		if (!in_array($document->status, $expected, true))
		{
			$this->addError(new Error(
				message: 'Document has improper status',
				code: 'SIGN_DOCUMENT_INCORRECT_STATUS',
				customData: [
					'has' => $document->status,
					'expected' => $expected,
				],
			));

			return [];
		}

		$rollbackResult = $this->documentService->rollbackDocument($document->id);

		if (!$rollbackResult->isSuccess())
		{
			$this->addErrors($rollbackResult->getErrors());

			return [];
		}

		return [];
	}

	/**
	 * @param list<int> $ids
	 *
	 * @return array{completed: bool, progress: int}
	 */
	#[Attribute\ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'ids'
	)]
	public function getManyFillAndStartProgressAction(array $ids): array
	{
		if (empty($ids))
		{
			$this->addErrorByMessage('No ids');

			return [];
		}

		$documents = Service\Container::instance()->getDocumentRepository()->listByIds($ids);
		$notFoundDocuments = array_diff($ids, array_keys($documents->getArrayByIds()));
		if (!empty($notFoundDocuments))
		{
			$this->addErrorByMessage('Not found documents with ids: ' . implode(',', $notFoundDocuments));

			return [];
		}

		$completedCount = 0;
		foreach ($documents as $document)
		{
			$result = (new Operation\GetFillAndStartProgressByDocument($document))->launch();
			$this->addErrorsFromResult($result);
			if ($result instanceof Operation\Result\ConfigureProgressResult && $result->completed)
			{
				$completedCount++;
			}
		}

		$totalCount = $documents->count();
		$progress = $totalCount > 0 ? round(100 / $totalCount * $completedCount) : 0;

		return [
			'completed' => $totalCount === $completedCount,
			'progress' => $progress,
		];
	}
}
