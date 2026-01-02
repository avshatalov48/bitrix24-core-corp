<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Document;

use Bitrix\Main;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicAnd;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Document\Template\TemplateCreatedDocument;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Operation\Document\ExportBlank;
use Bitrix\Sign\Operation\Document\Template\DeleteTemplateEntity;
use Bitrix\Sign\Operation\Document\Template\GetOrInstallOnboardingTemplate;
use Bitrix\Sign\Operation\Document\UnserializePortableBlank;
use Bitrix\Sign\Operation\Document\Template\ImportTemplate;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Operation\Document\Template\Send;
use Bitrix\Sign\Result\Operation\Document\Template\CreateDocumentsResult;
use Bitrix\Sign\Result\Operation\Document\Template\SendResult;
use Bitrix\Sign\Result\Operation\Document\ExportBlankResult;
use Bitrix\Sign\Result\Operation\Document\Template\SetupDocumentSignersResult;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableBlankResult;
use Bitrix\Sign\Result\Operation\Member\ValidateEntitySelectorMembersResult;
use Bitrix\Sign\Serializer\MasterFieldSerializer;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\ProviderCode;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;

class Template extends Controller
{
	/**
	 * @return array<array{uid: string, title: string, company: array{id: int, name: string, taxId: string}, fields: array}>
	 */
	public function listAction(
		Main\Engine\CurrentUser $user,
	): array
	{
		$templates = $this->container->getDocumentTemplateRepository()
			->listWithStatusesAndVisibility([Status::COMPLETED], [Visibility::VISIBLE])
		;
		$documents = $this->container->getDocumentRepository()
			->listByTemplateIds($templates->getIdsWithoutNull())
		;
		$documentService = $this->container->getDocumentService();
		$companyIds = $documentService->listMyCompanyIdsForDocuments($documents);
		$lastUsedTemplateDocument = $documentService->getLastCreatedEmployeeDocumentFromDocuments($user->getId(), $documents);

		if (empty($companyIds))
		{
			return [];
		}

		$companies = $this->container->getCrmMyCompanyService()->listWithTaxIds(
			inIds: $companyIds,
			checkRequisitePermissions: false,
		);
		$result = [];
		foreach ($documents->sortByTemplateIdsDesc() as $document)
		{
			if ($document === null)
			{
				continue;
			}

			$companyId = (int)($companyIds[$document->id] ?? null);
			if ($companyId < 1)
			{
				continue;
			}

			$company = $companies->findById($companyId);
			if ($company === null || $company->taxId === null || $company->taxId === '')
			{
				continue;
			}

			$templateId = (int)$document->templateId;
			if ($templateId < 1)
			{
				continue;
			}

			$template = $templates->findById($templateId);
			if ($template === null)
			{
				continue;
			}

			$result[] = [
				'id' => $template->id,
				'uid' => $template->uid,
				'title' => $template->title,
				'company' => [
					'name' => $company->name,
					'taxId' => $company->taxId,
					'id' => $company->id,
				],
				'isLastUsed' => $document->id === $lastUsedTemplateDocument?->createdFromDocumentId,
			];
		}

		return $result;
	}

	public function sendAction(
		string $uid,
		Main\Engine\CurrentUser $user,
		array $fields = [],
		bool $isOnboarding = false,
	): array
	{
		$template = Container::instance()->getDocumentTemplateRepository()->getByUid($uid);
		if ($template === null)
		{
			$this->addError(new Main\Error('Template not found'));

			return [];
		}

		if (B2eTariff::instance()->isB2eRestrictedInCurrentTariff())
		{
			$this->addB2eTariffRestrictedError();

			return [];
		}

		$createdById = (int)$user->getId();
		if($createdById < 1)
		{
			$this->addError(new Main\Error('User not found'));

			return [];
		}

		$members = new MemberCollection();
		if ($isOnboarding)
		{
			if (!Loader::includeModule('crm'))
			{
				$this->addError(new Main\Error('Module crm not installed'));

				return [];
			}

			if ($template->id === null)
			{
				$this->addError(new Main\Error('Template id is null'));

				return [];
			}

			$documentByTemplateId = $this->container->getDocumentService()->getByTemplateId($template->id);
			if ($documentByTemplateId === null)
			{
				$this->addError(new Main\Error('Document by template id not found'));

				return [];
			}

			$companyEntityId = $this->container->getMemberService()->getAssignee($documentByTemplateId)?->entityId;
			if ($companyEntityId === null)
			{
				$this->addError(new Main\Error('Assignee entity id not found'));

				return [];
			}

			$companies = $this->container->getCrmMyCompanyService()->listWithTaxIds(checkRequisitePermissions: false);
			$companyEntityIds = $companies->getIds();
			if (!in_array($companyEntityId, $companyEntityIds, true))
			{
				$result = (new Operation\Document\Template\CompleteOnboardingTemplateFilling($template))->launch();
				if (!$result->isSuccess())
				{
					$this->addErrors($result->getErrors());

					return [];
				}

				$companyEntityId = $result->companyEntityId;
			}

			$memberService = $this->container->getMemberService();
			$document = $this->container->getDocumentRepository()->getByTemplateId($template->id);
			$assignee = $memberService->makeAssigneeByDocumentAndEntityId($document, $companyEntityId);
			$signer = $memberService->makeSignerByDocumentAndEntityId($document, $createdById);
			$members = new MemberCollection($assignee, $signer);
		}

		$result = (new Send(
			template: $template,
			responsibleUserId: $createdById,
			fields: $fields,
			sendFromUserId: $createdById,
			representativeUserId: $isOnboarding ? $createdById : null,
			memberList: $isOnboarding ? $members : null,
		))->launch();
		if (!$result instanceof SendResult)
		{
			$this->addErrorsFromResult($result);

			return [];
		}

		$assigneeMember = $result->assigneeMember;
		$employeeMember = $result->employeeMember;
		$document = $result->newDocument;

		return [
			'assigneeMember' => [
				'id' => $assigneeMember->id,
				'uid' => $assigneeMember->uid,
			],
			'employeeMember' => [
				'id' => $employeeMember->id,
				'uid' => $employeeMember->uid,
			],
			'document' => [
				'id' => $document->id,
				'providerCode' => ProviderCode::toRepresentativeString($document->providerCode),
			],
		];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_TEMPLATE_EDIT,
		itemType: AccessibleItemType::TEMPLATE,
		itemIdOrUidRequestKey: 'uid',
	)]
	public function completeAction(string $uid, int $folderId): array
	{
		$templateRepository = Container::instance()->getDocumentTemplateRepository();
		$template = $templateRepository->getByUid($uid);
		if ($template === null)
		{
			$this->addErrorByMessage('Template not found');

			return [];
		}

		$template->folderId = $folderId;

		$result = (new Operation\Document\Template\Complete($template))->launch();
		$this->addErrorsFromResult($result);

		return [
			'template' => [
				'id' => $template->id,
			],
		];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_TEMPLATE_EDIT,
		itemType: AccessibleItemType::TEMPLATE,
		itemIdOrUidRequestKey: 'templateId',
	)]
	public function changeVisibilityAction(int $templateId, string $visibility): array
	{
		$visibility = Visibility::tryFrom($visibility);
		if ($visibility === null)
		{
			$this->addErrorByMessage('Incorrect visibility status');

			return [];
		}

		$result = Container::instance()->getDocumentTemplateService()->changeVisibility($templateId, $visibility);
		if (!$result->isSuccess())
		{
			$this->addErrorByMessage('Update visibility error');

			return [];
		}
		return [];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_TEMPLATE_DELETE,
		itemType: AccessibleItemType::TEMPLATE,
		itemIdOrUidRequestKey: 'templateId',
	)]
	public function deleteAction(int $templateId): array
	{
		$container = Container::instance();
		$templateRepository = $container->getDocumentTemplateRepository();

		$template = $templateRepository->getById($templateId);
		if ($template === null)
		{
			$this->addErrorByMessage('Template not found');

			return [];
		}

		$templateFolderRelationRepository = $container->getTemplateFolderRelationRepository();
		$result = $templateFolderRelationRepository->deleteByIdAndType($templateId, EntityType::TEMPLATE);
		if (!$result->isSuccess())
		{
			$this->addError(new Error('Delete relations error'));

			return [];
		}

		$result = (new Operation\Document\Template\Delete($template))->launch();
		$this->addErrorsFromResult($result);

		return [];
	}

	#[LogicAnd(
		new ActionAccess(
			permission: ActionDictionary::ACTION_B2E_TEMPLATE_READ,
			itemType: AccessibleItemType::TEMPLATE,
			itemIdOrUidRequestKey: 'templateId',
		),
		new ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_ADD),
	)]
	public function copyAction(int $templateId, int $folderId): array
	{
		if ($templateId < 1)
		{
			$this->addErrorByMessage('Incorrect template id');

			return [];
		}

		$template = Container::instance()->getDocumentTemplateRepository()->getById($templateId);
		if ($template === null)
		{
			$this->addErrorByMessage('Template not found');

			return [];
		}

		$createdByUserId = (int)CurrentUser::get()->getId();
		if ($createdByUserId < 1)
		{
			$this->addErrorByMessage('User not found');

			return [];
		}

		$copyTemplateResult = (new Operation\Document\Template\Copy($template, $createdByUserId, $folderId))->launch();
		if (!$copyTemplateResult->isSuccess())
		{
			$this->addErrorsFromResult($copyTemplateResult);

			return [];
		}

		$copyTemplate = $copyTemplateResult->getData()['copyTemplate'];

		return [
			'template' => [
				'id' => $copyTemplate->id,
			],
		];
	}

	public function getFieldsAction(
		string $uid,
	): array
	{
		$template = Container::instance()->getDocumentTemplateRepository()->getByUid($uid);
		if ($template === null)
		{
			$this->addError(new Main\Error('Template not found'));

			return [];
		}

		$document = Container::instance()->getDocumentRepository()->getByTemplateId($template->id);
		if ($document === null)
		{
			$this->addError(new Main\Error('Document not found'));

			return [];
		}

		if (!DocumentScenario::isB2EScenario($document->scenario) || empty($document->companyUid))
		{
			$this->addError(new Main\Error('Incorrect document'));

			return [];
		}

		$factory = new \Bitrix\Sign\Factory\Field();
		$fields = $factory->createDocumentFutureSignerFields($document, CurrentUser::get()->getId());

		return [
			'fields' => (new MasterFieldSerializer())->serialize($fields),
		];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_TEMPLATE_READ,
		itemType: AccessibleItemType::TEMPLATE,
		itemIdOrUidRequestKey: 'templateId',
	)]
	public function exportAction(int $templateId): array
	{
		if (!Storage::instance()->isBlankExportAllowed())
		{
			$this->addError(new Main\Error('Blank export is not allowed'));

			return [];
		}

		$template = Container::instance()->getDocumentTemplateRepository()->getById($templateId);
		if ($template === null)
		{
			$this->addError(new Main\Error('Template not found'));

			return [];
		}

		$document = Container::instance()->getDocumentRepository()->getByTemplateId($template->id);
		if ($document === null)
		{
			$this->addError(new Main\Error('Document not found'));

			return [];
		}

		if ($document->blankId === null)
		{
			$this->addError(new Main\Error('No blankId in document'));

			return [];
		}

		$result = (new ExportBlank($document->blankId))->launch();
		if ($result instanceof ExportBlankResult)
		{
			$result->blank->title = $template->title;

			return [
				'json' => Main\Web\Json::encode($result->blank),
				'filename' => "$template->title.json",
			];
		}

		$this->addErrorsFromResult($result);

		return [];
	}

	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_TEMPLATE_ADD,
	)]
	public function importAction(string $serializedTemplate): array
	{
		if (!Storage::instance()->isBlankExportAllowed())
		{
			$this->addError(new Main\Error('Blank export/import is not allowed'));

			return [];
		}

		$result = Container::instance()->getB2eTariffRestrictionService()->check();
		if (!$result->isSuccess())
		{
			$this->addErrorsFromResult($result);

			return [];
		}

		$result = (new UnserializePortableBlank($serializedTemplate))->launch();
		if (!$result instanceof UnserializePortableBlankResult)
		{
			$this->addErrorsFromResult($result);

			return [];
		}

		$createdById = (int)CurrentUser::get()->getId();
		if($createdById < 1)
		{
			$this->addError(new Main\Error('User not found'));

			return [];
		}

		$result = (new ImportTemplate($result->blank, $createdById))->launch();
		$this->addErrorsFromResult($result);

		return [];
	}

	/**
	 * @param array<array{entityType: string, id: int}> $entities
	 * @param int $folderId
	 * @return array
	 */
	public function moveToFolderAction(array $entities, int $folderId): array
	{
		if (!Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$this->addErrorByMessage('Folder grouping is not allowed');

			return [];
		}

		$container = Container::instance();
		$templateAccessService = $container->getTemplateAccessService();
		if (!$templateAccessService->hasAccessToEditTemplateEntities($entities)->isSuccess())
		{
			$this->addErrorByMessage('No access rights to edit all templates');

			return [];
		}

		$templateService = $container->getDocumentTemplateService();
		$result = $templateService->moveToFolder($entities, $folderId);
		if (!$result->isSuccess())
		{
			$this->addErrorByMessage('Failed to move templates to folder');

			return [];
		}

		return $result->getData();
	}

	/**
	 * @param array<array{entityType: string, id: int}> $entities
	 * @return array
	 */
	public function deleteEntitiesAction(array $entities): array
	{
		$templateAccessService = Container::instance()->getTemplateAccessService();
		if (!$templateAccessService->hasAccessToDeleteTemplateEntities($entities)->isSuccess())
		{
			$this->addErrorByMessage('No access rights to delete all templates');

			return [];
		}

		$templateFolderRelationService = Container::instance()->getTemplateFolderRelationService();
		$preparedRelations = $templateFolderRelationService->getPrepareTemplateFolderRelations($entities);
		if ($preparedRelations->isEmpty())
		{
			$this->addErrorByMessage('Relation collection cannot be empty');
			return [];
		}

		$result = (new DeleteTemplateEntity($preparedRelations->toArray()))->launch();
		if (!$result->isSuccess())
		{
			$this->addErrorByMessage('Delete folders or templates error');
			return [];
		}

		return [];
	}

	/**
	 * @param list<int> $templateIds
	 *
	 * @return array
	 */
	#[LogicAnd(
		new ActionAccess(
			permission: ActionDictionary::ACTION_B2E_TEMPLATE_READ,
			itemType: AccessibleItemType::TEMPLATE,
			itemIdOrUidRequestKey: 'templateIds',
		),
		new ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
	)]
	public function registerDocumentsAction(array $templateIds, bool $excludeRejected = true): array
	{
		if (empty($templateIds))
		{
			$this->addErrorByMessage('No template ids');

			return [];
		}

		$templates = Container::instance()
			->getDocumentTemplateRepository()
			->getByIds($templateIds)
		;

		$notFoundTemplates = array_diff($templateIds, $templates->getIds());
		if (!empty($notFoundTemplates))
		{
			$this->addErrorByMessage('Not found templates with ids: ' . implode(',', $notFoundTemplates));

			return [];
		}

		$sendFromUserId = (int)$this->getCurrentUser()->getId();
		if ($sendFromUserId < 1)
		{
			$this->addErrorByMessage('User not found');

			return [];
		}

		$operation = new Operation\Document\Template\RegisterDocumentsByTemplates(
			templates: $templates,
			sendFromUserId: $sendFromUserId,
			onlyInitiatedByType: InitiatedByType::COMPANY,
			excludeRejected: $excludeRejected,
		);

		$result = $operation->launch();
		if ($result instanceof CreateDocumentsResult)
		{
			return [
				'items' => array_map(
					static fn (TemplateCreatedDocument $createdDocument) => [
						'templateId' => $createdDocument->template->id,
						'document' => (new \Bitrix\Sign\Ui\ViewModel\Wizard\Document($createdDocument->document))
							->toArray()
						,
					],
					$result->createdDocuments->toArray(),
				),
			];
		}

		$this->addErrorsFromResult($result);

		return [];
	}

	/**
	 * @param list<int> $documentIds
	 * @param array{entityType: string, entityId: string} $signers
	 *
	 * @return array
	 */
	#[ActionAccess(
		permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
		itemType: AccessibleItemType::DOCUMENT,
		itemIdOrUidRequestKey: 'documentIds',
	)]
	public function setupSignersAction(
		array $documentIds,
		array $signers,
		bool $excludeRejected = true,
	): array
	{
		if (empty($documentIds))
		{
			$this->addErrorByMessage('No document ids');

			return [];
		}

		$entitiesResult = (new Operation\Member\Validation\ValidateEntitySelectorSigners($signers))->launch();
		if (!$entitiesResult instanceof ValidateEntitySelectorMembersResult)
		{
			$this->addErrorsFromResult($entitiesResult);

			return [];
		}

		$documents = Container::instance()->getDocumentRepository()->listByIds($documentIds);
		$notFoundDocuments = array_diff($documentIds, array_keys($documents->getArrayByIds()));
		if (!empty($notFoundDocuments))
		{
			$this->addErrorByMessage('Not found documents with ids: ' . implode(',', $notFoundDocuments));

			return [];
		}

		$operation = new Operation\Document\Template\SetupDocumentsSigners(
			documents: $documents,
			signers: $entitiesResult->entities,
			sendFromUserId: (int)$this->getCurrentUser()->getId(),
			excludeRejected: $excludeRejected,
		);

		$result = $operation->launch();
		if (!$result instanceof SetupDocumentSignersResult)
		{
			$this->addErrorsFromResult($result);

			return [];
		}

		return [
			'shouldCheckDepartmentsSync' => $result->shouldCheckDepartmentSync,
			'documents' => array_map(
				static fn(Document $document) => (new \Bitrix\Sign\Ui\ViewModel\Wizard\Document($document))->toArray(),
				$documents->toArray(),
			),
		];
	}

	public function installOnboardingTemplateAction(): array
	{
		$result = (new GetOrInstallOnboardingTemplate())->launch();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		$onboardingTemplate = $result->template;
		if (!$onboardingTemplate)
		{
			$this->addError(new Error('Onboarding template not found'));

			return [];
		}

		return [
			'template' => $onboardingTemplate,
		];
	}
}
