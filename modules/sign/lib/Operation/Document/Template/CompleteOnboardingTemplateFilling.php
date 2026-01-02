<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Result\Service\Sign\Document\UpdateTemplateResult;
use Bitrix\Sign\Service\Api\B2e\ProviderCodeService;
use Bitrix\Sign\Service\ApiService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\Crm\MyCompanyService;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\ProviderCode;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;
use Bitrix\Sign\Connector;

class CompleteOnboardingTemplateFilling implements Operation
{
	private readonly DocumentService $documentService;
	private readonly MyCompanyService $myCompanyService;
	private readonly TemplateRepository $templateRepository;
	private readonly MemberService $memberService;
	private readonly ProviderCodeService $providerCodeService;
	private readonly ApiService $apiService;

	public function __construct(
		private readonly Template $template,
		?DocumentService $documentService = null,
		?MyCompanyService $myCompanyService = null,
		?TemplateRepository $templateRepository = null,
		?MemberService $memberService = null,
		?ProviderCodeService $providerCodeService = null,
		?ApiService $apiService = null,
	)
	{
		$container = Container::instance();
		$this->documentService = $documentService ?? $container->getDocumentService();
		$this->myCompanyService = $myCompanyService ?? $container->getCrmMyCompanyService();
		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->memberService = $memberService ?? $container->getMemberService();
		$this->providerCodeService = $container->getApiProviderCodeService();
		$this->apiService = $apiService ?? $container->getApiService();
	}

	public function launch(): \Bitrix\Main\Result
	{
		if (!Loader::includeModule('crm'))
		{
			return Result::createByErrorMessage('Module crm not installed');
		}

		$document = $this->documentService->getByTemplateId((int)$this->template->id);
		if (!$document)
		{
			return Result::createByErrorMessage('Document not found');
		}
		$this->hiddenTemplate();

		$companies = $this->myCompanyService->listWithTaxIds(checkRequisitePermissions: false);

		$companyUid = null;
		$lastErrorResult = new Result();
		foreach ($companies as $company)
		{
			$companyEntityId = $company?->id;
			if (!$companyEntityId)
			{
				$lastErrorResult->addError(new Error('Company entity id not found'));
				continue;
			}

			$companyTaxId = $company?->taxId;
			if (!$companyTaxId)
			{
				$lastErrorResult->addError(new Error('Company tax id not found'));
				continue;
			}

			$companyByTaxIdResult = $this->apiService->post('v1/b2e.company.get', [
				'taxIds' => [$companyTaxId],
				'supportedProviders' => ProviderCode::getAllFormattedCodes(),
			]);
			$extractedCompanyUid = $this->extractCompanyUid($companyByTaxIdResult->getData());
			if(!$extractedCompanyUid)
			{
				$lastErrorResult->addError(new Error('Extracted company uid not found'));
				continue;
			}

			$providerCode = $this->providerCodeService->loadProviderCode($extractedCompanyUid);
			if ($providerCode)
			{
				$companyUid = $this->extractCompanyUid($companyByTaxIdResult->getData());
				break;
			}
			else
			{
				$registerCompanyResult = $this->registerCompany($companyTaxId, $companyEntityId);
				if ($registerCompanyResult->isSuccess())
				{
					$companyUid = $registerCompanyResult->getData()['id'] ?? null;
					break;
				}
				else
				{
					$lastErrorResult = $registerCompanyResult;
				}
			}
		}

		if (!$companyUid)
		{
			return $lastErrorResult;
		}

		$modifyCompanyResult = $this->documentService->modifyCompany($document->uid, $companyUid, $companyEntityId);
		if (!$modifyCompanyResult->isSuccess())
		{
			return $modifyCompanyResult->addErrors($modifyCompanyResult->getErrors());
		}

		$modifyProvider = $this->documentService->modifyProviderCode($document, ProviderCode::SES_RU_EXPRESS);
		if (!$modifyProvider->isSuccess())
		{
			return Result::createByErrorMessage('Modify provider error');
		}

		$currentUserId = (int)CurrentUser::get()->getId();
		if ($currentUserId < 1)
		{
			return Result::createByErrorMessage('Current user not found');
		}

		$assignee = $this->memberService->makeAssigneeByDocumentAndEntityId($document, $companyEntityId);
		$setupMembersResult = (new SetupTemplateMembers(
			document: $document,
			sendFromUserId: $currentUserId,
			representativeUserId: $currentUserId,
			memberList: new MemberCollection($assignee),
			hasSetupSigners: false,
		))->launch();
		if (!$setupMembersResult->isSuccess())
		{
			return $setupMembersResult->addErrors($setupMembersResult->getErrors());
		}

		$updateTemplateResult = $this->updateTemplateStatusAndVisibility();
		if (!$updateTemplateResult->isSuccess())
		{
			return Result::createByErrorMessage('Update template status and visibility error');
		}

		return new UpdateTemplateResult($this->template, $companyEntityId);
	}

	private function registerCompany(string $taxId, int $companyEntityId): \Bitrix\Main\Result
	{
		$result = $this->apiService->post('v1/b2e.company.registerByClient', [
			'taxId' => $taxId,
			'providerCode' => ProviderCode::toRepresentativeString(ProviderCode::SES_RU_EXPRESS),
			'providerData' => [
				'providerUid' => '',
				'companyName' => Connector\Crm\MyCompany::getById($companyEntityId)?->name,
			],
		]);

		return $result;
	}

	private function extractCompanyUid(array $data): ?string
	{
		$companies = (array)($data['companies'] ?? []);
		foreach ($companies as $company)
		{
			$providers = (array)($company['providers'] ?? []);
			foreach ($providers as $provider)
			{
				$providerCode = $provider['code'] ?? null;
				if ($providerCode === ProviderCode::toRepresentativeString(ProviderCode::SES_RU_EXPRESS))
				{
					return $provider['uid'] ?? null;
				}
			}
		}

		return null;
	}

	private function updateTemplateStatusAndVisibility(): \Bitrix\Main\Result
	{
		$this->template->status = Status::COMPLETED;
		$this->template->visibility = Visibility::VISIBLE;

		return $this->templateRepository->update($this->template);
	}

	private function hiddenTemplate(): void
	{
		$this->template->hidden = true;

		$this->templateRepository->update($this->template);
	}
}