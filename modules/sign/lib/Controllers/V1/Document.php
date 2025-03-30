<?php

namespace Bitrix\Sign\Controllers\V1;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\Document\InitiatedByType;

class Document extends \Bitrix\Sign\Engine\Controller
{
	private Service\Sign\DocumentService $documentService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);
		$this->documentService = Service\Container::instance()->getDocumentService();
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

		$result = $this->documentService->register(
			blankId: $blankId,
			entityType: Type\Document\EntityType::getByScenarioType($scenarioType),
			asTemplate: $asTemplate,
			createdById: $createdById,
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
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_TEMPLATE_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
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

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		$document = $result->getData()['document'];

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
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
		)
	)]
	public function changeBlankAction(string $uid, int $blankId): array
	{
		$result = $this->documentService->changeBlank($uid, $blankId);
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

	/**
	 * @param string $uid
	 *
	 * @return array
	 */
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
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
		)
	)]
	public function loadAction(string $uid): array
	{
		$documentRepository = Service\Container::instance()->getDocumentRepository();
		$document = $documentRepository->getByUid($uid);

		if (!$document)
		{
			$this->addError(new Error(Loc::getMessage('SIGN_CONTROLLER_DOCUMENT_NOT_FOUND')));
			return [];
		}

		return $this->mapToRepresentedDocumentView($document);
	}

	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'id'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'id'
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

		return $this->mapToRepresentedDocumentView($document);
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
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
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
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
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
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
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
			itemIdOrUidRequestKey: 'documentUid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'documentUid'
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
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
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
	public function modifyCompanyAction(string $documentUid, string $companyUid): array
	{
		if (empty($companyUid))
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
		$result = $this->documentService->modifyCompanyUid($documentUid, $companyUid);
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
	public function modifyExternalIdAction(string $uid, string $id): array
	{
		$result = $this->documentService->modifyExternalId($uid, trim($id));
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
	public function modifyExternalDateAction(string $uid, string $externalDate): array
	{
		$result = $this->documentService->modifyExternalDate($uid, $externalDate);
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
			itemIdOrUidRequestKey: 'uid'
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_READ,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid'
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

	private function mapToRepresentedDocumentView(\Bitrix\Sign\Item\Document $document): array
	{
		return [
			'id' => $document->id,
			'blankId' => $document->blankId,
			'entityId' => $document->entityId,
			'entityType' => $document->entityType,
			'entityTypeId' => $document->entityTypeId,
			'initiator' => $document->initiator,
			'langId' => $document->langId,
			'parties' => $document->parties,
			'resultFileId' => $document->resultFileId,
			'scenario' => $document->scenario,
			'status' => $document->status,
			'title' => $document->title,
			'uid' => $document->uid,
			'version' => $document->version,
			'createdById' => $document->createdById,
			'companyUid' => $document->companyUid,
			'representativeId' => $document->representativeId,
			'scheme' => $document->scheme,
			'dateCreate' => $document->dateCreate,
			'dateSign' => $document->dateSign,
			'regionDocumentType' => $document->regionDocumentType,
			'externalId' => $document->externalId,
			'stoppedById' => $document->stoppedById,
			'externalDateCreate' => $document->externalDateCreate,
			'providerCode' => $document->providerCode ? Type\ProviderCode::toRepresentativeString($document->providerCode) : null,
			'templateId' => $document->templateId,
			'chatId' => $document->chatId,
			'groupId' => $document->groupId,
			'createdFromDocumentId' => $document->createdFromDocumentId,
			'initiatedByType' => $document->initiatedByType,
			'hcmLinkCompanyId' => $document->hcmLinkCompanyId,
			'dateStatusChanged' => $document->dateStatusChanged,
		];
	}
}
