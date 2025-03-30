<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Document;

use Bitrix\Main;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicAnd;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Operation\Document\ExportBlank;
use Bitrix\Sign\Operation\Document\UnserializePortableBlank;
use Bitrix\Sign\Operation\Document\Template\ImportTemplate;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Operation\Document\Template\Send;
use Bitrix\Sign\Result\Operation\Document\Template\SendResult;
use Bitrix\Sign\Result\Operation\Document\ExportBlankResult;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableBlankResult;
use Bitrix\Sign\Serializer\MasterFieldSerializer;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Access\AccessibleItemType;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\ProviderCode;
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
		$documents = $documents->sortByTemplateIdsDesc();
		foreach ($documents as $document)
		{
			$companyId = $companyIds[$document->id];
			$company = $companies->findById($companyId);
			if ($company === null || $company->taxId === null || $company->taxId === '')
			{
				continue;
			}
			$template = $templates->findById($document->templateId);
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

		$result = (new Send(
			template: $template,
			sendFromUserId: $user->getId(),
			fields: $fields,
		))->launch();
		if (!$result instanceof SendResult)
		{
			$this->addErrorsFromResult($result);

			return [];
		}

		$employeeMember = $result->employeeMember;
		$document = $result->newDocument;

		return [
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
	public function completeAction(string $uid): array
	{
		$templateRepository = Container::instance()->getDocumentTemplateRepository();
		$template = $templateRepository->getByUid($uid);
		if ($template === null)
		{
			$this->addErrorByMessage('Template not found');

			return [];
		}

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

		$templateRepository = Container::instance()->getDocumentTemplateRepository();

		$currentTemplate = $templateRepository->getById($templateId);
		$currentStatus = $currentTemplate?->status ?? Status::NEW;

		$isCurrentStatusNew = $currentStatus === Status::NEW;
		$isVisible = $visibility === Visibility::VISIBLE;

		$isStatusNewAndVisible = ($isCurrentStatusNew && $isVisible);
		if ($isStatusNewAndVisible)
		{
			$this->addErrorByMessage('Incorrect visibility status');

			return [];
		}

		$result = $templateRepository->updateVisibility($templateId, $visibility);
		if (!$result->isSuccess())
		{
			$this->addErrorsFromResult($result);
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
		$template = Container::instance()->getDocumentTemplateRepository()->getById($templateId);
		if ($template === null)
		{
			$this->addErrorByMessage('Template not found');

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
	public function copyAction(int $templateId): array
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
		$result = (new Operation\Document\Template\Copy($template, $createdByUserId))->launch();
		$this->addErrorsFromResult($result);

		return [];
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

		$result = (new ImportTemplate($result->blank))->launch();
		$this->addErrorsFromResult($result);

		return [];
	}
}
