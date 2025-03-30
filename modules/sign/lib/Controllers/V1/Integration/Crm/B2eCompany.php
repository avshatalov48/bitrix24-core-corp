<?php

namespace Bitrix\Sign\Controllers\V1\Integration\Crm;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicOr;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Connector;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Item\CompanyCollection;
use Bitrix\Sign\Item\CompanyProvider;
use Bitrix\Sign\Item\Integration\Crm\MyCompanyCollection;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Item\Company;
use Bitrix\Sign\Type\Document\InitiatedByType;

class B2eCompany extends \Bitrix\Sign\Engine\Controller
{
	#[LogicOr(
		new ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
		new ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_EDIT),
	)]
	public function listAction(
		?string $forDocumentInitiatedByType = null,
	): array
	{
		$forDocumentInitiatedByType ??= InitiatedByType::COMPANY->value;
		$initiatedByType = InitiatedByType::tryFrom($forDocumentInitiatedByType);
		if ($initiatedByType === null)
		{
			$this->addError(new Error('Incorrect document initiated by type'));

			return [];
		}

		if (B2eTariff::instance()->isB2eRestrictedInCurrentTariff())
		{
			$this->addB2eTariffRestrictedError();

			return [];
		}

		if (!Loader::includeModule('crm'))
		{
			$this->addError(new Error('Module crm not installed'));
			return [];
		}

		$myCompanyService = $this->container->getCrmMyCompanyService();
		$myCompanies = $myCompanyService->listWithTaxIds(checkRequisitePermissions: false);

		$companies = $this->getFilledRegisteredCompanies($myCompanies, $initiatedByType);

		$aciveCompanyUuids = [];
		/** @var Company $company */
		foreach ($companies as $company)
		{
			foreach ($company->providers as $provider)
			{
				$aciveCompanyUuids[] = $provider->uid;
			}
		}

		$lastProviders = $this->container
			->getDocumentRepository()
			->getLastCompanyProvidersByUser(
				(int)CurrentUser::get()->getId(),
				$aciveCompanyUuids,
			)
		;

		// sort providers
		$companies = $companies->sortProviders(
			function(CompanyProvider $a, CompanyProvider $b) use ($lastProviders) {
				$dateA = $lastProviders->getByUid($a->uid)?->dateCreate ?? null;
				$dateB = $lastProviders->getByUid($b->uid)?->dateCreate ?? null;

				$tsForCompareA = max($dateA?->getTimestamp() ?? 0, $a->timestamp);
				$tsForCompareB = max($dateB?->getTimestamp() ?? 0, $b->timestamp);

				return $tsForCompareB <=> $tsForCompareA;
			},
		);

		// sort companies using providers
		$companies = $companies->getSorted(
			function(Company $a, Company $b) use ($lastProviders) {
				$recentProviderA = $a->providers[0] ?? null;
				$recentProviderB = $b->providers[0] ?? null;

				if (!$recentProviderA || !$recentProviderB)
				{
					return $recentProviderB ? 1 : -1;
				}

				$dateA = $lastProviders->getByUid($recentProviderA->uid)?->dateCreate ?? null;
				$dateB = $lastProviders->getByUid($recentProviderB->uid)?->dateCreate ?? null;

				$tsForCompareA = max($dateA?->getTimestamp() ?? 0, $recentProviderA->timestamp);
				$tsForCompareB = max($dateB?->getTimestamp() ?? 0, $recentProviderB->timestamp);

				return $tsForCompareB <=> $tsForCompareA;
			}
		);

		return [
			'showTaxId' => !$myCompanyService->isTaxIdIsCompanyId(),
			'companies' => $companies->toArray(),
		];
	}

	#[ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function deleteAction(string $id): array
	{
		$result = Container::instance()->getApiService()
			->post('v1/b2e.company.delete', ['id' => $id])
		;

		$this->addErrorsFromResult($result);

		return [];
	}

	private function getFilledRegisteredCompanies(
		MyCompanyCollection $myCompanies,
		InitiatedByType $forDocumentInitiatedByType = InitiatedByType::COMPANY,
	): CompanyCollection
	{
		$registeredCompanies = $this->getRegistered($myCompanies, $forDocumentInitiatedByType);

		$companies = new CompanyCollection();

		foreach ($myCompanies as $myCompany)
		{
			$company = new Company(
				id: $myCompany->id,
				title: $myCompany->name,
				rqInn: $myCompany->taxId,
			);
			if (!$company->rqInn || !isset($registeredCompanies[$company->rqInn]))
			{
				$companies->add($company);
				continue;
			}

			$registeredByTaxId = $registeredCompanies[$company->rqInn] ?? [];
			if (!empty($registeredByTaxId['register_url']) && is_string($registeredByTaxId['register_url']))
			{
				$company->registerUrl = $registeredByTaxId['register_url'];
				$contextLang = Context::getCurrent()->getLanguage();
				if ($contextLang)
				{
					$company->registerUrl = (new Uri($company->registerUrl))
						->addParams(['lang' => Context::getCurrent()->getLanguage()])
						->getUri()
					;
				}
			}
			if (empty($registeredByTaxId['providers']) || !is_array($registeredByTaxId['providers']))
			{
				$companies->add($company);
				continue;
			}

			foreach ($registeredByTaxId['providers'] as $provider)
			{
				if (!empty($provider['uid']) && is_string($provider['uid'])
					&& !empty($provider['code']) && is_string($provider['code'])
				)
				{
					$company->providers[] = new CompanyProvider(
						$provider['code'],
						$provider['uid'],
						(int)($provider['date'] ?? null),
						(bool)($provider['virtual'] ?? false),
						(bool)($provider['autoRegister'] ?? false),
						(string)($provider['name'] ?? ''),
						(string)($provider['description'] ?? ''),
						(string)($provider['iconUrl'] ?? ''),
						is_numeric($provider['expires'] ?? null) ? (int)$provider['expires'] : null,
						(string)($provider['externalProviderId'] ?? ''),
					);
				}
			}

			$companies->add($company);
		}

		return $companies;
	}

	private function getRegistered(MyCompanyCollection $myCompanies, InitiatedByType $forDocumentInitiatedByType): array
	{
		$taxIds = $myCompanies->listTaxIds();
		if (empty($taxIds))
		{
			return [];
		}

		$result = Container::instance()->getApiService()
			->post(
				'v1/b2e.company.get',
				[
					'taxIds' => $taxIds,
					'useProvidersWhereSignerSignFirst' => $forDocumentInitiatedByType === InitiatedByType::EMPLOYEE,
				],
			)
		;
		if ($result->isSuccess())
		{
			$data = $result->getData();
			$companies = (array)($data['companies'] ?? []);

			$map = [];
			foreach ($companies as $company)
			{
				$taxId = $company['taxId'] ?? null;
				$map[$taxId] = $company;
			}

			return $map;
		}

		$this->addErrors($result->getErrors());

		return [];
	}

	#[ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT)]
	public function registerAction(
		string $taxId,
		string $providerCode,
		int $companyId,
		string $externalProviderId = '',
	): array
	{
		$providerData = [
			'providerUid' => $externalProviderId,
			'companyName' => Connector\Crm\MyCompany::getById($companyId)?->name,
		];

		$result = Container::instance()->getApiService()
			->post('v1/b2e.company.registerByClient', [
				'taxId' => $taxId,
				'providerCode' => $providerCode,
				'providerData' => $providerData,
			])
		;

		$this->addErrorsFromResult($result);

		return $result->getData();
	}
}
